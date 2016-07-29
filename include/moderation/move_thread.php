<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$message = $PHORUM['DB']->get_message($msgthd_id);

$PHORUM['DATA']['HEADING'] =
    $PHORUM['DATA']['LANG']['Moderate'] . ': ' .
    $PHORUM['DATA']['LANG']['MoveThread'];

$PHORUM['DATA']['URL']["ACTION"]     = phorum_api_url(PHORUM_MODERATION_ACTION_URL);
$PHORUM['DATA']["FORM"]["forum_id"]  = $PHORUM["forum_id"];
$PHORUM['DATA']["FORM"]["thread_id"] = $msgthd_id;
$PHORUM['DATA']["FORM"]["mod_step"]  = PHORUM_DO_THREAD_MOVE;
$PHORUM['DATA']["FORM"]["subject"]   = phorum_api_format_htmlspecialchars($message["subject"]);

// get all the forums the moderator may move to
$PHORUM['DATA']["MoveForumsOption"]="";

// TODO: this does not match the check at the start of the read
// TODO: and list scripts, where we check if this user has perms
// TODO: for moderation of two or more forums, before we
// TODO: enable the move feature. We should either check
// TODO: for 2 or more moderated forums and check that moving
// TODO: is only done between moderated forums or check for
// TODO: 1 or more moderated forums and allow moving between
// TODO: any two forums. Now we have a mix of those two.
// add  && phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES, $id) if the
// mod should only be able to move to forums he also moderates

// get the forumlist
$forums = phorum_api_forums_tree();

// ignore the current forum
unset($forums[$PHORUM['forum_id']]);
$PHORUM['DATA']['FORUMS'] = $forums;

$PHORUM['DATA']['FRM'] = 1;

// Include the 'message' template, which is used by the
// do_thread_move.php script to handle error reporting.
$template = isset($PHORUM['DATA']['ERROR'])
          ? array('message', 'move_form')
          : 'move_form';

?>
