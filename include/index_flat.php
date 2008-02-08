<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2008  Phorum Development Team                              //
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

if (!defined('PHORUM')) return;

// --------------------------------------------------------------------
// Retrieve information from the database
// --------------------------------------------------------------------

// Get the data for the folder at which we are looking.
// This initializes our forums storage array, in which we
// will gather data for forums and folders.
$forums = phorum_api_forums_get(array($PHORUM['forum_id']));

// A list of folders that we have to show on the page. We start out with
// the folder at which we are looking.
// Key: the forum_id of the folder.
// Value: an array of forum_ids that are contained in this folder.
$folders = array($PHORUM['forum_id'] => array());

// This array is used to keep track of forums for which we will check
// if there are unread messages available.
$forums_to_check = array();

// If we are in a (v)root folder, then we show the forums that are in the
// (v)root as separate sections in the flat index view. To be able to do this,
// we have to know what folders are directly below the

// If we are in a (v)root folder, then we show the forums that are in the
// (v)root as separate sections in the flat index view. To be able to do this,
// we have to know what folders are directly below the (v)root. Here, we look
// those up and extend the $folders array.
if ($PHORUM['vroot'] == $PHORUM['forum_id'])
{
  $child_folders = phorum_api_forums_get(
  NULL, $PHORUM['forum_id'], NULL, $PHORUM['vroot'],
  $PHORUM['forum_id'], PHORUM_FLAG_FOLDERS
  );

  foreach ($child_folders as $forum_id => $folder)
  {
    // Add the child folder data to the list of forums.
    $forums[$forum_id] = $folder;

    // Keep track of folders that are below the (v)root.
    $folders[$forum_id] = array();
  }
}

// Loop over all the folders (flat view sections) that we will show and get
// their child forums and folders.
foreach ($folders as $folder_id => $dummy)
{
  $folder = $forums[$folder_id];

  // These folders are level zero folders. To the child forums and folders,
  // level 1 will be assigned. The level value can be used in the template
  // to see where a new top level folder starts.
  $forums[$folder_id]['level'] = 0;

  // Retrieve the children for the current folder. For the (v)root folder,
  // we only retrieve the contained forums, since its folders will be shown
    // as separate sections in the flat index view instead.
    $children = phorum_api_forums_get(
        NULL, $folder_id, NULL, $PHORUM['vroot'],
        $PHORUM['vroot'] == $folder_id ? PHORUM_FLAG_FORUMS : 0
    );

    foreach($children as $child_forum_id => $child_forum)
    {
        // Add the forum or folder to the child list for the current folder.
        $folders[$folder_id][$child_forum_id] = $child_forum;

        // Keep track of the visible forums for which we need to check
        // for unread messages later on.
        if ($PHORUM['user']['user_id'] &&
            $PHORUM['show_new_on_index'] != PHORUM_NEWFLAGS_NOCOUNT &&
            !$child_forum['folder_flag']) {
            $forums_to_check[] = $child_forum_id;
        }
    }
}

// For authenticated users, check if there are unread messages in the forums.
if ($PHORUM['user']['user_id'] && !empty($forums_to_check))
{
    if ($PHORUM['show_new_on_index'] == PHORUM_NEWFLAGS_CHECK) {
        $new_checks = phorum_db_newflag_check($forums_to_check);
    } elseif($PHORUM['show_new_on_index'] == PHORUM_NEWFLAGS_COUNT) {
        $new_counts = phorum_db_newflag_count($forums_to_check);
    }
}

// --------------------------------------------------------------------
// Setup the template data and display the template
// --------------------------------------------------------------------

$PHORUM['DATA']['FORUMS'] = array();

