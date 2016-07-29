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
 * This script implements the Phorum forum API.
 *
 * This API is used for managing the Phorum forum and folder hierarchy.
 * It can be used to retrieve information about the available forums and
 * folders and takes care of creating and editing them.
 *
 * This API combines forums and folders into a single API layer, because at the
 * data level, they are the same kind of entity. Folders are forums as well.
 * They just act differently, based on the "folder_flag" field.
 *
 * Below, you can find a description of the fields that are used for
 * forums and folders.
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
 *     TODO I think this should go in an "Internals" chapter in the
 *     developer docbook.
 *
 * @package    PhorumAPI
 * @subpackage Forums
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 *
 * @todo Implement PHORUM_FLAG_INCLUDE_EMPTY_FOLDERS flag.
 */

require_once PHORUM_PATH.'/include/api/custom_field.php';

// {{{ Constant and variable definitions

/**
 * Function call flag, which tells {@link phorum_api_forums_save()}
 * that it should not save the settings to the database, but only prepare
 * the data and return the prepared data array.
 */
define('PHORUM_FLAG_PREPARE', 1);

/**
 * Function call flag, which tells {@link phorum_api_forums_save()}
 * that the provided data have to be stored in the default settings.
 */
define('PHORUM_FLAG_DEFAULTS', 2);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should only contain forums from which the settings
 * can be inherited by another forum or folder.
 */
define('PHORUM_FLAG_INHERIT_MASTERS', 4);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should only contain folders.
 */
define('PHORUM_FLAG_FOLDERS', 8);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should only contain forums.
 */
define('PHORUM_FLAG_FORUMS', 16);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should contain inactive forums as well
 * (for these the "active" field is set to zero).
 */
define('PHORUM_FLAG_INCLUDE_INACTIVE', 32);

/**
 * Function call flag, which tells {@link phorum_api_forums_tree()}
 * to include empty folders in the tree.
 */
define('PHORUM_FLAG_INCLUDE_EMPTY_FOLDERS', 64);

/**
 * Function call flag, which tells {@link phorum_api_forums_format()}
 * to add information about unread messages to the formatted data.
 */
define('PHORUM_FLAG_ADD_UNREAD_INFO', 128);

/**
 * The FFLD_* definitions indicate the position of the configation
 * options in the forum field definitions.
 */
define('FFLD_MS',      0);
define('FFLD_TYPE',    1);
define('FFLD_DEFAULT', 2);

/**
 * This array describes folder data fields. It is mainly used internally
 * for configuring how to handle the fields and for doing checks on them.
 * Value format: <m|v>:<type>[:default]
 * m = master field; always determined by the folder's configuration data.
 * s = slave field; overridden by inheritance parent if inherid_id is set.
 */
$GLOBALS['PHORUM']['API']['folder_fields'] = array(
  'forum_id'                => 'm:int',
  'folder_flag'             => 'm:bool:1',
  'parent_id'               => 'm:int:0',
  'name'                    => 'm:string',
  'description'             => 'm:string:',
  'active'                  => 'm:bool:1',
  'forum_path'              => 'm:array',
  'display_order'           => 'm:int:0',
  'vroot'                   => 'm:int:0',
  'cache_version'           => 'm:int:0',
  'inherit_id'              => 'm:inherit_id:0',

  // Display settings.
  'template'                => 's:string:'.PHORUM_DEFAULT_TEMPLATE,
  'language'                => 's:string:'.PHORUM_DEFAULT_LANGUAGE
);

/**
 * This array describes forum data fields. It is mainly used internally
 * for configuring how to handle the fields and for doing checks on them.
 * Value format: <m|v>:<type>[:default]
 * m = master field; always determined by the forum's configuration data.
 * s = slave field; overridden by inheritance parent if inherid_id is set.
 */
$GLOBALS['PHORUM']['API']['forum_fields'] = array(
  'forum_id'                 => 'm:int',
  'folder_flag'              => 'm:bool:0',
  'parent_id'                => 'm:int:0',
  'name'                     => 'm:string',
  'description'              => 'm:string:',
  'active'                   => 'm:bool:1',
  'forum_path'               => 'm:array',
  'display_order'            => 'm:int:0',
  'vroot'                    => 'm:int:0',
  'cache_version'            => 'm:int:0',
  'inherit_id'               => 'm:inherit_id:0',

  // Display settings.
  'display_fixed'            => 's:bool:0',
  'template'                 => 's:string:'.PHORUM_DEFAULT_TEMPLATE,
  'language'                 => 's:string:'.PHORUM_DEFAULT_LANGUAGE,
  'reverse_threading'        => 's:bool:0',
  'float_to_top'             => 's:bool:1',
  'threaded_list'            => 's:int:0',
  'list_length_flat'         => 's:int:30',
  'list_length_threaded'     => 's:int:15',
  'threaded_read'            => 's:int:0',
  'read_length'              => 's:int:10',
  'display_ip_address'       => 's:bool:0',

  // Posting settings.
  'check_duplicate'          => 's:bool:1',

  // Statistics and statistics settings.
  'message_count'            => 'm:int:0',
  'thread_count'             => 'm:int:0',
  'sticky_count'             => 'm:int:0',
  'last_post_time'           => 'm:int:0',
  'count_views'              => 's:int:1',
  'count_views_per_thread'   => 's:bool:0',

  // Permission settings.
  'moderation'               => 's:int:0',
  'email_moderators'         => 's:bool:1',
  'allow_email_notify'       => 's:bool:1',
  'pub_perms'                => 's:int:'.PHORUM_USER_ALLOW_READ,
  'reg_perms'                => 's:int:'.(
       PHORUM_USER_ALLOW_READ  |
       PHORUM_USER_ALLOW_REPLY |
       PHORUM_USER_ALLOW_EDIT  |
       PHORUM_USER_ALLOW_NEW_TOPIC
  ),

  // Attachment settings.
  'allow_attachment_types'   => 's:string:',
  'max_attachment_size'      => 's:int:0',
  'max_totalattachment_size' => 's:int:0',
  'max_attachments'          => 's:int:0',
);
// }}}

