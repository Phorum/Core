<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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
 * This script implements the Phorum forum admin API.
 *
 * This API is used for managing Phorum forums and folders. It can be used to
 * retrieve information about the available forums and folders and takes care
 * of creating and editing them.
 *
 * This API combines forums and folders into one API script, because at the
 * data level, they are the same kind of entity. Folders are also forums,
 * only they act differently based on the "folder_flag" field. Therefore,
 * folders are also identified by a forum_id.
 *
 * @package    PhorumAPI
 * @subpackage ForumsAPI
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

// {{{ Function: phorum_api_forums_get
/**
 * Retrieve the data for one or more forums and/or folders.
 *
 * @param mixed $forum_id
 *     Either a single forum_id or an array of forum_ids.
 *
 * @return mixed
 *     If the $forum_id parameter is a single forum_id, then either an array
 *     containing forum data is returned or NULL if the forum was not found.
 *     If the $forum_id parameter is an array of forum_ids, then an array
 *     of forum data arrays is returned, indexed by the forum_id and sorted
 *     by their display order. For forum_ids that are not found, no entry
 *     will be available in the returned array.
 */
function phorum_api_forums_get($forum_id)
{
    $forum_ids = is_array($forum_id) ? $forum_id : array($forum_id);
    $forums = phorum_db_get_forums($forum_ids);

    if (is_array($forum_id)) {
        return $forums;
    } else {
        return isset($forums[$forum_id]) ? $forums[$forum_id] : NULL;
    }
}
// }}}

// {{{ Function: phorum_api_forums_by_folder()
/**
 * Retrieve data for all direct descendant forums and folders within a folder.
 *
 * @param integer $folder_id
 *     The forum_id of the folder for which to retrieve the forums.
 *
 * @return array
 *     An array of forums, index by the their forum_id and sorted
 *     by their display order.
 */
function phorum_api_forums_by_folder($folder_id = 0)
{
    return phorum_db_get_forums(0, $folder_id);
}
// }}}

// {{{ Function: phorum_api_forums_by_vroot()
/**
 * Retrieve data for all forums and folders that belong to a certain vroot.
 *
 * @param integer $vroot_id
 *     The forum_id of the vroot for which to retrieve the forums.
 *
 * @return array
 *     An array of forums, index by the their forum_id and sorted
 *     by their display order.
 */
function phorum_api_forums_by_vroot($vroot_id = 0)
{
    return phorum_db_get_forums(0, NULL, $vroot_id);
}
// }}}

// {{{ Function: phorum_api_forums_by_inheritance()
/**
 * Retrieve data for all forums inheriting their settings from a certain forum.
 *
 * @param integer $forum_id
 *     The forum_id for which to check what forums inherit its setting.
 *
 * @return array
 *     An array of forums, index by the their forum_id and sorted
 *     by their display order.
 */
function phorum_api_forums_by_inheritance($forum_id = 0)
{
    return phorum_db_get_forums(0, NULL, NULL, $forum_id);
}
// }}}

// {{{ Function: phorum_api_forums_change_order()
/**
 * Change the displaying order for forums and folders in a certain folder.
 *
 * @param integer $folder_id
 *     The forum_id of the folder in which to change the display order.
 *
 * @param integer $forum_id
 *     The id of the forum or folder to move.
 *
 * @param string $movement
 *     This field determines the type of movement to apply to the forum
 *     or folder. This can be one of:
 *     - "up": Move the forum or folder $value positions up
 *     - "down": Move the forum or folder $value permissions down
 *     - "pos": Move the forum or folder to position $value
 *     - "start": Move the forum or folder to the start of the list
 *     - "end": Move the forum or folder to the end of the list
 *
 * @param mixed $value
 *     This field specifies a value for the requested type of movement.
 *     An integer value is only needed for movements "up", "down" and "pos".
 *     For other movements, this parameter can be omitted.
 */
function phorum_api_forums_change_order($folder_id, $forum_id, $movement, $value = NULL)
{
    settype($folder_id, 'int');
    settype($forum_id, 'int');
    if ($value !== NULL) settype($value, 'int');

    // Get the forums for the specified folder.
    $forums = phorum_api_forums_by_folder($folder_id);

    // Prepare the forum list for easy ordering.
    $current_pos = NULL;
    $pos = 0;
    $forum_ids = array();
    foreach ($forums as $forum) {
        if ($forum['forum_id'] == $forum_id) $current_pos = $pos;
        $forum_ids[$pos++] = $forum['forum_id'];
    }

    $pos--;  // to make this the last index position in the array.

    // If the forum_id is not in the folder, then return right away.
    if ($current_pos === NULL) return;

    switch ($movement)
    {
        case "up":    $new_pos = $current_pos - $value; break;
        case "down":  $new_pos = $current_pos + $value; break;
        case "pos":   $new_pos = $value;                break;
        case "start": $new_pos = 0;                     break;
        case "end":   $new_pos = $pos;                  break;

        default:
            trigger_error(
                "phorum_api_forums_change_order(): " .
                "Illegal \$momement parameter \"$movement\" used",
                E_USER_ERROR
            );
    }

    // Keep the new position within boundaries.
    if ($new_pos < 0) $new_pos = 0;
    if ($new_pos > $pos) $new_pos = $pos;
    // No order change, then return.
    if ($new_pos == $current_pos) return;

    // Reorder the forum_ids array to represent the order change.
    $new_order = array();
    for ($i = 0; $i <= $pos; $i++)
    {
        if ($i == $current_pos) continue;
        if ($i == $new_pos) {
            if ($i < $current_pos) {
                $new_order[] = $forum_id;
                $new_order[] = $forum_ids[$i];
            } else {
                $new_order[] = $forum_ids[$i];
                $new_order[] = $forum_id;
            }
        } else {
            $new_order[] = $forum_ids[$i];
        }
    }

    // Loop through all the forums and update the ones that changed.
    // We have to look at them all, because the default value for
    // display order is 0 for all forums. So, in an unsorted folder,
    // all the display order values are set to 0 until you move one.
    foreach ($new_order as $display_order => $forum_id) {
        if ($forums[$forum_id]['display_order'] != $display_order) {
            phorum_db_update_forum(array(
                'forum_id'      => $forum_id,
                'display_order' => $display_order
            ));
        }
    }
}
// }}}

?>
