<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
 * This script implements the newflags API.
 *
 * Phorum's newflags system keeps track of unread messages for registered
 * users. It does so by administering the messages that the users have read
 * in the database. This is done per forum. All messages prior to the oldest
 * message that is marked read for the forum are considered read implicitly.
 *
 * @package    PhorumAPI
 * @subpackage NewflagsAPI
 * @copyright  2008, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Constants
/**
 * Function call flag, which tells {@link phorum_api_newflags_format_messages}
 * that the newflags have to be processed in threaded mode. This means that the
 * newflag will be set for thread starter messages in the message list that
 * have at least one new message in their thread.
 */
define('PHORUM_NEWFLAGS_BY_THREAD', 1);

/**
 * Function call flag, which tells {@link phorum_api_newflags_format_messages}
 * that the newflags have to be processed in single message mode. This means
 * that the newflag will be set for all messages that are new.
 */
define('PHORUM_NEWFLAGS_BY_MESSAGE', 2);

/**
 * Function call flag, which tells {@link phorum_api_newflags_format_messages}
 * that the newflags have to be added in single message mode (see
 * {@link PHORUM_NEWFLAGS_MESSAGE}, except for sticky messages, which have
 * to be added in threaded mode. This mode is useful for the list page,
 * where sticky threads are always displayed collapsed, even if the list page
 * view is threaded.
 */
define('PHORUM_NEWFLAGS_BY_MESSAGE_EXSTICKY', 3);

/**
 * Function call flag, which tells {@link phorum_api_newflags_markread()}
 * that a single messages have to be marked read. }
 */
define('PHORUM_MARKREAD_MESSAGES', 1);

/**
 * Function call flag, which tells {@link phorum_api_newflags_markread()}
 * that threads have to be marked read. }
 */
define('PHORUM_MARKREAD_THREADS', 2);

/**
 * Function call flag, which tells {@link phorum_api_newflags_markread()}
 * that full forums have to be marked read. }
 */
define('PHORUM_MARKREAD_FORUMS', 3);
// }}}

// {{{ Function: phorum_api_newflags_by_forum()
/**
 * Retrieve newflags data for a forum for the active Phorum user.
 *
 * This is mainly an internal helper function, which normally is
 * called from other Phorum core code. There should be no need for
 * you to call it from other code.
 *
 * @param mixed $forum
 *     Either a forum_id or a forum data array, containing at least the fields
 *     forum_id and cache_version.
 *
 * @return mixed
 *     The newflags data array for the forum or NULL if no newflags
 *     are available for that forum.
 */
function phorum_api_newflags_by_forum($forum)
{
    global $PHORUM;

    // No newflags for anonymous users.
    if (!$PHORUM['user']['user_id']) return NULL;

    // If a forum_id was provided as the argument, then load the forum info.
    if (!is_array($forum)) {
        $forums = phorum_db_get_forums($forum);
        if (empty($forums)) trigger_error(
            'phorum_api_newflags_by_forum(): unknown forum_id ' . $forum
        );
        $forum = $forums[$forum];
    }

    // Check the input data.
    if (!is_array($forum) ||
        !isset($forum['forum_id']) ||
        !isset($forum['cache_version'])) {
        trigger_error(
            'phorum_api_newflags_by_forum(): illegal argument; no forum info ' .
            'or either one of "forum_id" or "cache_version" is ' .
            'missing in the data.'
        );
        return NULL;
    }
    $forum_id = (int) $forum['forum_id'];
    $cache_version = $forum['cache_version'];

    // Initialize call time newflags info cache.
    if (!isset($PHORUM['user']['newflags'])) {
        $PHORUM['user']['newflags'] = array();
    }

    // First, try to retrieve a cached version of the newflags.
    if (!isset($PHORUM['user']['newflags'][$forum_id]))
    {
        $PHORUM['user']['newflags'][$forum_id] = NULL;
        if ($PHORUM['cache_newflags']) {
            $cachekey = $forum_id.'-'.$PHORUM['user']['user_id'];
            $PHORUM['user']['newflags'][$forum_id] = phorum_cache_get(
                'newflags', $cachekey, $cache_version
            );
        }
    }

    // No cached data found? Then retrieve the newflags from the database.
    if ($PHORUM['user']['newflags'][$forum_id] === NULL)
    {
        $PHORUM['user']['newflags'][$forum_id] =
            phorum_db_newflag_get_flags($forum_id);

        if ($PHORUM['cache_newflags']) {
            phorum_cache_put(
                'newflags', $cachekey,
                $PHORUM['user']['newflags'][$forum_id],
                86400, $cache_version
            );
        }
    }

    return $PHORUM['user']['newflags'][$forum_id];
}
// }}}

