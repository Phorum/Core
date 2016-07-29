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
 * This script implements sending a forum message to the moderator(s) that
 * have to moderate the new message.
 *
 * @package    PhorumAPI
 * @subpackage Mail
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/mail.php';

// {{{ Function: phorum_api_mail_message_moderate()
/**
 * Send a new forum message for moderation to the moderator(s).
 *
 * @param array $message
 *     An array containing the data for a forum message.
 */
function phorum_api_mail_message_moderate($message)
{
    // Not "global $PHORUM", because we do not want the loading of language
    // files to override our already loaded language file.
    $PHORUM = $GLOBALS['PHORUM'];

    // Retrieve the list of moderators for the current forum.
    $moderators = phorum_api_user_list_moderators(
        $PHORUM['forum_id'],
        $PHORUM['email_ignore_admin'],
        TRUE
    );

    // The list moderators function returns user_id => mail address.
    // We want the full user info, so we can lookup the preferred
    // language for the moderators.
    $moderators = phorum_api_user_get(array_keys($moderators));

    // Sort all moderators by their preferred language.
    $recipients = array();
    foreach ($moderators as $moderator)
    {
        if (!isset($recipients[$moderator['user_language']])) {
            $recipients[$moderator['user_language']] = array($moderator['email']);
        } else {
            $recipients[$moderator['user_language']][] = $moderator['email'];
        }
    }

    // No moderators (oomph)? Then we are done.
    if (empty($recipients)) return;

    if ($message['status'] > 0) {
        $mailsubjecttpl = 'NewUnModeratedSubject';
        $mailmessagetpl = 'NewUnModeratedMessage';
    } else {
        $mailsubjecttpl = 'NewModeratedSubject';
        $mailmessagetpl = 'NewModeratedMessage';
    }

    $mail_data = array
    (
        // Template variables
        'forumname'   => strip_tags($PHORUM['DATA']['NAME']),
        'forum_id'    => $message['forum_id'],
        'message_id'  => $message['message_id'],
        'author'      => phorum_api_user_get_display_name(
                             $message['user_id'],
                             $message['author'],
                             PHORUM_FLAG_PLAINTEXT
                         ),
        'subject'     => $message['subject'],
        'fully_body'  => $message['body'],
        'plain_body'  => phorum_api_format_wordwrap(phorum_api_format_strip($message['body']), 72),
        'approve_url' => phorum_api_url_no_uri_auth(
                             PHORUM_CONTROLCENTER_URL,
                             'panel=messages'
                         ),
        'read_url'    => phorum_api_url_no_uri_auth(
                             PHORUM_READ_URL,
                             $message['thread'],
                             $message['message_id']
                         ),

        // For the "mail_prepare" hook.
        'mailmessagetpl' => $mailmessagetpl,
        'mailsubjecttpl' => $mailsubjecttpl
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

        $mail_data['mailmessage'] = $PHORUM['DATA']['LANG'][$mailmessagetpl];
        $mail_data['mailsubject'] = $PHORUM['DATA']['LANG'][$mailsubjecttpl];

        phorum_api_mail($addresses, $mail_data);
    }
}
// }}}

?>
