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
define('phorum_page','changes');

include_once("./common.php");
include_once("./include/format_functions.php");
include_once("./include/diff_patch.php");

// set all our URL's ... we need these earlier
phorum_build_common_urls();

// checking read-permissions
if(!phorum_check_read_common()) {
  return;
}

// somehow we got to a folder
if($PHORUM["folder_flag"]){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
    phorum_redirect_by_url($dest_url);
    exit();
}

if(isset($PHORUM["args"][1]) && is_numeric($PHORUM["args"][1])){
    $message_id = $PHORUM["args"][1];
} else {
    $dest_url = phorum_get_url(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
    phorum_redirect_by_url($dest_url);
    exit();
}

$message = phorum_db_get_message($message_id);

if(empty($message)){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
    phorum_redirect_by_url($dest_url);
    exit();
}

$PHORUM["DATA"]["MODERATOR"] = phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

$edit_tracks = phorum_db_get_message_edits($message_id);

if(count($edit_tracks)==0 ||
   $PHORUM["track_edits"] == PHORUM_EDIT_TRACK_OFF ||
   ($PHORUM["track_edits"] == PHORUM_EDIT_TRACK_MODERATOR && !$PHORUM["DATA"]["MODERATOR"] ) ) {

    $dest_url = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message_id);
    phorum_redirect_by_url($dest_url);
    exit();
}


$diffs = array_reverse($edit_tracks);

// push an empty diff for the current status
array_push($diffs, array());


$prev_body = -1;
$prev_subject = -1;

foreach($diffs as $diff_info){

    if(!isset($diff_info["user_id"])){
        $this_version["username"] = empty($PHORUM['custom_display_name'])
                                  ? htmlspecialchars($message["author"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
                                  : $message["author"];
        $this_version["user_id"] = $message["user_id"];
        $this_version["date"] = phorum_date($PHORUM["long_date_time"], $message["datestamp"]);
        $this_version["original"] = true;
    } else {

        $edit_user = phorum_api_user_get($diff_info['user_id']);

        $this_version["username"] = empty($PHORUM['custom_display_name'])
                                  ? htmlspecialchars($edit_user["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
                                  : $edit_user["display_name"];
        $this_version["user_id"] = $diff_info["user_id"];
        $this_version["date"] = phorum_date($PHORUM["long_date_time"], $diff_info["time"]);
        $this_version["original"] = false;
    }

    // only happens in first loop
    if($prev_body == -1) {
        $prev_body = $message["body"];
    }

    // body diffs
    if(isset($diff_info['diff_body']) && !empty($diff_info['diff_body'])){

        $colored_body = phorum_unpatch_color($prev_body, $diff_info['diff_body']);
        $prev_body = phorum_unpatch($prev_body, $diff_info['diff_body']);

        $colored_body = htmlspecialchars($colored_body, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
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

    //print "DEBUG<br />".$this_version["colored_body"]."<br />---<br />$prev_body<br />\n";
    // subject diffs
    /*if(!empty($prev_diff_subject)){
        $colored_subject = phorum_unpatch_color($prev_subject, $prev_diff_subject);
        $colored_subject = htmlspecialchars($colored_subject, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        $colored_subject = str_replace(
        array("[phorum addition]", "[phorum removal]", "[/phorum addition]", "[/phorum removal]"),
        array("<span class=\"addition\">", "<span class=\"removal\">", "</span>", "</span>"),
        $colored_subject);
        $colored_subject = nl2br($colored_subject);

        $message_hist[count($message_hist)-1]["colored_subject"] = $colored_subject;
        $prev_subject = phorum_unpatch($prev_subject, $prev_diff_subject);
        $this_version["colored_subject"] = $prev_subject;
    } else {
        $prev_subject = $message["subject"];
        $this_version["colored_subject"] = $message["subject"];
    }*/

    // only happens in first loop
    if($prev_subject == -1) {
        $prev_subject = $message["subject"];
    }

    // subject diffs
    if(isset($diff_info['diff_subject']) && !empty($diff_info['diff_subject'])){

        $colored_subject = phorum_unpatch_color($prev_subject, $diff_info['diff_subject']);
        $prev_subject = phorum_unpatch($prev_subject, $diff_info['diff_subject']);

        $colored_subject = htmlspecialchars($colored_subject, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
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

    // no nl2br for subject
    //$this_version["colored_subject"] = nl2br($this_version["colored_subject"]);

    $message_hist[] = $this_version;

}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["ChangeHistory"];
// unset default description
$PHORUM["DATA"]["DESCRIPTION"] = "";

$PHORUM["DATA"]["MESSAGE"]["subject"] = htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
$PHORUM["DATA"]["MESSAGE"]["URL"]["READ"] = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message_id);

$PHORUM["DATA"]["CHANGES"] = $message_hist;

phorum_output("changes");


?>
