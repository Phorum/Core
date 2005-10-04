<?php
// //////////////////////////////////////////////////////////////////////////////
// //
// Copyright (C) 2003  Phorum Development Team                              //
// http://www.phorum.org                                                    //
// //
// This program is free software. You can redistribute it and/or modify     //
// it under the terms of either the current Phorum License (viewable at     //
// phorum.org) or the Phorum License that was distributed with this file    //
// //
// This program is distributed in the hope that it will be useful,          //
// but WITHOUT ANY WARRANTY, without even the implied warranty of           //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
// //
// You should have received a copy of the Phorum License                    //
// along with this program.                                                 //
// //////////////////////////////////////////////////////////////////////////////
define('phorum_page', 'post');

include_once("./common.php");
include_once("./include/email_functions.php");
include_once("./include/thread_info.php");
include_once("./include/format_functions.php");
include_once("./include/profile_functions.php");

if(empty($PHORUM["forum_id"])){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL);
    phorum_redirect_by_url($dest_url);
    exit();
}

// somehow we got to a folder in list.php
if($PHORUM["folder_flag"]){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
    phorum_redirect_by_url($dest_url);
    exit();
}

// set all our URL's
phorum_build_common_urls();

if (count($_POST) > 0) {
    // Trim the e-mail address.
    if (isset($_POST["email"])) {
        $_POST["email"] = trim($_POST["email"]);
    }

    // Trim and space-collapse the author name, so people can't
    // impersonate as other users using the same author name,
    // but with extra spaces in it.
    if (isset($_POST["author"])) {
        $_POST["author"] = preg_replace('/\s+/', ' ', trim($_POST["author"]));
    }

    // check that this user can post to the forum
    if ((empty($_POST["parent_id"]) && phorum_user_access_allowed(PHORUM_USER_ALLOW_NEW_TOPIC)) ||
            (!empty($_POST["parent_id"]) && phorum_user_access_allowed(PHORUM_USER_ALLOW_REPLY))) {
        // lets try and post
        // loading the banlists
        $PHORUM['banlists'] = phorum_db_get_banlists();

        $success = false;
        $error = "";
        // check a bunch of stuff
        if ($PHORUM["DATA"]["LOGGEDIN"]) { // checks for registered
            if (!phorum_check_ban_lists($PHORUM["user"]["username"], PHORUM_BAD_NAMES)) {
                $error = $PHORUM["DATA"]["LANG"]["ErrBannedName"];
            } elseif (!phorum_check_ban_lists($PHORUM["user"]["email"], PHORUM_BAD_EMAILS)) {
                $error = $PHORUM["DATA"]["LANG"]["ErrBannedEmail"];
            }
        } else { // checks for unregistered
            if (empty($_POST["author"])) {
                $error = $PHORUM["DATA"]["LANG"]["ErrAuthor"];
            } elseif (phorum_user_check_username($_POST["author"])) {
                $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdName"];
            } elseif (!empty($_POST["email"]) && phorum_user_check_email($_POST["email"])) {
                $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdEmail"];
            } elseif (!phorum_check_ban_lists($_POST["author"], PHORUM_BAD_NAMES)) {
                $error = $PHORUM["DATA"]["LANG"]["ErrBannedName"];
            } elseif (!phorum_check_ban_lists($_POST["email"], PHORUM_BAD_EMAILS)) {
                $error = $PHORUM["DATA"]["LANG"]["ErrBannedEmail"];
            }
        }

        if (empty($error)) {

            // do this here so we can call check field against it.
            if ($PHORUM["dns_lookup"]) {
                $REMOTE_ADDR = @gethostbyaddr($_SERVER["REMOTE_ADDR"]);
            } else {
                $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
            }

            // common checks for both
            if (empty($_POST["subject"])) {
                $error = $PHORUM["DATA"]["LANG"]["ErrSubject"];
            } elseif (empty($_POST["body"])) {
                $error = $PHORUM["DATA"]["LANG"]["ErrBody"];
            } elseif (!phorum_check_ban_lists($REMOTE_ADDR, PHORUM_BAD_IPS)) {
                $error = $PHORUM["DATA"]["LANG"]["ErrBannedIP"];
            } elseif (!empty($_POST["email"]) && !phorum_valid_email($_POST["email"])) {
                $error = $PHORUM["DATA"]["LANG"]["ErrEmail"];
            } elseif (!is_numeric($_POST['parent_id']) || !is_numeric($_POST['thread'])) {
                // something is wrong, maybe an abuse-trial?
                $error = $PHORUM["DATA"]["LANG"]["ErrInvalid"];
            } elseif (strlen($_POST['body']) > 64000) {
                $error = $PHORUM['DATA']['LANG']['ErrBodyTooLarge'];
            } else {
                $message = $_POST;
                // set some ints
                settype($message["thread"], "int");
                settype($message["parent_id"], "int");
                settype($message["email_reply"], "int");
                settype($message["show_signature"], "int");
                // SET DEFAULTS
                $message["forum_id"] = $PHORUM["forum_id"];
                $message["status"] = PHORUM_STATUS_APPROVED;
                $message["sort"] = PHORUM_SORT_DEFAULT;
                $message["closed"] = 0;

                if (empty($message["email_reply"])) $message["email_reply"] = 0;

                $message["ip"] = $REMOTE_ADDR;

                $message["user_id"] = $PHORUM["user"]["user_id"];
                $message["moderator_post"] = (int)phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

                if ($PHORUM["DATA"]["LOGGEDIN"]) {
                    $message["author"] = $PHORUM["user"]["username"];
                    $message["email"] = "";

                    if (isset($message['show_signature']) && $message['show_signature'] == 1)
                        $message['meta']['show_signature'] = 1;
                }

                if ($PHORUM["max_attachments"] > 0 && isset($_POST["attach"])) {
                    $message["status"] = PHORUM_STATUS_ATTACHING;
                }
                elseif ($PHORUM["moderation"] == PHORUM_MODERATE_ON && !phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
                    $message["status"] = PHORUM_STATUS_HOLD;
                }
                if (isset($_POST["special"])) {
                    if (empty($_POST["parent_id"]) && $_POST["special"] == "sticky" && phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
                        $message["sort"] = PHORUM_SORT_STICKY;
                    } elseif (empty($_POST["parent_id"]) && $_POST["special"] == "announcement" && $PHORUM["user"]["admin"]) {
                        $message["sort"] = PHORUM_SORT_ANNOUNCEMENT;
                        $message["closed"] = 1;
                        $message["forum_id"] = 0;
                    }
                }

                $message["msgid"] = md5(uniqid(rand())) . "." . preg_replace("/[^a-z0-9]/i", "", $PHORUM["name"]);

                // run pre post mods
                $message = phorum_hook("pre_post", $message);

                // we have to get the parents of the message
                // to check the closed status and sort.
                if ($message["thread"] != 0) {
                    $top_parent = phorum_db_get_message($message["thread"]);
                    if ($message["thread"] != $message["parent_id"]) {
                        $parent = phorum_db_get_message($message["parent_id"]);
                    } else {
                        $parent = $top_parent;
                    }

                    // this thread is not approved, get out.
                    if (empty($top_parent) || empty($parent) || $top_parent["closed"] || $top_parent["status"] != PHORUM_STATUS_APPROVED || $parent["closed"]) {
                        phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
                        exit();
                    }

                    // this is a sticky thread, set this sort also so threaded view works.
                    if ($top_parent["sort"] == PHORUM_SORT_STICKY) {
                        $message["sort"] = PHORUM_SORT_STICKY;
                    }
                }

                if (empty($_POST["preview"])) {
                    $success = phorum_db_post_message($message);


                    if ($success) {
                        // retrieving the message again to have it in the correct format
                        // (otherwise its a bit messed up in the post-function)
                        $email_reply=$message['email_reply'];
                        $message=phorum_db_get_message($message["message_id"]);

                        phorum_update_thread_info($message["thread"]);
                        // subscribe the user to the thread if requested and is registered.
                        if ($email_reply && $message["user_id"]) {
                            phorum_user_subscribe($message["user_id"], $PHORUM["forum_id"], $message["thread"], PHORUM_SUBSCRIPTION_MESSAGE);
                        }

                        if ($PHORUM["DATA"]["LOGGEDIN"]) { // setting the own message read
                            phorum_db_newflag_add_read(array(0=>array("id"=>$message["message_id"],"forum"=>$message["forum_id"])));
                            // rising message-counter for the user
                            phorum_user_addpost();
                        }

                        if ($message["status"] > 0) {
                            phorum_db_update_forum_stats(false, 1, $message["datestamp"]);
                            // mailing subscribed users
                            phorum_email_notice($message);
                        }

                        if ($PHORUM["email_moderators"] == PHORUM_EMAIL_MODERATOR_ON) {
                            // mailing moderators
                            phorum_email_moderators($message);
                        }
                    }
                }
            }
        }

        if ($success && empty($error)) {
            // run post post mods
            $message = phorum_hook("post_post", $message);

            if ($PHORUM["max_attachments"] > 0 && isset($_POST["attach"])) {
                $redir_url = phorum_get_url(PHORUM_ATTACH_URL, $message["message_id"]);
            } else {

                if($PHORUM["redirect_after_post"]=="read"){

                    if(isset($top_parent)) { // not set for top-posts
                        $pages=ceil(($top_parent["thread_count"]+1)/$PHORUM["read_length"]);
                    } else {
                        $pages=1;
                    }

                    if($pages>1){
                        $redir_url = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message["message_id"], "page=$pages");
                    } else {
                        $redir_url = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message["message_id"]);
                    }

                } else {

                    $redir_url = phorum_get_url(PHORUM_LIST_URL);

                }
            }

            phorum_redirect_by_url($redir_url);

            exit();
        } else {
            // need to set up for the template
            if (empty($error) && empty($_POST["preview"])) $error = $PHORUM["DATA"]["LANG"]['PostErrorOccured'];

            if (!$PHORUM["DATA"]["LOGGEDIN"]) {
                $PHORUM["DATA"]["POST"]["author"] = htmlspecialchars($_POST["author"]);
                $PHORUM["DATA"]["POST"]["email"] = htmlspecialchars($_POST["email"]);
            }

            $PHORUM["DATA"]["POST"]["subject"] = htmlspecialchars($_POST["subject"]);
            $PHORUM["DATA"]["POST"]["body"] = htmlspecialchars($_POST["body"]);
            $PHORUM["DATA"]["POST"]["thread"] = (int)$_POST["thread"];
            $PHORUM["DATA"]["POST"]["parentid"] = (int)$_POST["parent_id"];
            $PHORUM['DATA']['POST']['email_reply'] = (isset($_POST['email_reply']) && $_POST['email_reply'])?"1":"0";
            $PHORUM['DATA']['POST']['show_signature'] = (isset($_POST['show_signature']) && $_POST['show_signature'])?"1":"0";
            $PHORUM["DATA"]["POST"]["special"] = (isset($_POST["special"])) ? htmlspecialchars($_POST["special"]) : "0";
            $PHORUM["DATA"]["ERROR"] = htmlspecialchars($error);
        }
    }
}

