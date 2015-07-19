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

$previewmessage = $message;

// Add the message author's signature to the message body.
if (isset($message["user_id"]) && !empty($message["user_id"])) {
    $user = phorum_api_user_get($message["user_id"]);
    if (isset($PHORUM["hooks"]["read_user_info"])) {
        $user_info = phorum_hook("read_user_info", array($user["user_id"] => $user));
        $user = array_shift($user_info);
    }
    if ($user && $message["show_signature"]) {
        $previewmessage["body"] .= "\n\n" . $user["signature"];
    }
}

// Add the list of attachments.
if ($attach_count)
{
    define('PREVIEW_NO_ATTACHMENT_CLICK',
           "javascript:alert('" . $PHORUM["DATA"]["LANG"]["PreviewNoClickAttach"] . "')");

    // Create the URL and formatted size for attachment files.
    foreach ($previewmessage["attachments"] as $nr => $data) {
        $previewmessage["attachments"][$nr]["url"] =
            phorum_get_url(PHORUM_FILE_URL, "file={$data['file_id']}", "filename=".urlencode($data['name']));
        $previewmessage["attachments"][$nr]["download_url"] =
            phorum_get_url(PHORUM_FILE_URL, "file={$data['file_id']}", "filename=".urlencode($data['name']), "download=1");
        $previewmessage["attachments"][$nr]["size"] =
            phorum_filesize($data["size"]);
        $previewmessage["attachments"][$nr]["name"] = htmlspecialchars($data['name'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    }
}

// Fill the author for new postings with the display name
// for authenticated users, if editing the author
// field is not allowed.
if (($mode == "post" || $mode == "reply") &&
    !$PHORUM["DATA"]["OPTION_ALLOWED"]["edit_author"] &&
    $PHORUM["DATA"]["LOGGEDIN"]) {
    $previewmessage["author"] = $message["author"] =
        $PHORUM["user"]["display_name"];
}

// Format the message using the default formatting.
include_once("./include/format_functions.php");
$previewmessages = phorum_format_messages(array(
    $previewmessage['message_id'] => $previewmessage)
);
$previewmessage = $previewmessages[$previewmessage['message_id']];

// Recount the number of attachments. Formatting mods might have changed
// the number of attachments we have to display using default formatting.
// Also, remove the attachments that are not visible from the preview data.
$attach_count = 0;
if (isset($previewmessage["attachments"])) {
    foreach ($previewmessage["attachments"] as $id => $attachment) {
        if ($attachment["keep"]) {
            $attach_count ++;
        } else {
            unset($previewmessage["attachments"][$id]);
        }
    }
}

if ($attach_count)
{
    // Disable clicking on attachments in the preview (to prevent the
    // browser from jumping to a viewing page, which might break the
    // editing flow). This is not done in the previous loop where the
    // URL is set, so the formatting code for things like embedded
    // attachments can be used.
    foreach ($previewmessage["attachments"] as $nr => $data) {
        $previewmessage["attachments"][$nr]["url"] =
        $previewmessage["attachments"][$nr]["download_url"] =
            PREVIEW_NO_ATTACHMENT_CLICK;
    }
} else {
    unset($previewmessage["attachments"]);
}

// Fill the datestamp for new postings.
if ($mode != "edit") {
    $previewmessage["datestamp"] = time();
}

// Format datestamp.
$previewmessage["raw_datestamp"] = $previewmessage["datestamp"];
$previewmessage["datestamp"] = phorum_date($PHORUM["short_date_time"], $previewmessage["datestamp"]);

$PHORUM["DATA"]["PREVIEW"] = $previewmessage;

?>
