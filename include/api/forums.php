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
 * This API combines forums and folders into one API layer, because at the
 * data level, they are the same kind of entity. Folders are forums as well,
 * only they act differently, based on the "folder_flag" field. Below, you can
 * find the fields that are used for forums and folders.
 *
 * <b>Folder fields</b>
 *
 *     - name: the name to assign to the folder. Phorum will not escape HTML
 *       code in this name, so formatting the title using HTML is allowed.
 *     - description: the description for the folder. Phorum will not escape
 *       HTML code in this name, so formatting the description using HTML
 *       is allowed.
 *     - parent_id: The folder_id of the parent folder or 0 (zero) if the
 *       folder resides in the top level root folder.
 *     - vroot: The vroot in which the folder resides. If the folder is
 *       the top level folder for a vroot, then the value for this field will
 *       be the same as the folder's forum_id.
 *     - active: Whether the folder is active/visible (1) or not (0).
 *     - template: The name of the template to use for the folder.
 *     - language: The name of the language to use for the folder.
 *
 * <b>Forum fields</b>
 *
 *     - name: the name to assign to the forum. Phorum will not escape HTML
 *       code in this name, so formatting the title using HTML is allowed.
 *     - description: the description for the forum. Phorum will not escape
 *       HTML code in this name, so formatting the description using HTML
 *       is allowed.
 *     - parent_id: The folder_id of the parent folder or 0 (zero) if the
 *       forum resides in the top level root folder.
 *     - vroot: The vroot in which the forum resides.
 *     - active: Whether the forum is active/visible (1) or not (0).
 *     - template: The name of the template to use for the folder.
 *     - language: The name of the language to use for the folder.
 *     TODO other forum fields. Maybe a different location would be better?
 *
 * @package    PhorumAPI
 * @subpackage ForumsAPI
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

/**
 * This array describes folder data fields. It is mainly used internally
 * for configuring how to handle the fields and for doing checks on them.
 */
$GLOBALS['PHORUM']['API']['folder_fields'] = array(
  'forum_id'                => 'int',
  'folder_flag'             => 'bool',
  'parent_id'               => 'int',
  'name'                    => 'string',
  'description'             => 'string',
  'active'                  => 'bool',
  'forum_path'              => 'array',
  'display_order'           => 'int',
  'vroot'                   => 'int',

  // Display settings.
  'template'                => 'string',
  'language'                => 'string'
);

/**
 * This array describes forum data fields. It is mainly used internally
 * for configuring how to handle the fields and for doing checks on them.
 */
$GLOBALS['PHORUM']['API']['forum_fields'] = array(
  'forum_id'                 => 'int',
  'folder_flag'              => 'bool',
  'parent_id'                => 'int',
  'name'                     => 'string',
  'description'              => 'string',
  'active'                   => 'bool',
  'forum_path'               => 'array',
  'display_order'            => 'int',
  'vroot'                    => 'int',
  'cache_version'            => 'int',

  // Display settings.
  'display_fixed'            => 'bool',
  'inherit_id'               => 'int',
  'template'                 => 'string',
  'language'                 => 'string',
  'reverse_threading'        => 'bool',
  'float_to_top'             => 'bool',
  'threaded_list'            => 'int',
  'list_length_flat'         => 'int',
  'list_length_threaded'     => 'int',
  'threaded_read'            => 'int',
  'read_length'              => 'int',
  'display_ip_address'       => 'bool',

  // Posting settings.
  'check_duplicate'          => 'bool',

  // Statistics and statistics settings.
  'message_count'            => 'int',
  'thread_count'             => 'int',
  'sticky_count'             => 'int',
  'last_post_time'           => 'int',
  'count_views'              => 'bool',
  'count_views_per_thread'   => 'bool',

  // Permission settings.
  'moderation'               => 'int',
  'email_moderators'         => 'bool',
  'allow_email_notify'       => 'bool',
  'pub_perms'                => 'int',
  'reg_perms'                => 'int',

  // Attachment settings.
  'allow_attachment_types'   => 'string',
  'max_attachment_size'      => 'int',
  'max_totalattachment_size' => 'int',
  'max_attachments'          => 'int',
);

