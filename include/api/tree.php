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
 * This script implements utility functions for working with tree structures.
 *
 * @package    PhorumAPI
 * @subpackage Tree
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_tree_build()
/**
 * Build a tree structure, based on a one-dimensional array of tree nodes.
 *
 * The array elements must hold information about the hierarchy. This means
 * that each node must have a unique indentifier and a pointer to the
 * unique identifier of its parent node.
 *
 * If a node is encountered for which the provided parent does not exist,
 * then the node is linked to its branch node instead of its parent.
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
 * @param string|NULL $branch_id_fld
 *     The name of the node field that holds the unique node identifier
 *     of the node's branch or NULL to not use branches (branches are
 *     used for fixing tree hierarchy inconsistencies and are not
 *     required).
 *
 * @param integer|NULL $reverse_from_indent_level
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
 * @param integer|NULL $cut_fld
 *     Sometimes, it is useful to wrap very long words in a field (e.g.
 *     the subject field in a forum posting) to prevent those from
 *     breaking page layout. This field can be set to the name of the field
 *     that has to be cut. If not cutting is required, then NULL can be used.
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
function phorum_api_tree_build(
    $nodes, $root_id = 0, $id_fld = 'id', $parent_id_fld = 'parent_id',
    $branch_id_fld = NULL,
    $reverse_from_indent_level = NULL, $indent_factor = 1,
    $cut_fld = NULL, $cut_min = 20, $cut_max = 60, $cut_indent_factor = 2)
{
    // ----------------------------------------------------------------------
    // First pass: setup a mapping for each node and its child nodes.
    // This pass is done, so we can handle nodes that are not stored
    // in the same order as they appear in the tree. In the second
    // pass, we can use this prepared data to handle the tree sorting.
    // ----------------------------------------------------------------------

    $structure = array();
    $seen      = array();
    foreach ($nodes as $node)
    {
      $id        = $node[$id_fld];
      $parent_id = $node[$parent_id_fld];

      if ($id === $parent_id) trigger_error(
        "phorum_api_tree_build(): illegal tree data: node id $id is " .
        "configured with its own id as its parent_id", E_USER_ERROR
      );
      if (isset($seen[$id])) trigger_error(
        "phorum_api_tree_build(): illegal tree data: each node should " .
        "have a unique id, but node id $id is encountered multiple times"
      );
      $seen[$id] = TRUE;

      // Add data for the node.
      if (!isset($structure[$id])) {
        $structure[$id] = array(
          'data'     => $node,
          'children' => array()
        );
      } else {
        $structure[$id]['data'] = $node;
      }

      // Update the data for the parent node or create new data
      // if the parent node has not yet been encountered.
      if (!isset($structure[$parent_id])) {
        $structure[$parent_id] = array(
          'data'     => NULL,
          'children' => array($id => $id)
        );
      } else {
        $structure[$parent_id]['children'][$id] = $id;
      }
    }

    // ----------------------------------------------------------------------
    // Second pass: take care of orphins.
    //
    // If a node's parent is not available, then we have a stale node (this
    // should not happen in a perfect world.) In that case, we fix the tree
    // consistency by moving the node up to the branch level or (if the
    // branch node isn't configured or doesn't exist either) the root level.
    // ----------------------------------------------------------------------

    foreach ($structure as $id => &$item)
    {
        if (empty($item['data'])) continue;
        $node = &$item['data'];

        if (!isset($structure[$node[$parent_id_fld]]))
        {
            if ($branch_id_fld !== NULL) {
                $node[$parent_id_fld] = isset($structure[$node[$branch_id_fld]])
                                      ? $node[$branch_id_fld] : $root_id;
            } else {
                $node[$parent_id_fld] = $root_id;
            }
        }

        unset($node);
    }

    // ----------------------------------------------------------------------
    // Third pass: sort the prepared data into a tree structure.
    // ----------------------------------------------------------------------

    // If the root is virtual (not available as a real node in the
    // nodes array), then we don't want to include that one in the
    // stack level counting, so we move our stack level base.
    $stack_lvl_base = isset($structure[$root_id]['data']) ? 0 : -1;

    // Initialize variables for the sorting code.
    $tree      = array(); // the final sorted tree result array
    $stack     = array(); // a stack that is used when descending down the tree
    $stack_lvl = 0;       // the current depth level
    $cursor    = 0;       // the id of the node that is currenty being processed
    $node      = NULL;    // the node that is currently being processed

    // Build the result tree.
    for (;;)
    {
      // If the current cursor position has no children (left), then we need
      // to climb (back) up the tree, until we find a node that has children
      // left for processing or until the stack is empty.
      if (empty($structure[$cursor]['children']))
      {
        unset($structure[$cursor]);

        // As long as we didn't totally drain our stack ...
        while ($stack_lvl > 0)
        {
          // ... move up one level in this stack.
          unset($stack[$stack_lvl]);
          list ($cursor, $node) = $stack[--$stack_lvl];

          // If this node has children left, then break out
          // so the following code will process them.
          if (empty($structure[$cursor]['children'])) {
            unset($structure[$cursor]);
          } else {
            break;
          }
        }

        // Ready! We have processed all of the nodes.
        if ($stack_lvl === 0 && empty($structure[$root_id])) {
          break;
        }
      }
      // If the current cursor position has children, then process these.
      else
      {
        $children =& $structure[$cursor]['children'];

        // handle reverse threading
        if(!empty($reverse_from_indent_level) && $stack_lvl >= $reverse_from_indent_level) {
            $children = array_reverse($children,true);
        }

        // Remember the current cursor + node in the stack.
        $stack[$stack_lvl++] = array($cursor, $node);

        // Fetch the next child node.
        $cursor = array_shift($children);
        $node   = $structure[$cursor]['data'];
        unset($children[$cursor]);

        // Set the idention level for the node.
        $node['indent_cnt'] =
            ($stack_lvl + $stack_lvl_base) * $indent_factor;

        // break the page layout.
        if ($cut_fld !== NULL) {
            $cut_len =
                $cut_max - ($stack_lvl + $stack_lvl_base) * $cut_indent_factor;
            if ($cut_len < $cut_min) $cut_len = $cut_min;
            $node[$cut_fld] = phorum_api_format_wordwrap(
                $node[$cut_fld], $cut_len, ' ', TRUE
            );
        }

        // Move the message data to the tree array.
        $tree[$cursor] = $node;
      }
    }

    // Remove the root node from the tree, if it was only seen as
    // a virtual root node that we created at init time.
    if ($stack_lvl_base == -1) unset($tree[$root_id]);

    return $tree;
}
// }}}

?>
