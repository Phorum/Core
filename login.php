<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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

define('phorum_page','login');

require_once './common.php';
require_once PHORUM_PATH.'/include/api/generate.php';
require_once PHORUM_PATH.'/include/api/mail.php';

// Set all our URLs.
phorum_build_common_urls();

$template = 'login';    // the template to display
$error    = '';         // error message to show
$okmsg    = '';         // success message to show
$focus    = 'username'; // id of the field to focus to after loading the page
$heading  = $PHORUM['DATA']['LANG']['LogIn']; // The page heading

// init for later use
$redir = phorum_api_url(PHORUM_LIST_URL);


// Determine to what URL the user must be redirected after login.
if (!empty($PHORUM['args']['redir'])) {
    $redir = urldecode($PHORUM['args']['redir']);
} elseif (!empty($_GET['redir'])) {
    $redir = $_GET['redir'];
} elseif (!empty($_POST['redir'])) {
    $redir = $_POST['redir'];
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $base = strtolower(phorum_api_url_base());
    $len = strlen($base);
    if (strtolower(substr($_SERVER['HTTP_REFERER'], 0, $len)) == $base) {
        $redir = $_SERVER['HTTP_REFERER'];
    }
}

// ----------------------------------------------------------------------------
// Handle a logout request
// ----------------------------------------------------------------------------

if ($PHORUM['DATA']['LOGGEDIN'] && !empty($PHORUM['args']['logout']))
{
    /*
     * [hook]
     *     before_logout
     *
     * [description]
     *     This hook can be used for performing tasks before a user logout.
     *     The user data will still be availbale in
     *     <literal>$PHORUM["user"]</literal> at this point.
     *
     * [category]
     *     Login/Logout
     *
     * [when]
     *     In <filename>login.php</filename>, just before destroying the user
     *     session.
     *
     * [input]
     *     None
     *
     * [output]
     *     None
     */
    if (isset($PHORUM['hooks']['before_logout'])) {
        phorum_api_hook('before_logout');
    }

    phorum_api_user_session_destroy(PHORUM_FORUM_SESSION);

    // Determine the URL to redirect the user to. The hook "after_logout"
    // can be used by module writers to set a custom redirect URL.
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    } else {
        $url = phorum_api_url_no_uri_auth(PHORUM_LIST_URL);
    }

    /*
     * [hook]
     *     after_logout
     *
     * [description]
     *     This hook can be used for performing tasks after a successful
     *     user logout and for changing the page to which the user will be
     *     redirected (by returning a different redirection URL). The user
     *     data will still be available in <literal>$PHORUM["user"]</literal>
     *     at this point.
     *
     * [category]
     *     Login/Logout
     *
     * [when]
     *     In <filename>login.php</filename>, after a logout, just before
     *     redirecting the user to a Phorum page.
     *
     * [input]
     *     The redirection URL.
     *
     * [output]
     *     Same as input.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_after_logout($url)
     *     {
     *         global $PHORUM;
     *
     *         // Return to the site's main page on logout
     *         $url = $PHORUM["mod_foo"]["site_url"];
     *
     *         return $url;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM['hooks']['after_logout'])) {
        $url = phorum_api_hook('after_logout', $url);
    }

    phorum_api_redirect($url);
}

// ----------------------------------------------------------------------------
// Handle the initial login page request
// ----------------------------------------------------------------------------

// No data posted, so this is the first request. Here we set a temporary
// cookie, so we can check if the user's browser supports cookies.
if (empty($_POST) && $PHORUM['use_cookies'] > PHORUM_NO_COOKIES) {
    setcookie(
        'phorum_tmp_cookie',
        'this will be destroyed once logged in',
        0, $PHORUM['session_path'], $PHORUM['session_domain']
    );
}

// CSRF protection: we do not accept posting to this script,
// when the browser does not include a Phorum signed token
// in the request.
phorum_api_request_check_token();

// ----------------------------------------------------------------------------
// Handle custom module requests
// ----------------------------------------------------------------------------

/**
 * [hook]
 *     login_custom_action
 *
 * [description]
 *     This hook can be used to implement a custom login page request
 *     handler in a module. The handler can modify the POST data when
 *     needed, but it can also be used to fully override the login
 *     page POST handling.<br/>
 *     <br/>
 *     Note that for most authentication-related modifications, the user
 *     related hooks are the way to go. This hook was mainly added for
 *     supporting systems like OpenID, which require a totally different
 *     login mechanism (only an OpenID string is provided and no
 *     username + password, account registration might have to be
 *     triggered at first login.)
 *
 * [category]
 *     Login/Logout
 *
 * [when]
 *     Right before login.php starts processing a request.
 *
 * [input]
 *     An array containing the following fields:
 *     <ul>
 *     <li>template:
 *         the name of the template that has to be loaded. This field can
 *         be filled by the module if it wants to load a specific
 *         template.</li>
 *     <li>heading:
 *         the page heading (title) to use ("Log In" by default).
 *         This field can be set by the module if a specific heading
 *         is required.</li>
 *     <li>handled:
 *         if a module does handle the login page request, then it can set
 *         this field to a true value, to prevent Phorum from running the
 *         standard login script code.</li>
 *     <li>error:
 *         modules can fill this field with an error message to show.</li>
 *     <li>okmsg:
 *         modules can fill this field with an ok message to show.</li>
 *     <li>redir:
 *         modules can fill this field with the URL to redirect to after
 *         a successful login. The URL that Phorum will use by default
 *         is already available in the field.</li>
 *     <li>focus:
 *         modules can fill this field with the id of the form field to
 *         focus after loading the page.</li>
 *     </ul>
 *
 * [output]
 *     The same array as the one that was used for the hook call
 *     argument, possibly with the "template", "handled", "error",
 *     "okmsg" and "redir" fields updated in it.
 */
