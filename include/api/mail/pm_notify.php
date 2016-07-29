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
 * This script implements sending a PM notification by mail.
 *
 * @package    PhorumAPI
 * @subpackage Mail
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/mail.php';

// {{{ Function: phorum_api_mail_pm_notify()
/**
 * Send a PM notification by mail.
 *
 * @param array $message
 *     An array containing the private message data.
 *
 * @param array $recipients
 *     An array of users that have received the PM.
 */
function phorum_api_mail_pm_notify($message, $recipients)
{
    // Not "global $PHORUM", because we do not want the loading of language
    // files to override our already loaded language file.
    $PHORUM = $GLOBALS['PHORUM'];

    // Sort all recipients that want a notification by their preferred language.
    $recipients_by_lang = array();
    foreach ($recipients as $recipient)
    {
        if ($recipient['pm_email_notify']) {
            if (!isset($recipients_by_lang[$recipient['user_language']])) {
                $recipients_by_lang[$recipient['user_language']] = array($recipient);
            } else {
                $recipients_by_lang[$recipient['user_language']][] = $recipient;
            }
        }
    }

    // No users found that want a notification? Then we are done.
    if (empty($recipients_by_lang)) return;

    // Build the data array for phorum_api_mail().
    $mail_data = array
    (
        // Template variables.
        'pm_message_id'  => $message['pm_message_id'],
        'author'         => phorum_api_user_get_display_name(
                                $message['user_id'],
                                $message['from_username'],
                                PHORUM_FLAG_PLAINTEXT
                            ),
        'subject'        => $message['subject'],
        'full_body'      => $message['message'],
        'plain_body'     => phorum_api_format_wordwrap(phorum_api_format_strip($message['message']), 72),
        'read_url'       => phorum_api_url_no_uri_auth(
                                PHORUM_PM_URL,
                                'page=read',
                                'pm_id=' . $message['pm_message_id']
                            ),

        // For the "mail_prepare" hook.
        'mailmessagetpl' => 'PMNotifyMessage',
        'mailsubjecttpl' => 'PMNotifySubject'
    );

    foreach ($recipients_by_lang as $language => $users)
    {
        $language = basename($language);

        if (file_exists(PHORUM_PATH."/include/lang/$language.php")) {
            $mail_data['language'] = $language;
            include PHORUM_PATH."/include/lang/$language.php";
        } else {
            $mail_data['language'] = $PHORUM['language'];
            include PHORUM_PATH."/include/lang/{$PHORUM['language']}.php";
        }

        $mail_data['mailmessage'] = $PHORUM['DATA']['LANG']['PMNotifyMessage'];
        $mail_data['mailsubject'] = $PHORUM['DATA']['LANG']['PMNotifySubject'];

        $addresses = array();
        foreach ($users as $user) {
            $addresses[] = $user['email'];
        }

        phorum_api_mail($addresses, $mail_data);
    }
}
// }}}

?>
