<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$old_message = $PHORUM['DB']->get_message($msgthd_id);
$newpost=array("status"=>PHORUM_STATUS_APPROVED);

$mids = $PHORUM['DB']->get_messagetree($msgthd_id, $old_message["forum_id"]);
// make an array from the string
$mids_arr=explode(",",$mids);

// count the entries for later use
$num_approved=count($mids_arr);
foreach($mids_arr as $key => $mid) {
    // setting the new status
    $PHORUM['DB']->update_message($mid, $newpost);

    $invalidate_message_cache[] = array(
        "message_id" => $mid,
        "forum_id"   => $PHORUM["forum_id"]
    );

}

// updating the thread-info
phorum_api_thread_update_metadata($old_message['thread']);

// updating the forum-stats
$PHORUM['DB']->update_forum_stats(false, "+$num_approved", $old_message["datestamp"]);

if (isset($PHORUM["hooks"]["after_approve"])) {
    phorum_api_hook("after_approve", array($old_message, PHORUM_APPROVE_MESSAGE_TREE));
}

$PHORUM['DATA']['OKMSG']="$num_approved ".$PHORUM['DATA']['LANG']['MsgApprovedOk'];
if(isset($PHORUM['args']["prepost"])) {
    $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_api_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED);
} else {
    $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
}

?>