$hook_info = array(
    'template' => NULL,
    'heading'  => NULL,
    'handled'  => FALSE,
    'error'    => NULL,
    'okmsg'    => NULL,
    'redir'    => $redir,
    'focus'    => NULL
);
if (isset($PHORUM['hooks']['login_custom_action'])) {
    $hook_info = phorum_api_hook('login_custom_action', $hook_info);
}

// Retrieve template, error and okmsg info from the module info.
if ($hook_info['template'] !== NULL) { $template = $hook_info['template']; }
if ($hook_info['heading']  !== NULL) { $heading  = $hook_info['heading']; }
if ($hook_info['okmsg']    !== NULL) { $okmsg    = $hook_info['okmsg']; }
if ($hook_info['error']    !== NULL) { $error    = $hook_info['error']; }
if ($hook_info['redir']    !== NULL) { $redir    = $hook_info['redir']; }
if ($hook_info['focus']    !== NULL) { $focus    = $hook_info['focus']; }

// ----------------------------------------------------------------------------
// Handle login requests
// ----------------------------------------------------------------------------

if (!$hook_info['handled'] && isset($_POST['username']))
{
    $_POST['username'] = trim($_POST['username']);
    $_POST['password'] = trim($_POST['password']);

    $focus = $_POST['username'] == '' ? 'username' : 'password';

    // Check if the phorum_tmp_cookie was set. If not, the user's
    // browser does not support cookies. If cookies are required,
    // then the login will be denied.
    if ($PHORUM['use_cookies'] == PHORUM_REQUIRE_COOKIES &&
        !isset($_COOKIE['phorum_tmp_cookie'])) {
        $error = $PHORUM['DATA']['LANG']['RequireCookies'];
    }

    // Check if the username and password were filled in.
    elseif($_POST['username'] == '' || $_POST['password'] == '') {
        $error = $PHORUM['DATA']['LANG']['ErrRequired'];
    }

    // All data is available. Handle the login request.
    else
    {
        // See if the temporary cookie was found. If yes, then the
        // browser does support cookies. If not, then we disable
        // the use of cookies.
        if (!isset($_COOKIE['phorum_tmp_cookie'])) {
            $PHORUM['use_cookies'] = PHORUM_NO_COOKIES;
        }

        // Check if the login credentials are right.
        $user_id = phorum_api_user_authenticate(
            PHORUM_FORUM_SESSION, $_POST['username'], $_POST['password']
        );

        // They are. Setup the active user and start a Phorum session.
        if ($user_id)
        {
            // Make the authenticated user the active Phorum user
            // and start a Phorum user session. Because this is a fresh
            // login, we can enable the short term session and we request
            // refreshing of the session id(s).
            if (phorum_api_user_set_active_user(
                    PHORUM_FORUM_SESSION, $user_id,
                    PHORUM_FLAG_SESSION_ST
                ) &&
                phorum_api_user_session_create(
                    PHORUM_FORUM_SESSION,
                    PHORUM_SESSID_RESET_LOGIN
                ))
            {
                // Destroy the temporary cookie that is used for testing
                // for cookie compatibility.
                if (isset($_COOKIE['phorum_tmp_cookie'])) {
                    setcookie(
                        'phorum_tmp_cookie', '', 0,
                        $PHORUM['session_path'], $PHORUM['session_domain']
                    );
                }

                // Determine the URL to redirect the user to.
                // If redir is a number, it is a URL constant.
                $php = PHORUM_FILE_EXTENSION;
                if (is_numeric($_POST['redir'])){
                    $redir = phorum_api_url((int)$_POST['redir']);
                }
                // Redirecting to the registration or login page is a
                // little weird, so we just go to the list page if we came
                // from one of those.
                elseif (
                    !empty($PHORUM['use_cookies']) &&
                    !strstr($_POST['redir'], "register.$php") &&
                    !strstr($_POST['redir'], "login.$php")) {

                    $redir = $_POST['redir'];
                }
                // By default, we redirect to the list page.
                else {
                    $redir = phorum_api_url( PHORUM_LIST_URL );
                }

                // Checking if redirection is done to the same domain,
                // localhost or a URL defined through the settings.
                // This is done to prevent arbitrary redirection of
                // logged in users, which could be used for phishing
                // attacks on users.
                $redir_ok = FALSE;
                $check_urls = array();
                if (!empty($PHORUM['login_redir_urls'])) {
                    $check_urls = explode(',', $PHORUM['login_redir_urls']);
                }
                $check_urls[] = 'http://localhost';
                $check_urls[] = $PHORUM['http_path'];

                foreach ($check_urls as $check_url)
                {
                     // The redir-url has to start with one of these URLs.
                     if (stripos($redir, $check_url) === 0) {
                            $redir_ok = TRUE;
                            break;
                     }
                }
                // If redirection is done to an illegal URL, we redirect
                // the user to the list page by default.
                if (!$redir_ok) {
                    $redir = phorum_api_url(PHORUM_LIST_URL);
                }

                /*
                 * [hook]
                 *     after_login
                 *
                 * [description]
                 *     This hook can be used for performing tasks after a
                 *     successful user login and for changing the page to
                 *     which the user will be redirected (by returning a
                 *     different redirection URL). If you need to access the
                 *     user data, then you can do this through the global
                 *     <literal>$PHORUM</literal> variable. The user data
                 *     will be in <literal>$PHORUM["user"]</literal>.
                 *
                 * [category]
                 *     Login/Logout
                 *
                 * [when]
                 *     In <filename>login.php</filename>, after a successful
                 *     login, just before redirecting the user to a Phorum
                 *     page.
                 *
                 * [input]
                 *     The redirection URL.
                 *
                 * [output]
                 *     Same as input.
                 *
                 * [example]
                 *     <hookcode>
                 *     function phorum_mod_foo_after_login($url)
                 *     {
                 *         global $PHORUM;
                 *
                 *         // Redirect to the user's chosen page
                 *         $url = $PHORUM["user"]["phorum_mod_foo_user_url"];
                 *
                 *         return $url;
                 *     }
                 *     </hookcode>
                 */
                if (isset($PHORUM['hooks']['after_login'])) {
                    $redir = phorum_api_hook('after_login', $redir);
                }

                phorum_api_redirect($redir);
            }
        }

        // Login failed or session startup failed. For both we show
        // the invalid login error.
        $error = $PHORUM['DATA']['LANG']['InvalidLogin'];

        /*
         * [hook]
         *     failed_login
         *
         * [description]
         *     This hook can be used for tracking failing login attempts.
         *     This can be used for things like logging or implementing
         *     login failure penalties (like temporarily denying access after
         *     X login attempts).
         *
         * [category]
         *     Login/Logout
         *
         * [when]
         *     In <filename>login.php</filename>, when a user login fails.
         *
         * [input]
         *     An array containing three fields (read-only):
         *     <ul>
         *         <li>username</li>
         *         <li>password</li>
         *         <li>location
         *         <ul>
         *              <li>The location field specifies where the login
         *              failure occurred and its value can be either
         *              <literal>forum</literal> or
         *              <literal>admin</literal>.</li>
         *         </ul></li>
         *     </ul>
         *
         * [output]
         *     Same as input.
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_failed_login($data)
         *     {
         *         global $PHORUM;
         *
         *         // Get the current timestamp
         *         $curr_time = time();
         *
         *         // Check for a previous login failure from the current IP address
         *         if (!empty($PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]])) {
         *             // If the failures occur within the set time window,
         *             // increment the login failure count
         *             if ( $curr_time
         *                  <= ($PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"]
         *                     + (int)$PHORUM["mod_foo"]["login_failures_time_window"]) ) {
         *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["login_failure_count"] ++;
         *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"] = $curr_time;
         *             // Otherwise, reset the count.
         *             } else {
         *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["login_failure_count"] = 1;
         *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"] = $curr_time;
         *             }
         *         } else {
         *             // Log the timestamp and IP address of a login failure
         *             $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["login_failure_count"] = 1;
         *             $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"] = $curr_time;
         *         }
         *         $PHORUM['DB']->update_settings(array("mod_foo" => $PHORUM["mod_foo"]));
         *
         *         return $data;
         *     }
         *     </hookcode>
         */
        // TODO API: move to user API.
        if (isset($PHORUM['hooks']['failed_login'])) {
            phorum_api_hook('failed_login', array(
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'location' => 'forum'
            ));
        }
    }
}

