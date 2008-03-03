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

if(!defined("PHORUM")) return;

require_once("./include/api/base.php");
require_once("./include/api/user.php");

function phorum_valid_email($email){
    $PHORUM = $GLOBALS["PHORUM"];

    $ret = false;

    $email = trim($email);

    if(preg_match('/^([a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+(\.[a-z0-9\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]+)*)@(((([-a-z0-9]*[a-z0-9])?)|(#[0-9]+)|(\[((([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\.){3}(([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\]))\.)*((([-a-z0-9]*[a-z0-9])?)|(#[0-9]+)|(\[((([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\.){3}(([01]?[0-9]{0,2})|(2(([0-4][0-9])|(5[0-5]))))\]))$/i', $email)){
        if(!$PHORUM["dns_lookup"]){
            // format is valid
            // don't look up mail server
            $ret = true;
        } elseif(function_exists('checkdnsrr')) {

            $fulldomain = substr(strstr($email, "@"), 1).".";
            // check if a mailserver exists for the domain
            if(checkdnsrr($fulldomain, "MX")) {
                $ret = true;
            }

            // some hosts don't have an MX record, but accept mail themselves
            if(!$ret){
                // default timeout of 60 seconds makes the user way too long
                // in case of problems.
                ini_set('default_socket_timeout', 10);
                if(@fsockopen($fulldomain, 25)){
                    $ret = true;
                }
            }
        } else {
            // bah, you must be using windows and we can't run real checks
            $ret = true;
        }
    }

    return $ret;
}

/**
 * function for sending email to users, gets addresses-array and data-array
 */
function phorum_email_user($addresses, $data)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if(!isset($data['from_address'])) {
        $data['from_address'] = "\"".$PHORUM['system_email_from_name']."\" <".$PHORUM['system_email_from_address'].">";
    }

    // Allow modules to pre-process the mail message.
    if (isset($PHORUM["hooks"]["email_user_start"]))
        list($addresses,$data)=phorum_hook("email_user_start",array($addresses,$data));

    // Clear some variables that are meant for use by the email_user_start hook.
    unset($data['mailmessagetpl']);
    unset($data['mailsubjecttpl']);
    unset($data['language']);

    // Extract message body and subject.
    $mailmessage = $data['mailmessage'];
    unset($data['mailmessage']);
    $mailsubject = $data['mailsubject'];
    unset($data['mailsubject']);

    // Replace template variables.
    if(is_array($data) && count($data)) {
        foreach(array_keys($data) as $key){
            if ($data[$key] === NULL || is_array($data[$key])) continue;
            $mailmessage = str_replace("%$key%", $data[$key], $mailmessage);
            $mailsubject = str_replace("%$key%", $data[$key], $mailsubject);
        }
    }

    $num_addresses = count($addresses);
    $from_address = $data['from_address'];

    // Compose an RFC compatible Message-ID header.
    if(isset($data["msgid"]))
    {
        # Try to find a useful hostname to use in the Message-ID.
        $host = "";
        if (isset($_SERVER["HTTP_HOST"])) {
            $host = $_SERVER["HTTP_HOST"];
        } else if (function_exists("posix_uname")) {
            $sysinfo = @posix_uname();
            if (!empty($sysinfo["nodename"])) {
                $host .= $sysinfo["nodename"];
            }
            if (!empty($sysinfo["domainname"])) {
                $host .= $sysinfo["domainname"];
            }
        } else if (function_exists("php_uname")) {
            $host = @php_uname("n");
        } else if (($envhost = getenv("HOSTNAME")) !== false) {
            $host = $envhost;
        }
        if (empty($host)) {
            $host = "webserver";
        }

        $messageid = "<{$data['msgid']}@$host>";
        $messageid_header="\nMessage-ID: $messageid";
    } else {
        $messageid = "";
        $messageid_header="";
    }

    // Allow modules to send the mail message. A module can either
    // remove the recipients for which they did send a mail from the
    // addresses list or return 0 (zero) if the mail was sent
    // to all recipients.
    $send_messages = 1;
    if (isset($PHORUM["hooks"]["send_mail"]))
    {
        $hook_data = array(
            'addresses'  => $addresses,
            'from'       => $from_address,
            'subject'    => $mailsubject,
            'body'       => $mailmessage,
            'bcc'        => $PHORUM['use_bcc'],
            'messageid'  => $messageid
        );

        $send_messages = phorum_hook("send_mail", $hook_data);
    }

    if($send_messages != 0 && $num_addresses > 0){
        $phorum_major_version = substr(PHORUM, 0, strpos(PHORUM, '.'));
        $mailer = "Phorum" . $phorum_major_version;
        $mailheader ="Content-Type: text/plain; charset={$PHORUM["DATA"]["CHARSET"]}\nContent-Transfer-Encoding: {$PHORUM["DATA"]["MAILENCODING"]}\nX-Mailer: $mailer$messageid_header\n";

        if(isset($PHORUM['use_bcc']) && $PHORUM['use_bcc'] && $num_addresses > 3){
            mail(" ", $mailsubject, $mailmessage, $mailheader."From: $from_address\nBCC: " . implode(",", $addresses));
        } else {
            foreach($addresses as $address){
                mail($address, $mailsubject, $mailmessage, $mailheader."From: $from_address");
            }
        }
    }

    return $num_addresses;
}

