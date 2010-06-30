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

if(!defined("PHORUM")) return;

// For phorum_update_thread_info().
include_once("./include/thread_info.php");

include_once("./include/diff_patch.php");

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

// Create a message which can be used by the database library.
$dbmessage = array(
    "message_id"    => $message["message_id"],
    "thread"        => $message["thread"],
    "parent_id"     => $message["parent_id"],
    "forum_id"      => $message["forum_id"],
    "author"        => $message["author"],
    "subject"       => $message["subject"],
    "email"         => $message["email"],
    "status"        => $message["status"],
    "closed"        => ($message["allow_reply"])?0:1,
    "body"          => $message["body"],
    "meta"          => $message["meta"],
);

// Update sort setting, if allowed. This can only be done
// when editing the thread starter message.
if ( $message["parent_id"]==0 ) {

    if ($PHORUM["DATA"]["OPTION_ALLOWED"]["sticky"] && $message["special"]=="sticky") {
        $dbmessage["sort"] = PHORUM_SORT_STICKY;
    } else {
        // Not allowed to edit. Keep existing sort value.
        switch ($message["special"]) {
            case "sticky": $sort = PHORUM_SORT_STICKY; break;
            default: $sort = PHORUM_SORT_DEFAULT; break;
        }
        $dbmessage["sort"] = $sort;
    }
    
    // has the sorting order been changed?
    if($dbmessage['sort'] !== $origmessage['sort']) {
    	// too much to calculate here to avoid the full refresh
    	phorum_db_update_forum_stats(true);
    }

} else {

    // set some key fields to the same values as the first message in the thread
    $dbmessage["forum_id"] = $top_parent["forum_id"];
    $dbmessage["sort"] = $top_parent["sort"];

}

// Update the editing info in the meta data.
$dbmessage["meta"]["show_signature"] = $message["show_signature"];

// we are doing the diffs here to know about changes for edit-counts
// $origmessage loaded in check_permissions
$diff_body    = phorum_diff( $origmessage["body"], $message["body"]);
$diff_subject = phorum_diff($origmessage["subject"], $message["subject"]);


if(!empty($diff_body) || !empty($diff_subject))
{
    $name = phorum_api_user_get_display_name($PHORUM["user"]["user_id"], NULL, PHORUM_FLAG_PLAINTEXT);

    $dbmessage["meta"]["edit_count"] = isset($message["meta"]["edit_count"])
                                     ? $message["meta"]["edit_count"]+1 : 1;
    $dbmessage["meta"]["edit_date"] = time();
    $dbmessage["meta"]["edit_username"] = $name;
    $dbmessage["meta"]["edit_user_id"] = $PHORUM["user"]["user_id"];

    // perform diff if edit tracking is enabled
    if(!empty($PHORUM["track_edits"])){

        $edit_data = array(
            "diff_body" => $diff_body,
            "diff_subject" => $diff_subject,
            "time" => $dbmessage["meta"]["edit_date"],
            "user_id" => $PHORUM["user"]["user_id"],
            "message_id" => $dbmessage['message_id'],
        );

        phorum_db_add_message_edit($edit_data);

    }

}


// Update attachments in the meta data, link active attachments
// to the message and delete stale attachments.
$dbmessage["meta"]["attachments"] = array();
foreach ($message["attachments"] as $info)
{
    if ($info["keep"])
    {
        // Because there might be inconsistencies in the list due to going
        // backward in the browser after deleting attachments, a check is
        // needed to see if the attachments are really in the database.
        if (! phorum_api_file_exists($info["file_id"])) continue;

        $dbmessage["meta"]["attachments"][] = array(
            "file_id" => $info["file_id"],
            "name"    => $info["name"],
            "size"    => $info["size"],
        );

        phorum_db_file_link(
            $info["file_id"],
            $message["message_id"],
            PHORUM_LINK_MESSAGE
        );
    } else {
        if (phorum_api_file_check_delete_access($info["file_id"])) {
            phorum_api_file_delete($info["file_id"]);
        }
    }
}
if (!count($dbmessage["meta"]["attachments"])) {
    unset($dbmessage["meta"]["attachments"]);
}

/*
 * [hook]
 *     before_edit
 *
 * [description]
 *     This hook can be used to change the edited message before it is stored in
 *     the database.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In <filename>include/posting/action_edit.php</filename>, right before
 *     storing an edited message in the database.
 *
 * [input]
 *     An array containing message data and an optional parameter which holds the
 *     original message data (added in Phorum 5.2.15)
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_before_edit($dbmessage,$orig_message)
 *     {
 *         global $PHORUM;
 *
 *         // If the message body does not contain the disclaimer, add it
 *         if (strpos($dbmessage["body"], $PHORUM["DATA"]["LANG"]["mod_foo"]["Disclaimer"]) === false) {
 *             $dbmessage["body"] .= "\n".$PHORUM["DATA"]["LANG"]["mod_foo"]["Disclaimer"];
 *         }
 *
 *         return $dbmessage;
 *     }
 *     </hookcode>
 */