// {{{ Function: phorum_api_forums_get
/**
 * Retrieve the data for forums and/or folders in various ways.
 *
 * @param mixed $forum_ids
 *     A single forum_id or an array of forum_ids for which to retrieve the
 *     forum data. If this parameter is NULL, then the $parent_id
 *     parameter will be checked.
 *
 * @param mixed $parent_id
 *     Retrieve the forum data for all forums that have their parent_id set
 *     to $parent_id. If this parameter is NULL, then the $vroot parameter
 *     will be checked.
 *
 * @param mixed $vroot
 *     Retrieve the forum data for all forums that are in the given $vroot.
 *     If this parameter is NULL, then the $inherit_id parameter will be
 *     checked.
 *
 * @param mixed $inherit_id
 *     Retrieve the forum data for all forums that inherit their settings
 *     from the forum with id $inherit_id.
 *
 * @return mixed
 *     If the $forum_ids parameter is used and if it contains a single
 *     forum_id, then a single array containg forum data is returned or
 *     NULL if the forum was not found.
 *     For all other cases, an array of forum data arrays is returned, indexed
 *     by the forum_id and sorted by their display order. If the $forum_ids
 *     parameter is an array containing non-existant forum_ids, then the
 *     return array will have no entry available in the returned array.
 */
function phorum_api_forums_get($forum_ids = NULL, $parent_id = NULL, $vroot = NULL, $inherit_id = NULL)
{
    // Retrieve the forums/folders from the database.
    $forums = phorum_db_get_forums($forum_ids, $parent_id, $vroot, $inherit_id);

    // Filter and process the returned records.
    foreach ($forums as $id => $forum)
    {
        // Find the fields specification to use for this record.
        $fields = $forum['folder_flag']
                ? $GLOBALS['PHORUM']['API']['folder_fields']
                : $GLOBALS['PHORUM']['API']['forum_fields'];

        // Initialize the filtered data array.
        $filtered = array('folder_flag' => $forum['folder_flag'] ? 1 : 0);

        // Add fields to the filtered data.
        foreach ($fields as $fld => $fldtype)
        {
            switch ($fldtype)
            {
                case 'int':
                    $filtered[$fld] = (int)$forum[$fld];
                    break;

                case 'string':
                    $filtered[$fld] = $forum[$fld];
                    break;

                case 'bool':
                    $filtered[$fld] = empty($forum[$fld]) ? FALSE : TRUE;
                    break;

                case 'array':
                    $filtered[$fld] = unserialize($forum[$fld]);
                    break;

                default:
                    trigger_error(
                        'phorum_api_forums_get(): Illegal field type used: ' .
                        htmlspecialchars($fldtype),
                        E_USER_ERROR
                    );
                    break;
            }
        }

        $forums[$id] = $filtered;
    }

    if ($forum_ids === NULL || is_array($forum_ids)) {
      return $forums;
    } else {
      return isset($forums[$forum_ids]) ? $forums[$forum_ids] : NULL;
    }
}
// }}}

// {{{ Function: phorum_api_forums_save()
/**
 * Create or update a folder or forum.
 *
 * @param array $data
 *     An array containing folder or forum data. This array should contain at
 *     least the field "forum_id". This field can be NULL to create a new
 *     entry with an automatically assigned forum_id. It can also be set to a
 *     forum_id to either update an existing entry or to create a new one
 *     with the provided forum_id.
 *     If a new entry is created, then all forum or folder fields must be
 *     provided in the data.
 *
 * @return integer
 *     The forum_id of the forum or folder. For new ones, the newly assigned
 *     forum_id will be returned.
 */
