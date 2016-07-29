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

/**
 * This script implements sending a forum message notification by mail.
 *
 * @package    PhorumAPI
 * @subpackage Mail
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/mail.php';

// {{{ Function: phorum_api_mail_message_notify()
/**
 * Send a new forum message notification by mail to subscribed users.
 *
 * @param array $message
 *     An array containing the data for a forum message.
 */
function phorum_api_mail_message_notify($message)
{
    // Not "global $PHORUM", because we do not want the loading of language
    // files to override our already loaded language file.
    $PHORUM = $GLOBALS['PHORUM'];

    // Check if email notifications are allowed for the current forum.
    if (empty($PHORUM['allow_email_notify'])) return;

    $recipients = phorum_api_user_list_subscribers(
        $PHORUM['forum_id'],
        $message['thread'],
        PHORUM_SUBSCRIPTION_MESSAGE
    );

    // No subscribers? Then we are done.
    if (empty($recipients)) return;

    $mail_data = array
    (
        // Template variables
        'forumname'   => strip_tags($PHORUM['DATA']['NAME']),
        'forum_id'    => $message['forum_id'],
        "thread_id"   => $message['thread'],
        'message_id'  => $message['message_id'],
        'author'      => phorum_api_user_get_display_name(
                             $message['user_id'],
                             $message['author'],
                             PHORUM_FLAG_PLAINTEXT
                         ),
        'subject'     => $message['subject'],
        'fully_body'  => $message['body'],
        'plain_body'  => phorum_api_format_wordwrap(phorum_api_format_strip($message['body']), 72),
        'read_url'    => phorum_api_url_no_uri_auth(
                             PHORUM_READ_URL,
                             $message['thread'],
                             $message['message_id']
                         ),
        'remove_url'  => phorum_api_url_no_uri_auth(
                             PHORUM_FOLLOW_URL,
                             $message['thread'],
                             'stop=1'
                         ),
        'noemail_url' => phorum_api_url_no_uri_auth(
                            PHORUM_FOLLOW_URL,
                            $message['thread'],
                            'noemail=1'
                        ),
        'followed_threads_url' => phorum_api_url_no_uri_auth(
                            PHORUM_CONTROLCENTER_URL,
                            'panel='.PHORUM_CC_SUBSCRIPTION_THREADS
                        ),
        'msgid'       => $message['msgid'],

        // For the "mail_prepare" hook.
        'mailmessagetpl' => 'NewReplyMessage',
        'mailsubjecttpl' => 'NewReplySubject'
    );

    foreach ($recipients as $language => $addresses)
    {
        $language = basename($language);

        if (file_exists(PHORUM_PATH."/include/lang/$language.php")) {
            $mail_data['language'] = $language;
            include PHORUM_PATH."/include/lang/$language.php";
        } else {
            $mail_data['language'] = $PHORUM['language'];
            include PHORUM_PATH."/include/lang/{$PHORUM['language']}.php";
        }

        $mail_data['mailmessage'] = $PHORUM['DATA']['LANG']['NewReplyMessage'];
        $mail_data['mailsubject'] = $PHORUM['DATA']['LANG']['NewReplySubject'];

        phorum_api_mail($addresses, $mail_data);
    }
}
// }}}

?>
