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
 * @package    PhorumAPI
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 *
 * @todo
 *     Make sure that PHORUM_ORIGINAL_USER_CODE is handled somehow
 *     in the new API layer.
 *
 * @todo
 *     Make sure that any user_id works if handled through an external
 *     service. String based ids should be possible too, which might
 *     be needed for some systems.
 *
 * @todo
 *     Enable constants if include/users.php is no longer in use.
 */

if (!defined('PHORUM')) return;

// {{{ Constant and variable definitions
/**
 * The name of the session cookie to use for long term authentication.
 */
//define( "PHORUM_SESSION_LONG_TERM" , "phorum_session_v5" );

/**
 * The name of the session cookie to use for short term authentication
 * (this is used by the strict authentication scheme).
 */
//define( "PHORUM_SESSION_SHORT_TERM", "phorum_session_st" );

/**
 * The name of the session cookie for admin interface authentication.
 */
//define( "PHORUM_SESSION_ADMIN",      "phorum_admin_session" );

/**
 * An empty user record that is used for representing an anonymous user.
 */
$GLOBALS["PHORUM"]["anonymous_user"] = array(
    "user_id"  => 0,
    "username" => "",
    "admin"    => false,
    "newinfo"  => array()
);
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
 *     If the authentication was successful, this function returns the
 *     user_id of the authenticated user. Otherwise, FALSE is returned.
 */
function phorum_api_user_authenticate($username, $password)
{
    $user_id = NULL;

    // Give modules a chance to handle the user authentication (for example
    // to authenticate against an external source). The module can change the
    // user_id in the authinfo array to one of:
    // - user_id: the user_id of the authenticated user;
    // - FALSE: authentication credentials are rejected;
    // - NULL: let Phorum handle the authentication.
    if (isset($PHORUM["hooks"]["user_check_login"]))
    {
        // Run the hook.
        $authinfo = phorum_hook("user_check_login", array(
            "user_id"  => NULL,
            "username" => $username,
            "password" => $password
        ));

        // Authentication rejected by module.
        if ($authinfo["user_id"] === FALSE) {
            phorum_api_user_set_active_user(NULL);
            return FALSE;
        }

        $user_id = $authinfo["user_id"];
    }

    // No module handled the authentication?
    // Then we have to run the Phorum authentication.
    if ($user_id === NULL) 
    {
        $user_id = phorum_db_user_check_login($username, md5($password));

        // Password check failed? Then try the temporary password (used for
        // the password reminder feature).
        $temporary_matched = FALSE;
        if ($user_id == 0) {
            $user_id = phorum_db_user_check_login($username,md5($password),TRUE);
            if ($user_id != 0) {
                $temporary_matched = TRUE;
            }
        }

        // If the temporary password matched, then synchronize the main
        // password with the temporary password. The temporary password is kept
        // the same (setting it to an empty value might result in problems).
        if ($temporary_matched) {
            phorum_db_user_save(array(
                "user_id"  => $user_id,
                "password" => $password
            ));
        }
    }
    
    // Set the active Phorum user and return the user_id. If the user was
    // not authenticated, then the active user will be set to the anonymous
    // user and FALSE will be returned.
    return phorum_api_user_set_active_user($user_id);
}
// }}}

// {{{ Function: phorum_api_user_set_active_user()
/**
 * Set the active Phorum user. 
 *
 * @param mixed $user_id
 *     The user_id of the user that has to be the active user or NULL if
 *     the active user has to be set to the anonymous user (the default).
 *
 * @return mixed
 *     The user_id of the active user or FALSE if the anonymous user was set.
 */
function phorum_api_user_set_active_user($user_id = NULL)
{
    $user = NULL;

    // Retrieve the user data for the provided user_id.
    if ($user_id !== NULL) {
        $user = phorum_user_get($user_id);
    }

    // Set the active user.
    if ($user && $user['active'] == PHORUM_USER_ACTIVE) {
        $GLOBALS["PHORUM"]["user"] = $user;
        return $user_id;
    } else {
        $GLOBALS["PHORUM"]["user"] = $GLOBALS["PHORUM"]["anonymous_user"];
        return FALSE;
    }
}
// }}}

?>