foreach ($folders as $folder_id => $children)
{
    // A URL for the index for this folder.
    $forums[$folder_id]['URL']['LIST'] = phorum_get_url(
        PHORUM_INDEX_URL, $folder_id
    );

    // Skip folders that do not contain any visible children.
    if (empty($children)) continue;

    // Build a list of formatted child forums and folders.
    $shown_children = array();
    foreach($children as $forum_id => $forum)
    {
        // Setup template data for folders.
        if ($forum['folder_flag'])
        {
            // A URL for the index view for this folder.
            $forum['URL']['INDEX'] = phorum_get_url(PHORUM_INDEX_URL, $forum_id);
        }
        // Setup template data for forums.
        else
        {
            // If inaccessible forums should be hidden on the index, then check
            // if the current user has rights to access the current forum.
            if ($PHORUM['hide_forums'] &&
                !phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, $forum_id)) {
                continue;
            }

            // A URL for the message list for this forum.
            $forum['URL']['LIST'] = phorum_get_url(PHORUM_LIST_URL, $forum_id);

            // A "mark forum read" URL for authenticated users.
            if ($PHORUM['user']['user_id']) {
                $forum['URL']['MARK_READ'] = phorum_get_url(
                    PHORUM_INDEX_URL,
                    $forum_id,
                    'markread',
                    $PHORUM['forum_id']
                );
            }

            // A URL to the syndication feed.
            if (!empty($PHORUM['use_rss'])) {
                $forum['URL']['FEED'] = phorum_get_url(
                    PHORUM_FEED_URL,
                    $forum_id,
                    'type='.$PHORUM['default_feed']
                );
            }

            // Format the last post time, unless no messages were posted at all.
            if ($forum['message_count'] > 0)
            {
                // For dates, we store an unmodified version to always have
                // the original date available for modules. Not strictly
                // needed for this one, since we to not override the original
                // "last_post_time" field, but we still add it to be compliant
                // with the rest of the Phorum code.
                $forum['raw_last_post'] = $forum['last_post_time'];

                $forum['last_post'] = phorum_date(
                    $PHORUM['long_date_time'],
                    $forum['last_post_time']
                );
            }
            // If no messages were posted, we revert to a simple &nbsp;
            else {
                $forum['last_post'] = '&nbsp;';
            }

            // Some number formatting.
            $forum['message_count'] = number_format(
                $forum['message_count'], 0,
                $PHORUM['dec_sep'], $PHORUM['thous_sep']
            );
            $forum['thread_count'] = number_format(
                $forum['thread_count'], 0,
                $PHORUM['dec_sep'], $PHORUM['thous_sep']
            );

            // Add unread message information for authenticated users.
            if ($PHORUM['user']['user_id'])
            {
                if ($PHORUM['show_new_on_index'] == PHORUM_NEWFLAGS_COUNT)
                {
                    $forum['new_messages'] = number_format(
                        $new_counts[$forum_id]['messages'], 0,
                        $PHORUM['dec_sep'], $PHORUM['thous_sep']
                    );
                    $forum['new_threads'] = number_format(
                        $new_counts[$forum_id]['threads'], 0,
                        $PHORUM['dec_sep'], $PHORUM['thous_sep']
                    );
                }
                elseif($PHORUM['show_new_on_index'] == PHORUM_NEWFLAGS_CHECK)
                {
                    $new = empty($new_checks[$forum_id]) ? FALSE : TRUE;
                    $forum['new_message_check'] = $new;
                }
            }
        }

        // These are level one forums and folders.
        $forum['level'] = 1;

        $shown_children[] = $forum;
    }

    // Only add the current folder to the template data if it contains
    // visible forums or folders.
    if (count($shown_children)) {
        $PHORUM['DATA']['FORUMS'][] = $forums[$folder_id];
        $PHORUM['DATA']['FORUMS']   = array_merge(
            $PHORUM['DATA']['FORUMS'],
            $shown_children
        );
    }
}

// Build all our standard URL's.
phorum_build_common_urls();

// A message to show if there are no visible forums at all.
if (!count($PHORUM['DATA']['FORUMS'])) {
    $PHORUM['DATA']['OKMSG'] = $PHORUM['DATA']['LANG']['NoForums'];
    phorum_output('message');
    return;
}

/**
 * [hook]
 *     index
 *
 * [description]
 *     This hook can be used to modify the data for folders and forums
 *     that are shown on the index page.
 *
 * [category]
 *     Page data handling
 *
 * [when]
 *     Just before the index page is shown.
 *
 * [input]
 *     An array containing all the forums and folders that will be shown
 *     on the index page.
 *
 * [output]
 *     The same array as the one that was used for the hook call
 *     argument, possibly with some updated fields in it.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_index($data)
 *     {
 *         global $PHORUM;
 *
 *         // An example to add some data to the description of
 *         // forums on the index page in flat view.
 *         if ($PHORUM['index_style'] == PHORUM_INDEX_FLAT)
 *         {
 *             foreach ($data as $id => $item)
 *             {
 *                 if (!$item['folder_flag'])
 *                 {
 *                     $data[$id]['description'] .= '<br/>Blah foo bar baz';
 *                 }
 *             }
 *         }
 *
 *         return $data;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM['hooks']['index'])) {
    $PHORUM['DATA']['FORUMS'] = phorum_hook('index', $PHORUM['DATA']['FORUMS']);
}

// Display the page.
phorum_output('index_flat');

?>