<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * This script implements the Phorum user API.
 *
 * The user API is used for managing users and user related data.
 *
 * By default, Phorum stores all user data in the Phorum database.
 * This API however, does support modules that change this behavior
 * (e.g. for using user data that is stored in some external database).
 *
 * This API does also implement a custom session system, for remembering
 * authenticated users. See the documentation for the function
 * {@link phorum_api_user_session_create()} for more information on
 * Phorum user sessions.
 *
 * @package    PhorumAPI
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 *
 * @todo
 *     Make sure that PHORUM_ORIGINAL_USER_CODE is handled somehow
 *     in the new API layer (unless it's not needed to handle that
 *     due to superb design of course ;-).
 */

if (!defined('PHORUM')) return;

// {{{ Constant and variable definitions
/**
 * The name of the session cookie to use for long term authentication.
 */
define( 'PHORUM_COOKIE_LONG_TERM' , 'phorum_session_v5' );

/**
 * The name of the session cookie to use for short term authentication
 * (this is used by the strict authentication scheme).
 */
define( 'PHORUM_COOKIE_SHORT_TERM', 'phorum_session_st' );

/**
 * The name of the session cookie for admin interface authentication.
 */
define( 'PHORUM_COOKIE_ADMIN',      'phorum_admin_session' );

/**
 * Function call parameter, which tells {@link phorum_api_user_session_create()}
 * that a forum session has to be created for the active user.
 */
define('PHORUM_FORUM_SESSION',         1);

/**
 * Function call flag, which tells {@link phorum_api_user_set_active_user()}
 * that the short term forum session has to be activated.
 */
define('PHORUM_FLAG_SESSION_ST',       1);

/**
 * Function call parameter, which tells {@link phorum_api_user_session_create()}
 * that session ids have to be reset to new values as far as that is sensible
 * for a newly logged in user.
 */
define('PHORUM_SESSID_RESET_LOGIN',    1);

/**
 * Function call parameter, which tells {@link phorum_api_user_session_create()}
 * that all session ids have to be reset to new values. This is for example
 * appropriate after a user changed the password (so active sessions on
 * other computers or browsers will be ended).
 */
define('PHORUM_SESSID_RESET_ALL',      2);

/**
 * Function call parameter, which tells {@link phorum_api_user_session_create()}
 * that an admin session has to be created for the active user.
 */
define('PHORUM_ADMIN_SESSION',         2);

// }}}

// {{{ Function: phorum_api_user_authenticate()
/**
 * Check the authentication credentials for a user.
 *
 * @param string $username
 *     The username for the user.
 *
 * @param string $password
 *     The password for the user.
 *
 * @return mixed
 *     If the authentication credentials are correct, this function returns
 *     the user_id of the authenticated user. Otherwise, FALSE is returned.
 */
function phorum_api_user_authenticate($username, $password)
{
    $user_id = NULL;

    // Give modules a chance to handle the user authentication (for example
    // to authenticate against an external source). The module can change the
    // user_id field in the authinfo array to one of:
    // - user_id: the user_id of the authenticated user;
    // - FALSE: authentication credentials are rejected;
    // - NULL: let Phorum handle the authentication.
    if (isset($PHORUM['hooks']['user_check_login']))
    {
        // Run the hook.
        $authinfo = phorum_hook('user_check_login', array(
            'user_id'  => NULL,
            'username' => $username,
            'password' => $password
        ));

        // Authentication rejected by module.
        if ($authinfo['user_id'] === FALSE) {
            return FALSE;
        }

        // Check if the returned user_id is numerical, if the the module
        // did return a user_id.
        if ($authinfo['user_id']!==NULL && !is_numeric($authinfo['user_id'])) {
            trigger_error(
                'Hook user_check_login returned a non-numerical user_id "' .
                htmlspecialchars($authinfo['user_id']) .
                '" for the authenticated user. Phorum only supports numerical ' .
                'user_id values.',
                E_USER_ERROR
            );
        }

        $user_id = $authinfo['user_id'];
    }

    // No module handled the authentication?
    // Then we have to run the Phorum authentication.
    if ($user_id === NULL) 
    {
        // Check the password.
        $user_id = phorum_db_user_check_login($username, md5($password));

        // Password check failed? Then try the temporary password (used for
        // the password reminder feature).
        $temporary_matched = FALSE;
        if ($user_id == 0) {
            $user_id = phorum_db_user_check_login($username, md5($password), TRUE);
            if ($user_id != 0) {
                $temporary_matched = TRUE;
            }
        }

        // If the temporary password matched, then synchronize the main
        // password with the temporary password. The temporary password
        // is kept the same (setting it to an empty value would be bad
        // because it would make the MD5 sum a static value which others
        // could use to hack the account; it could be handled with more
        // checks in other places, but this is the most simple and fail
        // safe solution against this issue).
        if ($temporary_matched) {
            phorum_db_user_save(array(
                'user_id'  => $user_id,
                'password' => $password
            ));
        }
    }
    
    return $user_id ? $user_id : FALSE;
}
// }}}

