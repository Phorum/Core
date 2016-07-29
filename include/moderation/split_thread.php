<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$PHORUM['DATA']['HEADING'] =
    $PHORUM['DATA']['LANG']['Moderate'] . ': ' .
    $PHORUM['DATA']['LANG']['SplitThread'];

$PHORUM['DATA']['BREADCRUMBS'][] = array(
    'URL'  => NULL,
    'TEXT' => $PHORUM['DATA']['HEADING'],
    'TYPE' => 'split'
);

$PHORUM['DATA']['URL']["ACTION"] = phorum_api_url(PHORUM_MODERATION_ACTION_URL);

$message = $PHORUM['DB']->get_message($msgthd_id);

$new_subject = preg_replace('/^Re:\s*/', '', $message['subject']);

$PHORUM['DATA']["FORM"]["forum_id"]            = $PHORUM["forum_id"];
$PHORUM['DATA']["FORM"]["thread_id"]           = $message["thread"];
$PHORUM['DATA']["FORM"]["message_id"]          = $msgthd_id;
$PHORUM['DATA']["FORM"]["message_subject"]     = phorum_api_format_htmlspecialchars($message["subject"]);
$PHORUM['DATA']["FORM"]["new_message_subject"] = phorum_api_format_htmlspecialchars($new_subject);
$PHORUM['DATA']["FORM"]["mod_step"]            = PHORUM_DO_THREAD_SPLIT;

$template = "split_form";

?>