// {{{ Function: phorum_api_forums_get
/**
 * Retrieve the data for forums and/or folders in various ways. Note that
 * only one of the parameters $forum_ids, $parent_id, $vroot and $inherit_id
 * will be effective at a time. The parameter $only_inherit_masters can be
 * used in conjunction with all of these.
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
 * @param mixed $inherit_id
 *     Retrieve the forum data for all forums that inherit their settings
 *     from the forum with id $inherit_id.
 *
 * @param mixed $vroot
 *     Retrieve the forum data for all forums that are in the given $vroot.
 *     If this parameter is NULL, then forums from any vroot will be
 *     returned. This parameter can be used in combination with
 *     the $forum_ids, $parent_id and $inherit_id parameters.
 *
 * @param integer $flags
 *     If the {@link PHORUM_FLAG_INHERIT_MASTERS} flag is set, then
 *     only forums that can act as a settings inheritance master will be
 *     returned (these are the forums that do not inherit their settings
 *     from either the default settings or from another forum).
 *     If the {@link PHORUM_FLAG_FOLDERS} flag is set, then only
 *     folders will be returned.
 *     If the {@link PHORUM_FLAG_FORUMS} flag is set, then only
 *     forums will be returned.
 *     If the {@link PHORUM_FLAG_INCLUDE_INACTIVE} flag is set, then
 *     inactive forums and folders will be included in the return data.
 *     This is mainly useful for administrative interfaces that need to
 *     be able to access all created forums and folders.
 *
 * @return mixed
 *     If the $forum_ids parameter is used and if it contains a single
 *     forum_id, then a single array containing forum data is returned or
 *     NULL if the forum was not found.
 *     For all other cases, an array of forum data arrays is returned, indexed
 *     by the forum_id and sorted by their display order. If the $forum_ids
 *     parameter is an array containing non-existent forum_ids, then the
 *     return array will have no entry available in the returned array for
 *     those forum_ids.
 */
