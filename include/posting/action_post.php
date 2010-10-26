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

// For phorum_email_moderators() and phorum_email_notice().
include_once("./include/email_functions.php");

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

// Set some values.
$message["moderator_post"] = $PHORUM["DATA"]["MODERATOR"] ? 1 : 0;
$message["sort"] = PHORUM_SORT_DEFAULT;
$message["closed"] = $message["allow_reply"] ? 0 : 1;

// Determine and set the user's IP address.
$user_ip = $_SERVER["REMOTE_ADDR"];
if ($PHORUM["dns_lookup"]) {
    $resolved = @gethostbyaddr($_SERVER["REMOTE_ADDR"]);
    if (!empty($resolved)) {
        $user_ip = $resolved;
    }
}
$message["ip"] = $user_ip;

// For replies, inherit the closed parameter of our top parent.
// Only for rare race conditions, since you cannot reply to
// closed threads.
if ($mode == "reply") {
    $message["closed"] = $top_parent["closed"];
    $message["allow_reply"] = ! $top_parent["closed"];
}

// Check if allow_reply can be set.
if ($mode == "post" && ! $PHORUM["DATA"]["OPTION_ALLOWED"]["allow_reply"]) {
    $message["closed"] = 0;
    $message["allow_reply"] = 1;
}

// For sticky threads, set the sort parameter for replies to
// the correct value, so threaded views will work.
if ($mode == "reply") {
    if ($top_parent["sort"] == PHORUM_SORT_STICKY) {
        $message["sort"] = PHORUM_SORT_STICKY;
    }
}

// Do specific actions for new threads with a "special" flag.
if ($mode == "post" && isset($message["special"])) {
    if ($message["special"]=="sticky" &&
        $PHORUM["DATA"]["OPTION_ALLOWED"]["sticky"]) {
        $message["sort"] = PHORUM_SORT_STICKY;
    }
}

if ($PHORUM["DATA"]["LOGGEDIN"] && $message["show_signature"]) {
    $message["meta"]["show_signature"] = 1;
}

// Put messages on hold in case the forum is moderated.
if ($PHORUM["DATA"]["MODERATED"]) {
    $message["status"] = PHORUM_STATUS_HOLD;
} else {
    $message["status"] = PHORUM_STATUS_APPROVED;
}

// Create a unique message id.
$suffix = preg_replace("/[^a-z0-9]/i", "", $PHORUM["name"]);
$message["msgid"] = md5(uniqid(rand())) . ".$suffix";

// Add attachments to meta data. Because there might be inconsistencies in
// the list due to going backward in the browser after deleting attachments,
// a check is needed to see if the attachments are really in the database.
$message["meta"]["attachments"] = array();
foreach ($message["attachments"] as $info) {
    if ($info["keep"] && phorum_api_file_exists($info["file_id"])) {
        $message["meta"]["attachments"][] = array(
            "file_id"   => $info["file_id"],
            "name"      => $info["name"],
            "size"      => $info["size"],
        );
    }
}
if (!count($message["meta"]["attachments"])) {
    unset($message["meta"]["attachments"]);
}

