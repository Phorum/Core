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
 * This script implements the Atom output adapter for Phorum.
 *
 * @package    PhorumAPI
 * @subpackage Feed
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

/**
 * This function implements the Atom output adapter for the Feed API.
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
 *     - The generated feed data (Atom XML).
 *     - The Content-Type header to use for the feed.
 */
function phorum_api_feed_atom($messages, $forums, $url, $title, $description, $replies)
{
    global $PHORUM;

    $selfurl     = phorum_api_format_htmlspecialchars(phorum_api_url_current());
    $url         = phorum_api_format_htmlspecialchars($url);
    $title       = phorum_api_format_htmlspecialchars($title);
    $description = phorum_api_format_htmlspecialchars($description);
    $builddate   = phorum_api_format_htmlspecialchars(date('r'));
    $generator   = phorum_api_format_htmlspecialchars('Phorum '.PHORUM);

    $buffer = "<?xml version=\"1.0\" encoding=\"{$PHORUM['DATA']['CHARSET']}\"?>\n";
    $buffer.= "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";
    $buffer.= " <title>$title</title>\n";
    $buffer.= " <subtitle>$description</subtitle>\n";
    $buffer.= " <link rel=\"self\" href=\"$selfurl\" />\n";
    $buffer.= " <id>$url</id>\n";
    $buffer.= " <updated>$builddate</updated>\n";
    $buffer.= " <generator>$generator</generator>\n";

    // Lookup the plain text usernames for the authenticated authors.
    $users = $messages['users'];
    unset($messages['users']);
    unset($users[0]);
    $users = phorum_api_user_get_display_name($users, '', PHORUM_FLAG_PLAINTEXT);

    foreach ($messages as $message)
    {
        // Include information about the number of replies to threads.
        $title = strip_tags($message['subject']);
        if (!$replies)
        {
            $lang = $PHORUM['DATA']['LANG'];
            switch ($message['thread_count'])
            {
                case 1: $title .= " ({$lang['noreplies']})"; break;
                case 2: $title .= " (1 {$lang['reply']})"; break;
                default:
                    $replies = $message['thread_count'] - 1;
                    $title .= " ($replies {$lang['replies']})";
            }
        }

        // Publish date.
        $published = date('r', $message['datestamp']);

        // Updated date.
        if ($message['parent_id']) {
            if (!empty($message['meta']['edit_date'])) {
                $updated = date('r', $message['meta']['edit_date']);
            } else {
                $updated = $published;
            }
        } else {
            $updated = date('r', $message['modifystamp']);
        }

        // Generate the URL for reading the message.
        $url = phorum_api_format_htmlspecialchars(phorum_api_url(
            PHORUM_FOREIGN_READ_URL,
            $message["forum_id"], $message["thread"], $message["message_id"]
        ));

        // The forum in which the message is stored is used as the category.
        $category = phorum_api_format_htmlspecialchars(
            $forums[$message['forum_id']]['name']
        );

        // Format the author.
        $author = !empty($users[$message['user_id']])
                ? $users[$message['user_id']]
                : $message['author'];
        $author = phorum_api_format_htmlspecialchars($author);

        // Strip unprintable characters from the message body.
        $body = strtr($message['body'],
            "\001\002\003\004\005\006\007\010\013\014\016\017\020\021" .
            "\022\023\024\025\026\027\030\031\032\033\034\035\036\037",
            "????????????????????????????"
        );

        $buffer.= " <entry>\n";
        $buffer.= "  <title type=\"html\">$title</title>\n";
        $buffer.= "  <link href=\"$url\" />\n";
        $buffer.= "  <category term=\"$category\" />\n";
        $buffer.= "  <published>$published</published>\n";
        $buffer.= "  <updated>$updated</updated>\n";
        $buffer.= "  <id>$url</id>\n";
        $buffer.= "  <author>\n";
        $buffer.= "  <name>$author</name>\n";
        $buffer.= "  </author>\n";
        $buffer.= "  <summary type=\"html\"><![CDATA[$body]]></summary>\n";
        $buffer.= " </entry>\n";
    }

    $buffer.= "</feed>\n";

    return array($buffer, 'application/xml');
}

?>
