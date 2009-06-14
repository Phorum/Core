<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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

if (!defined("PHORUM")) return;

require_once './include/api/mail.php';

function phorum_email_moderators($message)
{
    global $PHORUM;

    $moderators = phorum_api_user_list_moderators($PHORUM['forum_id'], $PHORUM['email_ignore_admin'], TRUE);

    $userdata = phorum_api_user_get(array_keys($moderators));
    
    $mail_localized_users=array();
    foreach($userdata as $id => $user) {
        $lang = empty($user['user_language']) ? $PHORUM['language'] : $user['user_language'];
        $mail_localized_users[$lang][]=$user['email'];
    }
    
    if (count($mail_localized_users))
    {
        if($message["status"] > 0) { // just notification of a new message
            $mailsubjecttpl = 'NewUnModeratedSubject';
            $mailmessagetpl = 'NewUnModeratedMessage';
        } else { // posts needing approval
            $mailsubjecttpl = 'NewModeratedSubject';
            $mailmessagetpl = 'NewModeratedMessage';
        }

        $mail_data = array(
            // Template variables.
            "forumname"   => strip_tags($PHORUM["DATA"]["NAME"]),
            "forum_id"    => $PHORUM['forum_id'],
            "message_id"  => $message['message_id'],
            "author"      => phorum_api_user_get_display_name($message["user_id"], $message["author"], PHORUM_FLAG_PLAINTEXT),
            "subject"     => $message['subject'],
            "full_body"   => $message['body'],
            "plain_body"  => phorum_api_format_strip($message['body']),
            "approve_url" => phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=messages"),
            "read_url"    => phorum_api_url(PHORUM_READ_URL, $message['thread'], $message['message_id']),

            // For email_user_start.
            "mailmessagetpl" => $mailmessagetpl,
            "mailsubjecttpl" => $mailsubjecttpl,
        );
        
        if (isset($_POST[PHORUM_SESSION_LONG_TERM])) {
            // strip any auth info from the read url
            $mail_data["read_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["read_url"]);
            $mail_data["approve_url"] = preg_replace("!,{0,1}" . PHORUM_SESSION_LONG_TERM . "=" . urlencode($_POST[PHORUM_SESSION_LONG_TERM]) . "!", "", $mail_data["approve_url"]);
        }

        // go through the user-languages and send mail with their set lang
        foreach($mail_localized_users as $language => $mail_users)
        {
            $language = basename($language);

            if ( file_exists( "./include/lang/$language.php" ) ) {
                $mail_data['language'] = $language;
                include "./include/lang/$language.php";
            } else {
                $mail_data['language'] = $PHORUM['language'];
                include "./include/lang/{$PHORUM['language']}.php";
            }
            $mail_data["mailmessage"] = $PHORUM["DATA"]["LANG"][$mail_data['mailmessagetpl']];
            $mail_data["mailsubject"] = $PHORUM["DATA"]["LANG"][$mail_data['mailsubjecttpl']];
            
            phorum_email_user($mail_users, $mail_data);

        }        
        
        
    }
}

?>
