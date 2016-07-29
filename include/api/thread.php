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
 * This script implements the Phorum thread API.
 *
 * The thread API implements thread related functionality.
 *
 * @package    PhorumAPI
 * @subpackage Thread
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/tree.php';

// {{{ Function: phorum_api_thread_sort()
/**
 * Sort an array of forum messages into threads.
 *
 * There are a few template definitions that can influence the workings
 * of this module. These definitions are normally placed in the settings.tpl
 * for the active template. The possible definitions are:
 *
 * <ul>
 * <li>{DEFINE indentfactor <number>} (default 20)<br/>
 * <br/>
 *     The indention level for each message will be put in the field
 *     "indent_cnt". This define determines the factor that is used
 *     for translating an indent level to the indent_cnt.
 *     Example: when using {DEFINE indentmultiplier 10}, then the returned
 *     indent_cnt fields will be 0, 10, 20, etc. for indention levels
 *     0, 1, 2, etc.<br/><br/>
 * </li>
 * <li>{DEFINE subject_cut_min <number>} (default 20)</li>
 * <li>{DEFINE subject_cut_max <number>} (default 60)</li>
 * <li>{DEFINE subject_cut_indentfactor <number>} (default 2)<br/>
 * <br/>
 *     These definitions control the way in which long words in subjects
 *     are broken into pieces to prevent long words from messing up the
 *     layout. The longest allowed word is determined by subject_cut_max.
 *     Because of the indention that is applied to deeper indent levels,
 *     words can be less long when descending the message tree. The
 *     subject_cut_indentfactor is multiplied by the nesting level and the
 *     result is substracted from subject_cut_max to determine the maximum
 *     word length for deeper levels. When the computed maximum word length
 *     drops below subject_cut_min, then subject_cut_min is used instead.
 * </li>
 * </ul>
 *
 * @param array $messages
 *     An array of message data arrays.
 *
 * @return array
 *     An array, containing the messages in the order in which they
 *     appear in a threaded view, top to bottom. Each message has a field
 *     "indent_cnt" which indicates how much indention should be applied
 *     to to make it fit correctly in a graphical tree view.
 */
function phorum_api_thread_sort($messages)
{
    global $PHORUM;

    // Quick shortcut if we have no rows at all.
    if (count($messages) == 0) return $messages;

    // Get template defined settings values.
    // "indentmultiplier" is checked for backward compatibility.
    $indent_factor     = isset($PHORUM['TMP']['indentmultiplier'])
                       ? $PHORUM['TMP']['indentmultiplier'] : 20;
    if (isset($PHORUM['TMP']['indentfactor'])) {
        $indent_factor = $PHORUM['TMP']['indentfactor'];
    }
    $cut_min           = isset($PHORUM['TMP']['subject_cut_min'])
                       ? $PHORUM['TMP']['subject_cut_min'] : 20;
    $cut_max           = isset($PHORUM['TMP']['subject_cut_max'])
                       ? $PHORUM['TMP']['subject_cut_max'] : 60;
    $cut_indent_factor = isset($PHORUM['TMP']['subject_cut_indentfactor'])
                       ? $PHORUM['TMP']['subject_cut_indentfactor'] : 2;

    // Check if reverse threading is enabled. If this is the case, then
    // we want to apply reverse threading to indent level one and more.
    // This is because indent level zero is used by the thread starter
    // messages, which we want to add in the default order.
    $reverse_from_indent_level =
        empty($PHORUM['reverse_threading']) ? NULL : 1;

    // Use the Tree API to build threads.
    return phorum_api_tree_build(
        $messages,                   // The nodes to put in a tree
        0,                           // The root node id
        'message_id',                // The id field name
        'parent_id',                 // The parent id field name
        'thread',                    // The branch id field name
        $reverse_from_indent_level , // Sort descending from this level on
        $indent_factor,              // The indention multiplication factor
        'subject',                   // The field in which to cut long words
        $cut_min, $cut_max,          // The boundaries for the word cut length
        $cut_indent_factor           // For lower cut length at higher indent
    );
}
// }}}

// {{{ Function: phorum_api_thread_update_metadata()
/**
 * Update the meta data for a thread.
 *
 * The fields that are put in the thread starter data are:
 *
 * - threadviewcount: the sum of all view counts per message
 * - thread_count: the number of messages in the thread
 * - message_ids: an array of message_ids for all visible messages in a thread
 * - message_ids_moderator: an array of all message_ids in a thread
 * - modifystamp: time when the last visible message was posted in a thread
 * - recent_message_id: message_id of the most recent message
 * - recent_user_id: user_id of the author of the most recent message
 * - recent_author: the name of the author of the most recent message
 *
 * @param integer $thread_id
 *     The message_id of the thread starter message.
 */
