<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$PHORUM['DATA']['OKMSG']="1 ".$PHORUM["DATA"]['LANG']['MsgApprovedOk'];

$old_message = $PHORUM['DB']->get_message($msgthd_id);
$newpost=array("status"=>PHORUM_STATUS_APPROVED);

// setting the new status
$PHORUM['DB']->update_message($msgthd_id, $newpost);

// updating the thread-info
phorum_api_thread_update_metadata($old_message['thread']);

// updating the forum-stats
$PHORUM['DB']->update_forum_stats(false, 1, $old_message["datestamp"]);

/*
 * [hook]
 *     after_approve
 *
 * [description]
 *     This hook can be used for performing extra actions after a
 *     message has been approved.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>moderation.php</filename>, right approving a message
 *     and possibly its replies.
 *
 * [input]
 *     An array containing two elements:
 *     <ul>
 *     <li>The message data</li>
 *     <li>The type of approval (either
 *     <literal>PHORUM_APPROVE_MESSAGE</literal> or
 *     <literal>PHORUM_APPROVE_MESSAGE_TREE</literal>)</li>
 *     </ul>
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_after_approve($data)
 *     {
 *         global $PHORUM;
 *
 *         // alert the message author that their message has been
 *         // approved
 *         $pm_message = preg_replace(
 *             "%message_subject%",
 *             $data[0]["subject"],
 *             $PHORUM["DATA"]["LANG"]["mod_foo"]["MessageApprovedBody"]
 *             );
 *         $PHORUM['DB']->pm_send(
 *             $PHORUM["DATA"]["LANG"]["mod_foo"]["MessageApprovedSubject"],
 *             $pm_message,
 *             $data[0]["user_id"]
 *             );
 *
 *         return $data;
 *
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["after_approve"])) {
    phorum_api_hook("after_approve", array($old_message, PHORUM_APPROVE_MESSAGE));
}

if ($old_message['status'] != PHORUM_STATUS_HIDDEN ) {
    phorum_api_mail_message_notify($old_message);
}

if(isset($PHORUM['args']['old_forum']) && is_numeric($PHORUM['args']['old_forum'])) {
    $PHORUM['forum_id']=(int)$PHORUM['args']['old_forum'];
}


if(isset($PHORUM['args']["prepost"])) {
    $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_api_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED);
} else {
    $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
}

$invalidate_message_cache[] = array(
    "message_id" => $msgthd_id,
    "forum_id"   => $PHORUM["forum_id"]
);

?>
