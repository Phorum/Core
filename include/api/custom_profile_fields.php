<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
 * @package    PhorumAPI
 * @subpackage CustomProfileFieldAPI
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Constant and variable definitions

// Reserved custom profile field names.
$GLOBALS['PHORUM']['API']['cpf_reserved'] = array(
    'panel', 'name', 'value', 'error'
);

/**
 * The maximum size that can be used for storing data for a single
 * custom profile field.
 */
define('PHORUM_MAX_CPLENGTH', 65000);

// }}}

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

    // The available fields and their defaults. NULL indicates a mandatory
    // field. The field "id" can be NULL though, when creating a new
    // custom profile field.
    $fields = array(
        'id'            => NULL,
        'name'          => NULL,
        'length'        => 255,
        'html_disabled' => TRUE,
        'show_in_admin' => FALSE
    );

    // Check if all required fields are in the $field argument.
    // Assign default values for missing or NULL fields or trigger
    // or an error if the field is mandatory.
    foreach ($fields as $f => $default) {
        if (!array_key_exists($f, $field)) {
            if ($default === NULL) trigger_error(
                'phorum_api_custom_profile_field_configure(): Missing field ' .
                "in \$field parameter: $f",
                E_USER_ERROR
            );

            $field[$f] = $default;
        }
        elseif ($f != 'id' && $field[$f] === NULL) trigger_error(
            'phorum_api_custom_profile_field_configure(): Field $f in ' .
            "\$field parameter cannot be NULL",
            E_USER_ERROR
        );
    }
 
    $field['id'] = $field['id'] === NULL ? NULL : (int)$field['id'];
    $field['name'] = trim($field['name']);
    settype($field['length'], 'int');
    settype($field['html_disabled'], 'bool');
    settype($field['show_in_admin'], 'bool');

    // Check the profile field name.    
    if (!preg_match('/^[a-z][\w_]*$/i', $field['name'])) {
        return phorum_api_error_set(
            PHORUM_ERRNO_INVALIDINPUT,
            'Field names can only contain letters, numbers and ' .
            'underscores (_) and they must start with a letter.'
        );
    }

    // Check if the profile field name isn't an internally used name.
    // This is either one of the reserved names or a field that is
    // already used as a user data field.
    if (in_array($field['name'], $PHORUM['API']['cpf_reserved']) ||
        isset($GLOBALS['PHORUM']['API']['user_fields'][$field['name']])) {
        return phorum_api_error_set(
            PHORUM_ERRNO_INVALIDINPUT,
            "The name \"{$field['name']}\" is reserved for internal use " .
            'by Phorum. Please choose a different name for your custom ' .
            'profile field.'
        );
    }

    // Check the bounds for the field length.
    if ($field['length'] > PHORUM_MAX_CPLENGTH) {
        return phorum_api_error_set(
            PHORUM_ERRNO_INVALIDINPUT,
            "The length \"{$field['length']}\" for the custom profile " .
            'field is too large. The maximum length that can be used ' .
            'is ' . PHORUM_MAX_CPLENGTH . '.'
        );
    }
    if ($field['length'] <= 0) {
        return phorum_api_error_set(
            PHORUM_ERRNO_INVALIDINPUT,
            "The length for the custom profile field must be above zero."
        );
    }

    // For new fields, check if the name isn't already in use.
    if ($field['id'] === NULL &&
        phorum_api_custom_profile_field_byname($field['name'])) {
        return phorum_api_error_set(
            PHORUM_ERRNO_INVALIDINPUT,
            "A custom profile field with the name \"{$field['name']}\" " .
            'already exists. Please choose a different name for your ' .
            'custom profile field.'
        );
    }

    // For existing fields, check if the field id really exists.
    if ($field['id'] !== NULL &&
        !isset($PHORUM['PROFILE_FIELDS'][$field['id']])) {
        return phorum_api_error_set(
            PHORUM_ERRNO_INVALIDINPUT,
            "A custom profile field with id \"{$field['id']}\" does not " .
            'exist. Maybe the field was deleted before you updated its ' .
            'settings.'
        );
    }

    // If we have to create a new field, then find a new id for it.
    // For indexing, we use the "num_fields" profile field configuration
    // setting. This field is more an auto increment index counter than
    // the number of fields. For historical reasons, we keep this name
    // in here (some module contain code which makes use of num_fields
    // directly).
    if ($field['id'] === NULL)
    {
        // Since there are modules meddling with the data, we do not
        // fully trust the num_fields. If we see a field with an id
        // higher than what's in num_fields, then we move the counter up.
        $high = isset($PHORUM['PROFILE_FIELDS']['num_fields'])
              ? (int) $PHORUM['PROFILE_FIELDS']['num_fields'] : 0;
        foreach ($PHORUM['PROFILE_FIELDS'] as $checkid => $profile_field) {
            if ($checkid > $high) $high = $checkid;    
        }
        
        // Use the next available value as our id.
        $field['id'] = $high + 1;  

        // Update the index.
        $PHORUM['PROFILE_FIELDS']['num_fields'] = $field['id'];
    }

    // Update the profile fields information in the settings.
    $PHORUM['PROFILE_FIELDS'][$field['id']] = $field;
    phorum_db_update_settings(array(
        'PROFILE_FIELDS' => $PHORUM['PROFILE_FIELDS']
    ));

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
    foreach ($GLOBALS['PHORUM']['PROFILE_FIELDS'] as $id => $profile_field) {
        if ($id !== 'num_fields' && $profile_field['name'] == $name)
        {
            // Fix custom profile fields that were created the 5.1 way
            // (most probably by modules that handle configuration of these
            // fields on their own).
            if (empty($profile_field['id'])) $profile_field['id'] = $id;

            return $profile_field;
        }
    }

    return NULL;
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
    settype($id, "int");
    settype($hard_delete, "bool");

    // Only act if we really have something to delete.
    if (isset($GLOBALS["PHORUM"]["PROFILE_FIELDS"][$id]))
    {
        if ($hard_delete) {
            unset($GLOBALS["PHORUM"]["PROFILE_FIELDS"][$id]);
        } else {
            $GLOBALS["PHORUM"]["PROFILE_FIELDS"][$id]["deleted"] = TRUE;
        }

        phorum_db_update_settings(array(
            'PROFILE_FIELDS' => $GLOBALS["PHORUM"]['PROFILE_FIELDS']
        ));
    }
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
 *     the error which occurred.
 */
