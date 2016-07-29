<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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

define('phorum_page','changes');
require_once './common.php' ;

require_once PHORUM_PATH.'/include/api/diff.php';
require_once PHORUM_PATH.'/include/api/format/messages.php';

// set all our URL's ... we need these earlier
phorum_build_common_urls();

// checking read-permissions
if(!phorum_check_read_common()) {
  return;
}

// somehow we got to a folder
if ($PHORUM["folder_flag"]) {
    phorum_api_redirect(PHORUM_INDEX_URL, $PHORUM['forum_id']);
}

if (isset($PHORUM["args"][1]) && is_numeric($PHORUM["args"][1])) {
    $message_id = $PHORUM["args"][1];
} else {
    phorum_api_redirect(PHORUM_INDEX_URL, $PHORUM['forum_id']);
}

$message = $PHORUM['DB']->get_message($message_id);

if (empty($message)) {
    phorum_api_redirect(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
}

$PHORUM["DATA"]["MODERATOR"] = phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

$edit_tracks = $PHORUM['DB']->get_message_edits($message_id);

if(count($edit_tracks)==0 ||
   $PHORUM["track_edits"] == PHORUM_EDIT_TRACK_OFF ||
   ($PHORUM["track_edits"] == PHORUM_EDIT_TRACK_MODERATOR && !$PHORUM["DATA"]["MODERATOR"] ) ) {

    phorum_api_redirect(PHORUM_READ_URL, $message['thread'], $message_id);
}


$diffs = array_reverse($edit_tracks);

// push an empty diff for the current status
array_push($diffs, array());


$prev_body = -1;
$prev_subject = -1;

$message_hist = array();

foreach($diffs as $diff_info){

    if(!isset($diff_info["user_id"])){
        $this_version["username"] = empty($PHORUM['custom_display_name'])
                                  ? phorum_api_format_htmlspecialchars($message["author"])
                                  : $message["author"];
        $this_version["user_id"] = $message["user_id"];
        $this_version["date"] = phorum_api_format_date($PHORUM["long_date_time"], $message["datestamp"]);
        $this_version["original"] = true;
    } else {

        $edit_user = phorum_api_user_get($diff_info['user_id']);

        $this_version["username"] = empty($PHORUM['custom_display_name'])
                                  ? phorum_api_format_htmlspecialchars($edit_user["display_name"])
                                  : $edit_user["display_name"];
        $this_version["user_id"] = $diff_info["user_id"];
        $this_version["date"] = phorum_api_format_date($PHORUM["long_date_time"], $diff_info["time"]);
        $this_version["original"] = false;
    }

    // only happens in first loop
    if($prev_body == -1) {
        $prev_body = $message["body"];
    }

    // body diffs
    if(isset($diff_info['diff_body']) && !empty($diff_info['diff_body'])){

        $colored_body = phorum_api_diff_unpatch_color($prev_body, $diff_info['diff_body']);
        $prev_body = phorum_api_diff_unpatch($prev_body, $diff_info['diff_body']);

        $colored_body = phorum_api_format_htmlspecialchars($colored_body);
        $colored_body = str_replace(
                        array("[phorum addition]", "[phorum removal]", "[/phorum addition]", "[/phorum removal]"),
                        array("<span class=\"addition\">", "<span class=\"removal\">", "</span>", "</span>"),
                        $colored_body);
        $colored_body = nl2br($colored_body);
        $this_version["colored_body"] = $colored_body;

    } elseif(!isset($diff_info['diff_body'])) {

        $this_version['colored_body'] = nl2br($prev_body);

    } else {
        $this_version["colored_body"] = nl2br($prev_body);
    }

    $this_version['colored_body'] =
        phorum_api_format_censor($this_version['colored_body']);

    // only happens in first loop
    if($prev_subject == -1) {
        $prev_subject = $message["subject"];
    }

    // subject diffs
    if(isset($diff_info['diff_subject']) && !empty($diff_info['diff_subject'])){

        $colored_subject = phorum_api_diff_unpatch_color($prev_subject, $diff_info['diff_subject']);
        $prev_subject = phorum_api_diff_unpatch($prev_subject, $diff_info['diff_subject']);

        $colored_subject = phorum_api_format_htmlspecialchars($colored_subject);
        $colored_subject = str_replace(
                        array("[phorum addition]", "[phorum removal]", "[/phorum addition]", "[/phorum removal]"),
                        array("<span class=\"addition\">", "<span class=\"removal\">", "</span>", "</span>"),
                        $colored_subject);
        $colored_subject = nl2br($colored_subject);
        $this_version["colored_subject"] = $colored_subject;

    } elseif(!isset($diff_info['diff_subject'])) {

        $this_version['colored_subject'] = nl2br($prev_subject);

    } else {
        $this_version["colored_subject"] = nl2br($prev_subject);
    }

    $this_version['colored_subject'] =
        phorum_api_format_censor($this_version['colored_subject']);

    // no nl2br for subject
    //$this_version["colored_subject"] = nl2br($this_version["colored_subject"]);

    $message_hist[] = $this_version;
}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["ChangeHistory"];
// unset default description
$PHORUM["DATA"]["DESCRIPTION"] = "";
$PHORUM["DATA"]["MESSAGE"]["subject"] = phorum_api_format_htmlspecialchars($message["subject"]);
$PHORUM["DATA"]["MESSAGE"]["URL"]["READ"] = phorum_api_url(PHORUM_READ_URL, $message["thread"], $message_id);
$PHORUM["DATA"]["CHANGES"] = $message_hist;

phorum_api_output("changes");

?>
