<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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
 * This script implements messages formatting.
 *
 * @package    PhorumAPI
 * @subpackage Formatting
 * @copyright  2011, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/format/censor.php';
require_once PHORUM_PATH.'/include/api/newflags.php';

// {{{ Function: phorum_api_format_messages()
/**
 * This function handles preparing message data for use in the templates.
 *
 * @param array $messages
 *     An array of messages that have to be formatted.
 *     Each message is an array on its own, containing the message data.
 *
 * @param array $author_specs
 *     By default, the formatting function will create author info
 *     data, based on the fields "user_id", "author" and "email".
 *     This will create $messages["URL"]["PROFILE"] if needed (either pointing
 *     to a user profile for registered users or the email address of
 *     anonymous users that left an email address in the forum) and will
 *     do formatting on the "author" field.
 *
 *     By providing extra $author_specs, this formatting can be done on
 *     more author fields. This argument should be an array, containing
 *     arrays with five fields:
 *
 *     - the name of the field that contains a user_id
 *     - the name of the field that contains the name of the author
 *     - the name of the field that contains the email address
 *       (can be NULL if none available)
 *     - the name of the field to store the author name in
 *     - the name of the URL field to store the profile/email link in
 *
 *     For the default author field handling like described above,
 *     this array would be:
 *
 *     array("user_id", "author", "email", "author", "PROFILE");
 *
 * @return data - The formatted messages.
 */
