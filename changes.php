<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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

$PHORUM["DATA"]["MODERATOR"] = phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

if(!isset($message["meta"]["edit_track"]) ||
   count($message["meta"]["edit_track"])==0 ||
   $PHORUM["track_edits"] == PHORUM_EDIT_TRACK_OFF ||
   ($PHORUM["track_edits"] == PHORUM_EDIT_TRACK_MODERATOR && !$PHORUM["DATA"]["MODERATOR"] ) ) {

    $dest_url = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message_id);
    phorum_redirect_by_url($dest_url);
    exit();
}


$diffs = array_reverse($message["meta"]["edit_track"]);

// push an empty diff for the current status
array_push($diffs, array());

foreach($diffs as $diff_info){

    if(empty($diff_info["username"])){
        $this_version["username"] = $message["author"];
        $this_version["user_id"] = $message["user_id"];
        $this_version["date"] = phorum_date($PHORUM["long_date_time"], $message["datestamp"]);
        $this_version["original"] = true;
    } else {
        $this_version["username"] = $diff_info["username"];
        $this_version["user_id"] = $diff_info["user_id"];
        $this_version["date"] = phorum_date($PHORUM["long_date_time"], $diff_info["time"]);
        $this_version["original"] = false;
    }

    if(!empty($prev_diff)){
        $colored_body = phorum_unpatch_color($prev_body, $prev_diff);
        $colored_body = htmlspecialchars($colored_body);
        $colored_body = str_replace(
                        array("[phorum addition]", "[phorum removal]", "[/phorum addition]", "[/phorum removal]"),
                        array("<span class=\"addition\">", "<span class=\"removal\">", "</span>", "</span>"),
                        $colored_body);
        $colored_body = nl2br($colored_body);
        $message_hist[count($message_hist)-1]["colored_body"] = $colored_body;
        $prev_body = phorum_unpatch($prev_body, $prev_diff);
        $this_version["colored_body"] = $prev_body;
    } else {
        $prev_body = $message["body"];
        $this_version["colored_body"] = $message["body"];
    }

    $this_version["colored_body"] = nl2br($this_version["colored_body"]);

    $message_hist[] = $this_version;

    if(!empty($diff_info["diff"])){
        $prev_diff = $diff_info["diff"];
    }
}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["ChangeHistory"];
// unset default description
$PHORUM["DATA"]["DESCRIPTION"] = "";

$PHORUM["DATA"]["MESSAGE"]["subject"] = htmlspecialchars($message["subject"]);
$PHORUM["DATA"]["MESSAGE"]["URL"]["READ"] = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message_id);

$PHORUM["DATA"]["CHANGES"] = $message_hist;

include phorum_get_template("header");
phorum_hook("after_header");
include phorum_get_template("changes");
phorum_hook("before_footer");
include phorum_get_template("footer");


?>
