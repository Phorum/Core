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
 * This script implements the HTML output adapter for Phorum.
 *
 * @package    PhorumAPI
 * @subpackage Feed
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_feed_html()
/**
 * This function implements the HTML output adapter for the Feed API.
 *
 * @param array $messages
 *     An array of messages to include in the feed.
 *
 * @param array $forums
 *     An array of related forums.
 *
 * @param string $url
 *     The URL that points to the feed's target.
 *
 * @param string $title
 *     The title to use for the feed.
 *
 * @param string $description
 *     The description to use for the feed.
 *
 * @param bool $replies
 *     Whether or not this is a feed that includes reply messages.
 *     If not, then it will only contain thread starter messages.
 *
 * @return array
 *     An array containing two elements:
 *     - The generated feed data (HTML code).
 *     - The Content-Type header to use for the feed.
 */
function phorum_api_feed_html($messages, $forums, $url, $title, $description, $replies)
{
    global $PHORUM;

    $url         = phorum_api_format_htmlspecialchars($url);
    $title       = phorum_api_format_htmlspecialchars($title);
    $description = phorum_api_format_htmlspecialchars($description);
    $builddate   = phorum_api_format_htmlspecialchars(date('r'));

    $buffer = "<div id=\"phorum_feed\">\n";
    $buffer.= " <div id=\"phorum_feed_title\">\n";
    $buffer.= "  <a href=\"$url\" title=\"$description\">$title</a>\n";
    $buffer.= " </div>\n";
    $buffer.= " <div id=\"phorum_feed_date\">$builddate</div>\n";
    $buffer.= " <ul>\n";

    unset($messages['users']);

    foreach($messages as $message)
    {
        $title = phorum_api_format_htmlspecialchars(strip_tags($message["subject"]));
        if (!$replies)
        {
            $lang = $PHORUM['DATA']['LANG'];
            switch($message['thread_count']){
                case 1: $title.= " (no {$lang['replies']})"; break;
                case 2: $title.= " (1 {$lang['reply']})"; break;
                default:
                    $replies = $message['thread_count'] - 1;
                    $title.= " ($replies {$lang['replies']})";
            }

        }

        $url = phorum_api_format_htmlspecialchars(phorum_api_url(
            PHORUM_FOREIGN_READ_URL,
            $message["forum_id"], $message["thread"], $message["message_id"]
        ));

        $body = substr(phorum_api_format_htmlspecialchars(
            phorum_api_format_strip($message['body'])
        ), 0, 200);

        $buffer.= "  <li><a href=\"$url\" title=\"$body\">$title</a></li>\n";
    }

    $buffer.= " </ul>\n";
    $buffer.= "</div>\n";

    return array($buffer, 'text/html');
}
// }}}

?>
