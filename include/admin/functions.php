<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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

if (!defined("PHORUM_ADMIN")) return;

require_once './include/api/forums.php';

/**
 * Display an ok message for the admin interface.
 *
 * @param string $error
 *   The error message to show. The caller must take care of escaping HTML.
 */
function phorum_admin_error($error)
{
    echo "<div class=\"PhorumAdminError\">$error</div>\n";
}

/**
 * Display an ok message for the admin interface.
 *
 * @param string $error
 *   The ok message to show. The caller must take care of escaping HTML.
 */
function phorum_admin_okmsg($error)
{
    echo "<div class=\"PhorumAdminOkMsg\">$error</div>\n";
}

/**
 * @param integer $flag
 *   $flag can be 0, 1, 2 or 3
 *   0 = all forums / folders
 *   1 = all forums
 *   2 = only forums + vroot-folders (used in banlists)
 *   3 = only vroot-folders
 *
 * @param integer $vroot
 *   This parameter can be -1, 0 or > 0
 *    -1 returns forums from all vroots
 *     0 returns only forums / folders from the root (vroot = 0)
 *   > 0 returns only forums / folders within the given vroot
 */
function phorum_get_forum_info($flag = 0, $vroot = -1)
{
    $folders = array();

    $forums = phorum_api_forums_get(
        NULL, NULL, NULL, NULL,
        PHORUM_FLAG_INCLUDE_INACTIVE
    );

    foreach ($forums as $forum)
    {
        if ((
             $flag == 0 ||
             ($forum['folder_flag'] == 0 && $flag != 3) ||
             ($flag==2 && $forum['vroot'] > 0 && $forum['vroot'] == $forum['forum_id']) ||
             ($flag==3 && $forum['vroot'] == $forum['forum_id'])
            )
            && ($vroot == -1 || $vroot == $forum['vroot'])
           )
        {
            // Build a string path for the current forum record.
            $parts = $forum['forum_path'];
            array_shift($parts);
            $path = implode('::', $parts);

            // When we are not requesting vroot folders specifically,
            // then add an indication when the current forum record
            // is a vroot folder.
            if ($flag != 3 &&
                $forum['vroot'] &&
                $forum['vroot'] == $forum['forum_id']) {
                $path.=" (Virtual Root)";
            }

            $folders[$forum["forum_id"]] = $path;
        }
    }

    asort($folders, SORT_STRING);

    return $folders;
}

?>