// ----------------------------------------------------------------------------
// Handle password reminder requests
// ----------------------------------------------------------------------------

if (!$hook_info['handled'] && isset($_POST['lostpass']))
{
    // Trim the email address.
    $_POST['lostpass'] = trim($_POST['lostpass']);

    $hook_args = NULL;

    // Did the user enter an email address?
    if ($_POST['lostpass'] == '') {
        $error = $PHORUM['DATA']['LANG']['ErrRequired'];
        $focus = 'lostpass';
    }

    // Is the email address available in the database?
    elseif ($uid = phorum_api_user_search('email', $_POST['lostpass']))
    {
        // An existing user id was found for the entered email
        // address. Retrieve the user.
        $user = phorum_api_user_get($uid);

        // User registration not yet approved by a moderator.
        // Tell the user that we are awaiting approval.
        if ($user['active'] == PHORUM_USER_PENDING_MOD)
        {
            $template = 'message';
            $okmsg = $PHORUM['DATA']['LANG']['RegVerifyMod'];

            $hook_args = array(
                'status' => 'unapproved',
                'email'  => $_POST['lostpass'],
                'user'   => $user,
                'secret' => NULL
            );
        }

        // The user registration still needs email verification.
        // For this case, we generate a new confirmation code and
        // send out a new account verficiation message.
        elseif ($user['active'] == PHORUM_USER_PENDING_EMAIL ||
                $user['active'] == PHORUM_USER_PENDING_BOTH) {

            // Generate and store a new registration code.
            $regcode = substr(md5(microtime()), 0, 8);
            phorum_api_user_save(array(
                'user_id'       => $uid,
                'password_temp' => $regcode
            ));

            // The URL that the user can visit to confirm the account.
            $verify_url = phorum_api_url(
                PHORUM_REGISTER_URL,
                'approve='. $regcode . $uid
            );

            // Build the mail data for the phorum_api_mail() call.
            $mail_data = array();

            $mail_data['mailsubject'] =
                $PHORUM['DATA']['LANG']['VerifyRegEmailSubject'];

            // The mailmessage can be composed in two different ways.
            // This was done for backward compatibility for the language
            // files. Up to Phorum 5.2, we had VerifyRegEmailBody1 and
            // VerifyRegEmailBody2 for defining the lost password mail body.
            // In 5.3, we switched to a single variable VerifyRegEmailBody.
            if (isset($PHORUM['DATA']['LANG']['VerifyRegEmailBody']))
            {
                $mail_data['mailmessage'] = phorum_api_format_wordwrap(str_replace(
                    array(
                        '%title%',
                        '%username%',
                        '%verify_url%',
                        '%login_url%'
                    ),
                    array(
                        $PHORUM['title'],
                        $user['username'],
                        $verify_url,
                        phorum_api_url(PHORUM_LOGIN_URL)
                    ),
                    $PHORUM['DATA']['LANG']['VerifyRegEmailBody']
                ), 72);
            }
            else
            {
                // Hide the deprecated language strings from the
                // amin language tool by not using the full syntax
                // for those.
                $lang = $PHORUM['DATA']['LANG'];
                $mail_data['mailmessage'] =
                   phorum_api_format_wordwrap($lang['VerifyRegEmailBody1'], 72).
                   "\n\n$verify_url\n\n".
                   phorum_api_format_wordwrap($lang['VerifyRegEmailBody2'], 72);
            }

            phorum_api_mail($user['email'], $mail_data);

            $okmsg = $PHORUM['DATA']['LANG']['RegVerifyEmail'];
            $template='message';

            $hook_args = array(
                'status' => 'new_verification',
                'email'  => $_POST['lostpass'],
                'user'   => $user,
                'secret' => $regcode
            );
        }

        // The user is active. We generate a new password and send
        // that one to the user.
        else
        {
            // Generate and store a new password for the user.
            $newpass = phorum_api_generate_password();
            phorum_api_user_save(array(
                'user_id'       => $uid,
                'password_temp' => $newpass
            ));

            // Mail the new password.
            $user = phorum_api_user_get($uid);
            $mail_data = array();

            // The mailmessage can be composed in two different ways.
            // This was done for backward compatibility for the language
            // files. Up to Phorum 5.2, we had LostPassEmailBody1 and
            // LostPassEmailBody2 for defining the lost password mail body.
            // In 5.3, we switched to a single variable LostPassEmailBody.
            // Eventually, the variable replacements need to be handled
            // by the mail API layer.
            if (isset($PHORUM['DATA']['LANG']['LostPassEmailBody']))
            {
                $mail_data['mailmessage'] = phorum_api_format_wordwrap(str_replace(
                    array(
                        '%title%',
                        '%username%',
                        '%password%',
                        '%login_url%'
                    ),
                    array(
                        $PHORUM['title'],
                        $user['username'],
                        $newpass,
                        phorum_api_url(PHORUM_LOGIN_URL)
                    ),
                    $PHORUM['DATA']['LANG']['LostPassEmailBody']
                ), 72);
            }
            else
            {
                // Hide the deprecated language strings from the
                // amin language tool by not using the full syntax
                // for those.
                $lang = $PHORUM['DATA']['LANG'];

                $mail_data['mailmessage'] =
                   phorum_api_format_wordwrap($lang['LostPassEmailBody1'], 72) .
                   "\n\n".
                   $lang['Username'] .": $user[username]\n".
                   $lang['Password'] .": $newpass" .
                   "\n\n".
                   phorum_api_format_wordwrap($lang['LostPassEmailBody2'], 72);
            }

            $mail_data['mailsubject'] = $PHORUM['DATA']['LANG']['LostPassEmailSubject'];
            phorum_api_mail($user['email'], $mail_data);

            $okmsg = $PHORUM['DATA']['LANG']['LostPassSent'];

            $hook_args = array(
                'status' => 'new_password',
                'email'  => $_POST['lostpass'],
                'user'   => $user,
                'secret' => $newpass
            );
        }
    }

    // The entered email address was not found.
    else
    {
        $error = $PHORUM['DATA']['LANG']['LostPassError'];
        $focus = 'lostpass';

        $hook_args = array(
            'status' => 'user_unknown',
            'email'  => $_POST['lostpass'],
            'user'   => NULL,
            'secret' => NULL
        );
    }

    /*
     * [hook]
     *     password_reset
     *
     * [availability]
     *     Phorum 5 >= 5.2.13
     *
     * [description]
     *     This hook is called after handling a password reset request.
     *     Based on whether a user account can be found for the
     *     provided email address and what the account status for that
     *     user is, different actions are performed by Phorum before
     *     calling this hook:
     *     <ul>
     *       <li>If no user account can be found for the provided email
     *           address, then nothing is done.</li>
     *       <li>If the account is not yet approved by a moderator,
     *           then no new password is generated for the user.</li>
     *       <li>If the account is active, then a new password is
     *           mailed to the user's email address.</li>
     *       <li>If the account is new and not yet confirmed by
     *           email, then a new account confirmation code is
     *           generated and sent to the user's email address.</li>
     *     </ul>
     *     The main purpose of this hook is to log password reset
     *     requests.
     *
     * [category]
     *     Login/Logout
     *
     * [when]
     *     In <filename>login.php</filename>, after handling
     *     a password reset request.
     *
     * [input]
     *     An array containing four elements:
     *     <ul>
     *         <li>status: the password reset status, which can be:
     *             "new_password" (a new password was generated and
     *             sent for an active account),
     *             "new_verification" (a new account verification code
     *             was generated and sent for a new account that was
     *             not yet confirmed by email),
     *             "unapproved" (in case the account was not yet
     *             approved by a moderator, no new password or
     *             verification code was generated for the user) or
     *             "user_unknown" (when the provided email address cannot
     *             be found in the database).</li>
     *         <li>email: the email address that the user entered
     *             in the lost password form.</li>
     *         <li>user: a user data array. This is the user data for
     *             the email address that the user entered in the lost
     *             password form. If no matching user could be found
     *             (status = "user_unknown"), then this element will be
     *             NULL.</li>
     *         <li>secret: The new password or verification code for
     *             respectively the statuses "new_password" and
     *             "new_verification". For other statuses, this
     *             element will be NULL.</li>
     *     </ul>
     *
     * [output]
     *     Same as input.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_password_reset($data)
     *     {
     *         $log = NULL;
     *         switch ($data['status'])
     *         {
     *             case 'new_password':
     *                 $log = 'New password generated for ' .
     *                        $data['user']['username'] . ': ' .
     *                        $data['secret'];
     *                 break;
     *             case 'new_verification':
     *                 $log = 'New verification code generated for ' .
     *                        $data['user']['username'] . ': ' .
     *                        $data['secret'];
     *                 break;
     *             case 'user_unknown':
     *                 $log = 'Could not find a user for email ' .
     *                        $data['email'];
     *                 break;
     *             case 'unapproved':
     *                 $log = 'No new password generated for ' .
     *                        'unapproved user ' . $user['username'];
     *                 break;
     *         }
     *
     *         if ($log !== NULL) {
     *             log_the_password_reset($log);
     *         }
     *
     *         return $user;
     *     }
     *     </hookcode>
     */
    if ($hook_args && isset($PHORUM['hooks']['password_reset'])) {
        phorum_api_hook("password_reset", $hook_args);
    }
}