/*
 * [hook]
 *     before_post
 *
 * [description]
 *     This hook can be used to change the new message data before storing it in
 *     the database.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In <filename>include/posting/action_post.php</filename>, right before
 *     storing a new message in the database.
 *
 * [input]
 *     An array containing message data.
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_before_post($message)
 *     {
 *         global $PHORUM;
 *
 *         // Add the disclaimer to the new message body
 *         $message["body"] .= "\n".$PHORUM["DATA"]["LANG"]["mod_foo"]["Disclaimer"];
 *         
 *         return $message;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["before_post"]))
    $message = phorum_hook("before_post", $message);

// Keep a copy of the message we have got now.
$message_copy = $message;

// Store the message in the database.
$success = phorum_db_post_message($message);

if ($success)
{
    // Handle linking and deleting of attachments to synchronize
    // the message attachments with the working copy list
    // of attachments.
    foreach ($message_copy["attachments"] as $info) {
        if ($info["keep"]) {
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

    // Retrieve the message again to have it in the correct
    // format (otherwise it's a bit messed up in the
    // post-function). Do merge back data which is not
    // stored in the database, but which we might need later on.
    $message = phorum_db_get_message($message["message_id"],'message_id',false,true);
    foreach ($message_copy as $key => $val) {
        if (! isset($message[$key])) {
            $message[$key] = $val;
        }
    }

    phorum_update_thread_info($message["thread"]);

    /*
     * [hook]
     *     after_message_save
     *
     * [description]
     *     This hook can be used for performing actions based on what the
     *     message contained or altering it before it is emailed to the
     *     subscribed users. It is also useful for adding or removing 
     *     subscriptions.
     *
     * [category]
     *     Message handling
     *
     * [when]
     *     In <filename>include/posting/action_post.php</filename>, right after
     *     storing a new message and all database updates are done.
     *
     * [input]
     *     An array containing message data.
     *
     * [output]
     *     Same as input.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_after_message_save($message)
     *     {
     *         global $PHORUM;
     *
     *         // If the message was posted in a monitored forum, log the id
     *         if (in_array($message["forum_id"], $PHORUM["mod_foo"]["monitored_forums"])) {
     *             $PHORUM["mod_foo"]["monitored_messages"][$message["forum_id"]][] = $message["message_id"];
     *         }
     *
     *         return $message;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["after_message_save"]))
        $message = phorum_hook("after_message_save", $message);


    // Subscribe user to the thread if requested. When replying, this
    // can also be used to unsubscribe a user from a thread.
    $subscribe_type = NULL;
    switch ($message["subscription"]) {
        case NULL:
            if ($PHORUM["DATA"]["OPTION_ALLOWED"]["subscribe"]) {
                $subscribe_type = PHORUM_SUBSCRIPTION_NONE;
            }
            break;
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
        default:
            trigger_error(
                "Illegal message subscription type: " .
                htmlspecialchars($message["subscription"])
            );
            break;
    }

    if ($subscribe_type === PHORUM_SUBSCRIPTION_BOOKMARK ||
        $subscribe_type === PHORUM_SUBSCRIPTION_MESSAGE) {
        phorum_api_user_subscribe(
            $message["user_id"],
            $message["thread"],
            $PHORUM["forum_id"],
            $subscribe_type
        );
    } elseif ($mode == 'reply') {
        phorum_api_user_unsubscribe(
            $message["user_id"],
            $message["thread"]
        );
    }

    if ($PHORUM["DATA"]["LOGGEDIN"])
    {
        // Mark own message read.
        phorum_db_newflag_add_read(array(0=>array(
            "id"    => $message["message_id"],
            "forum" => $message["forum_id"],
        )));

        // Increase the user's post count.
        phorum_api_user_increment_posts();
    }

    // Actions for messages which are approved.
    if ($message["status"] > 0)
    {
        // Update forum statistics.
        phorum_db_update_forum_stats(false, 1, $message["datestamp"]);

        // Mail subscribed users.
        phorum_email_notice($message);

    }

    // Mail moderators.
    if ($PHORUM["email_moderators"] == PHORUM_EMAIL_MODERATOR_ON) {
        phorum_email_moderators($message);
    }

    /*
     * [hook]
     *     after_post
     *
     * [description]
     *     This hook can be used for performing actions based on what the
     *     message contained. It is specifically useful for fully overriding
     *     the redirect behavior. When you only need to provide a different
     *     URL, then make use of the after_post_redirect hook.
     *
     * [category]
     *     Message handling
     *
     * [when]
     *     In <filename>include/posting/action_post.php</filename>, after all 
     *     the posting work is done and before executing the built-in
     *     redirect behavior.
     *
     * [input]
     *     An array containing message data.
     *
     * [output]
     *     Same as input.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_after_post($message)
     *     {
     *         global $PHORUM;
     *
     *         // remove the post count increment for the user in select forums
     *         if (in_array($message["forum_id"], $PHORUM["mod_foo"]["forums_to_ignore"])) {
     *             phorum_api_user_save (
     *                 array (
     *                     "user_id"    => $PHORUM["user"]["user_id"],
     *                     "posts"      => $PHORUM["user"]["posts"]
     *                     )
     *                 );
     *         }
     *
     *         return $message;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["after_post"]))
        $message = phorum_hook("after_post", $message);

    // Posting is completed. Take the user back to the forum.
    if ($PHORUM["redirect_after_post"] == "read")
    {
        // Messsage that are not approved are only visible for moderators.
        $not_viewable =
            $message["status"] != PHORUM_STATUS_APPROVED &&
            !$PHORUM["DATA"]["MODERATOR"];

        // Thread reply message: jump to the last message in the thread
        // or to the thread starter in case the new message is not viewable.
        if (isset($top_parent)) {
            if ($not_viewable) {
                $redir_url = phorum_get_url(
                    PHORUM_READ_URL, $message["thread"]
                );
            } else {
                $readlen = $PHORUM["read_length"];
                $pages = ceil(($top_parent["thread_count"]+1) / $readlen);

                if ($pages > 1) {
                    $redir_url = phorum_get_url(
                        PHORUM_READ_URL, $message["thread"],
                        $message["message_id"], "page=$pages"
                    );
                } else {
                    $redir_url = phorum_get_url(
                        PHORUM_READ_URL, $message["thread"],
                        $message["message_id"]
                    );
                }
            }

        // This starter message: Jump to the thread starter message or to
        // the forum's message list in case the new message is not viewable.
        } else {
            $redir_url = $not_viewable
                       ? phorum_get_url(PHORUM_LIST_URL)
                       : phorum_get_url(PHORUM_READ_URL, $message["thread"]);
        }
    }
    else
    {
        $redir_url = phorum_get_url(PHORUM_LIST_URL);
    }

    /*
     * [hook]
     *     after_post_redirect
     *
     * [description]
     *     This hook can be used for modifying the URL that will be used
     *     to redirect the user after posting a message.
     *
     * [category]
     *     Message handling
     *
     * [when]
     *     In <filename>include/posting/action_post.php</filename>, after the 
     *     redirect URL has been constructed and just before the user is
     *     redirected (back to the message list or read page.)
     *
     * [input]
     *     The redirect URL as the first argument and the message data
     *     as the second argument.
     *
     * [output]
     *     This hook must return the redirect URL to use.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_after_post_redirect($url, $message)
     *     {
     *         // For some reason, we find it interesting to redirect
     *         // the user to the Disney site after posting a message.
     *         return "http://www.disney.com/";
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["after_post_redirect"]))
        $redir_url = phorum_hook("after_post_redirect", $redir_url, $message);

    if ($message["status"] > 0) {
        phorum_redirect_by_url($redir_url);
    } else {
    	// give a message about this being a moderated forum before redirecting
    	$PHORUM['DATA']['OKMSG']=$PHORUM['DATA']['LANG']['ModeratedForum'];
    	$PHORUM['DATA']["URL"]["REDIRECT"]=$redir_url;

    	// BACKMSG is depending on the place we are returning to
    	if ($PHORUM["redirect_after_post"] == "read") {
    		$PHORUM['DATA']['BACKMSG'] = $PHORUM['DATA']['LANG']['BackToThread'];
    	} else {
    		$PHORUM['DATA']['BACKMSG'] = $PHORUM['DATA']['LANG']['BackToList'];
    	}

    	// make it a little bit more visible
    	$PHORUM['DATA']["URL"]["REDIRECT_TIME"]=10;
    	phorum_output('message');
    	exit(0);
    }

    return;
}

// If we get here, the posting was not successful. The return value from
// the post function is 0 in case of duplicate posting and FALSE in case
// a database problem occured.

// Restore the original message.
$message = $message_copy;

// Setup the data for displaying an error to the user.
$PHORUM["DATA"]["ERROR"] = $success === 0
                         ? $PHORUM["DATA"]["LANG"]['PostErrorDuplicate']
                         : $PHORUM["DATA"]["LANG"]['PostErrorOccured'];

?>
