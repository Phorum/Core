<?php

$previewmessage = $message;

if ($attach_count) 
{
    define('PREVIEW_NO_ATTACHMENT_CLICK', 
           "javascript:alert('" . $PHORUM["DATA"]["LANG"]["PreviewNoClickAttach"] . "')");

    // Create the URL and formatted size for attachment files.
    foreach ($previewmessage["attachments"] as $nr => $data) {
        $previewmessage["attachments"][$nr]["url"] =
            phorum_get_url(PHORUM_FILE_URL, "file={$data['file_id']}");
        $previewmessage["attachments"][$nr]["size"] =
            phorum_filesize($data["size"]);
    }
}

// Format the message using the default formatting.
include_once("./include/format_functions.php");
$previewmessages = phorum_format_messages(array($previewmessage));
$previewmessage = array_shift($previewmessages);

// Recount the number of attachments. Formatting mods might have changed
// the number of attachments we have to display using default formatting.
$attach_count = 0;
if (isset($previewmessage["attachments"])) {
    foreach ($previewmessage["attachments"] as $attachment) {
        if ($attachment["keep"]) {
            $attach_count ++;
        }
    }    
}

if ($attach_count)
{
    // Disable clicking on attachments in the preview (to prevent the
    // browser from jumping to a viewing page, which might break the
    // editing flow). This is not done in the previous loop where the
    // URL is set, so the formatting code for things like inline
    // attachments can be used.
    foreach ($previewmessage["attachments"] as $nr => $data) {
        $previewmessage["attachments"][$nr]["url"] = PREVIEW_NO_ATTACHMENT_CLICK;
    }
} else {
    unset($previewmessage["attachments"]);
}

// Fill the author name and datestamp for new postings.
if ($mode != "edit" && $PHORUM["DATA"]["LOGGEDIN"]) {
    $previewmessage["author"] = $PHORUM["user"]["username"];
    $previewmessage["datestamp"] = time();
}

// Format datestamp. 
$previewmessage["datestamp"] = phorum_date($PHORUM["short_date"], $previewmessage["datestamp"]);
   
$PHORUM["DATA"]["PREVIEW"] = $previewmessage;
    
?>