function phorum_api_forums_save($data)
{
    // $data must be an array.
    if (!is_array($data)) {
        trigger_error(
            'phorum_api_forums_save(): $data argument is not an array',
            E_USER_ERROR
        );
        return NULL;
    }

    // We need at least the forum_id field.
    if (!array_key_exists('forum_id', $data))  {
        trigger_error(
           'phorum_api_forums_save(): missing field "forum_id" ' .
           'in the data array',
           E_USER_ERROR
        );
        return NULL;
    }
    if ($data['forum_id'] !== NULL && !is_numeric($data['forum_id'])) {
        trigger_error(
            'phorum_api_forums_save(): field "forum_id" not NULL or numerical',
            E_USER_ERROR
        );
        return NULL;
    }

    // Check if we are handling an existing or new entry.
    $existing = NULL;
    if ($data['forum_id'] !== NULL) {
        $existing = phorum_api_forums_get($data['forum_id']);
    }

    // The forum_path is a field that is generated by the API code. So we
    // pull it from the incoming data array here.
    unset($data['forum_path']);

    // Create a data array that is understood by the database layer.
    // We start out with the existing record, if we have one.
    $dbdata = $existing === NULL ? array() : $existing;

    // Merge in the fields from the $data argument.
    foreach ($data as $fld => $val) {
        $dbdata[$fld] = $val;
    }

    // By now, we need the folder_flag field, so we know what kind
    // of entry we are handling.
    if (!array_key_exists('folder_flag', $dbdata))  {
        trigger_error(
           'phorum_api_forums_save(): missing field "folder_flag" ' .
           'in the data array',
           E_USER_ERROR
        );
        return NULL;
    }

    // The folder_flag cannot change during the lifetime of an entry.
    if ($existing) {
        $check1 = $existing['folder_flag'] ? TRUE : FALSE;
        $check2 = $dbdata['folder_flag']   ? TRUE : FALSE;
        if ($check1 != $check2) {
            trigger_error(
                "phorum_api_forums_save(): the folder_flag cannot change",
                E_USER_ERROR
            );
            return NULL;
        }
    }

    // Find the fields specification to use for this record.
    $fields = $dbdata['folder_flag']
            ? $GLOBALS['PHORUM']['API']['folder_fields']
            : $GLOBALS['PHORUM']['API']['forum_fields'];

    // Check and format fields.
    foreach ($dbdata as $fld => $val)
    {
        // Make sure that a valid field name is used. We do a strict check
        // on this (in the spirit of defensive programming).
        if (!array_key_exists($fld, $fields)) {
            trigger_error(
                'phorum_api_forums_save(): Illegal field name used in ' .
                'data: ' . htmlspecialchars($fld),
                E_USER_ERROR
            );
            return NULL;
        }

        $fldtype = $fields[$fld];
        unset($fields[$fld]); // for tracking if all fields are available.

        switch ($fldtype)
        {
            case 'int':
                $dbdata[$fld] = $val === NULL ? NULL : (int) $val;
                break;

            case 'string':
                $dbdata[$fld] = $val === NULL ? NULL : trim($val);
                break;

            case 'bool':
                $dbdata[$fld] = $val ? 1 : 0;
                break;

            case 'array':
                $dbdata[$fld] = is_array($val) ? serialize($val) : '';
                break;

            default:
                trigger_error(
                    'phorum_api_forums_save(): Illegal field type used: ' .
                    htmlspecialchars($fldtype),
                    E_USER_ERROR
                );
                return NULL;
                break;
        }
    }

    // Check if all required fields are available.
    // The forum_path is autogenerated and does not have to be provided.
    unset($fields['forum_path']);
    unset($dbdata['forum_path']);
    if (count($fields)) {
        trigger_error(
            'phorum_api_forums_save(): Missing field(s) in the data: ' .
            implode(', ', array_keys($fields)),
            E_USER_ERROR
        );
        return NULL;
    }

    // Add or update the forum or folder in the database.
    if ($existing) {
        phorum_db_update_forum($dbdata);
    } else {
        $dbdata['forum_id'] = phorum_db_add_forum($dbdata);
    }

    // (Re)build the forum_path if required.
    if ( !$existing ||
         ($existing['parent_id'] != $dbdata['parent_id']) ||
         ($existing['vroot']     != $dbdata['vroot']) ||
         ($existing['name']      != $dbdata['name']) ) {
        $path = phorum_api_forums_build_path($dbdata['forum_id']);
        phorum_db_update_forum(array(
            'forum_id'   => $dbdata['forum_id'],
            'forum_path' => serialize($path)
        ));
        print "SET " . join(",",$path) . "\n";
        print_r(array(
            'forum_id'   => $dbdata['forum_id'],
            'forum_path' => serialize($path)
        ));
    }


    return $dbdata['user_id'];
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

// {{{ Function: phorum_api_forums_build_path()
/**
 * This function can be used for building the folder paths that lead up to
 * forums/folders.
 *
 * The function is internally used by Phorum to build the paths that are stored
 * in the "forum_path" field of the forums table. If you need access to the
 * path for a folder or forum, then do not call this function for retrieving
 * that info, but look at the "forum_path" field instead.
 *
 * @param mixed $forum_id
 *     If $forum_id is NULL, then the paths for all available forums and
 *     folders will be built. Otherwise, only the path for the requested
 *     forum_id is built.
 *
 * @return array
 *     If the $forum_id parameter is a single forum_id, then a single path
 *     is returned. If it is NULL, then an array or paths is returned, indexed
 *     by the forum_id for which the path was built.
 *     Each path is an array, containing the nodes in the path
 *     (key = forum_id, value = name). The first element in a path array will
 *     be the (v)root and the last element the forum or folder for which the
 *     path was built.
 *
 *     Note: the root node (forum_id = 0) will also be returned in the
 *     data when using NULL or 0 as the $forum_id argument. This is however a
 *     generated node for which no database record exists. So if you are using
 *     this functions return data for updating folders in the database, then
 *     beware to skip the forum_id = 0 root node.
 */
function phorum_api_forums_build_path($forum_id = NULL)
{
    $paths = array();

    // The forum_id = 0 root node is not in the database.
    // Here, we create a representation for that node that will work.
    $root = array(
        'vroot'    => 0,
        'forum_id' => 0,
        'name'     => $GLOBALS['PHORUM']['title']
    );

    // If we are going to update the paths for all nodes, then we pull
    // in our full list of forums and folders from the database. If we only
    // need the path for a single node, then the node and all its parent
    // nodes are retrieved using single calls to the database.
    if ($forum_id === NULL) {
        $nodes = phorum_db_get_forums();
        $nodes[0] = $root;
    } else {
        if ($forum_id == 0) {
            $nodes = array(0 => $root);
        } else {
            $nodes = phorum_db_get_forums($forum_id);
        }
    }

    // Build the paths for the retrieved node(s).
    foreach($nodes as $id => $node)
    {
        $path = array();

        while (TRUE)
        {
            // Add the node to the path.
            $path[$node['forum_id']] = $node['name'];

            // Stop building when we hit a (v)root.
            if ($node['forum_id'] == 0 ||
                $node['vroot'] == $node['forum_id']) break;

            // Find the parent node. The root node (forum_id = 0) is special,
            // since that one is not in the database. We create an entry on
            // the fly for that one here.
            if ($node['parent_id'] == 0) {
                $node = $root;
            } elseif ($forum_id !== NULL) {
                $tmp = phorum_db_get_forums($node['parent_id']);
                $node = $tmp[$node['parent_id']];
            } else {
                $node = $nodes[$node['parent_id']];
            }
        }

        // Reverse the path, since we have been walking up the path here.
        // For the parts of the application that use this data, it's more
        // logical if the root nodes come first in the path arrays.
        $paths[$id] = array_reverse($path, TRUE);
    }

    // We cannot remember what this was needed for. For now, we leave it out.
    // $paths = array_reverse($folders, true);

    if ($forum_id === NULL) {
        return $paths;
    } else {
        return isset($paths[$forum_id]) ? $paths[$forum_id] : NULL;
    }
}
// }}}

// ------------------------------------------------------------------------
// Alias functions (useful shortcut calls to the main file api functions).
// ------------------------------------------------------------------------

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
   return phorum_api_forums_get(NULL, $folder_id);
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
    return phorum_api_forums_get(NULL, NULL, $vroot_id);
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
    return phorum_api_forums_get(NULL, NULL, NULL, $forum_id);
}
// }}}

?>
