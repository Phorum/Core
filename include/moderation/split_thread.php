<?php 

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$PHORUM['DATA']['URL']["ACTION"]=phorum_api_url(PHORUM_MODERATION_ACTION_URL);
$PHORUM['DATA']["FORM"]["forum_id"]=$PHORUM["forum_id"];
$message =phorum_db_get_message($msgthd_id);
$PHORUM['DATA']["FORM"]["thread_id"]=$message["thread"];
$PHORUM['DATA']["FORM"]["message_id"]=$msgthd_id;
$PHORUM['DATA']["FORM"]["message_subject"]=htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
$PHORUM['DATA']["FORM"]["mod_step"]=PHORUM_DO_THREAD_SPLIT;
$template = "split_form";

?>