// {{{ Function: phorum_api_newflags_format_messages()
/**
 * Add newflag info for the active Phorum user to a list of messages.
 *
 * In the message data for messages that should have the new flag enabled,
 * a field $msg["new"] is added. This field is initialized to the language
 * variable {LANG->newflag}.
 *
 * @param array $messages
 *     An array of messages to process.
 *
 * @param integer $mode
 *     The mode in which to add newflags. Possible values are:
 *     {@link PHORUM_NEWFLAGS_BY_THREAD},
 *     {@link PHORUM_NEWFLAGS_BY_MESSAGE},
 *     {@link PHORUM_NEWFLAGS_BY_MESSAGE_EXSTICKY}
 *
 * @param boolean $fullcount
 *     This parameter only acts on newflags that are processed by thread.
 *     If it is set to a true value, then instead of checking if there
 *     is any new message in a thread, the function will count how many
 *     new messages are available exactly. This total count will be
 *     put in the variable $msg["new_count"].
 *
 * @return array $messages
 *     The possibly modified array of messages.
 */
function phorum_api_newflags_format_messages($messages, $mode = PHORUM_NEWFLAGS_BY_MESSAGE, $fullcount = FALSE)
{
    global $PHORUM;

    // No newflags for anonymous users.
    if (!$PHORUM['user']['user_id']) return $messages;

    // Fetch info about the available forums.
    $forums = phorum_db_get_forums(NULL, NULL, $PHORUM['vroot']);

    foreach ($messages as $id => $message)
    {
        // Do not handle newflags for moved message notifications.
        if (!empty($message['move'])) continue;

        // Find the info for the message's forum.
        $forum_id = $message['forum_id'];
        if (!isset($forums[$forum_id])) continue;
        $forum = $forums[$forum_id];

        // Fetch the user's newflags for the message's forum.
        if (!isset($PHORUM['user']['newflags'][$forum_id])) {
            $newflags = phorum_api_newflags_by_forum($forum);
        } else {
            $newflags = $PHORUM['user']['newflags'][$forum_id];
        }
        if (empty($newflags)) continue;

        $new = 0;
        if ($mode == PHORUM_NEWFLAGS_BY_THREAD ||
            ($mode == PHORUM_NEWFLAGS_BY_MESSAGE_EXSTICKY &&
             $message['sort'] == PHORUM_SORT_STICKY))
        {
            // Is this really a thread starter message?
            if (empty($message['meta']['message_ids'])) continue;

            // Check for new messages in the thread.
            foreach ($message['meta']['message_ids'] as $mid) {
                if (!isset($newflags[$mid]) && $mid > $newflags['min_id']) {
                    $new++;
                    if (!$fullcount) break;
                }
            }
        }
        else // PHORUM_NEWFLAGS_BY_MESSAGE
        {
            $mid = $message['message_id'];
            if (!isset($newflags[$mid]) && $mid > $newflags['min_id']) {
                $new++;
            }
        }

        // Add newflag information to the message if needed.
        if ($new) {
            $messages[$id]['new'] = $PHORUM['DATA']['LANG']['newflag'];
            if ($fullcount) $messages[$id]['new_count'] = $new;
        }
    }

    return $messages;
}
// }}}

// {{{ Function: phorum_api_newflags_firstunread()
/**
 * Find the first unread message in a thread for the active Phorum user.
 *
 * @param integer $thread_id
 *     The message_id of the thread for which to find the first unread message.
 *
 * @return integer
 *     The message_id of the first unread message or 0 if the message_id
 *     cannot be determined. If all messages in the thread are read, then
 *     this function will return the last message_id of the thread instead.
 */
function phorum_api_newflags_firstunread($thread_id)
{
    // Lookup the thread's information.
    $thread = phorum_db_get_message($thread_id);
    if (!$thread) return 0;

    // Retrieve the newflags for the forum.
    $newflags = phorum_api_newflags_by_forum($thread['forum_id']);
    if (empty($newflags)) return 0;

    // Find the first unread message.
    // We also keep track of the last id, which we will return in case
    // all messages in the thread are already read.
    $last_id = 0;
    $first_unread_id = 0;
    foreach ($thread['meta']['message_ids'] as $mid) {
        if ($last_id < $mid) $last_id = $mid;
        if ((!$first_unread_id || $first_unread_id > $mid) &&
            !isset($newflags[$mid]) && $mid > $newflags['min_id']) {
            $first_unread_id = $mid;
        }
    }

    return $first_unread_id ? $first_unread_id : $last_id;
}
// }}}

