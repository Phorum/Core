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

// Retrieve the message id to work with.
$message_id = 0;

if ($mode != "post") {
    if (! isset($PHORUM["postingargs"][2])) trigger_error(
        "Missing message_id parameter in request for mode $mode", E_USER_ERROR
    );
    $message_id = $PHORUM["postingargs"][2];
}

// Create an initial message structure.
$message = array();
foreach ($PHORUM["post_fields"] as $key => $info) {
    $message[$key] = $info[pf_INIT];
}

// Retrieve the message replied to or the message being edited.
if ($mode != "post")
{
    // Check read access on the forum that we're handling.
    if (!phorum_check_read_common()) exit;

    // Load the message from the database. If the message
    // can't be retrieved, then return to the message list.
    $dbmessage = phorum_db_get_message($message_id);
    if (! $dbmessage) {
        phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
        exit;
    }
}

// Set message data for replying to posts.
if ($mode == "reply" || $mode == "quote")
{
    // Set thread and parent information.
    $message["parent_id"] = $dbmessage["message_id"];
    $message["thread"] = $dbmessage["thread"];

    // Create Re: subject prefix.
    if (substr($dbmessage["subject"], 0, 4) != "Re: ") {
        $dbmessage["subject"] = "Re: " . $dbmessage["subject"];
    }
    $message["subject"] = $dbmessage["subject"];

    // Add a quoted version of the body for quoted reply messages.
    if ($mode == "quote")
    {
        // Lookup the name that we have to use for the author, if the
        // author is a registered user. The author field could be used
        // directly, but it can contain HTML formatting code, in case
        // some module uses the custom display name functionality.
        $author = phorum_api_user_get_display_name(
            $dbmessage["user_id"], $dbmessage['author'], PHORUM_FLAG_PLAINTEXT
        );

        $quoted = 0;
        if (isset($PHORUM["hooks"]["quote"])) {
            $quoted = phorum_hook("quote", array($author, $dbmessage["body"], $dbmessage["user_id"]));
        }

        if (empty($quoted) || is_array($quoted))
        {
            $quoted = phorum_strip_body($dbmessage["body"], false);
            $quoted = str_replace("\n", "\n> ", $quoted);
            $quoted = wordwrap(trim($quoted), 50, "\n> ", true);
            $quoted = "$author " .
                      "{$PHORUM["DATA"]["LANG"]["Wrote"]}:\n" .
                      str_repeat("-", 55) . "\n> $quoted\n\n\n";
        }

        $message["body"] = $quoted;
    }
}

// Set message data for editing posts.
if ($mode == "edit" || $mode == "moderation") {
    // Transfer all database fields to the form fields.
    $message = phorum_posting_merge_db2form($message, $dbmessage, ALLFIELDS);
}

// For new messages, set some default values for logged in users.
if (($mode == "post" || $mode == "reply" || $mode == "quote") && $PHORUM["DATA"]["LOGGEDIN"])
{
    if (isset($PHORUM["user"]["show_signature"]) &&
        $PHORUM["user"]["show_signature"]) {
        $message["show_signature"] = 1;
    }

    $message['subscription'] = "";
    if (isset($PHORUM["user"]["email_notify"]) &&
        $PHORUM["user"]["email_notify"] > 0) {

        if($PHORUM["user"]["email_notify"] == 2) {
            $message["subscription"] = "message";
        } elseif($PHORUM["user"]["email_notify"] == 1) {
            $message["subscription"] = "bookmark";
        } else {
            $message["subscription"] = "";
        }

    }
}

// When replying, the user might already be subscribed to the thread,
// in which case the follow mode needs to be initializated to not
// lose the follow mode on posting the reply.
if (($mode == "reply"  || $mode == "quote") && $PHORUM["DATA"]["LOGGEDIN"])
{
    $type = phorum_api_user_get_subscription(
        $PHORUM["user"]["user_id"], $message["forum_id"], $message["thread"]);

    switch ($type) {
        case NULL:
            if($PHORUM["user"]["email_notify"] == 2) {
                $message["subscription"] = "message";
            } elseif($PHORUM["user"]["email_notify"] == 1) {
                $message["subscription"] = "bookmark";
            } else {
                $message["subscription"] = "";
            }
            break;
        case PHORUM_SUBSCRIPTION_BOOKMARK:
            $message["subscription"] = "bookmark";
            break;
        case PHORUM_SUBSCRIPTION_MESSAGE:
            $message["subscription"] = "message";
            break;
        case PHORUM_SUBSCRIPTION_NONE:
        default:
            $message["subscription"] = "";
            break;
    }
}

?>
