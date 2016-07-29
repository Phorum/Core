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

// Check if the active user has moderation permissions for the active forum.
$PHORUM["DATA"]["MODERATOR"] =
    phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

// Retrieve the id of the thread or message on which to perform a
// moderation operation.
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

// When no thread or message id is provided or if the user isn't a moderator,
// then redirect the user back to the message list.
if (empty($msgthd_id) || !$PHORUM["DATA"]["MODERATOR"]) {
   phorum_api_redirect(phorum_moderation_back_url());
}

// If the user is not fully logged in, send him to the login page.
// because moderation actions can vary so much, the only safe bet is to send
// them to the referrer if they are not fully logged in
if (!$PHORUM["DATA"]["FULLY_LOGGEDIN"]) {
    phorum_api_redirect(
        PHORUM_LOGIN_URL, "redir=".urlencode($_SERVER["HTTP_REFERER"]));
}

// If we gave the user a confirmation form and he clicked "No", send him back.
if (isset($_POST["confirmation"]) && empty($_POST["confirmation_yes"])) {
    phorum_api_redirect(phorum_moderation_back_url());
}

// The user cancelled the moderation action.
if (isset($_POST['cancel'])) {
    phorum_api_redirect(phorum_moderation_back_url());
}

// Build all our common URL's.
phorum_build_common_urls();

// The template to load at the end of this script.
$template = "message";

// Messages for which to invalidate the cache at the end of this script.
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
 *         $PHORUM['DB']->update_settings(array(
 *             "mod_foo" => $PHORUM["mod_foo"]
 *         ));
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

    case PHORUM_MAKE_STICKY: // make a thread sticky
        include PHORUM_PATH . '/include/moderation/make_sticky.php';
        break;

    case PHORUM_MAKE_UNSTICKY: // make a thread unsticky
        include PHORUM_PATH . '/include/moderation/make_unsticky.php';
        break;

    default:
        phorum_api_redirect(phorum_moderation_back_url());
}

// Remove the affected messages from the cache if caching is enabled.
if ($PHORUM['cache_messages']) {
    $invalidate_forums = array();
    foreach($invalidate_message_cache as $message) {
        phorum_api_cache_remove('message', $message['forum_id']."-".$message["message_id"]);
        $invalidate_forums[$message['forum_id']]=$message['forum_id'];
    }

    if(is_array($invalidate_forums) && count($invalidate_forums)) {
        // increment the cache version for all involved forums once
        foreach($invalidate_forums as $forum_id) {
            phorum_api_forums_increment_cache_version($forum_id);
        }
    }
}

if (!isset($PHORUM['DATA']['BACKMSG'])) {
    $PHORUM['DATA']["BACKMSG"] = $PHORUM['DATA']["LANG"]["BacktoForum"];
}

phorum_api_output($template);

// ----------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------

/**
 * Outputs a confirmation form.
 *
 * To maintain backwards compatibility with the templates,
 * we generate a form in code and output it using stdblock.
 *
 * The function exits the script after displaying the form.
 *
 * @param   string    $message  Message to display to users
 * @param   string    $action   The URI to post the form to
 * @param   array     $args     The hidden form values to be used in the form
 * @return  void
 *
 */
function phorum_show_confirmation_form($message, $action, $args)
{
    global $PHORUM;

    ob_start();

    ?>
    <div style="text-align: center;">
        <strong><?php echo phorum_api_format_htmlspecialchars($message); ?></strong>
        <br />
        <br />
        <form
            action="<?php echo phorum_api_format_htmlspecialchars($action); ?>"
            method="post">

            <input type="hidden"
                name="forum_id" value="<?php echo $PHORUM["forum_id"]; ?>" />
            <input type="hidden" name="confirmation" value="1" />

            <?php foreach ($args as $name => $value){ ?>
                <input type="hidden"
                    name="<?php echo phorum_api_format_htmlspecialchars($name); ?>"
                    value="<?php echo phorum_api_format_htmlspecialchars($value); ?>" />
            <?php } ?>

            <?php echo $PHORUM["DATA"]["POST_VARS"]; ?>

            <input type="submit"
                name="confirmation_yes"
                value="<?php echo $PHORUM["DATA"]["LANG"]["Yes"]; ?>" />

            <input type="submit"
                name="confirmation_no"
                value="<?php echo $PHORUM["DATA"]["LANG"]["No"]; ?>" />

        </form>
        <br />
    </div>
    <?php

    $PHORUM["DATA"]["BLOCK_CONTENT"] = ob_get_clean();
    phorum_api_output("stdblock");
    exit();
}

/**
 * A utility function to determine a suitable URL to redirect back from
 * the moderation code.
 *
 * @return string
 */
function phorum_moderation_back_url()
{
    global $PHORUM;

    // When the parameter "prepost" is available in the request, then
    // the moderation action was initiated from the moderation interface
    // in the user control center.
    if (isset($_POST['prepost']) ||
        isset($_GET['prepost'])  ||
        isset($PHORUM['args']['prepost']))
    {
        return phorum_api_url(
            PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED
        );
    }

    // Find the id of the thread or message on which the moderation
    // action has been performed.
    if (isset($_POST["thread"])) {
        $msgthd_id = (int)$_POST["thread"];
    } elseif(isset($PHORUM['args'][2])) {
        $msgthd_id = (int)$PHORUM['args'][2];
    } else {
        $msgthd_id = 0;
    }

    // If no id was found, then redirect back to the list page for
    // the active forum or the index page if no active forum is available.
    if (empty($msgthd_id))
    {
        if (empty($PHORUM["forum_id"])) {
            return phorum_api_url(PHORUM_INDEX_URL);
        } else {
            return phorum_api_url(PHORUM_LIST_URL);
        }
    }

    // Check if the message still exists. It might be gone after a
    // moderation action. When the message no longer exists, redirect
    // the user back to the list page for the active forum.
    $message = $PHORUM['DB']->get_message($msgthd_id);
    if (!$message) {
        return phorum_api_url(PHORUM_LIST_URL);
    }

    // Redirect back to the message that we found.
    return phorum_api_url(
       PHORUM_READ_URL, $message['thread'], $message['message_id']
    );
}

?>