// {{{ Function: phorum_api_newflags_markread()
/**
 * Mark forums, threads or messages as read for the active Phorum user.
 *
 * @param mixed $markread_ids
 *     This parameter provides the ids of the items that have to be marked
 *     read. It can be either a single item id (depending on the $mode
 *     parameter either message_id, thread_id or forum_id) or an array
 *     of item ids.
 *
 * @param integer $mode
 *     This determines whether messages, threads or forums are marked
 *     read. Possible values for this parameter are:
 *     {@link PHORUM_MARKREAD_MESSAGES},
 *     {@link PHORUM_MARKREAD_THREADS},
 *     {@link PHORUM_MARKREAD_FORUMS}
 */
function phorum_api_newflags_markread($markread_ids, $mode = PHORUM_MARKREAD_MESSAGES)
{
    global $PHORUM;

    // No newflags for anonymous users.
    if (!$PHORUM['user']['user_id']) return $messages;

    // Make sure that the $markread_ids parameter is an array of integers.
    if (!is_array($markread_ids)) {
        $markread_ids = array((int) $markread_ids);
    } else {
        foreach ($markread_ids as $key => $val) {
            $markread_ids[$key] = (int) $val;
        }
    }

    // An array to keep track of the forums for which we need to invalidate
    // the cache later on.
    $processed_forum_ids = array();

    // Handle marking forums read.
    if ($mode == PHORUM_MARKREAD_FORUMS)
    {
        foreach ($markread_ids as $forum_id)
        {
            phorum_db_newflag_allread($forum_id);
            $processed_forum_ids[$forum_id] = $forum_id;
        }
    }
    // Handle marking threads read.
    elseif ($mode == PHORUM_MARKREAD_THREADS)
    {
        // Retrieve the data for the threads to mark read.
        $threads = phorum_db_get_message($markread_ids, 'message_id', TRUE);

        // Process the threads.
        $markread = array();
        foreach ($threads as $thread)
        {
            // In case this was no thread or broken thread data.
            if ($thread['parent_id'] != 0 ||
                empty($thread['meta']['message_ids'])) continue;

            // Fetch the user's newflags for the thread's forum, so we
            // can limit the messages to mark read to the actual unread
            // messages in the thread.
            $forum_id = $thread['forum_id'];
            if (!isset($PHORUM['user']['newflags'][$forum_id])) {
                $newflags = phorum_api_newflags_by_forum($forum_id);
            } else {
                $newflags = $PHORUM['user']['newflags'][$forum_id];
            }

            // Find out what message_ids are unread in the thread.
            // If we have no newflags for the forum (yet), then consider
            // all the messages in the thread as new.
            $markread = array();
            foreach ($thread['meta']['message_ids'] as $mid) {
                if (empty($newflags) ||
                    (!isset($newflags[$mid]) && $mid > $newflags['min_id'])) {
                    $markread[] = array(
                        'id'    => $mid,
                        'forum' => $forum_id
                    );
                }
            }

            $processed_forum_ids[$forum_id] = $forum_id;
        }

        // Mark the messages in the thread(s) as read.
        phorum_db_newflag_add_read($markread);
    }

    // Handle marking messages read.
    elseif ($mode == PHORUM_MARKREAD_MESSAGES)
    {
        // Retrieve the data for the messages to mark read.
        $messages = phorum_db_get_message($markread_ids);

        // Process the messages.
        $markread = array();
        foreach ($messages as $message)
        {
            $markread[] = array(
                'id'    => $message['message_id'],
                'forum' => $message['forum_id']
            );

            $processed_forum_ids[$message['forum_id']] = $message['forum_id'];
        }

        // Mark the messages read in the database.
        phorum_db_newflag_add_read($markread);
    }

    // Invalidate cached forum newflags data.
    foreach ($processed_forum_ids as $forum_id)
    {
        unset($PHORUM['user']['newflags'][$forum_id]);
        if ($PHORUM['cache_newflags'])
        {
            $cachekey = $forum_id.'-'.$PHORUM['user']['user_id'];
            phorum_cache_remove('newflags',$cachekey);
            phorum_cache_remove('newflags_index',$cachekey);
        }
    }
}
// }}}

?>
