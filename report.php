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
define('phorum_page','report');

include_once("./common.php");
include_once("./include/email_functions.php");
include_once("./include/format_functions.php");

include_once("./include/api/base.php");
include_once("./include/api/user.php");

// set all our URL's ... we need these earlier
phorum_build_common_urls();

// checking read-permissions
if(!phorum_check_read_common()) {
  return;
}

$report = false;
$template = "report";

$message=array();
// get the message
if (isset($PHORUM["args"][1]) && is_numeric($PHORUM["args"][1])) {
    $message_id = $PHORUM["args"][1];
    $message = phorum_db_get_message($message_id);
}

if(is_array($message) && count($message)) {

    // check for report requests
    if(!empty($_POST["report"])) {
        if ($PHORUM["DATA"]["LOGGEDIN"]){
            if (empty($_POST["explanation"])){
                $_POST["explanation"] = "<" . $PHORUM["DATA"]["LANG"]["None"] . ">";
            }

            $mail_users = phorum_api_user_list_moderators($PHORUM['forum_id'], $PHORUM['email_ignore_admin'], TRUE);
            
            if(count($mail_users)){
                $mail_data = array(
                "mailmessage" => $PHORUM["DATA"]["LANG"]['ReportPostEmailBody'],
                "mailsubject" => $PHORUM["DATA"]["LANG"]['ReportPostEmailSubject'],
                "forumname"   => $PHORUM["DATA"]["NAME"],
                "reportedby"  => $PHORUM["user"]["display_name"],
                "author"      => $message["author"],
                "subject"     => $message["subject"],
                "body"        => wordwrap($message["body"], 72),
                "ip"          => $message["ip"],
                "raw_date"    => $message["datestamp"],
                "date"        => phorum_date($PHORUM["short_date_time"], $message["datestamp"]),
                "explanation" => wordwrap($_POST["explanation"], 72),
                "url"         => phorum_get_url_no_uri_auth(PHORUM_READ_URL, $message["thread"], $message_id),
                "delete_url"  => phorum_get_url_no_uri_auth(PHORUM_MODERATION_URL, PHORUM_DELETE_MESSAGE, $message_id),
                "hide_url"    => phorum_get_url_no_uri_auth(PHORUM_MODERATION_URL, PHORUM_HIDE_POST, $message_id),
                "edit_url"    => phorum_get_url_no_uri_auth(PHORUM_POSTING_URL, 'moderation', $message_id),
                "reporter_url"=> phorum_get_url_no_uri_auth(PHORUM_PROFILE_URL, $PHORUM["user"]["user_id"]),
                "message"     => $message
                );

                if (isset($PHORUM["hooks"]["report"]))
                    $mail_data = phorum_hook("report", $mail_data);

                phorum_email_user($mail_users, $mail_data);

                $PHORUM["DATA"]["URL"]["REDIRECT"]=phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["thread"]);
                $PHORUM["DATA"]["BACKMSG"]=$PHORUM["DATA"]["LANG"]["BackToThread"];
                $PHORUM["DATA"]["OKMSG"]=$PHORUM["DATA"]["LANG"]["ReportPostSuccess"];
                $template="message";
                $report = true;
            }
        }
        else{
            $PHORUM["DATA"]["ReportPostMessage"] = $PHORUM["DATA"]["LANG"]['ReportPostNotAllowed'];
        }
    }

    // format message
    list($message) = phorum_format_messages(array($message));

    $PHORUM["DATA"]["PostSubject"] = $message["subject"];
    $PHORUM["DATA"]["PostAuthor"] = $message["author"];
    $PHORUM["DATA"]["PostBody"] = $message["body"];
    $PHORUM["DATA"]["raw_PostDate"] = $message["datestamp"];
    $PHORUM["DATA"]["PostDate"] = phorum_date($PHORUM["short_date_time"], $message["datestamp"]);
    $PHORUM["DATA"]["ReportURL"] = phorum_get_url(PHORUM_REPORT_URL, $message_id);

    // if the report was not successfully sent, keep whatever explanation they gave already
    if (isset($_POST["explanation"]) && !$report) {
        $PHORUM["DATA"]["explanation"] = $_POST["explanation"];
    }
    else {
        $PHORUM["DATA"]["explanation"] = "";
    }
} else {

    $PHORUM["DATA"]["ERROR"] = $PHORUM['DATA']['LANG']['MessageNotFound'];
    $template='message';
}

phorum_output($template);

?>