function phorum_email_pm_notice($message, $langusers)
{
    $mail_data = array(
        // Template variables.
        "pm_message_id"  => $message["pm_message_id"],
        "author"         => phorum_api_user_get_display_name($message["user_id"], $message["from_username"], PHORUM_FLAG_PLAINTEXT),
        "subject"        => $message["subject"],
        "full_body"      => $message["message"],
        "plain_body"     => wordwrap(phorum_strip_body($message["message"]),72),
        "read_url"       => phorum_get_url(PHORUM_PM_URL, "page=read", "pm_id=" . $message["pm_message_id"]),

        // For email_user_start.
        "mailmessagetpl" => 'PMNotifyMessage',
        "mailsubjecttpl" => 'PMNotifySubject'
    );

    if (isset($_POST[PHORUM_SESSION_LONG_TERM])) {
        // strip any auth info from the read url
        $mail_data["read_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["read_url"]);
    }

    foreach ($langusers as $language => $users)
    {
        $PHORUM = $GLOBALS["PHORUM"];

        $language = basename($language); 

        if ( file_exists( "./include/lang/$language.php" ) ) {
            $mail_data['language'] = $language;
            include( "./include/lang/$language.php" );
        } else {
            $mail_data['language'] = $PHORUM['language'];
            include("./include/lang/{$PHORUM['language']}.php");
        }

        $mail_data["mailmessage"] = $PHORUM["DATA"]["LANG"]['PMNotifyMessage'];
        $mail_data["mailsubject"] = $PHORUM["DATA"]["LANG"]['PMNotifySubject'];

        $addresses = array();
        foreach ($users as $user) {
            $addresses[] = $user["email"];
        }

        phorum_email_user($addresses, $mail_data);
    }
}

function phorum_email_notice($message)
{
    $PHORUM=$GLOBALS["PHORUM"];

    // do we allow email-notification for that forum?
    if(!$PHORUM['allow_email_notify']) {
        return;
    }

    include_once("./include/format_functions.php");

    $mail_users_full = phorum_api_user_list_subscribers($PHORUM['forum_id'], $message['thread'], PHORUM_SUBSCRIPTION_MESSAGE);

    if (count($mail_users_full)) {

        $mail_data = array(
            // Template variables.
            "forumname"   => strip_tags($PHORUM["DATA"]["NAME"]),
            "forum_id"    => $PHORUM['forum_id'],
            "message_id"  => $message['message_id'],
            "author"      => phorum_api_user_get_display_name($message["user_id"], $message["author"], PHORUM_FLAG_PLAINTEXT),
            "subject"     => $message['subject'],
            "full_body"   => $message['body'],
            "plain_body"  => phorum_strip_body($message['body']),
            "read_url"    => phorum_get_url(PHORUM_READ_URL, $message['thread'], $message['message_id']),
            "remove_url"  => phorum_get_url(PHORUM_FOLLOW_URL, $message['thread'], "stop=1"),
            "noemail_url" => phorum_get_url(PHORUM_FOLLOW_URL, $message['thread'], "noemail=1"),
            "followed_threads_url" => phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_THREADS),
            "msgid"       => $message["msgid"],

            // For email_user_start.
            "mailmessagetpl" => 'NewReplyMessage',
            "mailsubjecttpl" => 'NewReplySubject'
        );
        if (isset($_POST[PHORUM_SESSION_LONG_TERM])) {
            // strip any auth info from the read url
            $mail_data["read_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["read_url"]);
            $mail_data["remove_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["remove_url"]);
            $mail_data["noemail_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["noemail_url"]);
            $mail_data["followed_threads_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["followed_threads_url"]);
        }
        // go through the user-languages and send mail with their set lang
        foreach($mail_users_full as $language => $mail_users)
        {
            $language = basename($language);

            if ( file_exists( "./include/lang/$language.php" ) ) {
                $mail_data['language'] = $language;
                include( "./include/lang/$language.php" );
            } else {
                $mail_data['language'] = $PHORUM['language'];
                include("./include/lang/{$PHORUM['language']}.php");
            }
            $mail_data["mailmessage"] = $PHORUM["DATA"]["LANG"]['NewReplyMessage'];
            $mail_data["mailsubject"] = $PHORUM["DATA"]["LANG"]['NewReplySubject'];
            phorum_email_user($mail_users, $mail_data);

        }
    }
}

function phorum_email_moderators($message)
{
    $PHORUM=$GLOBALS["PHORUM"];

    $mail_users = phorum_api_user_list_moderators($PHORUM['forum_id'], $PHORUM['email_ignore_admin'], TRUE);

    if (count($mail_users)) {
        include_once("./include/format_functions.php");
        if($message["status"] > 0) { // just notification of a new message
            $mailsubjecttpl = 'NewUnModeratedSubject';
            $mailmessagetpl = 'NewUnModeratedMessage';
            $mailsubject    = $PHORUM["DATA"]["LANG"]['NewUnModeratedSubject'];
            $mailmessage    = $PHORUM["DATA"]["LANG"]['NewUnModeratedMessage'];

        } else { // posts needing approval
            $mailsubjecttpl = 'NewModeratedSubject';
            $mailmessagetpl = 'NewModeratedMessage';
            $mailsubject    = $PHORUM["DATA"]["LANG"]['NewModeratedSubject'];
            $mailmessage    = $PHORUM["DATA"]["LANG"]['NewModeratedMessage'];
        }

        $mail_data = array(
            // Template variables.
            "forumname"   => strip_tags($PHORUM["DATA"]["NAME"]),
            "forum_id"    => $PHORUM['forum_id'],
            "message_id"  => $message['message_id'],
            "author"      => phorum_api_user_get_display_name($message["user_id"], $message["author"], PHORUM_FLAG_PLAINTEXT),
            "subject"     => $message['subject'],
            "full_body"   => $message['body'],
            "plain_body"  => phorum_strip_body($message['body']),
            "approve_url" => phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=messages"),
            "read_url"    => phorum_get_url(PHORUM_READ_URL, $message['thread'], $message['message_id']),
            "mailmessage" => $mailmessage,
            "mailsubject" => $mailsubject,

            // For email_user_start.
            "mailmessagetpl" => $mailmessagetpl,
            "mailsubjecttpl" => $mailsubjecttpl,
            "language"    => $PHORUM['language'],
        );
        if (isset($_POST[PHORUM_SESSION_LONG_TERM])) {
            // strip any auth info from the read url
            $mail_data["read_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["read_url"]);
            $mail_data["approve_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["approve_url"]);
        }
        phorum_email_user($mail_users, $mail_data);
    }
}

?>