function phorum_api_thread_update_metadata($thread_id)
{
    global $PHORUM;

    // Retrieve all messages for the thread from the database.
    $messages = $PHORUM['DB']->get_messages($thread_id, 0, 1, 1, FALSE);

    // We do not need the returned user info.
    unset($messages['users']);

    // Retrieve the thread starter message. If the starter message does
    // not exist, then we go back empty handed. This could happen when
    // a full thread is deleted from the database, in which case the
    // starter message is gone as well (when only one or more replies
    // are deleted, then this function is called to update the thread's
    // meta data).
    if (!isset($messages[$thread_id])) return;
    $thread = $messages[$thread_id];

    // Initialize the data that we will save at the end of this function.
    // This array will be filled during this function. The meta data is
    // added already, because we have to merge new data with the
    // existing data.
    $save = array('meta' => $thread['meta']);

    // For cleaning up pre-5.2 data. The recent_post meta data field
    // contents were moved to real fields in the messages table.
    unset($save['meta']['recent_post']);

    // Compute the threadviewcount, based on the individual message views.
    // This can be especially useful for updating the view counters after
    // enabling the view_count_per_thread option.
    // Additionally create a list of messages that are visible. These are
    // the messages that normal users can see. Admins and moderators
    // will always see all messages for a thread.
    $threadviewcount = 0;
    $visible_messages = array();
    foreach ($messages as $id => $message) {
        $threadviewcount += $message['viewcount'];
        if ($message['status'] > 0) {
            $visible_messages[$message['message_id']] = $message;
        }
    }
    $save['threadviewcount'] = $threadviewcount;

    // Determine the thread count.
    $thread_count = count($visible_messages);
    $save['thread_count'] = $thread_count;

    // Create a list of message_ids.
    $message_ids = array_keys($visible_messages);
    sort($message_ids, SORT_NUMERIC);
    $save['meta']['message_ids'] = $message_ids;

    // Create a list of message_ids for admin and moderator users.
    $message_ids_moderator = array_keys($messages);
    sort($message_ids_moderator, SORT_NUMERIC);
    $save['meta']['message_ids_moderator'] = $message_ids_moderator;

    // Find the most recent_message in the thread and keep track
    // of the time when that message was posted.
    $recent_message_id = 0;
    $modifystamp = $thread['datestamp'];
    foreach ($visible_messages as $message_id => $message)
    {
        if ($message['datestamp'] > $modifystamp ||
            ($message['datestamp'] == $modifystamp && $message_id > $recent_message_id)) {
          $modifystamp = $message['datestamp'];
          $recent_message_id = $message_id;
        }
    }

    // Update the thread's modifystamp according to the most recent
    // message's post time.
    $save['modifystamp'] = $modifystamp;

    // Retrieve the data for the most recent message.
    // If we have no recent message (happens if all messages are hidden)
    // then we take the thread starter message as the most recent message.
    $recent_message = $recent_message_id
                    ? $visible_messages[$recent_message_id]
                    : $thread;

    // Update the thread's recent message data.
    $save['recent_message_id'] = $recent_message['message_id'];
    $save['recent_user_id']    = $recent_message['user_id'];
    $save['recent_author']     = $recent_message['author'];

    if (!empty($PHORUM['cache_messages']))
    {
        // Cache the message index.
        // We can simply store the data here again. There is no need to
        // invalidate the cache, because this function is the only function
        // that fills the message index cache and it is called in any place
        // where we change something that is related to the thread.
        phorum_api_cache_put(
            'message_index',
            $thread['forum_id']."-$thread_id-1",
            $message_ids
        );
        phorum_api_cache_put(
            'message_index',
            $thread['forum_id']."-$thread_id-0",
            $message_ids_moderator
        );

        // We do invalidate the thread starter message though, because
        // filling the cache will be done from another part of the system.
        phorum_api_cache_remove('message', $thread['forum_id'].'-'.$thread_id);
    }

    $PHORUM['DB']->update_message($thread_id, $save);
}
// }}}

// {{{ Function: phorum_api_thread_set_sort()
/**
 * Set the sort type to use for a thread.
 *
 * Note: Historically, there were more sort types available in the Phorum core,
 * which is the reason why sticky threads aren't simply implemented using a
 * single is_sticky field or so.
 *
 * @param integer $thread_id
 *     The id of the thread to set the sort type for.
 *
 * @param integer $sort
 *     PHORUM_SORT_DEFAULT (default) to make the thread a default thread.
 *     PHORUM_SORT_STICKY to make the thread a sticky thread.
 */
function phorum_api_thread_set_sort($thread_id, $sort = PHORUM_SORT_DEFAULT)
{
    global $PHORUM;

    // Check the $sort parameter.
    if ($sort !== PHORUM_SORT_DEFAULT &&
        $sort !== PHORUM_SORT_STICKY) trigger_error(
        'phorum_api_thread_set_sort(): Illegal sort type provided',
        E_USER_ERROR
    );

    // Retrieve the messages for the provided thread id.
    $messages = $PHORUM['DB']->get_messages($thread_id, 0, TRUE, TRUE, FALSE);
    unset($messages['users']);

    // Update all messages with the new sort value.
    $forum_id = NULL;
    foreach ($messages as $id => $message)
    {
        if ($message['sort'] !== $sort)
        {
            $forum_id = $message['forum_id'];

            $PHORUM['DB']->update_message($id, array('sort' => $sort));

            if (!empty($PHORUM['cache_messages']))
            {
                $cache_key = $PHORUM["forum_id"] . "-" . $message["message_id"];
                phorum_api_cache_remove('message', $cache_key);
            }
        }
    }

    // Update the forum statistics to update the sticky_count.
    if ($forum_id !== NULL)
    {
        $tmp = $PHORUM['forum_id'];
        $PHORUM['forum_id'] = $forum_id;
        $PHORUM['DB']->update_forum_stats(true);
        $PHORUM['forum_id'] = $tmp;
    }
}
// }}}

?>
