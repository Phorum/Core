<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
////////////////////////////////////////////////////////////////////////////////
define('phorum_page','login');

include_once( "./common.php" );
include_once( "./include/email_functions.php" );

// ----------------------------------------------------------------------------
// Handle logout
// ----------------------------------------------------------------------------

if ($PHORUM['DATA']['LOGGEDIN'] && !empty($PHORUM["args"]["logout"])) {

    /*
     * [hook]
     *     before_logout
     *
     * [description]
     *     This hook can be used for performing tasks before a user logout. The
     *     user data will still be availbale in 
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
    if (isset($PHORUM["hooks"]["before_logout"]))
        phorum_hook("before_logout");

    phorum_api_user_session_destroy(PHORUM_FORUM_SESSION);

    // Determine the URL to redirect the user to. The hook "after_logout"
    // can be used by module writers to set a custom redirect URL.
    if (isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER["HTTP_REFERER"];
    } else {
        $url = phorum_get_url(PHORUM_LIST_URL);
    }

    // Strip the session id from the URL in case URI auth is in use.
    if (stristr($url, PHORUM_SESSION_LONG_TERM)){
        $url = preg_replace('!'.PHORUM_SESSION_LONG_TERM.'=[^$&,]+!', "", $url);
    }

    /*
     * [hook]
     *     after_logout
     *
     * [description]
     *     This hook can be used for performing tasks after a successful user
     *     logout and for changing the page to which the user will be redirected
     *     (by returning a different redirection URL). The user data will still
     *     be availbale in <literal>$PHORUM["user"]</literal> at this point.
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
    if (isset($PHORUM["hooks"]["after_logout"]))
        $url = phorum_hook("after_logout", $url);

    phorum_redirect_by_url($url);
    exit();
}

// ----------------------------------------------------------------------------
// Handle login and password reminder
// ----------------------------------------------------------------------------

// Set all our URLs.
phorum_build_common_urls();

$template = "login";
$error = "";
$okmsg = "";

// Handle posted form data.
if (count($_POST) > 0) {

    // The user wants to retrieve a new password.
    if (isset($_POST["lostpass"])) {

        // Trim the email address.
        $_POST["lostpass"] = trim($_POST["lostpass"]);

        $hook_args = NULL;

        // Did the user enter an email address?
        if (empty($_POST["lostpass"])) {
            $error = $PHORUM["DATA"]["LANG"]["LostPassError"];
        }

        // Is the email address available in the database?
        elseif ($uid = phorum_api_user_search("email", $_POST["lostpass"])) {

            // An existing user id was found for the entered email
            // address. Retrieve the user.
            $user = phorum_api_user_get($uid);

            $tmp_user=array();

            // User registration not yet approved by a moderator.
            if($user["active"] == PHORUM_USER_PENDING_MOD) {
                $template = "message";
                $okmsg = $PHORUM["DATA"]["LANG"]["RegVerifyMod"];

                $hook_args = array(
                    'status' => 'unapproved',
                    'email'  => $_POST['lostpass'],
                    'user'   => $user,
                    'secret' => NULL
                );

            // User registration still need email verification.
            } elseif ($user["active"] == PHORUM_USER_PENDING_EMAIL ||
                      $user["active"] == PHORUM_USER_PENDING_BOTH) {

                // Generate and store a new email confirmation code.
                $tmp_user["user_id"] = $uid;
                $tmp_user["password_temp"] = substr(md5(microtime()), 0, 8);
                phorum_api_user_save($tmp_user);

                // Mail the new confirmation code to the user.
                $verify_url = phorum_get_url(PHORUM_REGISTER_URL, "approve=".$tmp_user["password_temp"]."$uid");
                $maildata["mailsubject"] = $PHORUM["DATA"]["LANG"]["VerifyRegEmailSubject"];
                $maildata["mailmessage"] =
                   wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody1"],72).
                   "\n\n$verify_url\n\n".
                   wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody2"],72);
                phorum_email_user(array($user["email"]), $maildata);

                $okmsg = $PHORUM["DATA"]["LANG"]["RegVerifyEmail"];
                $template="message";

                $hook_args = array(
                    'status' => 'new_verification',
                    'email'  => $_POST['lostpass'],
                    'user'   => $user,
                    'secret' => $tmp_user['password_temp']
                );

            // The user is active.
            } else {

                // Generate and store a new password for the user.
                include_once( "./include/profile_functions.php" );
                $newpass = phorum_gen_password();
                $tmp_user["user_id"] = $uid;
                $tmp_user["password_temp"] = $newpass;
                phorum_api_user_save($tmp_user);

                // Mail the new password.
                $user = phorum_api_user_get($uid);
                $maildata = array();
                $maildata['mailmessage'] =
                   wordwrap($PHORUM["DATA"]["LANG"]["LostPassEmailBody1"],72).
                   "\n\n".
                   $PHORUM["DATA"]["LANG"]["Username"] .": $user[username]\n".
                   $PHORUM["DATA"]["LANG"]["Password"] .": $newpass".
                   "\n\n".
                   wordwrap($PHORUM["DATA"]["LANG"]["LostPassEmailBody2"],72);
                $maildata['mailsubject'] = $PHORUM["DATA"]["LANG"]["LostPassEmailSubject"];
                phorum_email_user(array( 0 => $user['email'] ), $maildata);

                $okmsg = $PHORUM["DATA"]["LANG"]["LostPassSent"];

                $hook_args = array(
                    'status' => 'new_password',
                    'email'  => $_POST['lostpass'],
                    'user'   => $user,
                    'secret' => $newpass
                );
            }
        }

        // The entered email address was not found.
        else {
            $error = $PHORUM["DATA"]["LANG"]["LostPassError"];

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
         *
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
            phorum_hook("password_reset", $hook_args);
        }
    }

    // The user wants to login.
    else {

        // Check if the phorum_tmp_cookie was set. If not, the user's
        // browser does not support cookies.
        if ($PHORUM["use_cookies"] == PHORUM_REQUIRE_COOKIES && !isset($_COOKIE["phorum_tmp_cookie"])) {

            $error = $PHORUM["DATA"]["LANG"]["RequireCookies"];

        } else {

            // See if the temporary cookie was found. If yes, then the
            // browser does support cookies. If not, then we disable
            // the use of cookies.
            if (!isset($_COOKIE["phorum_tmp_cookie"])) {
                $PHORUM["use_cookies"] = PHORUM_NO_COOKIES;
            }

            // Check if the login credentials are right.
            $user_id = phorum_api_user_authenticate(
                PHORUM_FORUM_SESSION,
                trim($_POST["username"]),
                trim($_POST["password"])
            );

            // They are. Setup the active user and start a Phorum session.
            if ($user_id)
            {
                // Make the authenticated user the active Phorum user
                // and start a Phorum user session. Because this is a fresh
                // login, we can enable the short term session and we request
                // refreshing of the session id(s).
                if (phorum_api_user_set_active_user(PHORUM_FORUM_SESSION, $user_id, PHORUM_FLAG_SESSION_ST) && phorum_api_user_session_create(PHORUM_FORUM_SESSION, PHORUM_SESSID_RESET_LOGIN)) {

                    // Destroy the temporary cookie that is used for testing
                    // for cookie compatibility.
                    if (isset($_COOKIE["phorum_tmp_cookie"])) {
                        setcookie(
                            "phorum_tmp_cookie", "", 0,
                            $PHORUM["session_path"], $PHORUM["session_domain"]
                        );
                    }

                    // Determine the URL to redirect the user to.
                    // If redir is a number, it is a URL constant.
                    if(is_numeric($_POST["redir"])){
                        $redir = phorum_get_url((int)$_POST["redir"]);
                    }
                    // Redirecting to the registration or login page is a
                    // little weird, so we just go to the list page if we came
                    // from one of those.
                    elseif (isset($PHORUM['use_cookies']) && $PHORUM["use_cookies"] && !strstr($_POST["redir"], "register." . PHORUM_FILE_EXTENSION) && !strstr($_POST["redir"], "login." . PHORUM_FILE_EXTENSION)) {
                        $redir = $_POST["redir"];
                    // By default, we redirect to the list page.
                    } else {
                        $redir = phorum_get_url( PHORUM_LIST_URL );
                    }
                    
                    // checking for redirection url on the same domain, 
                    // localhost or domain defined through the settings
                    $redir_ok = false;
                    $check_urls = array();
                    if(!empty($PHORUM['login_redir_urls'])) {
                        
                        $check_urls = explode(",",$PHORUM['login_redir_urls']);
                    }
                    $check_urls[]="http://localhost";
                    $check_urls[]=$PHORUM['http_path'];
                                        
                    foreach($check_urls as $check_url) {
                         // the redir-url has to start with one of these URLs
                         if(stripos($redir,$check_url) === 0) {
                                $redir_ok = true;
                                break;
                         }
                    }
                    if(!$redir_ok) {
                        $redir = phorum_get_url( PHORUM_LIST_URL );
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
                     *         $url = $PHORUM["user"]["phorum_mod_foo_user_login_url"];
                     *
                     *         return $url;
                     *     }
                     *     </hookcode>
                     */
                    if (isset($PHORUM["hooks"]["after_login"]))
                        $redir = phorum_hook("after_login", $redir);

                    phorum_redirect_by_url($redir);
                    exit();
                }
            }

            // Login failed or session startup failed. For both we show
            // the invalid login error.
            $error = $PHORUM["DATA"]["LANG"]["InvalidLogin"];

            // TODO API: move to user API.
            /*
             * [hook]
             *     failed_login
             *
             * [description]
             *     This hook can be used for tracking failing login attempts.
             *     This can be used for things like logging or implementing
             *     login failure penalties (like temporary denying access after
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
             *         // Check for a previous login failure from the current
             *         // IP address
             *         if (!empty($PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]])) {
             *             // If the failures occur within the set time window,
             *             // increment the login failure count
             *             if ($curr_time <= ($PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"] + (int)$PHORUM["mod_foo"]["login_failures_time_window"])) {
             *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["login_failure_count"] ++;
             *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"] = $curr_time;
             *             // Otherwise, reset the count.
             *             } else {
             *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["login_failure_count"] = 1;
             *                 $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"] = $curr_time;
             *         } else {
             *             // Log the timestamp and IP address of a login failure
             *             $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["login_failure_count"] = 1;
             *             $PHORUM["mod_foo"]["login_failures"][$_SERVER["REMOTE_ADDR"]]["timestamp"] = $curr_time;
             *         }
             *         phorum_db_update_settings(array("mod_foo" => $PHORUM["mod_foo"]));
             *
             *         return $data;
             *     }
             *     </hookcode>
             */
            if (isset($PHORUM["hooks"]["failed_login"]))
                phorum_hook("failed_login", array(
                    "username" => $_POST["username"],
                    "password" => $_POST["password"],
                    "location" => "forum"
                ));
        }
    }
}

