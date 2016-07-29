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

define('phorum_page','report');
require_once './common.php';

require_once PHORUM_PATH.'/include/api/mail.php';
require_once PHORUM_PATH.'/include/api/format/messages.php';

// set all our URL's ... we need these earlier
phorum_build_common_urls();

// checking read-permissions
if(!phorum_check_read_common()) {
  return;
}

$report = false;
$template = "report";

$message=array();
$message_id = 0;
// get the message
if (isset($PHORUM["args"][1]) && is_numeric($PHORUM["args"][1])) {
    $message_id = $PHORUM["args"][1];
    $message = $PHORUM['DB']->get_message($message_id);
} else {
    phorum_api_redirect(PHORUM_LIST_URL);
}

if (is_array($message) && count($message))
{
    // check for report requests
    if (!empty($_POST["cancel"]))
    {
        return phorum_api_redirect(phorum_api_url(
            PHORUM_FOREIGN_READ_URL,
            $message["forum_id"], $message["thread"], $message['message_id']
        ));
    }
    if (!empty($_POST["report"]))
    {
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
                "body"        => phorum_api_format_wordwrap($message["body"], 72),
                "ip"          => $message["ip"],
                "raw_date"    => $message["datestamp"],
                "date"        => phorum_api_format_date($PHORUM["short_date_time"], $message["datestamp"]),
                "explanation" => phorum_api_format_wordwrap($_POST["explanation"], 72),
                "url"         => phorum_api_url(PHORUM_READ_URL, $message["thread"], $message_id),
                "delete_url"  => phorum_api_url(PHORUM_MODERATION_URL, PHORUM_DELETE_MESSAGE, $message_id),
                "hide_url"    => phorum_api_url(PHORUM_MODERATION_URL, PHORUM_HIDE_POST, $message_id),
                "edit_url"    => phorum_api_url(PHORUM_POSTING_URL, 'moderation', $message_id),
                "reporter_url"=> phorum_api_url(PHORUM_PROFILE_URL, $PHORUM["user"]["user_id"]),
                "message"     => $message
                );

                if (isset($_POST[PHORUM_SESSION_LONG_TERM])) {
                    // strip any auth info from the created urls
                    $mail_data["url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["url"]);
                    $mail_data["delete_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["delete_url"]);
                    $mail_data["hide_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["hide_url"]);
                    $mail_data["edit_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["edit_url"]);
                    $mail_data["reporter_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["reporter_url"]);
                }

                if (isset($PHORUM["hooks"]["report"]))
                    $mail_data = phorum_api_hook("report", $mail_data);

                phorum_api_mail($mail_users, $mail_data);

                $PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_api_url(
                    PHORUM_FOREIGN_READ_URL,
                    $message["forum_id"], $message["thread"], $message['message_id']
                );
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
    list($message) = phorum_api_format_messages(array($message));

    $PHORUM["DATA"]["PostSubject"] = $message["subject"];
    $PHORUM["DATA"]["PostAuthor"] = $message["author"];
    $PHORUM["DATA"]["PostBody"] = $message["body"];
    $PHORUM["DATA"]["raw_PostDate"] = $message["datestamp"];
    $PHORUM["DATA"]["PostDate"] = phorum_api_format_date($PHORUM["short_date_time"], $message["datestamp"]);
    $PHORUM["DATA"]["ReportURL"] = phorum_api_url(PHORUM_REPORT_URL, $message_id);

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

$PHORUM['DATA']['BREADCRUMBS'][] = array(
    'URL'  => '',
    'TEXT' => $PHORUM['DATA']['LANG']['Report'],
    'TYPE' => 'report'
);


phorum_api_output($template);

?>
