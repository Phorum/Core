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

if (!defined("PHORUM")) return;

require_once PHORUM_PATH.'/include/api/format/forums.php';

// --------------------------------------------------------------------
// Retrieve information from the database
// --------------------------------------------------------------------

// Retrieve the children for the current folder.
$forums = phorum_api_forums_get(
    NULL, $PHORUM['forum_id'], NULL, $PHORUM['vroot']
);

// For the directory index view, we show the folders and forums separately.
// Here we separate the forum_ids for these two.
$folder_ids = array();
$forum_ids  = array();
foreach ($forums as $forum_id => $forum)
{
    // Handle folders.
    if ($forum['folder_flag'])
    {
        $folder_ids[] = $forum_id;
    }
    // Handle forums.
    else
    {
        // If inaccessible forums should be hidden on the index, then check
        // if the current user has rights to access the current forum.
        if (!$forum['folder_flag'] && $PHORUM['hide_forums'] &&
            !phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, $forum_id)) {
            continue;
        }

        $forum_ids[] = $forum_id;
    }
}

// --------------------------------------------------------------------
// Setup the template data and display the template
// --------------------------------------------------------------------

// Format the data for the forums and folders that we gathered.
$forums = phorum_api_format_forums($forums, PHORUM_FLAG_ADD_UNREAD_INFO);

// If we are at the (v)root index page and if we only have one forum or
// folder visible there, then directly jump to that one.
if (!empty($PHORUM['jump_on_single_forum']) &&
    $PHORUM['vroot'] == $PHORUM['forum_id'] &&
    count($forums) == 1) {
    $forum = array_pop($forums);
    $url = $forum['folder_flag']
         ? $forum['URL']['INDEX'] : $forum['URL']['LIST'];
    phorum_api_redirect($url);
}

// Build all our standard URL's.
phorum_build_common_urls();

// A message to show if there are no visible forums or folders at all.
if (empty($forums)) {
    $PHORUM['DATA']['OKMSG'] = $PHORUM['DATA']['LANG']['NoForums'];
    phorum_api_output('message');
    return;
}

// Run the "index" hook. This one is documented in include/index/flat.php.
if (isset($PHORUM['hooks']['index'])) {
    $forums = phorum_api_hook('index', $forums);
}

// Build the template folders array.
$PHORUM['DATA']['FOLDERS'] = array();
foreach ($folder_ids as $folder_id) {
    $PHORUM['DATA']['FOLDERS'][] = $forums[$folder_id];
}

// Build the template forums array.
$PHORUM['DATA']['FORUMS'] = array();
foreach ($forum_ids as $forum_id) {
    $PHORUM['DATA']['FORUMS'][] = $forums[$forum_id];
}

// Display the page.
phorum_api_output("index_directory");

?>
