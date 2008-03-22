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

/**
 * This script implements the Phorum custom profile fields API.
 *
 * Custom profile fields are a way of dynamically extending the available
 * data fields for a user, without having to extend the user database table
 * with additional fields.
 *
 * This API can be used for handling the configuration of these custom
 * profile fields. The actual use of the fields is fully integrated in the 
 * Phorum user API.
 *
 * This is a backward compatibility layer, which interfaces to the
 * Custom Fields API, which can be used to tie custom fields to other
 * things than users alone. If you start writing new code, then it's best
 * to use the Custom Fields API right away instead of this one.
 *
 * @package    PhorumAPI
 * @subpackage CustomProfileFieldAPI
 * @copyright  2008, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 * @deprecated Superceded by the more generic Custom Fields API.
 */

if (!defined('PHORUM')) return;

require_once('./include/api/custom_fields.php');

// {{{ Function: phorum_api_custom_profile_field_configure
/**
 * Create or update the configuration for a custom profile field.
 *
 * @param array $field
 *     This parameter holds the field configuration to save. This array
 *     must contain the following fields: 
 *
 *     - id: If a new field has to be created, then use NULL for this field.
 *           If a profile field has to be updated, then use the existing 
 *           profile field's id.
 *
 *     - name: The name that has to be assigned to the custom profile field. 
 *           This name can only contain letters, numbers and underscores
 *           (_) and it has to start with a letter.
 *
 *     The following fields are optional. If they are missing, then a default
 *     value will be used for them.
 *
 *     - length: The maximum length for the field data. This will make sure
 *           that the data that is stored in the custom profile field will
 *           be truncated in case its length surpasses the configured
 *           custom profile field length. If this field is missing or set
 *           to NULL, then the default length 255 will be used.
 *
 *     - html_disabled: If this field is set to a true value, then
 *           special HTML characters are not usable in this field. When
 *           displaying the custom field's data, Phorum will automatically
 *           escape these characters. Only use a false value for this
 *           field if the data that will be saved in the field is really safe
 *           for direct use in a web page (to learn about the security risks
 *           involved, search for "XSS" and "cross site scripting" on 
 *           the internet). If this field is missing or set to NULL, then 
 *           the default setting TRUE will be used.
 *
 *     - show_in_admin: If this field is set to a true value, then the field
 *           will be displayed on the details page for a user in the admin
 *           "Edit Users" section. If this field is missing or set to NULL,
 *           then the default setting FALSE will be used.
 *
 * @return array
 *     This function returns the profile field data in an array, containing
 *     the same fields as the {@link $field} function parameter. If a new
 *     field was created, then the "file_id" field will be set to the new
 *     custom profile field id. The fields "length" and "html_disabled" will
 *     also be updated to their defaults if they were set to NULL in
 *     the $field argument. 
 */
function phorum_api_custom_profile_field_configure($field)
{
    global $PHORUM;

    $field['type'] = PHORUM_CUSTOM_FIELD_USER;

    $field = phorum_api_custom_field_configure($field);

    return $field;
}
// }}}

// {{{ Function: phorum_api_custom_profile_field_byname
/**
 * Retrieve the information for a custom profile field by its name.
 *
 * @param string $name
 *     The name of the profile field to lookup.
 *
 * @return mixed
 *     If no profile field could be found for the name, then NULL will
 *     be returned. Otherwise the field configuration will be returned.
 *     The field configuration is an array, containing the fields:
 *     "id", "name", "length" and "html_disabled".
 *
 *     If the field was marked as deleted by the
 *     {@link phorum_api_custom_profile_field_delete()} function, then the
 *     field "deleted" will be available and set to a true value.
 */
function phorum_api_custom_profile_field_byname($name)
{
    $return = phorum_api_custom_field_byname($name,PHORUM_CUSTOM_FIELD_USER);

    return $return;
}
// }}}

// {{{ Function: phorum_api_custom_profile_field_delete
/**
 * Delete a custom profile field.
 *
 * @param int $id
 *     The id of the custom profile field to delete.
 *
 * @param bool $hard_delete
 *     If this parameter is set to a false value (the default), then the
 *     profile field will only be marked as deleted. The configuration 
 *     will be kept intact in the database. This way, we can help admins
 *     in restoring fields that were deleted by accident.
 *
 *     If it is set to a true value, then the configuration will be 
 *     fully deleted.
 */
function phorum_api_custom_profile_field_delete($id, $hard_delete = FALSE)
{
    $return = phorum_api_custom_field_delete($id, PHORUM_CUSTOM_FIELD_USER, $hard_delete);

    return $return;
}
// }}}

// {{{ Function: phorum_api_custom_profile_field_restore
/**
 * Restore a previously deleted custom profile field.
 *
 * If a profile field is deleted, it's settings and data are not deleted.
 * The field is only flagged as deleted. This function can be used for
 * reverting the delete action.
 *
 * @param int $id
 *     The id of the custom profile field to restore.
 *
 * @return bool
 *     TRUE if the restore was successfull or FALSE if there was an error.
 *     The functions {@link phorum_api_strerror()} and
 *     {@link phorum_api_errno()} can be used to retrieve information about
 *     the error that occurred.
 */
function phorum_api_custom_profile_field_restore($id)
{
    $return = phorum_api_custom_field_restore($id, PHORUM_CUSTOM_FIELD_USER);

    return $return;
}
// }}}

// {{{ Function: phorum_api_custom_profile_field_checkconfig()
/**
 * Check and fix the custom profile field configuration.
 *
 * This function has mainly been implemented for fixing problems that
 * are introduced by modules that create custom profile fields on their
 * own. Besides that, it was also written to upgrade the profile field
 * configuration, because Phorum 5.2 introduced some new fields in
 * the config.
 */
function phorum_api_custom_profile_field_checkconfig()
{
    phorum_api_custom_field_checkconfig();
}
// }}}

?>