// No data posted, so this is the first request. Here we set a temporary
// cookie, so we can check if the user's browser supports cookies.
elseif($PHORUM["use_cookies"] > PHORUM_NO_COOKIES) {
    setcookie( "phorum_tmp_cookie", "this will be destroyed once logged in", 0, $PHORUM["session_path"], $PHORUM["session_domain"] );
}

// Determine to what URL the user must be redirected after login.
if (!empty( $PHORUM["args"]["redir"])) {
    $redir = htmlspecialchars(urldecode($PHORUM["args"]["redir"]), ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
} elseif (!empty( $_REQUEST["redir"])) {
    $redir = htmlspecialchars($_REQUEST["redir"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
} elseif (!empty( $_SERVER["HTTP_REFERER"])) {
    $base = strtolower(phorum_get_url(PHORUM_BASE_URL));
    $len = strlen($base);
    if (strtolower(substr($_SERVER["HTTP_REFERER"],0,$len)) == $base) {
        $redir = htmlspecialchars($_SERVER["HTTP_REFERER"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    }
}
if (! isset($redir)) {
    $redir = phorum_get_url(PHORUM_LIST_URL);
}

// fill the breadcrumbs-info.
$PHORUM['DATA']['BREADCRUMBS'][]=array(
    'URL'=>'',
    'TEXT'=>$PHORUM['DATA']['LANG']['LogIn'],
    'TYPE'=>'login'
);

// fill the page heading info.
$PHORUM['DATA']['HEADING'] = $PHORUM['DATA']['LANG']['LogIn'];
$PHORUM['DATA']['HTML_DESCRIPTION'] = '';
$PHORUM['DATA']['DESCRIPTION'] = '';

// Setup template data.
$PHORUM["DATA"]["LOGIN"]["redir"] = $redir;
$PHORUM["DATA"]["URL"]["REGISTER"] = phorum_get_url( PHORUM_REGISTER_URL );
$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url( PHORUM_LOGIN_ACTION_URL );
$PHORUM["DATA"]["LOGIN"]["forum_id"] = ( int )$PHORUM["forum_id"];
$PHORUM["DATA"]["LOGIN"]["username"] = (!empty($_POST["username"])) ? htmlspecialchars( $_POST["username"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"] ) : "";
$PHORUM["DATA"]["ERROR"] = $error;
$PHORUM["DATA"]["OKMSG"] = $okmsg;


$PHORUM["DATA"]['POST_VARS'].="<input type=\"hidden\" name=\"redir\" value=\"{$redir}\" />\n";

// Set the field to set the focus to after loading.
$PHORUM["DATA"]["FOCUS_TO_ID"] = empty($_POST["username"]) ? "username" : "password";

// Display the page.
phorum_output($template);

?>
