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

if (!defined('PHORUM')) return;

// --------------------------------------------------------------------
// Retrieve information from the database
// --------------------------------------------------------------------

// This array is used to keep track of forums for which we will check
// if there are unread messages available.
$forums_to_check = array();

// A list of folders that we have to show on the page. We start out with
// the folder at which we are looking.
// Key: the forum_id of the folder.
// Value: an array of forum_ids that are contained in this folder.
$folders = array($PHORUM['forum_id'] => array());

// Get the data for the folder at which we are looking.
// This initializes our forums storage array, in which we
// will gather data for forums and folders.
$forums = phorum_api_forums_get(array($PHORUM['forum_id']));

// If we are in a (v)root folder, then we show the forums that are in the
// (v)root as separate sections in the flat index view. To be able to do this,
// we have to know what folders are directly below the (v)root. Here, we look
// those up and extend the $folders array.
if ($PHORUM['vroot'] == $PHORUM['forum_id'])
{
    $child_folders = phorum_api_forums_get(
        NULL, $PHORUM['forum_id'], NULL, $PHORUM['vroot'],
        PHORUM_FLAG_FOLDERS
    );

    foreach ($child_folders as $forum_id => $folder)
    {
        // Add the child folder data to the list of forums.
        $forums[$forum_id] = $folder;

        // Keep track of folders that are shown below the starting node.
        $folders[$forum_id] = array();
    }
}

// Loop over all the folders (flat view sections) that we will show and get
// their child forums and folders.
foreach ($folders as $folder_id => $dummy)
{
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

    foreach ($children as $child_forum_id => $child_forum)
    {
        // If inaccessible forums should be hidden on the index, then check
        // if the current user has rights to access the current forum.
        if (!$child_forum['folder_flag'] && $PHORUM['hide_forums'] &&
            !phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, $child_forum_id)) {
            continue;
        }

        // These are level one forums and folders.
        $child_forum['level'] = 1;

        // Remember the data.
        $forums[$child_forum_id] = $child_forum;

        // Add the forum or folder to the child list for the current folder.
        $folders[$folder_id][$child_forum_id] = $child_forum_id;
    }
}

// --------------------------------------------------------------------
// Setup the template data and display the template
// --------------------------------------------------------------------

// Format the data for the forums and folders that we gathered.
$forums = phorum_api_format_forums($forums, PHORUM_FLAG_ADD_UNREAD_INFO);

// Build the ordered list of folders and forums for the template.
// Filter out empty folders.
$PHORUM['DATA']['FORUMS'] = array();
foreach ($folders as $folder_id => $children)
{
    // Only add the current folder to the template data if it contains
    // visible forums or folders.
    if (empty($children)) continue;

    // Add the current folder.
    $PHORUM['DATA']['FORUMS'][] = $forums[$folder_id];

    // Add its children.
    foreach ($children as $child_forum_id) {
      $PHORUM['DATA']['FORUMS'][] = $forums[$child_forum_id];
    }
}

// Build all our standard URL's.
phorum_build_common_urls();

// A message to show if there are no visible forums at all.
if (empty($PHORUM['DATA']['FORUMS'])) {
    $PHORUM['DATA']['OKMSG'] = $PHORUM['DATA']['LANG']['NoForums'];
    phorum_api_output('message');
    return;
}

// If we are at the (v)root index page and if we only have one forum or
// folder visible there, then directly jump to that one.
// We check for two forums here, since the first would be the section
// folder and the second one would be the entry inside that folder.
if (!empty($PHORUM['jump_on_single_forum']) &&
    $PHORUM['vroot'] == $PHORUM['forum_id'] &&
    count($PHORUM['DATA']['FORUMS']) == 2) {
    $forum = array_pop($PHORUM['DATA']['FORUMS']);
    $url = $forum['folder_flag']
         ? $forum['URL']['INDEX'] : $forum['URL']['LIST'];
    phorum_api_redirect($url);
    exit;
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
 *     Just before the index page is shown. The hook can be called from
 *     either <filename>include/index/flat.php</filename> or
 *     <filename>include/index/directory.php</filename>, based on the
 *     value of the <literal>$PHORUM["index_style"]</literal> setting
 *     variable.
 *
 * [input]
 *     An array containing all the forums and folders that will be shown
 *     on the index page. Note that there are some slight differences between
 *     the provided data for a "flat" and for a "directory" index style.
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
    $PHORUM['DATA']['FORUMS'] = phorum_api_hook(
        'index', $PHORUM['DATA']['FORUMS']
    );
}

// Display the page.
phorum_api_output('index_flat');

?>