// {{{ Function: phorum_api_user_set_active_user()
/**
 * Set the active Phorum user.
 *
 * This function can be used to setup the Phorum data to indicate which
 * user is logged in or to setup the anonymous user. Calling this function
 * is all that is needed to tell Phorum which user is logged in.
 *
 * Next to setting up the user data, the function will handle user activity
 * tracking (based on the "track_user_activity" setting) and setup some
 * special (template) variables:
 *
 * The variabe $PHORUM["DATA"]["ADMINISTRATOR"] will be set to TRUE if 
 * the active user is an administrator, FALSE otherwise. 
 *
 * For type {@link PHORUM_FORUM_SESSION}, the following extra variables
 * will be filled:
 *
 * - $PHORUM["DATA"]["LOGGEDIN"]:
 *   TRUE if the user is logged in, FALSE otherwise.

 * - $PHORUM["DATA"]["FULLY_LOGGEDIN"]:
 *   TRUE if a short term session is active (by setting the 
 *   {@link PHORUM_FLAG_SESSION_ST} flag for the $flags parameter),
 *   FALSE otherwise.
 *
 * @param string $type
 *     The type of session for which to set the active user. This must be
 *     one of {@link PHORUM_FORUM_SESSION} or {@link PHORUM_ADMIN_SESSION}.
 *     See the documentation for {@link phorum_api_user_session_create()}
 *     for more information on Phorum user sessions.
 *
 * @param mixed $user
 *     The user_id or the full user data array for the user that has to be
 *     the active user or NULL if the active user has to be set to the
 *     anonymous user (the default).
 *
 * @param integer $flags
 *     If the flag {@link PHORUM_FLAG_SESSION_ST} is set, then the short
 *     term session will be enabled for {@link PHORUM_FORUM_SESSION}
 *     based sessions.
 *
 * @return boolean
 *     TRUE if a real user was set as the active user successfully
 *     or FALSE if the anonymous user was set.
 */
