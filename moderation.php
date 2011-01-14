<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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

define('phorum_page','moderation');
require_once './common.php';

require_once './include/moderation_functions.php';
require_once PHORUM_PATH.'/include/api/thread.php';
require_once PHORUM_PATH.'/include/api/mail/message_notify.php';

// Check if the active user has read permission on the active forum.
if (!phorum_check_read_common()) {
  return;
}

// CSRF protection: we do not accept posting to this script,
// when the browser does not include a Phorum signed token
// in the request.
phorum_api_request_check_token();

// Check if the active user is a moderator for the active forum.
$PHORUM["DATA"]["MODERATOR"] =
    phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

// Retrieve the id of the thread on which to perform a moderation operation.
if (isset($_POST["thread"])) {
    $msgthd_id = (int)$_POST["thread"];
} elseif(isset($PHORUM['args'][2])) {
    $msgthd_id = (int)$PHORUM['args'][2];
} else {
    $msgthd_id = 0;
}

// Retrieve the moderation step to perform.
if (isset($_POST["mod_step"])) {
    $mod_step = (int)$_POST["mod_step"];
} elseif(isset($PHORUM['args'][1])) {
    $mod_step = (int)$PHORUM['args'][1];
} else {
    $mod_step = 0;
}

// When no thread id is provided or if the user isn't a moderator,
// then redirect the user back to the message list.
if (empty($msgthd_id) || !$PHORUM["DATA"]["MODERATOR"]) {
   phorum_return_to_list();
}

// If the user is not fully logged in, send him to the login page.
// because moderation actions can vary so much, the only safe bet is to send
// them to the referrer if they are not fully logged in
if (!$PHORUM["DATA"]["FULLY_LOGGEDIN"]) {
    phorum_api_redirect(
        PHORUM_LOGIN_URL, "redir=".urlencode($_SERVER["HTTP_REFERER"]));
}

// TODO write a utility function that can be used to send a user back
// to a logical location.

// If we gave the user a confirmation form and they clicked No, send them back.
if (isset($_POST["confirmation"]) && empty($_POST["confirmation_yes"]))
{
    if (isset($_POST["prepost"])) {
        $url = phorum_api_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED);
    } else {
        $message = phorum_db_get_message($msgthd_id);
        $url = phorum_api_url(PHORUM_READ_URL, $message["thread"], $message["message_id"]);
    }

    phorum_api_redirect($url);
}

// Build all our common URL's.
phorum_build_common_urls();

// The template to load at the end of this script.
$template = "message";

// Messages for which to invalidate the cache for at the end of this script.
$invalidate_message_cache = array();

/*
 * [hook]
 *     moderation
 *
 * [description]
 *     This hook can be used for logging moderator actions. You can
 *     use the <literal>$PHORUM</literal> array to retrieve additional info
 *     like the moderating user's id and similar.<sbr/>
 *     <sbr/>
 *     The moderation step id is the variable <literal>$mod_step</literal>
 *     that is used in <filename>moderation.php</filename>. Please read that
 *     script to see what moderation steps are available and for what moderation
 *     actions they stand.<sbr/>
 *     <sbr/>
 *     When checking the moderation step id for a certain step, always use
 *     the contstants that are defined for this in
 *     <filename>include/constants.php</filename>. The numerical value of this
 *     id can change between Phorum releases.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     At the start of <filename>moderation.php</filename>
 *
 * [input]
 *     The id of the moderation step which is run (read-only).
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_moderation ($mod_step)
 *     {
 *         global $PHORUM;
 *
 *         // Update the last timestamp for the moderation step
 *         $PHORUM["mod_foo"]["moderation_step_timestamps"][$mod_step] = time();
 *         phorum_db_update_settings(array("mod_foo" => $PHORUM["mod_foo"]));
 *
 *         return $mod_step;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["moderation"])) {
    phorum_api_hook("moderation", $mod_step);
}

// Run the code for the requested moderation step.
switch ($mod_step)
{
    case PHORUM_DELETE_MESSAGE: // this is a message delete
        include PHORUM_PATH . '/include/moderation/delete_message.php';
        break;

    case PHORUM_DELETE_TREE: // this is a message delete
        include PHORUM_PATH . '/include/moderation/delete_tree.php';
        break;

    case PHORUM_MOVE_THREAD: // this is the first step of a message move
        include PHORUM_PATH . '/include/moderation/move_thread.php';
        break;

    case PHORUM_DO_THREAD_MOVE: // this is the last step of a message move
        include PHORUM_PATH . '/include/moderation/do_thread_move.php';
        break;

    case PHORUM_CLOSE_THREAD: // we have to close a thread
        include PHORUM_PATH . '/include/moderation/close_thread.php';
        break;

    case PHORUM_REOPEN_THREAD: // we have to reopen a thread
        include PHORUM_PATH . '/include/moderation/reopen_thread.php';
        break;

    case PHORUM_APPROVE_MESSAGE: // approving a message
        include PHORUM_PATH . '/include/moderation/approve_message.php';
        break;

    case PHORUM_APPROVE_MESSAGE_TREE: // approve a message and all replies to it
        include PHORUM_PATH . '/include/moderation/approve_message_tree.php';
        break;

    case PHORUM_HIDE_POST: // hiding a message (and its replies)
        include PHORUM_PATH . '/include/moderation/hide_post.php';
        break;

    case PHORUM_MERGE_THREAD: // this is the first step of a thread merge
        include PHORUM_PATH . '/include/moderation/merge_thread.php';
        break;

    case PHORUM_DO_THREAD_MERGE: // this is the last step of a thread merge
        include PHORUM_PATH . '/include/moderation/do_thread_merge.php';
        break;

    case PHORUM_SPLIT_THREAD: // this is the first step of a thread split
        include PHORUM_PATH . '/include/moderation/split_thread.php';
        break;

    case PHORUM_DO_THREAD_SPLIT: // this is the last step of a thread split
        include PHORUM_PATH . '/include/moderation/do_thread_split.php';
        break;

    default:
        phorum_return_to_list();
}

// remove the affected messages from the cache if caching is enabled.
if ($PHORUM['cache_messages']) {
    foreach($invalidate_message_cache as $message) {
        phorum_api_cache_remove('message', $message['forum_id']."-".$message["message_id"]);
        phorum_db_update_forum(array('forum_id'=>$PHORUM['forum_id'],'cache_version'=>($PHORUM['cache_version']+1)));
    }
}


if(!isset($PHORUM['DATA']['BACKMSG'])) {
    $PHORUM['DATA']["BACKMSG"]=$PHORUM['DATA']["LANG"]["BackToList"];
}

phorum_api_output($template);

?>
