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

require_once('./include/api/tree.php');

function phorum_sort_threads($rows)
{
    global $PHORUM;

    // Quick shortcut if we have no rows at all.
    if(count($rows) == 0) { return $rows; }

    // Get template defined settings values.
    $indent_factor     = isset($PHORUM['TMP']['indentmultiplier'])
                       ? $PHORUM['TMP']['indentmultiplier'] : 20;
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
    $tree = phorum_api_tree_build(
        $rows,                       // The nodes to put in a tree
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

    return $tree;
}

?>
