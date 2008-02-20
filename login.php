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
////////////////////////////////////////////////////////////////////////////////
define('phorum_page','login');

include_once( "./common.php" );
include_once( "./include/email_functions.php" );

// ----------------------------------------------------------------------------
// Handle logout
// ----------------------------------------------------------------------------

if ($PHORUM['DATA']['LOGGEDIN'] && !empty($PHORUM["args"]["logout"])) {

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
        $url = str_replace(PHORUM_SESSION_LONG_TERM."=".urlencode($PHORUM["args"][PHORUM_SESSION_LONG_TERM]), "", $url);
    }

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

            }
        }

        // The entered email address was not found.
        else {
            $error = $PHORUM["DATA"]["LANG"]["LostPassError"];
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

                    // The hook "after_login" can be used by module writers to
                    // set a custom redirect URL.
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