function phorum_api_format_messages($messages, $author_specs = NULL)
{
    global $PHORUM;

    // Prepare author specs.
    if ($author_specs === NULL) $author_specs = array();
    $author_specs[] = array("user_id","author","email","author","PROFILE");

    // Prepare censoring replacements.
    list ($censor_search, $censor_replace) = phorum_api_format_censor_compile();

    // Prepare the profile URL template. This is used to prevent
    // having to call the phorum_api_url() function over and over again.
    $profile_url_template = phorum_api_url(PHORUM_PROFILE_URL, '%spec_data%');

    // A special <br> tag to keep track of breaks that are added by phorum.
    $phorum_br = '<phorum break>';

    // Apply Phorum's formatting rules to all messages.
    foreach($messages as $id => $message)
    {
        // Normally, the message_id must be set, since we should be handling
        // message data. It might not be set however, because sometimes
        // the message formatting is called using some fake message data
        // for formatting something else than a message.
        if (!isset($message['message_id'])) {
            $messages[$id]['message_id'] = $message['message_id'] = $id;
        }

        // -----------------------------------------------------------------
        // Message body
        // -----------------------------------------------------------------

        if (isset($message['body']) && $message['body'] != '')
        {
            $body = $message["body"];

            // Convert legacy <...> URLs into bare URLs.
            $body = preg_replace(
                "/<(
                    (?:http|https|ftp):\/\/
                    [a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%]+?
                  )>/xi", "$1", $body
            );

            // Escape special HTML characters.
            $escaped_body = phorum_api_format_htmlspecialchars($body);

            // When there is a charset mismatch between the database
            // and the language file, then bodies might get crippled
            // because of the htmlspecialchars() call. Here we try to
            // correct this issue. It's not perfect, but we do what
            // we can ...
            if ($escaped_body == '')
            {
                if (function_exists("iconv")) {
                    // We are gonna guess and see if we get lucky.
                    $escaped_body = iconv(
                        "ISO-8859-1", $PHORUM["DATA"]["HCHARSET"], $body);
                } else {
                    // We let htmlspecialchars use its defaults.
                    $escaped_body = htmlspecialchars($body);
                }
            }

            $body = $escaped_body;

            // Replace newlines with $phorum_br temporarily.
            // This way the mods know what breaks were added by
            // Phorum and what breaks by the user.
            $body = str_replace("\n", "$phorum_br\n", $body);

            // Censor bad words in the body.
            if ($censor_search !== NULL) {
                $body = preg_replace($censor_search, $censor_replace, $body);
            }

            $messages[$id]['body'] = $body;
        }

        // -----------------------------------------------------------------
        // Message subject
        // -----------------------------------------------------------------

        // Censor bad words in the subject.
        if (isset($message['subject']) && $censor_search !== NULL) {
            $messages[$id]['subject'] = preg_replace(
                $censor_search, $censor_replace, $message['subject']
            );
        }

        // Escape special HTML characters.
        if (isset($message['subject'])) {
            $messages[$id]['subject'] = phorum_api_format_htmlspecialchars($messages[$id]['subject']);
        }

        // -----------------------------------------------------------------
        // Message author
        // -----------------------------------------------------------------

        // Escape special HTML characters in the email address.
        if (isset($message['email'])) {
            $messages[$id]['email'] = phorum_api_format_htmlspecialchars($message['email']);
        }

        // Do author formatting for all provided author fields.
        foreach ($author_specs as $spec)
        {
            // Use "Anonymous user" as the author name if there's no author
            // name available for some reason.
            if (!isset($message[$spec[1]]) || $message[$spec[1]] == '')
            {
                $messages[$id][$spec[3]] =
                    $PHORUM["DATA"]["LANG"]["AnonymousUser"];
            }
            // Author info for a registered user.
            elseif (!empty($message[$spec[0]]))
            {
                $url = str_replace(
                    '%spec_data%', $message[$spec[0]], $profile_url_template);

                $messages[$id]["URL"][$spec[4]] = $url;
                $messages[$id][$spec[3]] =
                    (empty($PHORUM["custom_display_name"])
                     ? phorum_api_format_htmlspecialchars($message[$spec[1]])
                     : $message[$spec[1]]);
            }
            // For an anonymous user that left an email address.
            // We only show the address if addresses aren't hidden globally,
            // if the active user is an administrator or if the active user
            // is a moderator with the PHORUM_MOD_EMAIL_VIEW constant enabled.
            elseif ($spec[2] !== NULL &&
                    !empty($message[$spec[2]]) &&
                    (empty($PHORUM['hide_email_addr']) ||
                     !empty($PHORUM["user"]["admin"]) ||
                     phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES) && PHORUM_MOD_EMAIL_VIEW ||
                     phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_USERS) && PHORUM_MOD_EMAIL_VIEW) )
            {
                $messages[$id][$spec[3]] = phorum_api_format_htmlspecialchars($message[$spec[1]]);
                $email_url = phorum_api_format_html_encode("mailto:".$message[$spec[2]]);
                $messages[$id]["URL"]["PROFILE"] = $email_url;
            }
            // For an anonymous user that did not leave an e-mail address.
            else {
                $messages[$id][$spec[3]] = phorum_api_format_htmlspecialchars($message[$spec[1]]);
            }

            if ($censor_search !== NULL) {
                $messages[$id][$spec[3]] = preg_replace(
                    $censor_search, $censor_replace, $messages[$id][$spec[3]]
                );
            }
        }
    }

    // A hook for module writers to apply custom message formatting.
    if (isset($PHORUM["hooks"]["format"])) {
        $messages = phorum_api_hook("format", $messages);
    }

    // A hook for module writers for doing post formatting fixups.
    if (isset($PHORUM["hooks"]["format_fixup"])) {
        $messages = phorum_api_hook("format_fixup", $messages);
    }

    // Clean up after the mods are done.
    foreach ($messages as $id => $message)
    {
        // Clean up line breaks inside pre and xmp tags. These tags
        // take care of showing newlines as breaks themselves.
        if (isset($message['body']) && $message['body'] != '')
        {
            foreach (array('pre','goep','xmp') as $tagname) {
                if (preg_match_all("/(<$tagname.*?>).+?(<\/$tagname>)/si",
                                   $message['body'], $matches)) {
                    foreach ($matches[0] as $match) {
                        $stripped = str_replace ($phorum_br, '', $match);
                        $message['body'] = str_replace(
                            $match, $stripped, $message['body']
                        );
                    }
                }
            }

            // Remove line break after div, quote and code tags. These
            // tags have their own line break. Without this, there would
            // be to many white lines.
            $message['body'] = preg_replace(
                "/\s*(<\/?(?:div|xmp|blockquote|pre)[^>]*>)\s*\Q$phorum_br\E/",
                '$1', $message['body']
            );

            // Normalize the Phorum line breaks that are left.
            $messages[$id]['body'] = str_replace(
                $phorum_br, "<br />", $message['body']
            );
        }
    }

    return $messages;
}
// }}}

?>
