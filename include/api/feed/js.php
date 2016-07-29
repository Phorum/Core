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
 * This script implements the JavaScript output adapter for Phorum.
 *
 * @package    PhorumAPI
 * @subpackage Feed
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/json.php';

/**
 * This function implements the JavaScript output adapter for the Feed API.
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
 *     - The generated feed data (JavaScript code)
 *     - The Content-Type header to use for the feed.
 */
function phorum_api_feed_js($messages, $forums, $url, $title, $description, $replies)
{
    global $PHORUM;

    $feed = array(
        'title'       => $title,
        'description' => $description,
        'modified'    => phorum_api_format_date($PHORUM['short_date'], time())
    );

    // Lookup the plain text usernames for the authenticated authors.
    $users = $messages['users'];
    unset($messages['users']);
    unset($users[0]);
    $users = phorum_api_user_get_display_name($users, '', PHORUM_FLAG_PLAINTEXT);

    foreach ($messages as $message)
    {
        $author = !empty($users[$message['user_id']])
                ? $users[$message['user_id']] : $message['author'];

        // Created date.
        $fmt = $PHORUM['short_date'];
        $created = phorum_api_format_date($fmt, $message['datestamp']);

        // Updated date.
        if ($message['parent_id']) {
            if (!empty($message['meta']['edit_date'])) {
                $modified = $message['meta']['edit_date'];
            } else {
                $modified = $message['datestamp'];
            }
        } else {
            $modified = $message['modifystamp'];
        }
        $modified = phorum_api_format_date($fmt, $modified);


        $url = htmlspecialchars(phorum_api_url(
            PHORUM_FOREIGN_READ_URL,
            $message['forum_id'], $message['thread'], $message['message_id']
        ));

        $item = array(
            'title'       => strip_tags($message['subject']),
            'author'      => $author,
            'category'    => $forums[$message['forum_id']]['name'],
            'created'     => $created,
            'modified'    => $modified,
            'url'         => $url,
            'description' => $message['body']
        );

        if ($message["thread_count"]) {
            $replies = $message["thread_count"] - 1;
            $item["replies"] = $replies;
        }

        $feed["items"][] = $item;
    }

    // this is where we convert the array into js
    $buffer = 'phorum_feed = ' . phorum_api_json_encode($feed);

    return array($buffer, 'text/javascript');
}

?>
