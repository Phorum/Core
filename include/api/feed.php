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
 * This script implements syndication feeds for Phorum.
 * There are multiple output adapters available for generating
 * various output formats (currently RSS, Atom, HTML en JavaScript).
 *
 * @package    PhorumAPI
 * @subpackage Feed
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/format/messages.php';

// {{{ Constant definition

/**
 * Function call flag, which tells {@link phorum_api_feed()} that
 * a feed has to be generated for all (readable) forums in the
 * current (v)root.
 */
define('PHORUM_FEED_VROOT', 0);

/**
 * Function call flag, which tells {@link phorum_api_feed()} that
 * a feed has to be generated for a single forum.
 */
define('PHORUM_FEED_FORUM', 1);

/**
 * Function call flag, which tells {@link phorum_api_feed()} that
 * a feed has to be generated for a single thread.
 */
define('PHORUM_FEED_THREAD', 2);

// }}}

// {{{ Function: phorum_api_feed()
/**
 * Collect the data that is used for the feed and pass this information
 * on to the requested output adapter.
 *
 * @param string $adapter
 *     The output adapter to use. The adapters that are available are:
 *     - rss
 *     - atom
 *     - html
 *     - js
 *
 * @param integer $source_type
 *     The type of source. This is one of:
 *     - {@link PHORUM_FEED_VROOT}
 *     - {@link PHORUM_FEED_FORUM}
 *     - {@link PHORUM_FEED_THREAD}
 *
 * @param integer $id
 *     This parameter has a different meaning for each $source_type:
 *     - For {@link PHORUM_FEED_VROOT}: the forum_id of the (v)root.
 *     - For {@link PHORUM_FEED_FORUM}: the forum_id of the forum.
 *     - For {@link PHORUM_FEED_THREAD}: the message_id of the thread.
 *
 * @param integer $count
 *     The number of messages to include in the feed.
 *
 * @param boolean $replies
 *     TRUE to include reply messages in the feed, FALSE otherwise.
 *     This parameter is forced to TRUE for {@link PHORUM_FEED_THREAD}.
 */
