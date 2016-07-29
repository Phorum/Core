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
 * This script implements forums formatting.
 *
 * @package    PhorumAPI
 * @subpackage Formatting
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_format_forums()
/**
 * This function handles preparing forum or folder data
 * for use in the templates.
 *
 * @param array $forums
 *     An array of forum and/or folder data records to format.
 *
 * @param int $flags
 *     If the {@link PHORUM_FLAG_ADD_UNREAD_INFO} flag is set, then template
 *     data for showing new messages/threads will be added to the data records.
 *     The exact data that is added depends on the value of the
 *     $PHORUM['show_new_on_index'] setting variable.
 *
 * @param array
 *     The same as the $forums argument array, with formatting applied
 *     and template variables added.
 */
function phorum_api_format_forums($forums, $flags = 0)
{
    global $PHORUM;

    static $index_url_template;
    static $list_url_template;
    static $markread_url_template;
    static $feed_url_template;

    if (empty($feed_url_template))
    {
        $index_url_template = phorum_api_url(
            PHORUM_INDEX_URL, '%forum_id%'
        );
        $orig_forum_id = $PHORUM['forum_id'];
        $PHORUM['forum_id'] = '%forum_id%';
        $post_url_template = phorum_api_url(
            PHORUM_POSTING_URL
        );
        $PHORUM['forum_id'] = $orig_forum_id;
        $list_url_template = phorum_api_url(
            PHORUM_LIST_URL, '%forum_id%'
        );
        $markread_url_template = phorum_api_url(
            PHORUM_INDEX_URL, '%forum_id%', 'markread', '%folder_id%'
        );
        $feed_url_template = phorum_api_url(
            PHORUM_FEED_URL, '%forum_id%', 'type='.$PHORUM['default_feed']
        );
    }

    // For tracking forums for which we have to check unread messages.
    $forums_to_check = array();

    foreach ($forums as $forum_id => $forum)
    {
        // Setup template data for folders.
        if ($forum['folder_flag'])
        {
            // A URL for the index view for this folder. We also set this
            // one up as URL->LIST, because up to Phorum 5.3 that variable
            // was in use.
            $forum['URL']['INDEX'] =
            $forum['URL']['LIST'] =
                str_replace('%forum_id%', $forum_id, $index_url_template);
        }
        // Setup template data for forums.
        else
        {
            // A URL for the message list for this forum.
            $forum['URL']['LIST'] =
                str_replace('%forum_id%', $forum_id, $list_url_template);

            // A "mark forum read" URL for authenticated users.
            if ($PHORUM['user']['user_id']) {
                $forum['URL']['MARK_READ'] = str_replace(
                    array('%forum_id%','%folder_id%'),
                    array($forum_id, $PHORUM['forum_id']),
                    $markread_url_template
                );
            }

            // A URL to post a new message.
            $forum['URL']['POST'] =
                str_replace('%forum_id%', $forum_id, $post_url_template);

            // A URL to the syndication feed.
            if (!empty($PHORUM['use_rss'])) {
                $forum['URL']['FEED'] =
                    str_replace('%forum_id%', $forum_id, $feed_url_template);
            }

            // For dates, we store an unmodified version to always have
            // the original date available for modules. Not strictly
            // needed for this one, since we to not override the original
            // "last_post_time" field, but we still add it to be compliant
            // with the rest of the Phorum code.
            $forum['raw_last_post'] = $forum['last_post_time'];

            // Format the last post time, unless no messages were posted at all.
            if ($forum['message_count'] > 0)
            {
                $forum['last_post'] = phorum_api_format_date(
                    $PHORUM['long_date_time'],
                    $forum['last_post_time']
                );
            }
            // If no messages were posted, we revert to a simple &nbsp;
            else {
                $forum['last_post'] = '&nbsp;';
            }

            // Some number formatting.
            $forum['raw_message_count'] = $forum['message_count'];
            $forum['message_count'] = number_format(
                $forum['message_count'], 0,
                $PHORUM['dec_sep'], $PHORUM['thous_sep']
            );
            $forum['raw_thread_count'] = $forum['thread_count'];
            $forum['thread_count'] = number_format(
                $forum['thread_count'], 0,
                $PHORUM['dec_sep'], $PHORUM['thous_sep']
            );

            $forums_to_check[] = $forum_id;
        }

        // Put the formatted record back in the array.
        $forums[$forum_id] = $forum;
    }

    // Add unread message information.
    if ($flags & PHORUM_FLAG_ADD_UNREAD_INFO &&
        $PHORUM['show_new_on_index'] != PHORUM_NEWFLAGS_NOCOUNT &&
        $PHORUM['user']['user_id'] &&
        !empty($forums_to_check)) {

        $forums = phorum_api_newflags_apply_to_forums(
            $forums,
            $PHORUM['show_new_on_index'],
            $forums_to_check
        );
    }

    return $forums;
}
//}}}

?>
