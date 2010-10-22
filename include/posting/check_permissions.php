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

// Check if the user is allowed to post a new message or a reply.
if( ($mode == "post" && !phorum_api_user_check_access(PHORUM_USER_ALLOW_NEW_TOPIC)) ||
    ($mode == "reply" && !phorum_api_user_check_access(PHORUM_USER_ALLOW_REPLY)) ) {
    if ($PHORUM["DATA"]["LOGGEDIN"]) {
        // If users are logged in and can't post, they don't have rights to do so.
        $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["NoPost"];
    } else {
        // Check if they could post if logged in. If so, let them know to log in.
        if( ($mode == "reply" && $PHORUM["reg_perms"] & PHORUM_USER_ALLOW_REPLY) ||
            ($mode == "post" && $PHORUM["reg_perms"] & PHORUM_USER_ALLOW_NEW_TOPIC) ) {
            $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["PleaseLoginPost"];
            $PHORUM["DATA"]["CLICKHEREMSG"] = $PHORUM["DATA"]["LANG"]["ClickHereToLogin"];
            $PHORUM["DATA"]["URL"]["CLICKHERE"] = phorum_get_url(PHORUM_LOGIN_URL);
        } else {
            $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["NoPost"];
        }
    }
    $PHORUM["posting_template"] = "message";
    return;

// Check that they are logged in according to the security settings in
// the admin. If they aren't then either set a message with a login link
// (when running as include) or redirect to the login page.
} elseif($PHORUM["DATA"]["LOGGEDIN"] && !$PHORUM["DATA"]["FULLY_LOGGEDIN"]){

    if (isset($PHORUM["postingargs"]["as_include"])) {

        // Generate the URL to return to after logging in.
        $args = array(PHORUM_REPLY_URL, $PHORUM["args"][1]);
        if (isset($PHORUM["args"][2])) $args[] = $PHORUM["args"][2];
        if (isset($PHORUM["args"]["quote"])) $args[] = "quote=1";
        $redir = urlencode(call_user_func_array('phorum_get_url', $args));
        $url = phorum_get_url(PHORUM_LOGIN_URL, "redir=$redir");

        $PHORUM["DATA"]["URL"]["CLICKHERE"] = $url;
        $PHORUM["DATA"]["CLICKHEREMSG"] = $PHORUM["DATA"]["LANG"]["ClickHereToLogin"];
        $PHORUM["DATA"]["OKMSG"] = '<a name="REPLY"></a>' .
                                   $PHORUM["DATA"]["LANG"]["NoPost"] . ' ' .
                                   $PHORUM["DATA"]["LANG"]["PeriodicLogin"];
        $PHORUM["posting_template"] = "message";
        return;

    } else {

        // Generate the URL to return to after logging in.
        $args = array(PHORUM_POSTING_URL);
        if (isset($PHORUM["args"][1])) $args[] = $PHORUM["args"][1];
        if (isset($PHORUM["args"][2])) $args[] = $PHORUM["args"][2];
        if (isset($PHORUM["args"]["quote"])) $args[] = "quote=1";
        $redir = urlencode(call_user_func_array('phorum_get_url', $args));

        phorum_redirect_by_url(phorum_get_url(PHORUM_LOGIN_URL,"redir=$redir"));
        exit();

    }
}

// Put read-only user info in the message.
if ($mode == "post" || $mode == "reply")
{
    if ($PHORUM["DATA"]["LOGGEDIN"]){
        $message["user_id"] = $PHORUM["user"]["user_id"];
        // If the author field is read only or not filled, then
        // use the user's display name as the author.
        if ($PHORUM["post_fields"]["author"][pf_READONLY] ||
            $message["author"] == '') {
            $message["author"]  = $PHORUM["user"]["display_name"];
        }
    } else {
        $message["user_id"] = 0;
    }
}

// On finishing up, find the original message data in case we're
// editing or replying. Put read-only data in the message to prevent
// data tampering.
if ($finish && ($mode == 'edit' || $mode == 'reply'))
{
    $id = $mode == "edit" ? "message_id" : "parent_id";
    $origmessage = phorum_db_get_message($message[$id]);
    if (! $origmessage) {
        phorum_redirect_by_url(phorum_get_url(PHORUM_INDEX_URL));
        exit();
    }

    // Copy read-only information for editing messages.
    if ($mode == "edit") {
        $message = phorum_posting_merge_db2form($message, $origmessage, READONLYFIELDS);
    // Copy read-only information for replying to messages.
    } else {
        $message["parent_id"] = $origmessage["message_id"];
        $message["thread"] = $origmessage["thread"];
    }
}

// We never store the email address in the message in case it
// was posted by a registered user.
if ($message["user_id"]) {
    $message["email"] = "";
}

// Find the startmessage for the thread.
if ($mode == "reply" || $mode == "edit") {
    $top_parent = phorum_db_get_message($message["thread"]);
}

// Do permission checks for replying to messages.
if ($mode == "reply")
{
    // Find the direct parent for this message.
    if ($message["thread"] != $message["parent_id"]) {
        $parent = phorum_db_get_message($message["parent_id"]);
    } else {
        $parent = $top_parent;
    }

    // If this thread is unapproved, then get out.
    $unapproved =
        empty($top_parent) ||
        empty($parent) ||
        $top_parent["status"] != PHORUM_STATUS_APPROVED ||
        $parent["status"] != PHORUM_STATUS_APPROVED;

    if ($unapproved)
    {
        // In case we run the editor included in the read page,
        // we should not redirect to the listpage for moderators.
        // Else a moderator can never read an unapproved message.
        if (isset($PHORUM["postingargs"]["as_include"])) {
            if ($PHORUM["DATA"]["MODERATOR"]) {
                $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["UnapprovedMessage"];
                return;
            }
        }

        // In other cases, redirect users that are replying to
        // unapproved messages to the message list.
        phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
        exit;
    }

    // closed topic, show a message
    if($top_parent["closed"]) {
        $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["ThreadClosed"];
        $PHORUM["posting_template"] = "message";
        return;
    }    
}

// Do permission checks for editing messages.
if ($mode == "edit")
{
    // Check if the user is allowed to edit this post.
    $timelim = $PHORUM["user_edit_timelimit"];
    $useredit =
        $message["user_id"] == $PHORUM["user"]["user_id"] &&
        phorum_api_user_check_access(PHORUM_USER_ALLOW_EDIT) &&
        ! empty($top_parent) &&
        ! $top_parent["closed"] &&
        (! $timelim || $message["datestamp"] + ($timelim * 60) >= time());

    // Moderators are allowed to edit messages.
    $moderatoredit =
        $PHORUM["DATA"]["MODERATOR"] &&
        $message["forum_id"] == $PHORUM["forum_id"];

    if (!$useredit && !$moderatoredit) {
        $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["EditPostForbidden"];
        return;
    }
}


?>