function phorum_api_forums_get(
    $forum_ids = NULL, $parent_id = NULL, $inherit_id = NULL,
    $vroot = NULL, $flags = 0)
{
    global $PHORUM;

    // We might get an $inherit_id parameter that is NULL or -1, since we
    // present the database value NULL as -1 from this API (because using
    // NULL values in a form isn't really an option.
    $inherit_id = ($inherit_id != -1 && $inherit_id !== NULL)
                ? (int)$inherit_id : NULL;

    // Find the return_type parameter for the db call.
    // 0 = forums and folders, 1 = folders, 2 = forums.
    if ($flags & PHORUM_FLAG_FOLDERS) {
        $return_type = ($flags & PHORUM_FLAG_FORUMS) ? 0 : 1;
    } elseif ($flags & PHORUM_FLAG_FORUMS) {
        $return_type = 2;
    } else {
        $return_type = 0;
    }

    // Retrieve the forums/folders from the database.
    $forums = $PHORUM['DB']->get_forums(
        $forum_ids, $parent_id, $vroot, $inherit_id,
        $flags & PHORUM_FLAG_INHERIT_MASTERS,
        $return_type,
        $flags & PHORUM_FLAG_INCLUDE_INACTIVE
    );

    // Process the returned records.
    foreach ($forums as $id => $forum)
    {
        $forums[$id]['folder_flag'] = $forum['folder_flag'] ? 1 : 0;
        $forums[$id]['forum_path']  = unserialize($forum['forum_path']);

        // This is a special one. The database value is NULL or
        // a positive integer, but NULL is not an easy value to
        // use in HTML forms. Therefore, we provide the value
        // -1 to indicate a NULL value here.
        $forums[$id]['inherit_id'] = $forum['inherit_id'] === NULL
                                   ? -1 : (int) $forum['inherit_id'];
    }

    // retrieve and apply the custom fields for forums
    if (!empty($PHORUM['CUSTOM_FIELDS'][PHORUM_CUSTOM_FIELD_FORUM])) {
        $forums = phorum_api_custom_field_apply(
            PHORUM_CUSTOM_FIELD_FORUM, $forums);
    }

    // If forum_id 0 (zero) is requested, then we create a fake folder
    // record. This is the root folder, which does not correspond to an
    // actual record in the database.
    if ($forum_ids !== NULL) {
        if ((is_array($forum_ids) && in_array(0, $forum_ids)) ||
            (!is_array($forum_ids) && $forum_ids !== NULL && $forum_ids == 0)) {

            $template = $PHORUM['default_forum_options']['template'];
            $language = $PHORUM['default_forum_options']['language'];

            $forums[0] = array(
                'forum_id'      => 0,
                'folder_flag'   => 1,
                'vroot'         => 0,
                'parent_id'     => NULL,
                'inherit_id'    => 0,
                'active'        => 1,
                'name'          => $PHORUM['title'],
                'description'   => $PHORUM['description'],
                'forum_path'    => array(0 => $PHORUM['title']),
                'template'      => $template,
                'language'      => $language,
                'cache_version' => 0,
                'reg_perms'     => 0,
                'pub_perms'     => 0
            );
        }
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
 * This function can be used for creating and updating folders or forums and
 * for updating default forum settings.
 *
 * Here is an example for creating a forum below the folder with forum_id 1234,
 * which inherits its settings from the default forum settings.
 * <code>
 * $newforum = array(
 *     'forum_id'    => NULL,
 *     'folder_flag' => 0,
 *     'parent_id'   => 1234,
 *     'inherit_id'  => 0,
 *     'name'        => 'Foo bar baz talk'
 * );
 * $forum = phorum_api_forums_save($newforum);
 * print "The forum_id for the new forum is " . $forum['forum_id'] . "\n";
 * </code>
 *
 * This example will update some default forum settings. This will also
 * update the forums / folders that inherit their settings from the
 * default settings.
 * <code>
 * $newsettings = array(
 *     'display_ip_address' => 0,
 *     'count_views'        => 1,
 *     'language'           => 'foolang'
 * );
 * phorum_api_forums_save($newsettings, PHORUM_FLAG_DEFAULTS);
 * </code>
 *
 * @param array $data
 *     An array containing folder or forum data. This array should contain at
 *     least the field "forum_id". This field can be NULL to create a new
 *     entry with an automatically assigned forum_id (in which case you will
 *     also need to provide at least the fields "folder_flag" and "name).
 *     It can also be set to a forum_id to either update an existing entry or
 *     to create a new one with the provided forum_id.
 *
 * @param boolean $flags
 *     If the {@link PHORUM_FLAG_PREPARE} flag is set, then this function
 *     will not save the data in the database. Instead, it will only prepare
 *     the data for storage and return the prepared data.
 *     If the {@link PHORUM_FLAG_DEFAULTS} flag is set, then the data will
 *     be stored in the default forum settings.
 *
 * @return array
 *     If the {@link PHORUM_FLAG_PREPARE} is set, this function will only
 *     prepare the data for storage and return the prepared data array.
 *     Otherwise, the stored data will be returned. The main difference is
 *     that for new forums or folders, the forum_id field will be updated
 *     to the newly assigned forum_id.
 */
function phorum_api_forums_save($data, $flags = 0)
{
    global $PHORUM;

    // $data must be an array.
    if (!is_array($data)) {
        trigger_error(
            'phorum_api_forums_save(): $data argument is not an array',
            E_USER_ERROR
        );
        return NULL;
    }

    // Used for keeping track of an existing db record.
    $existing = NULL;
    // Initialize data for saving default forum settings.
    if ($flags & PHORUM_FLAG_DEFAULTS)
    {
        $existing = empty($PHORUM['default_forum_options'])
                  ? NULL : $PHORUM['default_forum_options'];

        // Force a few settings to static values to have the data
        // processed correctly by the code below.
        $data['forum_id']    = NULL;
        $data['parent_id']   = 0;
        $data['inherit_id']  = NULL;
        $data['folder_flag'] = 0;
        $data['name']        = 'Default settings';
    }
    // Initialize data for saving forum settings.
    else
    {
        // We always require the forum_id field. For new forums, we want to
        // retrieve an explicit forum_id = NULL field.
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
            $existing = phorum_api_forums_by_forum_id(
                $data['forum_id'], PHORUM_FLAG_INCLUDE_INACTIVE
            );
        }

        // The forum_path is a field that is generated by the API code. So we
        // pull it from the incoming data array here.
        unset($data['forum_path']);
    }

    // Create a data array that is understood by the database layer.
    // We start out with the existing record, if we have one.
    $dbdata = $existing === NULL ? array() : $existing;

    // Merge in the fields from the $data argument.
    foreach ($data as $fld => $val) {
        $dbdata[$fld] = $val;
    }
    // Some checks when we are not handling saving of default settings.
    if (!($flags & PHORUM_FLAG_DEFAULTS))
    {
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
        if ($existing)
        {
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
    }

    // Find the fields specification to use for this record.
    $fields = $dbdata['folder_flag']
            ? $PHORUM['API']['folder_fields']
            : $PHORUM['API']['forum_fields'];

    // A copy of the $fields array to keep track of missing fields.
    $missing = $fields;

    // the empty array to collect custom fields
    $custom_forum_field_data = array();

    // Check and format the provided fields.
    foreach ($dbdata as $fld => $val)
    {
        // Determine the field type.
        if (!array_key_exists($fld, $fields)) {
            $spec = array(FFLD_MS => 'm', FFLD_TYPE => 'custom_field');
        } else {
            $spec = explode(':', $fields[$fld]);
        }

        $fldtype = $spec[FFLD_TYPE];

        // For tracking if all required fields are available.
        unset($missing[$fld]);

        switch ($fldtype)
        {
            case 'int':
                $dbdata[$fld] = (int) $val;
                break;

            case 'inherit_id':
                // This is a special one. The database value is NULL or
                // a positive integer, but NULL is not an easy value to
                // use in HTML forms. Therefore, we also accept the value
                // -1 to indicate a NULL value here.
                $dbdata[$fld] = ($val === NULL || $val == -1)
                              ? NULL : (int) $val;
                break;

            case 'string':
                $dbdata[$fld] = trim($val);
                break;

            case 'bool':
                $dbdata[$fld] = $val ? 1 : 0;
                break;

            case 'array':
                $dbdata[$fld] = is_array($val) ? serialize($val) : '';
                break;
            case 'custom_field':
                $custom_forum_field_data[$fld] = $val;
                unset($dbdata[$fld]);
                break;

            default:
                trigger_error(
                    'phorum_api_forums_save(): Illegal field type used: ' .
                    htmlspecialchars($spec[FFLD_TYPE]),
                    E_USER_ERROR
                );
                return NULL;
                break;
        }
    }

    // The forum_path is autogenerated and does not have to be provided.
    // Therefore, we take it out of the loop here.
    unset($missing['forum_path']);
    unset($dbdata['forum_path']);

    // Check if all required fields are available.
    if (count($missing))
    {
        // Try to fill in some default values for the missing fields.
        foreach ($missing as $fld => $fldspec)
        {
            $spec = explode(':', $fldspec);
            if (isset($spec[FFLD_DEFAULT])) {
                $dbdata[$fld] = $spec[FFLD_DEFAULT];
                unset($missing[$fld]);
            }
        }
    }

    // Apply inheritance driven settings to the data if some sort of
    // inheritance is configured. Options for this field are:
    // - NULL       : no inheritance used
    // - 0          : inherit from the default forum options
    // - <forum_id> : inherit from the forum identified by this forum_id
    if ($dbdata['inherit_id'] !== NULL)
    {
        // Check if the settings for this forum aren't inherited by
        // a different forum already. Inherited inheritance is not allowed.
        if ($existing) {
            $childs = phorum_api_forums_by_inheritance($dbdata['forum_id']);
            if (!empty($childs)) {
                trigger_error(
                    'phorum_api_forums_save(): forum_id ' .
                    $dbdata['forum_id'] . ' cannot inherit data from some ' .
                    'other forum or default settings, because on or more ' .
                    'other folders and/or forums are inheriting their data ' .
                    'from this one already. Inherited inheritance is not ' .
                    'allowed.',
                    E_USER_ERROR
                );
                return NULL;
            }
        }

        // Inherit from the default settings.
        if ($dbdata['inherit_id'] == 0) {
            $defaults = $PHORUM['default_forum_options'];
        }
        // Inherit from a specific forum.
        else
        {
            // Inheriting from yourself? No way.
            if ($dbdata['inherit_id'] == $dbdata['forum_id']) {
                trigger_error(
                    'phorum_api_forums_save(): a forum or folder cannot ' .
                    'inherit settings from itself. Save was called for ' .
                    'forum_id ' . $dbdata['forum_id'] . ' with that same ' .
                    'forum_id set as the inherit_id.',
                    E_USER_ERROR
                );
                return NULL;
            }

            $defaults = phorum_api_forums_by_forum_id(
                $dbdata['inherit_id'], PHORUM_FLAG_INCLUDE_INACTIVE
            );

            // Check if the inherit_id forum was found.
            if ($defaults === NULL) {
                trigger_error(
                    'phorum_api_forums_save(): no forum found for ' .
                    'inherid_id ' . $dbdata['inherit_id'],
                    E_USER_ERROR
                );
                return NULL;
            }

            // It is only allowed to inherit settings from forums.
            if (!empty($defaults['folder_flag'])) {
                trigger_error(
                    'phorum_api_forums_save(): inherit_id ' .
                    $dbdata['inherit_id'] . ' points to a folder instead of ' .
                    'a forum. You can only inherit from forums.',
                    E_USER_ERROR
                );
                return NULL;
            }

            // Inherited inheritance is not allowed.
            if ($defaults['inherit_id'] != -1) {
                trigger_error(
                    'phorum_api_forums_save(): inherit_id ' .
                    $dbdata['inherit_id'] . ' points to a forum that ' .
                    'inherits settings itself. Inherited inheritance is ' .
                    'not allowed.',
                    E_USER_ERROR
                );
                return NULL;
            }
        }

        // Overlay our data record with the inherited settings.
        if (is_array($defaults)){
            foreach ($defaults as $fld => $value)
            {
                // We need to check if the $fld is in $fields, because we
                // could be applying forum defaults to a folder here.
                // A folder does not contain all the same fields as a forum.
                // Also check if we're handling a slave (s) field.
                if (isset($fields[$fld]) && $fields[$fld][0] == 's') {
                    $dbdata[$fld] = $value;
                    unset($missing[$fld]);
                }
            }
        }
    }

    // Check if there are any missing fields left.
    if (count($missing)) {
        trigger_error(
            'phorum_api_forums_save(): Missing field(s) in the data: ' .
            implode(', ', array_keys($missing)),
            E_USER_ERROR
        );
        return NULL;
    }

    // If we are storing default settings, then filter the data array to
    // only contain fields that are no master fields. We could store them
    // unfiltered in the database, but this provides cleaner data.
    if ($flags & PHORUM_FLAG_DEFAULTS)
    {
        $filtered = array();
        foreach ($dbdata as $fld => $value) {
            if (isset($fields[$fld]) && $fields[$fld][0] == 's') {
                $filtered[$fld] = $value;
            }
        }
        $dbdata = $filtered;
    }

    // Return the prepared data if the PHORUM_FLAG_PREPARE flag was set.
    if ($flags & PHORUM_FLAG_PREPARE) {
        return $dbdata;
    }

    // Store default settings in the database.
    if ($flags & PHORUM_FLAG_DEFAULTS)
    {
        // Create or update the settings record.
        $PHORUM['DB']->update_settings(array(
            'default_forum_options' => $dbdata
        ));

        // Update the global default forum options variable, so it
        // matches the updated settings.
        $PHORUM['default_forum_options'] = $dbdata;

        // Update all forums that inherit the default settings.
        $childs = phorum_api_forums_by_inheritance(0);
        if (!empty($childs)) {
            foreach ($childs as $child) {
                phorum_api_forums_save(array(
                    'forum_id' => $child['forum_id']
                ));
            }
        }

        return $dbdata;
    }

    // Store the forum or folder in the database.
    if ($existing) {
        $PHORUM['DB']->update_forum($dbdata);
    } else {
        $dbdata['forum_id'] = $PHORUM['DB']->add_forum($dbdata);
    }

    if (is_array($custom_forum_field_data) &&
        count($custom_forum_field_data) &&
        !empty($dbdata['forum_id']))
    {
        $PHORUM['DB']->save_custom_fields(
            $dbdata['forum_id'],
            PHORUM_CUSTOM_FIELD_FORUM,
            $custom_forum_field_data
        );
    }

    // Handle changes that influence the forum tree paths.
    // We handle the updates in a separate function, because we need
    // to be able to do recursive handling for those.
    if ( !$existing ||
         ($existing['parent_id'] != $dbdata['parent_id']) ||
         ($existing['vroot']     != $dbdata['vroot']) ||
         ($existing['name']      != $dbdata['name']) ) {

        $recurse = $existing ? TRUE : FALSE;
        if (!phorum_api_forums_update_path($dbdata, $recurse)) return NULL;
    }

    // Handle cascading of inherited settings.
    // Inheritance is only possible from existing forums that do not inherit
    // settings themselves. So only if the currently saved entry does match
    // those criteria, we might have to cascade.
    if ($existing &&
        $existing['folder_flag'] == 0 &&
        $existing['inherit_id'] == -1)
    {
        // Find the forums and folders that inherit from this forum.
        $childs = phorum_api_forums_by_inheritance($existing['forum_id']);

        // If there are child forums, then update their inherited settings.
        if (!empty($childs)) {
            foreach ($childs as $child) {
                phorum_api_forums_save(array(
                    'forum_id' => $child['forum_id']
                ));
            }
        }
    }

    return $dbdata;
}
// }}}

// {{{ Function: phorum_api_forums_update_path()
/**
 * This function can be used to (recursively) update forum_path fields.
 *
 * The function is internally used by Phorum to update the paths that are
 * stored in the "forum_path" field of the forums table. Under normal
 * circumstances, this function will be called when appropriate by the
 * {@link phorum_api_forums_save()} function.
 *
 * @param array $forum
 *     A forum data array. The forum_path will be updated for this forum.
 *     The array requires at least the fields: forum_id, parent_id,
 *     folder_flag and vroot.
 *
 * @param boolean $recurse
 *     If this parameter is set to TRUE (the default), then recursive
 *     path updates will be done. The function will walk down the folder/forum
 *     tree to update all paths.
 *
 * @return mixed
 *     On failure trigger_error() will be called. If some error handler
 *     does not stop script execution, this function will return NULL.
 *     On success, an updated $forum array will be returned.
 */
function phorum_api_forums_update_path($forum, $recurse = TRUE)
{
    global $PHORUM;

    // Check if the parent_id is valid.
    $parent = phorum_api_forums_get(
        $forum['parent_id'], PHORUM_FLAG_INCLUDE_INACTIVE
    );

    // Check if the parent was found.
    if ($parent === NULL) {
        trigger_error(
            'phorum_api_forums_save(): parent_id ' .
            htmlspecialchars($forum['parent_id']) . ' points to a folder ' .
            'that does not exist.',
            E_USER_ERROR
        );
        return NULL;
    }

    // Check if the parent is a folder.
    if (!$parent['folder_flag']) {
        trigger_error(
            'phorum_api_forums_save(): parent_id ' .
            htmlspecialchars($forum['parent_id']) . ' does not point to ' .
            'a folder. You can only put forums/folders inside folders.',
            E_USER_ERROR
        );
        return NULL;
    }

    // If this is not a vroot folder, then the $forum needs to inherit
    // its vroot from its parent. We'll silently fix inconsitencies
    // in this info here.
    if (!$forum['folder_flag'] || $forum['vroot'] != $forum['forum_id']) {
        $forum['vroot'] = $parent['vroot'];
    }

    // Check if the vroot is valid.
    if ($forum['vroot'] != 0)
    {
        // Retrieve the info from the vroot.
        $vroot = phorum_api_forums_get(
            $forum['vroot'], NULL, NULL, NULL, PHORUM_FLAG_INCLUDE_INACTIVE
        );

        // Check if the vroot was found.
        if ($vroot === NULL) {
            trigger_error(
                'phorum_api_forums_save(): vroot ' .
                htmlspecialchars($forum['vroot']) . ' points to a folder ' .
                'that does not exist.',
                E_USER_ERROR
            );
            return NULL;
        }

        // Check if the vroot is a folder.
        if (!$vroot['folder_flag']) {
            trigger_error(
                'phorum_api_forums_save(): vroot ' .
                htmlspecialchars($forum['vroot']) . ' does not point ' .
                'to a folder. Only folders can be vroots.',
                E_USER_ERROR
            );
            return NULL;
        }

        // Check if the vroot folder is setup as a vroot.
        if ($vroot['vroot'] != $vroot['forum_id']) {
            trigger_error(
                'phorum_api_forums_save(): vroot ' .
                htmlspecialchars($forum['vroot']) . ' points to a folder ' .
                'that is  not setup as a vroot folder.',
                E_USER_ERROR
            );
            return NULL;
        }
    }

    // Rebuild the forum_path for this forum.
    $path = phorum_api_forums_build_path($forum['forum_id']);
    $forum['forum_path'] = $path;
    $PHORUM['DB']->update_forum(array(
        'vroot'      => $forum['vroot'],
        'forum_id'   => $forum['forum_id'],
        'forum_path' => $forum['forum_path']
    ));

    // Cascade path updates down the forum tree. This is only
    // applicable to folders and if recursion is enabled.
    if ($forum['folder_flag'] && $recurse)
    {
        // Find the forums and folders that are contained by this folder.
        $childs = phorum_api_forums_by_parent_id($forum['forum_id']);

        // Handle recursion for the child forums and folders.
        if (!empty($childs)) {
            foreach ($childs as $child){
                if (!phorum_api_forums_update_path($child)) {
                    return NULL;
                }
            }
        }
    }

    return $forum;
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
 * that info, but look at the "forum_path" field in the forum or folder
 * info instead.
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
    global $PHORUM;

    $paths = array();

    // The forum_id = 0 root node is not in the database.
    // Here, we create a representation for that node that will work.
    $root = array(
        'vroot'    => 0,
        'forum_id' => 0,
        'name'     => $PHORUM['title']
    );

    // If we are going to update the paths for all nodes, then we pull
    // in our full list of forums and folders from the database. If we only
    // need the path for a single node, then the node and all its parent
    // nodes are retrieved using single calls to the database.
    if ($forum_id === NULL) {
        $nodes = $PHORUM['DB']->get_forums(NULL,NULL,NULL,NULL,false,0,true);
        $nodes[0] = $root;
    } else {
        if ($forum_id == 0) {
            $nodes = array(0 => $root);
        } else {
            $nodes = $PHORUM['DB']->get_forums($forum_id,NULL,NULL,NULL,false,0,true);
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
                $tmp = $PHORUM['DB']->get_forums(
                    $node['parent_id'],NULL,NULL,NULL,false,0,true);
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

    if ($forum_id === NULL) {
        return $paths;
    } else {
        return isset($paths[$forum_id]) ? $paths[$forum_id] : NULL;
    }
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
 *     - "down": Move the forum or folder $value positions down
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
    global $PHORUM;

    settype($folder_id, 'int');
    settype($forum_id, 'int');
    if ($value !== NULL) settype($value, 'int');

    // Get the forums for the specified folder.
    $forums = phorum_api_forums_by_folder_id($folder_id);

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
                'phorum_api_forums_change_order(): Illegal $movement ' .
                'parameter "'.htmlspecialchars($movement) . '" used',
                E_USER_ERROR
            );
            return NULL;
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
            $PHORUM['DB']->update_forum(array(
                'forum_id'      => $forum_id,
                'display_order' => $display_order
            ));
        }
    }
}
// }}}

// {{{ Function: phorum_api_forums_tree()
/**
 * This function can be used to build a tree structure for the available
 * folders and forums.
 *
 * @param mixed $vroot
 *     The vroot for which to build the forums tree (0 (zero) to
 *     use the main root folder) or NULL to use the current (v)root.
 *
 * @param int $flags
 *     If the {@link PHORUM_FLAG_INCLUDE_INACTIVE} flag is set, then
 *     inactive forums and folders will be included in the tree.
 *     If the {@link PHORUM_FLAG_INCLUDE_EMPTY_FOLDERS} flag is set, then
 *     empty folders will be included in the tree. By default, empty folders
 *     will be taken out of the tree.
 *
 * @return array
 *     An array containing arrays that describe nodes in the tree.
 *     The nodes are in the order in which they would appear in an expanded
 *     tree, moving from top to bottom. An "indent" field is added to each
 *     node array to tell at what indention level the node lives.
 */
function phorum_api_forums_tree($vroot = NULL, $flags = 0)
{
    global $PHORUM;

    if ($vroot === NULL) {
        $vroot = isset($PHORUM['vroot']) ? $PHORUM['vroot'] : 0;
    } else {
        settype($vroot, 'int');
    }

    // Get the information for the root.
    $root = phorum_api_forums_by_forum_id($vroot, $flags);
    if (!$root) {
        trigger_error(
            "phorum_api_forums_tree(): vroot $vroot does not exist",
            E_USER_ERROR
        );
        return NULL;
    }
    if ($root['vroot'] != $root['forum_id']) {
        trigger_error(
            "phorum_api_forums_tree(): vroot $vroot is not a vroot folder",
            E_USER_ERROR
        );
        return NULL;
    }

    // Temporarily witch to the vroot for which we are building a tree.
    $orig_vroot = isset($PHORUM['vroot']) ? $PHORUM['vroot'] : 0;
    $PHORUM['vroot'] = $vroot;

    // Check what forums the current user can read in that vroot.
    $allowed_forums = phorum_api_user_check_access(
        PHORUM_USER_ALLOW_READ, PHORUM_ACCESS_LIST
    );

    // Load the data for those forums.
    $forums = phorum_api_forums_by_forum_id($allowed_forums, $flags);

    // Sort the forums in a tree structure.
    // First pass: build a parent / child relationship structure.
    $tmp_forums = array();
    foreach ($forums as $forum_id => $forum)
    {
        $tmp_forums[$forum_id]['forum_id'] = $forum_id;
        $tmp_forums[$forum_id]['parent'] = $forum['parent_id'];
        if (empty($forums[$forum["parent_id"]]["childcount"])) {
            $tmp_forums[$forum["parent_id"]]["children"] = array($forum_id);
            $forums[$forum["parent_id"]]["childcount"] = 1;
        } else {
            $tmp_forums[$forum["parent_id"]]["children"][] = $forum_id;
            $forums[$forum["parent_id"]]["childcount"]++;
        }
    }

    // Second pass: sort the folders and forums in their tree order.
    $order = array();
    $stack = array();
    $seen  = array();
    $curr_id = $vroot;
    while (count($tmp_forums))
    {
        // Add the current element to the tree order array. Do not add it
        // in case we've already seen it (we move down and back up the tree
        // during processing, so we could see an element twice
        // while doing that).
        if ($curr_id != 0 && empty($seen[$curr_id])) {
            $order[$curr_id] = $forums[$curr_id];
            $seen[$curr_id] = true;
        }

        // Push the current element on the tree walking stack
        // to move down the tree.
        array_push($stack, $curr_id);

        // Get the current element's data.
        $data = $tmp_forums[$curr_id];

        // If there are no children (anymore), then move back up the the tree.
        if (empty($data["children"]))
        {
            unset($tmp_forums[$curr_id]);
            array_pop($stack);
            $curr_id = array_pop($stack);
        }
        // Otherwise, take the first child and process that one in the
        // next iteration of this loop.
        else {
            $curr_id = array_shift($tmp_forums[$curr_id]["children"]);
        }

        if (!is_numeric($curr_id)) break;
    }

    $tree = array();
    foreach ($order as $forum)
    {
        if ($forum["folder_flag"])
        {
            // Skip empty folders, if we didn't request them
            if (empty($forums[$forum['forum_id']]['childcount']) &&
               !($flags & PHORUM_FLAG_INCLUDE_EMPTY_FOLDERS)) continue;

            $url = phorum_api_url(PHORUM_INDEX_URL, $forum["forum_id"]);
        } else {
            $url = phorum_api_url(PHORUM_LIST_URL, $forum["forum_id"]);
        }

        // Add the indent level for the node.
        $indent = count($forum["forum_path"]) - 2;
        if($indent < 0) $indent = 0;
        $forum['indent'] = $indent;

        // Some entries that are added to the forum array to be backward
        // compatible with the deprecated phorum_build_forum_list() function.
        $forum['stripped_name'] = strip_tags($forum['name']);
        $forum['indent_spaces'] = str_repeat('&nbsp;', $indent);
        $forum['url']           = $url;
        $forum['path']          = $forum['forum_path'];

        $tree[$forum["forum_id"]] = $forum;
    }

    return $tree;
}
// }}}

// {{{ Function: phorum_api_forums_get_parent_id_options()
/**
 * This function can be used to build a list of valid parent_id options
 * for a given forum_id.
 *
 * The forum_id parameter is used to skip the folder and its own children
 * when creating a parent folder list for a folder.
 *
 * The returned options list consists of:
 * - Folders that can act as a parent folder
 * - An option for using the root folder (which is not a real folder in the db)
 *
 * @param mixed $forum_id
 *     The forum_id for which to create the parent folder list or NULL if all
 *     possible parent folders should be included in the list (useful in
 *     case you need a list of parent_id options for a new forum or folder).
 *
 * @return array
 *     An array of valid parent folder options. The keys are values that can
 *     be used for setting the parent_id. The values are descriptions of
 *     the options.
 */
function phorum_api_forums_get_parent_id_options($forum_id = NULL)
{
    // The options array to build.
    $options = array();

    // Retrieve the available folders.
    $folders = phorum_api_forums_get(
        NULL, NULL, NULL, NULL,
        PHORUM_FLAG_FOLDERS | PHORUM_FLAG_INCLUDE_INACTIVE
    );

    // Add the available folders.
    foreach ($folders as $id => $folder)
    {
        // Skip the folder and its childs for which a parent_id option
        // list is being built.
        if (!empty($forum_id) &&
            isset($folder['forum_path'][$forum_id])) {
            continue;
        }

        // Format the option description.
        if ($folder['vroot']) {
            $options[$id] = '/ Vroot: ' . implode(' / ', $folder['forum_path']);
        } else {
            array_shift($folder['forum_path']);
            $options[$id] = '/ ' . implode(' / ', $folder['forum_path']);
        }
    }

    // Sort the options.
    natcasesort($options);

    // Add the root folder option. Make sure it always is the first option.
    $options = array_reverse($options, TRUE);
    $options[0] = '/ (Root folder)';
    $options = array_reverse($options, TRUE);

    return $options;
}
// }}}

// {{{ Function: phorum_api_forums_get_inherit_id_options()
/**
 * This function can be used to build a list of valid inherit_id options
 * for a given forum_id.
 *
 * The forum_id is used to skip the forum itself when building an inheritance
 * options list for a forum. A forum cannot inherit its own settings.
 *
 * The returned options list consists of:
 * - Forums that can act as an inherit master
 * - An option for inheriting from the default forum settings
 * - An option for using no inheritance at all
 *
 * @param mixed $forum_id
 *     The forum_id for which to create the options list or NULL if all
 *     possible inherit masters should be included in the list (useful in
 *     case you need a list of inherit_id options for a new forum or folder).
 *
 * @return array
 *     An array of inheritance options. The keys are values that can be
 *     used for setting the inherit_id. The values are descriptions of
 *     the options.
 */
function phorum_api_forums_get_inherit_id_options($forum_id = NULL)
{
    if ($forum_id !== NULL) settype($forum_id, 'int');

    // The options array to build.
    $options = array();

    // Retrieve the forums that can be used for inheriting settings.
    $masters = phorum_api_forums_get(
        NULL, NULL, NULL, NULL,
        PHORUM_FLAG_INHERIT_MASTERS | PHORUM_FLAG_INCLUDE_INACTIVE
    );

    // Remove the forum_id to ignore from the list.
    if ($forum_id !== NULL) {
        unset($masters[$forum_id]);
    }

    // Add the available inheritable forums.
    foreach ($masters as $id => $forum)
    {
        // Format the option description.
        if ($forum['vroot']) {
            $options[$id] = '/ Vroot: ' . implode(' / ', $forum['forum_path']);
        } else {
            array_shift($forum['forum_path']);
            $options[$id] = '/ ' . implode(' / ', $forum['forum_path']);
        }
    }

    // Sort the options.
    natcasesort($options);

    // Add the standard inheritance options. Make sure they always are
    // the first options in the list.
    $options = array_reverse($options, TRUE);
    $options[0]  = "The default forum settings";
    $options[-1] = "No inheritance - I want to customize the settings";
    $options = array_reverse($options, TRUE);

    return $options;
}
// }}}

// {{{ Function: phorum_api_forums_get_display_modes()
/**
 * Retrieve the display modes for the list and read pages for a given forum.
 *
 * @param integer|array $forum
 *   The id of the forum for which to retrieve the display modes or
 *   a forum data array.
 *
 * @return array
 *   An array containing two fields: "list" and "read".
 *   The values of these fields indicate the read mode.
 *   This is one of the values:
 *   - 0 : flat reading
 *   - 1 : threaded reading
 *   - 2 : hybrid reading (only applicable for "read")
 */
function phorum_api_forums_get_display_modes($forum)
{
    global $PHORUM;

    if (!is_array($forum))
    {
        $forum = phorum_api_forums_by_forum_id($forum);
        if (!$forum) {
            trigger_error(
                "phorum_api_forums_get_display_modes(): no forum found for " .
                "forum id $forum",
                E_USER_ERROR
            );
            return NULL;
        }
    }

    // Fetch the display modes from the forum settings.
    $read_mode = $forum['threaded_read'];
    $list_mode = $forum['threaded_list'];

    // Apply user overrides, when applicable.
    if (empty($forum['display_fixed']) && $PHORUM['user']['user_id'])
    {
        $user = $PHORUM['user'];

        if ($user["threaded_read"] == PHORUM_THREADED_ON) {
            $read_mode = 1;
        } elseif ($user["threaded_read"] == PHORUM_THREADED_OFF) {
            $read_mode = 0;
        } elseif ($user["threaded_read"] == PHORUM_THREADED_HYBRID) {
            $read_mode = 2;
        }

        if ($PHORUM["user"]["threaded_list"] == PHORUM_THREADED_ON) {
            $list_mode = 1;
        } elseif ($PHORUM["user"]["threaded_list"] == PHORUM_THREADED_OFF) {
            $list_mode = 0;
        }
    }

    // Return the results.
    return array(
        'list' => $list_mode,
        'read' => $read_mode
    );
}
// }}}

// {{{ Function: phorum_api_forums_increment_cache_version()
/**
 * Increment the cache_version value for a forum.
 *
 * @param integer $forum_id
 */
function phorum_api_forums_increment_cache_version($forum_id)
{
    $forum = phorum_api_forums_by_forum_id($forum_id);
    if (!$forum) {
        trigger_error(
            "phorum_api_forums_increment_cache_version(): no forum found for " .
            "forum id $forum_id",
            E_USER_ERROR
        );
        return NULL;
    }

    phorum_api_forums_save(array(
        'forum_id'      => $forum['forum_id'],
        'cache_version' => $forum['cache_version'] + 1
    ));
}
// }}}

// {{{ Function: phorum_api_forums_delete()
/**
 * Delete a forum or folder.
 *
 * When a folder is deleted, then the contained folders and forums are
 * linked to the parent of the folder.
 *
 * @param integer $forum_id
 *   The forum_id to delete.
 *
 * @return mixed
 *   An array containing the data for the deleted forum or folder.
 *   NULL in case no forum or folder exists for the provided forum id.
 */
function phorum_api_forums_delete($forum_id)
{
    global $PHORUM;

    $forum = phorum_api_forums_get($forum_id);

    // Check if the forum or folder was found. If not, then return NULL.
    // We do not trigger an error here, since the forum/folder not existing
    // is the desired situation anyway.
    if ($forum === NULL) {
        return NULL;
    }

    // Handle deleting a folder.
    if ($forum['folder_flag'])
    {
        /*
         * [hook]
         *     admin_folder_delete
         *
         * [availability]
         *     Phorum 5 >= 5.3
         *
         * [description]
         *     This hook is called whenever a folder is deleted.
         *
         * [category]
         *     Admin interface
         *
         * [when]
         *     Right before the folder will be deleted from the database.
         *
         * [input]
         *     The ID of the folder.
         *
         * [output]
         *     Same as input.
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_admin_folder_delete ($id)
         *     {
         *         // E.g. Notify an external system that the folder has
         *         // been deleted.
         *
         *         // Return the folder ID for other hooks.
         *         return $id;
         *
         *     }
         *     </hookcode>
         */
        phorum_api_hook("admin_folder_delete", $forum_id);

        // When the folder is a vroot folder currently, then disable
        // the vroot setting for it by linking it to the vroot of
        // the parent folder. This will take care of recursive updates
        // down the hierarchy as well.
        if ($forum['vroot'] == $forum['forum_id'])
        {
            $parent_vroot = 0;
            if ($forum['parent_id']) {
                $parent_folder = phorum_api_forums_get($forum['parent_id']);
                if ($parent_folder) { // This check should not be necessary.
                    $parent_vroot = $parent_folder['vroot'];
                }
            }

            phorum_api_forums_save(array(
                'forum_id'      => $forum['forum_id'],
                'vroot'         => $parent_vroot
            ));
        }

        // This call deletes the folder from the database.
        // It will link child folders and forums to the deleted folder's parent.
        $PHORUM['DB']->drop_folder($forum_id);
    }
    // Handle deleting a forum.
    else
    {
       /*
        * [hook]
        *     admin_forum_delete
        *
        * [description]
        *     This hook is called whenever a forum is deleted.
        *
        * [category]
        *     Admin interface
        *
        * [when]
        *     Right before the forum will be deleted from the database.
        *
        * [input]
        *     The ID of the forum.
        *
        * [output]
        *     Same as input.
        *
        * [example]
        *     <hookcode>
        *     function phorum_mod_foo_admin_forum_delete ($id)
        *     {
        *         // E.g. Notify an external system that the forum has
        *         // been deleted.
        *
        *         // Return the forum ID for other hooks.
        *         return $id;
        *
        *     }
        *     </hookcode>
        */
        phorum_api_hook("admin_forum_delete", $forum_id);

        $PHORUM['DB']->drop_forum($forum_id);
    }

    return $forum;
}
// }}}

// ------------------------------------------------------------------------
// Alias functions (useful shortcut calls to the main file api functions).
// ------------------------------------------------------------------------

// {{{ Function: phorum_api_forums_by_forum_id()
/**
 * Retrieve data for one or more forums based on the forum_id.
 *
 * @param mixed $forum_ids
 *     A single forum_id or an array of forum_ids for which to
 *     retrieve the forum data.
 *
 * @param int $flags
 *     This function takes the same flags as the
 *     {@link phorum_api_forums_get()} function.
 *
 * @return mixed
 *     If the $forum_ids parameter contains a single forum_id, then a single
 *     array containing forum data is returned or NULL if the forum was not
 *     found.
 *     Otherwise, an array of forum data arrays is returned, indexed
 *     by the forum_id and sorted by their display order. If the $forum_ids
 *     parameter is an array containing non-existent forum_ids, then the
 *     return array will have no entry available in the returned array for
 *     those forum_ids.
 */
function phorum_api_forums_by_forum_id($forum_ids = 0, $flags = 0)
{
   return phorum_api_forums_get($forum_ids, NULL, NULL, NULL, $flags);
}
// }}}

// {{{ Function: phorum_api_forums_by_folder_id()
/**
 * Retrieve data for all direct descendant forums and folders within a folder.
 *
 * @param integer $folder_id
 *     The forum_id of the folder for which to retrieve the forums.
 *
 * @param int $flags
 *     This function takes the same flags as the
 *     {@link phorum_api_forums_get()} function.
 *
 * @return array
 *     An array of forums and folders, index by the their forum_id and sorted
 *     by their display order.
 */
function phorum_api_forums_by_folder_id($folder_id = 0, $flags = 0)
{
   return phorum_api_forums_get(NULL, $folder_id, NULL, NULL, $flags);
}
// }}}

// {{{ Function: phorum_api_forums_by_parent_id()
/**
 * Retrieve data for all child forums and folders for a given parent folder.
 *
 * @param integer $parent_id
 *     The parent_id of the folder for which to retrieve the forums.
 *
 * @param int $flags
 *     This function takes the same flags as the
 *     {@link phorum_api_forums_get()} function.
 *
 * @return array
 *     An array of forums and folders, index by the their forum_id and sorted
 *     by their display order.
 */
function phorum_api_forums_by_parent_id($parent_id = 0, $flags = 0)
{
    return phorum_api_forums_get(NULL, $parent_id, NULL, NULL, $flags);
}
// }}}

// {{{ Function: phorum_api_forums_by_vroot()
/**
 * Retrieve data for all forums and folders that belong to a certain vroot.
 *
 * @param integer $vroot_id
 *     The forum_id of the vroot for which to retrieve the forums.
 *
 * @param int $flags
 *     This function takes the same flags as the
 *     {@link phorum_api_forums_get()} function.
 *
 * @return array
 *     An array of forums and folders, index by the their forum_id and sorted
 *     by their display order.
 */
function phorum_api_forums_by_vroot($vroot_id = 0, $flags = 0)
{
    return phorum_api_forums_get(NULL, NULL, NULL, $vroot_id, $flags);
}
// }}}

// {{{ Function: phorum_api_forums_by_inheritance()
/**
 * Retrieve data for all forums and folders that inherit their settings
 * from a certain forum.
 *
 * @param integer $forum_id
 *     The forum_id for which to check what forums inherit its setting.
 *
 * @param int $flags
 *     This function takes the same flags as the
 *     {@link phorum_api_forums_get()} function.
 *
 * @return array
 *     An array of forums and folders, index by the their forum_id and sorted
 *     by their display order.
 */
function phorum_api_forums_by_inheritance($forum_id = 0, $flags = 0)
{
    return phorum_api_forums_get(NULL, NULL, $forum_id, NULL, $flags);
}
// }}}

?>