include phorum_get_template("header");
phorum_hook("after_header");

if (!empty($_POST["preview"]) && empty($error)) {

    if($PHORUM["DATA"]["LOGGEDIN"]){ // doing some stuff only if he is logged in
        // hook to modify user info
        $user_info = phorum_hook("read_user_info", array($PHORUM["user"]["user_id"]=>$PHORUM["user"]));
        $preview_user=array_shift($user_info);

        if (isset($preview_user["signature"]) && isset($message['meta']['show_signature']) && $message['meta']['show_signature'] == 1) {
            $phorum_sig = trim($preview_user["signature"]);
            if (!empty($phorum_sig)) {
                $message["body"] .= "\n\n$phorum_sig";
            }
        }
    }

    // mask host if not a moderator or admin
    if(empty($PHORUM["user"]["admin"]) && (empty($PHORUM["DATA"]["MODERATOR"]) || !PHORUM_MOD_IP_VIEW)){
        if($PHORUM["display_ip_address"]){
            if($message["moderator_post"]){
                $message["ip"]=$PHORUM["DATA"]["LANG"]["Moderator"];
            } elseif(is_numeric(str_replace(".", "", $message["ip"]))){
                $message["ip"]=substr($message["ip"],0,strrpos($message["ip"],'.')).'.---';
            } else {
                $message["ip"]="---".strstr($message["ip"], ".");
            }

        } else {
            $message["ip"]=$PHORUM["DATA"]["LANG"]["IPLogged"];
        }
    }

    $message=phorum_hook("preview", $message);

    // format message
    $messages = phorum_format_messages(array($message));
    $message = array_shift($messages);

    $PHORUM["DATA"]["PREVIEW"] = $message;

    include phorum_get_template("preview");
}

include "./include/post_form.php";

phorum_hook("before_footer");
include phorum_get_template("footer");

?>