function phorum_api_custom_profile_field_restore($id)
{
    settype($id, "int");

    if (isset($GLOBALS["PHORUM"]["PROFILE_FIELDS"][$id]))
    {
        $f = $GLOBALS["PHORUM"]["PROFILE_FIELDS"][$id];
        if (isset($f['deleted']) && $f['deleted']) $f['deleted'] = 0;
        $GLOBALS["PHORUM"]["PROFILE_FIELDS"][$id] = $f;

        phorum_db_update_settings(array(
            'PROFILE_FIELDS' => $GLOBALS["PHORUM"]['PROFILE_FIELDS']
        ));
    }
    else return phorum_api_error_set(
        PHORUM_ERRNO_NOTFOUND,
        "Unable to restore custom profile field $id: " .
        "no configuration found."
    );

    return TRUE;
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
    global $PHORUM;

    // Used to find the real maximum used field id.
    $max_id = isset($PHORUM['PROFILE_FIELDS']['num_fields'])
            ? (int) $PHORUM['PROFILE_FIELDS']['num_fields'] : 0;

    foreach ($PHORUM['PROFILE_FIELDS'] as $id => $config)
    {
        // Keep track of the highest id that we see.
        if ($id > $max_id) $max_id = $id;

        // The least that should be in the config, is the name of the
        // field. If there is no name, then we don't bother at all.
        if (!isset($config['name']) || $config['name'] == '') continue;

        // 5.2 includes the id in the field configuration. 
        if (empty($config['id'])) {
            $PHORUM['PROFILE_FIELDS'][$id]['id'] = $id;
        }

        // Some default values.
        if (!array_key_exists('length', $config)) {
            $PHORUM['PROFILE_FIELDS'][$id]['length'] = 255;
        }
        if (!array_key_exists('html_disabled', $config)) {
            $PHORUM['PROFILE_FIELDS'][$id]['html_disabled'] = TRUE;
        }
        if (!array_key_exists('show_in_admin', $config)) {
            $PHORUM['PROFILE_FIELDS'][$id]['show_in_admin'] = FALSE;
        }

        // Some typecasting won't hurt.
        settype($PHORUM['PROFILE_FIELDS'][$id]['id'],            'int');
        settype($PHORUM['PROFILE_FIELDS'][$id]['name'],          'string');
        settype($PHORUM['PROFILE_FIELDS'][$id]['length'],        'int');
        settype($PHORUM['PROFILE_FIELDS'][$id]['html_disabled'], 'bool');
        settype($PHORUM['PROFILE_FIELDS'][$id]['show_in_admin'], 'bool');
    }

    // Set the maximum field id that we've seen.
    $PHORUM['PROFILE_FIELDS']['num_fields'] = $max_id;

    // Save the custom profile field settings to the database.
    phorum_db_update_settings(array(
        'PROFILE_FIELDS' => $PHORUM['PROFILE_FIELDS']
    ));
}
// }}}

?>
