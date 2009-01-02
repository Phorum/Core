<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
 * This script implements utility functions for working with tree structures.
 *
 * @package    PhorumAPI
 * @subpackage Tree
 * @copyright  2008, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Function: phorum_api_tree_build()
/**
 * Build a tree structure, based on a one-dimensional array of tree nodes.
 *
 * The array elements must hold information about the hierarchy. This means
 * that each node must have a unique indentifier and a pointer to the
 * unique identifier of its parent node.
 *
 * The elements in the tree node array must be ordered based on the order in
 * which they were added to the tree structure. Most of the time the
 * unique identifier is some auto-incremental value. For this kind of array,
 * the elements can be sorted by their unique identifier.
 *
 * If a node is encountered for which the provided parent does not exist,
 * then the node is linkd to its branch node instead of its parent.
 * If the branch node doesn't exist as well, then the node will be
 * linked to the root of the tree. In forum terms, think of the root
 * as a forum, the branch node as a thread and the other nodes as
 * reply messages in the thread.
 *
 * @param array $nodes
 *     The array of nodes that have to be added to the tree structure.
 *
 * @param string $root_id
 *     The unique node identifier of the root node. You are allowed
 *     to make this a virtual node, which isn't actually available in
 *     the $nodes array argumet.
 *
 * @param string $id_fld
 *     The name of the node field that holds the unique node identifier.
 *
 * @param string $parent_id_fld
 *     The name of the node field that holds the unique node identifier
 *     of the node's parent.
 *
 * @param NULL $branch_id_fld
 *     The name of the node field that holds the unique node identifier
 *     of the node's branch or NULL to not use branches (branches are
 *     used for fixing tree hierarchy inconsistencies and are not
 *     required).
 *
 * @param mixed $reverse_from_indent_level
 *     This parameter can be used to add nodes in reverse order to the
 *     tree from the given indent level on. NULL (the default) indicates
 *     that only standard tree ordering is used.
 *
 * @param int $indent_factor
 *     The indention level for each node will be put in the return array
 *     elements in the field "indent_cnt". This parameter determines the
 *     factor that is used for translating an indent level to the
 *     indent_cnt. Example: when using $indent_factor = 10, then the returned
 *     indent_cnt fields will be 0, 10, 20, etc. for indention levels
 *     0, 1, 2, etc.
 *
 * @param mixed $cut_fld
 *     Sometimes, it is useful to wrap very long words in a field (e.g.
 *     the subject field in a forum posting) to prevent those from
 *     breaking page layout. This field can be set to the name of the field
 *     that has to be cut.
 *
 * @param int $cut_min
 *     The minimum length that is used for splitting up long words for the
 *     $cut_fld option.
 *
 * @param int $cut_max
 *     The maximum length that is used for splitting up long words for the
 *     $cut_fld option.
 *
 * @param int $cut_indent_factor
 *     The length that is substracted from the $cut_max parameter for the
 *     active indent level, for determining the cut length to use for the
 *     $cut_fld options. If the cut length drops below $cut_min, then
 *     $cut_min will be used instead.
 *
 * @return array
 *     An array, containing the tree nodes in the order in which they
 *     appear in a tree view, top to bottom. Each node has a field
 *     "indent_cnt" which indicates how much indention should be applied
 *     to the node to make it fit correctly in a graphical tree view.
 */
function phorum_api_tree_build($nodes, $root_id = 0, $id_fld = 'id', $parent_id_fld = 'parent_id', $branch_id_fld = NULL, $reverse_from_indent_level = NULL, $indent_factor = 1, $cut_fld = NULL, $cut_min = 20, $cut_max = 60, $cut_indent_factor = 2)
{
    // Initialize the prepared nodes array. We add the root level
    // to the array, so this function can be called without providing
    // the actual root node in the $nodes array.
    $pnodes = array(
        $root_id => array( 'children' => array() )
    );

    // Add all nodes to the prepared nodes.
    foreach ($nodes as $node)
    {
        // If this node's parent is not available, then we have a stale
        // node. In that case, we fix the tree consistency by moving
        // the node up to the branch level or (if the branch node
        // isn't configured or doesn't exist either) the root level.
        if (!isset($pnodes[$node[$parent_id_fld]])) {
            if ($branch_id_fld !== NULL) {
                $node[$parent_id_fld] = isset($pnodes[$node[$branch_id_fld]])
                                  ? $node['thread'] : $root_id;
            } else {
                $node[$parent_id_fld] = $root_id;
            }
        }

        // Create the node for the current message.
        $pnodes[$node[$id_fld]] = array(
            'data'      => $node,
            'children'  => array()
        );

        // Add the node to the parent node's child node list.
        $pnodes[$node[$parent_id_fld]]['children'][$node[$id_fld]] =
            $node[$id_fld];
    }

    // If the root is virtual (not available as a real node in the
    // nodes array), then we don't want to include that one in the
    // stack level counting, so we move our stack level base.
    $stack_lvl_base = isset($pnodes[$root_id]['data']) ? 0 : -1;

    // Do create some fake data now to prevent warnings in the code below.
    if ($stack_lvl_base == -1 && $cut_fld !== NULL) {
        $pnodes[$root_id]['data'] = array(
            $cut_fld => ''
        );
    }

    // Build the result tree. We start at the root node.
    $tree      = array();
    $stack     = array();
    $stack_lvl = 0;
    $count     = count($pnodes);
    $cursor    = $root_id;

    for (;;)
    {
        $node = $pnodes[$cursor];

        // Set the idention level for the node.
        $node['data']['indent_cnt'] =
            ($stack_lvl + $stack_lvl_base) * $indent_factor;

        // Break up long words in the cut_field, so these won't
        // break the page layout.
        if ($cut_fld !== NULL) {
            $cut_len =
                $cut_max - ($stack_lvl + $stack_lvl_base) * $cut_indent_factor;
            if ($cut_len < $cut_min) $cut_len = $cut_min;
            $node['data'][$cut_fld] = wordwrap(
                $node['data'][$cut_fld], $cut_len, ' ', TRUE
            );
        }

        // Move the message data to the tree array.
        $tree[$cursor] = $node['data'];

        // One down, $count to go! Break out if all nodes are processed.
        if (--$count == 0) break;

        // If the current node has no children, then we need to climb
        // back up the tree, until we find a node that has children left
        // for processing or until the stack is empty.
        if (empty($node['children']))
        {
            // As long as we didn't totally drain our stack ...
            while ($stack_lvl > 0)
            {
                // ... move up one level in this stack.
                $cursor = $stack[--$stack_lvl];

                // If this node has children left, then break out
                // so the following code will process them.
                if (!empty($pnodes[$cursor]['children'])) {
                    break;
                }
            }
        }

        // If the current node has children, then process these children.
        // To remember where we are right now in the tree, we put
        // the current node on the stack, pull a child from the
        // child node list and move to that child node.
        if (!empty($pnodes[$cursor]['children']))
        {
            $stack[$stack_lvl] = $cursor;

            if ($reverse_from_indent_level !== NULL &&
                $stack_lvl >= $reverse_from_indent_level) {
                $cursor = array_pop($pnodes[$cursor]['children']);
            } else {
                $cursor = array_shift($pnodes[$cursor]['children']);
            }

            $stack_lvl ++;
        }
    }

    // Remove the root node from the tree, if it was only seen as
    // a virtual root node that we created at init time.
    if ($stack_lvl_base == -1) unset($tree[$root_id]);

    return $tree;
}
// }}}

?>