// ----------------------------------------------------------------------------
// Build template data and output the page
// ----------------------------------------------------------------------------

$redir = htmlspecialchars($redir, ENT_COMPAT, $PHORUM['DATA']['HCHARSET']);

// Fill the breadcrumbs-info.
$PHORUM['DATA']['BREADCRUMBS'][]=array(
    'URL'  => '',
    'TEXT' => $PHORUM['DATA']['LANG']['LogIn'],
    'TYPE' => 'login'
);

// Fill the page heading info.
$PHORUM['DATA']['HEADING'] = $heading;
$PHORUM['DATA']['HTML_DESCRIPTION'] = '';
$PHORUM['DATA']['DESCRIPTION'] = '';

// Setup template data.
$PHORUM['DATA']['LOGIN']['redir'] = $redir;
$PHORUM['DATA']['URL']['REGISTER'] = phorum_api_url(PHORUM_REGISTER_URL);
$PHORUM['DATA']['URL']['ACTION'] = phorum_api_url(PHORUM_LOGIN_ACTION_URL);
$PHORUM['DATA']['LOGIN']['forum_id'] = (int)$PHORUM['forum_id'];
$PHORUM['DATA']['LOGIN']['username'] = (!empty($_POST['username'])) ? htmlspecialchars($_POST['username'], ENT_COMPAT, $PHORUM['DATA']['HCHARSET']) : '';
$PHORUM['DATA']['ERROR'] = $error;
$PHORUM['DATA']['OKMSG'] = $okmsg;

$PHORUM['DATA']['POST_VARS'] .=
    "<input type=\"hidden\" name=\"redir\" value=\"$redir\" />\n";

$PHORUM['DATA']['FOCUS_TO_ID'] = $focus;

// Display the login page.
phorum_api_output($template);

?>