// Update the data in the database and run pre and post editing hooks.
if (isset($PHORUM["hooks"]["before_edit"]))
    $dbmessage = phorum_hook("before_edit", $dbmessage, $origmessage);
phorum_db_update_message($message["message_id"], $dbmessage);
/*
 * [hook]
 *     after_edit
 *
 * [description]
 *     This hook can be used for sending notifications or for making log entries
 *     in the database when editing takes place.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In <filename>include/posting/action_edit.php</filename>, right after
 *     storing an edited message in the database.
 *
 * [input]
 *     An array containing message data (read-only)  and an optional parameter 
 *     which holds the original message data (added in Phorum 5.2.15)
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_after_edit($dbmessage, $orig_message)
 *     {
 *         global $PHORUM;
 *
 *         // If the message editor is not the same as the message author, alert
 *         // the message author that their message has been edited
 *         if ($PHORUM["user"]["user_id"] != $dbmessage["user_id"]) {
 *             $pm_message = preg_replace(
 *                 "/%message_subject%/",
 *                 $dbmessage["subject"],
 *                 $PHORUM["DATA"]["LANG"]["mod_foo"]["MessageEditedBody"]
 *                 );
 *             phorum_db_pm_send(
 *                 $PHORUM["DATA"]["LANG"]["mod_foo"]["MessageEditedSubject"],
 *                 $pm_message,
 *                 $dbmessage["user_id"]
 *                 );
 *         }
 *
 *         return $dbmessage
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["after_edit"]))
    phorum_hook("after_edit", $dbmessage,$origmessage);

// remove the message from the cache if caching is enabled
// no need to clear the thread-index as the message has only been changed
if($PHORUM['cache_messages']) {
    phorum_cache_remove('message',$message["message_id"]);
    phorum_db_update_forum(array('forum_id'=>$PHORUM['forum_id'],'cache_version'=>($PHORUM['cache_version']+1)));
}

// Update children to the same sort setting.
if (! $message["parent_id"] &&
    $origmessage["sort"] != $dbmessage["sort"])
{
    $messages = phorum_db_get_messages($message["thread"], 0);
    unset($messages["users"]);
    foreach($messages as $message_id => $msg){
        if($msg["sort"]!=$dbmessage["sort"] ||
           $msg["forum_id"] != $dbmessage["forum_id"]) {
            $msg["sort"]=$dbmessage["sort"];
            phorum_db_update_message($message_id, $msg);
            if($PHORUM['cache_messages']) {
                phorum_cache_remove('message',$message_id);
            }
        }
    }
}

// Update all thread messages to the same closed setting.
if (! $message["parent_id"] &&
    $origmessage["closed"] != $dbmessage["closed"]) {
    if ($dbmessage["closed"]) {
        phorum_db_close_thread($message["thread"]);
    } else {
        phorum_db_reopen_thread($message["thread"]);
    }
}

// Update thread info.
phorum_update_thread_info($message['thread']);

// Update thread subscription.
if (isset($message["subscription"]))
{
    $subscribe_type = NULL;
    switch ($message["subscription"]) {
        case "bookmark":
            if ($PHORUM["DATA"]["OPTION_ALLOWED"]["subscribe"]) {
                $subscribe_type = PHORUM_SUBSCRIPTION_BOOKMARK;
            }
            break;
        case "message":
            if ($PHORUM["DATA"]["OPTION_ALLOWED"]["subscribe_mail"]) {
                $subscribe_type = PHORUM_SUBSCRIPTION_MESSAGE;
            }
            break;
        case "":
            if ($PHORUM["DATA"]["OPTION_ALLOWED"]["subscribe_mail"]) {
                $subscribe_type = PHORUM_SUBSCRIPTION_NONE;
            }
            break;
        default:
            trigger_error(
                "Illegal message subscription type: " .
                htmlspecialchars($message["subscription"])
            );
            break;
    }

    if ($subscribe_type === NULL) {
        phorum_api_user_unsubscribe(
            $message["user_id"],
            $message["thread"]
        );
    } else {
        phorum_api_user_subscribe(
            $message["user_id"],
            $message["thread"],
            $PHORUM["forum_id"],
            $subscribe_type
        );
    }
}

$PHORUM["posting_template"] = "message";
$PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["MsgModEdited"];
$PHORUM['DATA']["BACKMSG"] = $PHORUM['DATA']["LANG"]["BackToThread"];
$PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_get_url(
    PHORUM_READ_URL,
    $message["thread"],
    $message["message_id"]
);

/*
 * [hook]
 *     posting_action_edit_post
 *
 * [description]
 *     Allow modules to perform custom action whenever the user edits his post.
 *     This can be used to e.g. redirect the user immediately back to the edited
 *     post where he came from.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In <filename>action_edit.php</filename> at the end of the file when 
 *     everything has been done.
 *
 * [input]
 *     Array containing message data.
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_posting_action_edit_post ($message)
 *     {
 *         global $PHORUM;
 *
 *         // perform a custom redirect
 *         phorum_redirect_by_url($PHORUM["DATA"]["URL"]["REDIRECT"]);
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["posting_action_edit_post"]))
    phorum_hook("posting_action_edit_post", $message);
?>
