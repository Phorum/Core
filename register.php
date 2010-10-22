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
define('phorum_page','register');

include_once("./common.php");
include_once("./include/profile_functions.php");
include_once("./include/email_functions.php");

// set all our URL's
phorum_build_common_urls();

// The URL contains an approve argument, which means that a new user
// is confirming a new user account.
if (isset($PHORUM["args"]["approve"])) {

    // Extract registration validation code and user_id.
    $tmp_pass=md5(substr($PHORUM["args"]["approve"], 0, 8));
    $user_id = (int)substr($PHORUM["args"]["approve"], 8);
    $user_id = phorum_api_user_search(
        array("user_id", "password_temp"),
        array($user_id,  $tmp_pass),
        array("=",       "=")
    );

    // Validation code correct.
    if ($user_id) {

        $user = phorum_api_user_get($user_id);

        $moduser=array();

        // The user has been denied by a moderator.
        if ($user["active"] == PHORUM_USER_INACTIVE) {
             $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegVerifyFailed"];
        // The user should still be approved by a moderator.
        } elseif ($user["active"] == PHORUM_USER_PENDING_MOD) {
            // TODO: this message should be changed in 5.1 to have a unique message!!!
            $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegVerifyMod"];
        // The user is waiting for email and/or email+moderator confirmation.
        } else {
            // Waiting for both? Then switch to wait for moderator.
            if ($user["active"] == PHORUM_USER_PENDING_BOTH) {
                $moduser["active"] = PHORUM_USER_PENDING_MOD;
                $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegVerifyMod"];
            // Only email confirmation was required. Active the user.
            } else {
                $moduser["active"] = PHORUM_USER_ACTIVE;
                $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegAcctActive"];
            }

            // Save the new user active status.
            $moduser["user_id"] = $user_id;
            phorum_api_user_save($moduser);
        }

    // Validation code incorrect.
    } else {
        $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegVerifyFailed"];
    }

    phorum_output("message");
    return;

}

$error = ''; // Init error as empty.