function phorum_api_user_set_active_user($type, $user = NULL, $flags = 0)
{
    global $PHORUM;

    // Reset error storage.
    $GLOBALS['PHORUM']['API']['errno'] = NULL;
    $GLOBALS['PHORUM']['API']['error'] = NULL;

    // Determine what user to use.
    if ($user !== NULL)
    {
        // Use a full user array.
        if (is_array($user)) {
            // Some really basic checks on the user data. If something's
            // missing, then we fall back to the anonymous user.
            if (!isset($user['user_id']) ||
                !isset($user['active'])) {
                phorum_api_error_set(
                    PHORUM_ERRNO_ERROR,
                    'phorum_api_user_set_active_user(): ' .
                    'user record seems incomplete'
                ); 
                $user = NULL;
            }
        }
        // Retrieve the user by its user_id.
        elseif (is_numeric($user)) {
            $user = phorum_user_get($user, TRUE, TRUE);
        }
        // Bogus $user parameter.
        else trigger_error(
            'phorum_api_user_set_active_user(): $user argument should be ' .
            'one of NULL, array or integer',
            E_USER_ERROR
        );

        // Fall back to the anonymous user if the user is not activated.
        if ($user && $user['active'] != PHORUM_USER_ACTIVE) {
            phorum_api_error_set(
                PHORUM_ERRNO_ERROR,
                'phorum_api_user_set_active_user(): ' .
                'the user is not active'
            ); 
            $user = NULL;
        }

        // Fall back to the anonymous user if the user does not have
        // admin rights, while an admin setup was requested.
        if ($type == PHORUM_ADMIN_SESSION && $user && empty($user['admin'])) {
            phorum_api_error_set(
                PHORUM_ERRNO_ERROR,
                'phorum_api_user_set_active_user(): ' .
                'the user is '
            ); 
            $user = NULL;
        }
    }

    // Clear the special variables.
    $PHORUM['DATA']['LOGGEDIN']       = FALSE;
    $PHORUM['DATA']['FULLY_LOGGEDIN'] = FALSE;
    $PHORUM['DATA']['ADMINISTRATOR']  = FALSE;

    // ----------------------------------------------------------------------
    // Set the anonymous user.
    // ----------------------------------------------------------------------

    if (! $user)
    {
        // Fill the Phorum user with anonymous user data.
        $PHORUM['user'] = array(
            'user_id'  => 0,
            'username' => '',
            'admin'    => false,
            'newinfo'  => array()
        );

        return FALSE;
    }

    // ----------------------------------------------------------------------
    // Set the active Phorum user and handle activity tracking.
    // ----------------------------------------------------------------------

    $PHORUM['user'] = $user;

    if (!empty($user['admin'])) {
        $PHORUM['DATA']['ADMINISTRATOR'] = TRUE;
    }

    if ($type == PHORUM_FORUM_SESSION) {
        $PHORUM['DATA']['LOGGEDIN'] = TRUE;
        if ($flags & PHORUM_FLAG_SESSION_ST) {
            $PHORUM['DATA']['FULLY_LOGGEDIN'] = TRUE;
        }
    }

    // Handle tracking user activity. The "track_user_activity" setting
    // specifies the user activity update interval in seconds (the lower
    // this setting is, the more often the database will be updated).
    if ($PHORUM['track_user_activity'] &&
        (empty($user['date_last_active']) || 
         $user['date_last_active'] < time() - $PHORUM['track_user_activity']))
    {
        $date_last_active  = time();
        $last_active_forum = empty($PHORUM['forum_id']) 
                           ? 0 : $PHORUM['forum_id'];

        // Update the user data in the database.
        phorum_user_save_simple(array(
            'user_id'           => $session_user['user_id'],
            'date_last_active'  => $date_last_active,
            'last_active_forum' => $last_active_forum
        ));

        // Update the live user data.
        $PHORUM['user']['date_last_active']  = $date_last_active;
        $PHORUM['user']['last_active_forum'] = $last_active_forum;
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_api_user_session_create()
/**
 * Create a Phorum user session.
 *
 * Phorum does not use PHP sessions. Instead, it uses its own session
 * management system for remembering logged in users. There are
 * multiple reasons for that, amongst which are:
 *
 * - the lack of session support (on some PHP installs);
 * - missing out of the box load balancing support (sessions are normally
 *   written to local session state files, so multiple machines would not
 *   work well together);
 * - file I/O problems (both performance and file system permissions can
 *   be a problem);
 * - the amount of unneeded overhead that is caused by the PHP session system;
 * - the fact that Phorum also supports URI based sessions (without cookie).
 *
 * This function can be used to create or maintain a login session for a
 * Phorum user. A prerequisite is that an active Phorum user is set through
 * the {@link phorum_api_user_set_active_user()} function, before calling
 * this function.
 *
 * There are two session types available: {@link PHORUM_FORUM_SESSION}
 * (used for the front end application) and {@link PHORUM_ADMIN_SESSION}
 * (used for the administrative back end).
 *
 * Admin sessions are used for the administrative back end system. For
 * security reasons, the back end does not share the front end session,
 * but uses a fully separate session instead. This session does not
 * have a timeout restriction, but it does not survive closing the
 * browser. It is always tracked using a cookie, never using URI
 * authentication (for security reasons).
 *
 * The forum sessions can be split up into long term and short term sessions:
 *
 * - Long term session:
 *   The standard Phorum user session. This session is long lasting and will
 *   survive after closing the browser (unless the long term session timeout
 *   is set to zero). If strict security is not enabled, then this session
 *   is all a user needs to fully use all forum options. This session is
 *   tracked using either a cookie or URI authentication.
 *
 * - Short term session:
 *   This session has a limited life time and will not survive closing the
 *   browser. If strict security is enabled, then the user will not be able
 *   to use all forum functions, unless there is a short term session active 
 *   (e.g. posting forum messages and reading/writing private messages are
 *   restricted). This session is tracked using a cookie. If URI authentication
 *   is in use (because of admin config or cookie-less browsers) Phorum will
 *   only look at the long term session (even in strict security mode), since
 *   URI authentication can be considered to be short term by nature.
 *
 * @param string $type
 *     The type of session to initialize. This must be one of
 *     {@link PHORUM_FORUM_SESSION} or {@link PHORUM_ADMIN_SESSION}.
 *
 * @param integer $reset
 *     If it is set to 0 (zero, the default), then existing session_ids
 *     will be reused if possible.
 *
 *     If this parameter is set to PHORUM_SESSID_RESET_LOGIN, then a new
 *     session id will be generated for short term forum sessions and if
 *     cookies are disabled for some reason, for long term forum sessions
 *     as well (to prevent accidental distribution of URLs with auth info
 *     in them). This is the type of session id reset that is appropriate
 *     after handling a login action.
 *
 *     If this parameter is set to PHORUM_SESSID_RESET_ALL, then all session
 *     ids will be reset to new values. This is for example
 *     appropriate after a user changed the password (so active sessions on
 *     other computers or browsers will be ended).
 *
 * @return boolean
 *     TRUE in case the session was initialized successfully.
 *     Otherwise, FALSE will be returned. The functions
 *     {@link phorum_api_strerror()} and {@link phorum_api_errno()} can be
 *     used to retrieve information about the error which occurred.
 */
function phorum_api_user_session_create($type, $reset = 0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Reset error storage.
    $GLOBALS['PHORUM']['API']['errno'] = NULL;
    $GLOBALS['PHORUM']['API']['error'] = NULL;

    // Check if we have a valid session type.
    if ($type != PHORUM_FORUM_SESSION && 
        $type != PHORUM_ADMIN_SESSION) trigger_error(
        'phorum_api_user_session_create(): Illegal session type: ' .
        htmlspecialchars($type),
        E_USER_ERROR
    );

    // Check if the active Phorum user was set.
    if (empty($PHORUM['user']) ||
        empty($PHORUM['user']['user_id'])) trigger_error(
        'phorum_api_user_session_create(): Missing user in environment',
        E_USER_ERROR
    );

    // Check if the user is activated.
    if ($GLOBALS['PHORUM']['user']['active'] != PHORUM_USER_ACTIVE) {
        return phorum_api_error_set(
            PHORUM_ERRNO_NOACCESS,
            'The user is not (yet) activated (user id '.$user['user_id'].')'
        );
    }

    // For admin sessions, check if the user has administrator rights.
    // This is also checked from phorum_api_user_set_active_user(), but
    // one can never be too sure about this.
    if ($type == PHORUM_ADMIN_SESSION &&
        empty($GLOBALS['PHORUM']['user']['admin'])) {
        return phorum_api_error_set(
            PHORUM_ERRNO_NOACCESS,
            'The user is not an administrator (user id '.$user['user_id'].')'
        );
    }

    // Shortcut for checking if session ids are stored in cookies.
    // Note that the software that uses this function is responsible for
    // setting $PHORUM["use_cookies"] to PHORUM_NO_COOKIES if the client
    // does not support cookies.
    $use_cookies = isset($PHORUM['use_cookies']) &&
                   $PHORUM['use_cookies'] > PHORUM_NO_COOKIES;

    // ----------------------------------------------------------------------
    // Retrieve or generate required session id(s).
    // ----------------------------------------------------------------------

    $user = $GLOBALS['PHORUM']['user'];

    // Generate a long term session id. This one is used by all session types. 
    // Create a new long term session id if no session id is available yet or
    // if a refresh was requested and cookies are disabled (with cookies
    // enabled, we always reuse the existing long term session, so the session
    // can be remembered and shared between multiple browsers / computers).
    $refresh_sessid_lt =
        empty($user['sessid_lt']) ||
        (!$use_cookies && $reset == PHORUM_SESSID_RESET_LOGIN) ||
        $reset == PHORUM_SESSID_RESET_ALL;
    if ($refresh_sessid_lt) {
        $sessid_lt = md5($user['username'].microtime().$user['password']);
        phorum_user_save_simple(array(
            'user_id'   => $user['user_id'],
            'sessid_lt' => $sessid_lt,
        ));
        $GLOBALS['PHORUM']['user']['sessid_lt'] = $sessid_lt;
    } else {
        $sessid_lt = $user['sessid_lt']; 
    }

    // For forum sessions, generate a short term session id if tight
    // security is enabled in the configuration and cookies are enabled
    // (with URI authentication, the tight security system is bypassed
    // since the user will have to login on every visit already).
    $refresh_sessid_st = FALSE;
    if ($type == PHORUM_FORUM_SESSION &&
        !empty($PHORUM['tight_security']) &&
        $use_cookies)
    {
        // How much longer is the existing short term session id valid?
        $timeleft = empty($user['sessid_st_timeout'])
                  ? 0 : $user['sessid_st_timeout'] - time();

        // Create a new short term session id if ..
        if (empty($user['sessid_st']) || // .. no session id is available yet 
            $reset) {                    // .. any type of reset was requested
            $sessid_st = md5($user['username'].microtime().$user['password']);
            $refresh_sessid_st = TRUE;
        } else {
            // Reuse the existing short term session id 
            $sessid_st = $user['sessid_st'];

            // Have the session timeout reset if more than one third of the
            // session's life time has passed and if the session has not
            // yet expired.
            if ($timeleft > 0 &&
                $timeleft < $PHORUM['short_session_timeout']*60/2) {
                $refresh_sessid_st = TRUE;
            }
        }

        // The session data needs updating.
        if ($refresh_sessid_st) {
            $timeout = time() + $PHORUM['short_session_timeout']*60; 
            phorum_user_save_simple(array(
                'user_id'           => $user['user_id'],
                'sessid_st'         => $sessid_st,
                'sessid_st_timeout' => $timeout
            ));
            $GLOBALS['PHORUM']['user']['sessid_st'] = $sessid_st;
            $GLOBALS['PHORUM']['user']['sessid_st_timeout'] = $timeout;
        }
    } 

    // For admin sessions, the session id is computed using the long term
    // session id and some random data that was generated at install time.
    if ($type == PHORUM_ADMIN_SESSION) {
        $sessid_admin = md5($sessid_lt . $PHORUM['admin_session_salt']);
    }

    // ----------------------------------------------------------------------
    // Route the required session id(s) to the user.
    // ----------------------------------------------------------------------

    $user = $GLOBALS['PHORUM']['user'];

    if ($type == PHORUM_FORUM_SESSION)
    {
        // The long term session can be stored in either a cookie or
        // URL / form posting data.
        if ($use_cookies) {
            $timeout = empty($PHORUM['session_timeout'])
                     ? 0 : time() + 86400 * $PHORUM['session_timeout'];
            setcookie(
                PHORUM_COOKIE_LONG_TERM,
                $user['user_id'].':'.$sessid_lt,
                $timeout,
                $PHORUM['session_path'], $PHORUM['session_domain']
            );
        } else {
            // Add the session id to the URL building GET variables.
            $GLOBALS['PHORUM']['DATA']['GET_VARS'][PHORUM_COOKIE_LONG_TERM] =
                PHORUM_COOKIE_LONG_TERM . "=" .
                urlencode($user['user_id'].':'.$sessid_lt);

            // Add the session id to the form POST variables.
            $GLOBALS['PHORUM']['DATA']['POST_VARS'] .=
                '<input type="hidden" name="'.PHORUM_COOKIE_LONG_TERM.'" ' .
                'value="'.$user['user_id'].':'.$sessid.'" />';
        }

        // The short term session id is always put in a cookie.
        if ($refresh_sessid_st) {
            setcookie(
                PHORUM_COOKIE_SHORT_TERM,
                $user['user_id'].':'.$user['sessid_st'],
                $user['sessid_st_timeout'],
                $PHORUM['session_path'], $PHORUM['session_domain']
            ); 
        }
    }

    // The admin session id is always put in a cookie.
    elseif ($type == PHORUM_ADMIN_SESSION) {
        setcookie(
            PHORUM_COOKIE_ADMIN,
            $user['user_id'].':'.$sessid_admin,
            0, // admin sessions are destroyed as soon as the browser closes
            $PHORUM['session_path'], $PHORUM['session_domain']
        ); 
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_api_user_session_restore()
/**
 * Restore a Phorum user session.
 *
 * This function will check for a valid user session for either the
 * forum or the admin interface (based on the $type parameter). If a valid
 * session is found, then the user session will be restored.
 *
 * @param string $type
 *     The type of session to check for. This must be one of
 *     {@link PHORUM_FORUM_SESSION} or {@link PHORUM_ADMIN_SESSION}.
 *     See the documentation for {@link phorum_api_user_session_create()}
 *     for more information on Phorum user sessions.
 *
 * @return boolean
 *     TRUE in case a valid session is detected, otherwise FALSE.
 *     Note that a {@link PHORUM_FORUM_SESSION} will return TRUE if
 *     a long term session is detected and that the sort term session
 *     might be missing. Code which depends on short term sessions should
 *     investigate the $PHORUM["DATA"]["FULLY_LOGGEDIN"] variable.
 */
function phorum_api_user_session_restore($type)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // ----------------------------------------------------------------------
    // Determine which session cookie(s) to check.
    // ----------------------------------------------------------------------

    // A list of session cookies to lookup.
    // The possible values for the items in this array are:
    //
    // 0: this cookie does not have to be checked
    // 1: a check has to be done for this cookie
    // 2: the check for this cookie was successful
    //
    $check_session = array(
        PHORUM_COOKIE_LONG_TERM  => 0,
        PHORUM_COOKIE_SHORT_TERM => 0,
        PHORUM_COOKIE_ADMIN      => 0
    );

    if ($type == PHORUM_FORUM_SESSION)
    {
        // Lookup the long term cookie.
        $check_session[PHORUM_COOKIE_LONG_TERM] = 1;

        // Lookup the short term cookie if tight security and
        // cookies are both enabled. With URI authentication,
        // only the long term cookie is checked and considered
        // to be equal to short term cookies (since the user has
        // to login on every session anyway).
        if (!empty($PHORUM['tight_security']) &&
            isset($PHORUM['use_cookies']) &&
            $PHORUM['use_cookies'] > PHORUM_NO_COOKIES) {
            $check_session[PHORUM_COOKIE_SHORT_TERM] = 1;
        }
    }
    elseif ($type == PHORUM_ADMIN_SESSION)
    {
        // Lookup the admin cookie.
        $check_session[PHORUM_COOKIE_ADMIN] = 1;
    }
    else {
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            'phorum_api_user_session_restore(): Illegal session type: ' .
            htmlspecialchars($type)
        );
    }

    // ----------------------------------------------------------------------
    // Check the cookie(s).
    // ----------------------------------------------------------------------

    $session_user = NULL;
    foreach ($check_session as $cookie => $do_check)
    {
        if (!$do_check) continue;

        // First, check for a real cookie, which can always be expected for
        // short term and admin sessions and for long term sessions if
        // cookies are enabled.
        if (($cookie != PHORUM_SESSION_LONG_TERM ||
             (isset($PHORUM['use_cookies']) &&
             $PHORUM['use_cookies'] > PHORUM_NO_COOKIES)) &&
              isset($_COOKIE[$cookie]) ) {
            $value = $_COOKIE[$cookie];
            $GLOBALS['PHORUM']['use_cookies'] = TRUE;
        // Check for URI based authentication.
        } elseif ($PHORUM['use_cookies'] < PHORUM_REQUIRE_COOKIES &&
                  isset($PHORUM['args'][$cookie])) {
            $value = urldecode($PHORUM['args'][$cookie]);
            $GLOBALS['PHORUM']['use_cookies'] = FALSE;
        // Check for session id in form POST data.
        } elseif ($PHORUM['use_cookies'] < PHORUM_REQUIRE_COOKIES &&
                  isset($_POST[$cookie])) {
            $value = $_POST[$cookie];
            $GLOBALS['PHORUM']['use_cookies'] = FALSE;
        // Check for session id in form GET data (should rarely happen, but
        // it helps sometimes).
        } elseif ($PHORUM['use_cookies'] < PHORUM_REQUIRE_COOKIES &&
                  isset($_GET[$cookie])) {
            $value = $_GET[$cookie];
            $GLOBALS['PHORUM']['use_cookies'] = FALSE;
        } else {
            // Cookie not found. Continue with the next one.
            continue;
        }

        // The cookie value is formatted as <user id>:<session id>. 
        // Split these into separate parts.
        list($user_id, $sessid) = explode(':', $value, 2);

        // The user_id should be numerical at all times.
        if (!is_numeric($user_id)) continue;

       // Find the data for the session user by its user_id. If the user
       // cannot be found, then the anonymous user is setup.
        if ($session_user === NULL) {
            $session_user = phorum_user_get($user_id, TRUE, TRUE);
            if (empty($session_user) ||
                $session_user['active'] != PHORUM_USER_ACTIVE) {

                // TODO API what about this? Need to fit this in a good spot
                // TODO API once the clear session code is done.
                // Finish any session that was going on.
                phorum_user_clear_session($type);

                // Setup the anonymous Phorum user.
                phorum_api_user_set_active_user($type, NULL);

                return FALSE;
            }
        } else {
            // The user_id should be the same for all cookies.
            // If a different user_id is found, then the cookie
            // that we're currently looking at is ignored. It could
            // be an old cookie for a different user.
            if ($session_user['user_id'] !== $user_id) {
                continue;
            }
        }

        // Check if the session id from the cookie is valid for the user.
        $valid_session = 
            ($cookie == PHORUM_COOKIE_LONG_TERM  &&
             !empty($session_user['sessid_lt']) &&
             $session_user['sessid_lt'] == $sessid) ||

            ($cookie == PHORUM_COOKIE_SHORT_TERM &&
             !empty($session_user['sessid_st']) &&
             $session_user['sessid_st'] == $sessid) ||

            ($cookie == PHORUM_COOKIE_ADMIN &&
             !empty($session_user['sessid_lt']) &&
             md5($session_user['sessid_lt'].$PHORUM['admin_session_salt']) == $sessid);

        // Keep track of valid session cookies.
        if ($valid_session) {
            $check_session[$cookie] = 2; 
        }
    }

    // ----------------------------------------------------------------------
    // Check if a user session needs to be restored.
    // ----------------------------------------------------------------------

    $do_restore_session = FALSE;
    $do_restore_short_term_session = FALSE;

    if ($type == PHORUM_FORUM_SESSION)
    {
        // Valid long term forum session found.
        if ($check_session[PHORUM_COOKIE_LONG_TERM] == 2)
        {
            $do_restore_session = TRUE;

            if ($check_session[PHORUM_COOKIE_SHORT_TERM] == 1) {
                // Checked short term session, but no valid session found.
                $do_restore_short_term_session = FALSE;
            } else {
                // Short term session not checked (0) or valid (2).
                $do_restore_short_term_session = TRUE;
            }
        }
    }
    elseif ($type == PHORUM_ADMIN_SESSION)
    {
        // Valid admin session found. Note that the function
        // phorum_api_user_set_active_user() might still reject the user
        // if it's not an admin user (anymore).
        if ($check_session[PHORUM_COOKIE_ADMIN] == 2) {
            $do_restore_session = TRUE;
        }
    }

    // ----------------------------------------------------------------------
    // Restore the user session.
    // ----------------------------------------------------------------------

    // No session to restore? Then setup the anonymous user.
    if (! $do_restore_session)
    {
        // TODO API what about this? Need to fit this in a good spot once
        // TODO API the clear session code is done.
        // Finish any session that was going on.
        phorum_user_clear_session($type);

        // Setup the anonymous Phorum user.
        phorum_api_user_set_active_user($type, NULL);

        return FALSE;
    }
    // Restore a user's session.
    else
    {
        // Setup the Phorum user.
        $flags = 0;
        if ($do_restore_short_term_session) $flags |= PHORUM_FLAG_SESSION_ST;
        phorum_api_user_set_active_user($type, $session_user, $flags);

        // Keep the session alive for the user.
        phorum_api_user_session_create($type);

        return TRUE;
    }
}
// }}}


?>
