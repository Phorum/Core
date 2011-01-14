<?php 

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$template = "merge_form";
$PHORUM['DATA']['URL']["ACTION"]     = phorum_api_url(PHORUM_MODERATION_ACTION_URL);
$PHORUM['DATA']["FORM"]["forum_id"]  = $PHORUM["forum_id"];
$PHORUM['DATA']["FORM"]["thread_id"] = $msgthd_id;
$PHORUM['DATA']["FORM"]["mod_step"]  = PHORUM_DO_THREAD_MERGE;

// the moderator selects the target thread to merge to
$merge_t1 = phorum_moderator_data_get('merge_t1');
if( !$merge_t1 || $merge_t1==$msgthd_id ) {
    phorum_moderator_data_put('merge_t1', $msgthd_id);
    $PHORUM['DATA']["FORM"]["merge_none"] =true;
    $message = phorum_db_get_message($merge_t1, "message_id", true);
    $PHORUM['DATA']["FORM"]["merge_subject1"] =htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
}
// the moderator selects the source thread to merge from
else {
    $PHORUM['DATA']["FORM"]["merge_t1"] =$merge_t1;
    $message = phorum_db_get_message($merge_t1, "message_id", true);
    $PHORUM['DATA']["FORM"]["merge_subject1"] =htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    $message = phorum_db_get_message($msgthd_id);
    $PHORUM['DATA']["FORM"]["thread_subject"] =htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
}

?>