// Process posted form data.
if (count($_POST)) {

    // Sanitize input data.
    foreach ($_POST as $key => $val) {
        if ($key == 'username') {
            // Trim and space-collapse usernames, so people can't
            // impersonate as other users using the same username,
            // but with extra spaces in it.
            $_POST[$key] = preg_replace('/\s+/', ' ', trim($val));
        } else {
            $_POST[$key] = trim($val);
        }
    }

    // Check if all required fields are filled and valid.
    if (!isset($_POST["username"]) || empty($_POST['username'])) {
        $error = $PHORUM["DATA"]["LANG"]["ErrUsername"];
    } elseif (!isset($_POST["email"]) || !phorum_valid_email($_POST["email"])) {
        $error = $PHORUM["DATA"]["LANG"]["ErrEmail"];
    } elseif (empty($_POST["password"]) || $_POST["password"] != $_POST["password2"]) {
        $error = $PHORUM["DATA"]["LANG"]["ErrPassword"];
    }
    // Check if the username and email address don't already exist.
    elseif(phorum_api_user_search("username", $_POST["username"])) {
        $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdName"];
    } elseif (phorum_api_user_search("email", $_POST["email"])){
        $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdEmail"];
    }

    // Check banlists.
    if (empty($error)) {
        $error = phorum_check_bans(array(
            array($_POST["username"], PHORUM_BAD_NAMES),
            array($_POST["email"],    PHORUM_BAD_EMAILS),
            array(NULL,               PHORUM_BAD_IPS),
        ));
    }

    // Create user if no errors have been encountered.
    if (empty($error)) {

        // Setup the default userdata to store.
        $userdata = array(
            'username'   => NULL,
            'password'   => NULL,
            'email'      => NULL,
        );
        // Add custom profile fields as acceptable fields.
        foreach ($PHORUM["PROFILE_FIELDS"] as $id => $field) {
            if ($id === 'num_fields' || !empty($field['deleted'])) continue;
            $userdata[$field["name"]] = NULL;
        }
        // Update userdata with $_POST information.
        foreach ($_POST as $key => $val) {
           if (array_key_exists($key, $userdata)) {
               $userdata[$key] = $val;
           }
        }
        // Remove unused custom profile fields.
        foreach ($PHORUM["PROFILE_FIELDS"] as $id => $field) {
            if ($id === 'num_fields' || !empty($field['deleted'])) continue;
            if (is_null($userdata[$field["name"]])) {
                unset($userdata[$field["name"]]);
            }
        }
        // Add static info.
        $userdata["date_added"]=time();
        $userdata["date_last_active"]=time();
        $userdata["hide_email"]=true;

        // Set user active status depending on the registration verification
        // setting. Generate a confirmation code for email verification.
        if ($PHORUM["registration_control"] == PHORUM_REGISTER_INSTANT_ACCESS) {
            $userdata["active"] = PHORUM_USER_ACTIVE;
        } elseif ($PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_EMAIL) {
            $userdata["active"] = PHORUM_USER_PENDING_EMAIL;
            $userdata["password_temp"]=substr(md5(microtime()), 0, 8);
        } elseif ($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_MODERATOR) {
            $userdata["active"] = PHORUM_USER_PENDING_MOD;
        } elseif ($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_BOTH) {
            $userdata["password_temp"]=substr(md5(microtime()), 0, 8);
            $userdata["active"] = PHORUM_USER_PENDING_BOTH;
        }

        /*
         * [hook]
         *     before_register
         *
         * [description]
         *     This hook can be used for performing tasks before user 
         *     registration. This hook is useful if you want to add some data to
         *     or change some data in the user data and to check if the user 
         *     data is correct.<sbr/>
         *     <sbr/>
         *     When checking the registration data, the hook can set the "error"
         *     field in the returned user data array. When this field is set 
         *     after running the hook, the registration processed will be halted
         *     and the error will be displayed. If you created a custom form 
         *     field "foo" and you require that field to be filled in, you could
         *     create a hook function like the one in the example below.<sbr/>
         *     <sbr/>
         *     The error must be safely HTML escaped, so if you use untrusted
         *     data in your error, then make sure that it is escaped using
         *     <phpfunc>htmlspecialchars</phpfunc> to prevent XSS (see also
         *     paragraph 3.6: Secure your pages from XSS).
         *
         * [category]
         *     User data handling
         *
         * [when]
         *     In <filename>register.php</filename>, right before a new user is
         *     stored in the database.
         *
         * [input]
         *     An array containing the user data of the soon-to-be-registered
         *     user.
         *
         * [output]
         *     Same as input.
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_before_register ($data)
         *     {
         *         $myfield = trim($data['foo']);
         *         if (empty($myfield)) {
         *             $data['error'] = 'You need to fill in the foo field';
         *         }
         *
         *         return $data;
         *     }
         *     </hookcode>
         */
        if (isset($PHORUM["hooks"]["before_register"]))
            $userdata = phorum_hook("before_register", $userdata);

        // Set $error, in case the before_register hook did set an error.
        if (isset($userdata['error'])) {
            $error = $userdata['error'];
            unset($userdata['error']);
        }

        if (empty($error))
        {
            // Add the user to the database.
            $userdata["user_id"] = NULL;
            $user_id = phorum_api_user_save($userdata);

            if ($user_id)
            {
                // The user was added. Determine what message to show.
                if ($PHORUM["registration_control"] == PHORUM_REGISTER_INSTANT_ACCESS) {
                    $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegThanks"];
                } elseif($PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_EMAIL ||
                         $PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_BOTH) {
                    $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegVerifyEmail"];
                } elseif($PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_MODERATOR) {
                    $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["RegVerifyMod"];
                }

                // Send a message to the new user in case email verification is required.
                if ($PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_BOTH ||
                    $PHORUM["registration_control"] == PHORUM_REGISTER_VERIFY_EMAIL) {
                    $verify_url = phorum_get_url(PHORUM_REGISTER_URL, "approve=".$userdata["password_temp"]."$user_id");
                    // make the link an anchor tag for AOL users
                    if (preg_match("!aol\.com$!i", $userdata["email"])) {
                        $verify_url = "<a href=\"$verify_url\">$verify_url</a>";
                    }
                    $maildata = array();
                    $maildata["mailsubject"] = $PHORUM["DATA"]["LANG"]["VerifyRegEmailSubject"];
                    $maildata["mailmessage"] = wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody1"], 72)."\n\n$verify_url\n\n".wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody2"], 72);
                    phorum_email_user(array($userdata["email"]), $maildata);
                }

                $PHORUM["DATA"]["BACKMSG"] = $PHORUM["DATA"]["LANG"]["RegBack"];
                $PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_get_url(PHORUM_LOGIN_URL);

                /*
                 * [hook]
                 *     after_register
                 *
                 * [description]
                 *     This hook can be used for performing tasks (like logging
                 *     and notification) after a successful user registration.
                 *
                 * [category]
                 *     User data handling
                 *
                 * [when]
                 *     In <filename>register.php</filename>, right after a
                 *     successful registration of a new user is done and all
                 *     confirmation mails are sent.
                 *
                 * [input]
                 *     An array containing the user data of the newly registered
                 *     user (read-only).
                 *
                 * [output]
                 *     Same as input.
                 *
                 * [example]
                 *     <hookcode>
                 *     function phorum_mod_foo_after_register($data)
                 *     {
                 *         global $PHORUM;
                 *
                 *         // Keep a log of user registrations by user id with
                 *         // the IP address of the computer they used to
                 *         // register
                 *         $PHORUM["mod_foo"]["user_registrations"][$userdata["user_id"]] = $_SERVER["REMOTE_ADDR"];
                 *
                 *         phorum_db_update_settings(array("mod_foo" => $PHORUM["mod_foo"]));
                 *
                 *         return $data;
                 *     }
                 *     </hookcode>
                 */
                if (isset($PHORUM["hooks"]["after_register"])) {
                    $userdata["user_id"] = $user_id;
                    phorum_hook("after_register",$userdata);
                }

                phorum_output("message");
                return;

            // Adding the user to the database failed.
            } else {
                $error = $PHORUM["DATA"]["LANG"]["ErrUserAddUpdate"];
            }
        }
    }

    // Some error encountered during processing? Then setup the
    // data to redisplay the registration form, including an error.
    if (!empty($error)) {
        foreach($_POST as $key => $val){
            $PHORUM["DATA"]["REGISTER"][$key] = htmlspecialchars($val, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        }
        $PHORUM["DATA"]["ERROR"] = $error;
    }

// No data posted, so this is the first request. Initialize form data.
} else {
    // Initialize fixed fields.
    $PHORUM["DATA"]["REGISTER"]["username"] = "";
    $PHORUM["DATA"]["REGISTER"]["email"] = "";
    $PHORUM["DATA"]["ERROR"] = "";

    // Initialize custom profile fields.
    foreach($PHORUM["PROFILE_FIELDS"] as $id => $field) {
        if ($id === 'num_fields' || !empty($field['deleted'])) continue;
        $PHORUM["DATA"]["REGISTER"][$field["name"]] = "";
    }
}

// fill the breadcrumbs-info.
$PHORUM['DATA']['BREADCRUMBS'][]=array(
    'URL'=>'',
    'TEXT'=>$PHORUM['DATA']['LANG']['Register'],
    'TYPE'=>'register'
);

// fill the page heading info.
$PHORUM['DATA']['HEADING'] = $PHORUM['DATA']['LANG']['Register'];
$PHORUM['DATA']['HTML_DESCRIPTION'] = '';
$PHORUM['DATA']['DESCRIPTION'] = '';

# Setup static template data.
$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url( PHORUM_REGISTER_ACTION_URL );
$PHORUM["DATA"]["REGISTER"]["forum_id"] = $PHORUM["forum_id"];
$PHORUM["DATA"]["REGISTER"]["block_title"] = $PHORUM["DATA"]["LANG"]["Register"];

// Display the registration page.
phorum_output("register");

?>