function phorum_api_feed($adapter, $source_type, $id, $count, $replies)
{
    global $PHORUM;

    settype($id, 'int');
    settype($count, 'int');
    settype($source_type, 'int');
    $replies = $replies ? 1 : 0;

    $adapter = basename($adapter);
    if (!preg_match('/^[a-z][\w_]*$/', $adapter)) trigger_error(
        'phorum_api_feed(): Illegal feed adapter name ' .
        '"'.htmlspecialchars($adapter).'" used',
        E_USER_ERROR
    );
    if (!file_exists(PHORUM_PATH.'/include/api/feed/'.$adapter.'.php')) {
        trigger_error(
            'phorum_api_feed(): Unknown feed adapter ' .
            '"'.htmlspecialchars($adapter).'" used',
            E_USER_ERROR
        );
    }

    // ----------------------------------------------------------------------
    // Prepare the data for the requested feed type
    // ----------------------------------------------------------------------

    // Prepare data for handling a vroot feed.
    if ($source_type === PHORUM_FEED_VROOT)
    {
        $forums = phorum_api_forums_by_vroot($id);
        $thread_id = NULL;
        $forum_ids = array_keys($forums);
        $cache_part = implode(',', array_keys($forums));
    }

    // Prepare data for handling a forum based feed.
    elseif ($source_type === PHORUM_FEED_FORUM)
    {
        if ($PHORUM['forum_id'] == $id) {
            $forum = $PHORUM; // contains all required data already
        } else {
            $forum = phorum_api_forums_by_forum_id($id);
            if (empty($forum)) trigger_error(
                "phorum_api_feed(): Forum for forum_id \"$id\" not found.",
                E_USER_ERROR
            );
        }
        $forums = array($id => $forum);
        $thread_id = NULL;
        $forum_ids = array_keys($forums);
        $cache_part = $forum['forum_id'];
    }

    // Prepare data for handling a thread based feed.
    elseif ($source_type === PHORUM_FEED_THREAD)
    {
        // When a feed for a thread is requested, we always include the
        // reply messages for that thread in the feed.
        $replies = 1;

        // Retrieve the thread starter message.
        $thread = $PHORUM['DB']->get_message($id);
        if (empty($thread)) trigger_error(
            "phorum_api_feed(): Thread for message_id \"$id\" not found.",
            E_USER_ERROR
        );
        if (!empty($thread['parent_id'])) trigger_error(
            "phorum_api_feed(): Message for message_id \"$id\" is not " .
            "the start message of a thread.",
            E_USER_ERROR
        );

        $thread_id = $id;
        $forum_ids = NULL;
        $cache_part = $id;
    }

    // Unknown feed type requested.
    else trigger_error(
        "phorum_api_feed(): Illegal value \"$source_type\" used " .
        "for parameter \$source_type.",
        E_USER_ERROR
    );

    // ----------------------------------------------------------------------
    // Retrieve the data for the requested feed
    // ----------------------------------------------------------------------

    $data         = NULL;
    $content_type = NULL;

    // Try to retrieve the data from cache.
    if (!empty($PHORUM['cache_rss']))
    {
        // Build the cache key that uniquely identifies the requested feed.
        $cache_key = $PHORUM['user']['user_id'] . '|' .
                     $adapter . '|' . $source_type . '|' . $cache_part . '|' .
                     $replies . '|' . $count;
        $cache = phorum_api_cache_get('feed', $cache_key);
        if (!empty($cache)) {
            list ($data, $content_type) = $cache;
        }
    }

    // No data from cache. Load the recent threads / messages
    // directly from the database and generate the feed data.
    if (empty($data))
    {
        // ----------------------------------------------------------------
        // Retrieve the messages to show
        // ----------------------------------------------------------------

        $messages = $PHORUM['DB']->get_recent_messages(
            $count,      // get $count messages
            0,           // on the first page
            $forum_ids,  // from these forums
            $thread_id,  // or from this thread
            $replies ? LIST_RECENT_MESSAGES : LIST_RECENT_THREADS
        );

        // Temporarily, remove the user list from the messages array.
        $users = $messages['users'];
        unset($messages['users']);

        // Apply the "read" hook(s) to the messages.
        if (isset($PHORUM['hooks']['read'])) {
            $messages = phorum_api_hook('read', $messages);
        }

        // Apply formatting to the messages.
        $messages = phorum_api_format_messages($messages);

        // Put the array of users back in the messages array.
        $messages['users'] = $users;

        // ----------------------------------------------------------------
        // Setup the feed URL, title and description based on
        // the type of feed that was requested.
        // ----------------------------------------------------------------

        if ($source_type === PHORUM_FEED_VROOT)
        {
            $feed_url = phorum_api_url(PHORUM_INDEX_URL);
            $feed_title = strip_tags($PHORUM['DATA']['TITLE']);
            $feed_description = (!empty($PHORUM['description']))
                              ? $PHORUM['description'] : '';
        }
        if ($source_type === PHORUM_FEED_FORUM)
        {
            $feed_url = phorum_api_url(PHORUM_LIST_URL);
            /**
             * @todo The formatting of the forum base feed data should
             *       be based on the data in $forum and not the common.php
             *       $PHORUM contents. This is left as is for now, because
             *       the wrong data will only be shown for threads that
             *       were moved to a different forum.
             */
            $feed_title = strip_tags(
                $PHORUM['DATA']['TITLE'].' - '.$PHORUM['DATA']['NAME']);
            $feed_description = strip_tags($PHORUM['DATA']['DESCRIPTION']);
        }
        if ($source_type === PHORUM_FEED_THREAD)
        {
            // Retrieve the information for the forum to which the thread
            // belongs. Normally, this should be in $PHORUM already, but
            // let's make sure that the caller is using the correct forum id
            // in the URL here (since the thread might have been moved to
            // a different forum).
            $forum_id = $thread['forum_id'];
            if ($PHORUM['forum_id'] == $forum_id) {
                $forum = $PHORUM; // contains all required data already
            } else {
                $forum = phorum_api_forums_by_forum_id($forum_id);
                if (empty($forum)) trigger_error(
                    "phorum_api_feed(): Forum for forum_id \"$id\" not found.",
                    E_USER_ERROR
                );
            }
            $forums = array($forum_id => $forum);

            $feed_url = phorum_api_url(
                PHORUM_FOREIGN_READ_URL,
                $thread['forum_id'], $thread_id, $thread_id
            );
            $feed_title = strip_tags($thread['subject']);
            $feed_description = strip_tags($thread['body']);
        }

        // ----------------------------------------------------------------
        // All data has been collected. Now the feed is generated.
        // ----------------------------------------------------------------

        require_once PHORUM_PATH.'/include/api/feed/'.$adapter.'.php';
        $adapter_function = 'phorum_api_feed_'.$adapter;
        list ($data, $content_type) = $adapter_function(
            $messages, $forums,
            $feed_url, $feed_title, $feed_description, $replies
        );

        // Store the feed data in the cache for future use.
        if (!empty($PHORUM['cache_rss'])) {
            phorum_api_cache_put(
                'feed', $cache_key,
                array($data, $content_type, 600)
            );
        }
    }

    // ----------------------------------------------------------------------
    // Output the feed data to the client
    // ----------------------------------------------------------------------

    header("Content-Type: $content_type");
    print $data;

    /*
     * [hook]
     *     feed_sent
     *
     * [description]
     *     This hook is called whenever the feed has been sent to the client
     *     (regardless of the cache setting). This can be used to add internal
     *     server side tracking code.
     *
     * [category]
     *     Feed
     *
     * [when]
     *     Feed sent to the client
     *
     * [input]
     *     None
     *
     * [output]
     *     None
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_feed_after ()
     *     {
     *       # E.g. do server side tracking
     *       @file_get_contents('your tracking service');
     *     }
     *     </hookcode>
     */
    phorum_api_hook('feed_sent');

    // Exit explicitly here, for not giving back control to portable and
    // embedded Phorum setups.
    exit(0);
}
// }}}

?>
