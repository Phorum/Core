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
 * This script implements the Phorum custom fields API.
 *
 * Custom fields are a way of dynamically extending the available
 * data fields for various objects (users, forums, messages),
 * without having to extend the related database tables with
 * additional fields.
 *
 * This API can be used for handling the configuration of these custom
 * fields. The actual use of the fields is fully integrated in the related
 * API calls.
 *
 * @package    PhorumAPI
 * @subpackage CustomField
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Constant and variable definition

/**
 * The maximum size that can be used for storing data for a single
 * custom field. This value depends on the type of field that is used
 * in the database for storing custom field data. If you need a higher
 * value for this, then mind that the custom fields table needs to be
 * altered as wel.
 */
define('PHORUM_MAX_CUSTOM_FIELD_LENGTH', 65000);

/**
 * The custom field type that indicates that a custom field
 * is linked to the users.
 */
define('PHORUM_CUSTOM_FIELD_USER', 1);

/**
 * The custom field type that indicates that a custom field
 * is linked to the forums.
 */
define('PHORUM_CUSTOM_FIELD_FORUM', 2);

/**
 * The custom field type that indicates that a custom field
 * is linked to the messages.
 */
define('PHORUM_CUSTOM_FIELD_MESSAGE', 3);

global $PHORUM;

// Reserved custom field names.
$PHORUM['API']['cpf_reserved'] = array(
    'panel', 'name', 'value', 'error'
);

// }}}

// {{{ Function: phorum_api_custom_field_configure
/**
 * Create or update the configuration for a custom field.
 *
 * @param array $field
 *     This parameter holds the field configuration to save. This array
 *     must contain the following fields:
 *
 *     - id: If a new field has to be created, then use NULL for this field.
 *           If a custom field has to be updated, then use the existing
 *           custom field's id.
 *
 *     - name: The name that has to be assigned to the custom field.
 *           This name can only contain letters, numbers and underscores
 *           (_) and it has to start with a letter.
 *
 *     The following fields are optional. If they are missing, then a default
 *     value will be used for them.
 *
 *     - length: The maximum length for the field data. This will make sure
 *           that the data that is stored in the custom field will
 *           be truncated in case its length surpasses the configured
 *           custom field length. If this field is missing or set to NULL,
 *           then the default length 255 will be used.
 *
 *     - html_disabled: If this field is set to a true value, then
 *           special HTML characters are not usable in this field. When
 *           displaying the custom field's data, Phorum will automatically
 *           escape these characters. Only use a false value for this
 *           field if the data that will be saved in the field is really safe
 *           for direct use in a web page (to learn about the security risks
 *           involved, search for "XSS" and "cross site scripting" on
 *           the internet) or if it is used to store serialized data.
 *           If this field is missing or set to NULL, then the default
 *           setting TRUE will be used.
 *
 *     - type: This field specifies the type of a custom field.
 *           This can be one of
 *           {@link PHORUM_CUSTOM_FIELD_USER},
 *           {@link PHORUM_CUSTOM_FIELD_FORUM} or
 *           {@link PHORUM_CUSTOM_FIELD_MESSAGE}.
 *
 *     - show_in_admin: If this field is set to a true value, then the field
 *           will be displayed on the details page e.g. for a user in the
 *           admin "Edit Users" section. If this field is missing or set
 *           to NULL, then the default setting FALSE will be used.
 *
 * @return array
 *     This function returns the custom field data in an array, containing
 *     the same fields as the {@link $field} function parameter. If a new
 *     field was created, then the "file_id" field will be set to the new
 *     custom field id. The fields "length" and "html_disabled" will also
 *     be updated to their defaults if they were set to NULL in
 *     the $field argument.
 */
function phorum_api_custom_field_configure($field)
{
    global $PHORUM;

    // The available fields and their defaults. NULL indicates a mandatory
    // field. The field "id" can be NULL though, when creating a new
    // custom field.
    $fields = array(
        'id'            => NULL,
        'name'          => NULL,
        'field_type'    => NULL,
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
                'phorum_api_custom_field_configure(): Missing field ' .
                "in \$field parameter: $f",
                E_USER_ERROR
            );

            $field[$f] = $default;
        }
        elseif ($f != 'id' && $field[$f] === NULL) trigger_error(
            "phorum_api_custom_field_configure(): Field $f in " .
            '$field parameter cannot be NULL',
            E_USER_ERROR
        );
    }

    $field['id'] = $field['id'] === NULL ? NULL : (int)$field['id'];
    $field['name'] = trim($field['name']);
    settype($field['field_type'], 'int');
    settype($field['length'], 'int');
    settype($field['html_disabled'], 'bool');
    settype($field['show_in_admin'], 'bool');

    if ($field['field_type'] !== PHORUM_CUSTOM_FIELD_USER &&
        $field['field_type'] !== PHORUM_CUSTOM_FIELD_FORUM &&
        $field['field_type'] !== PHORUM_CUSTOM_FIELD_MESSAGE) trigger_error(
        'phorum_api_custom_field_configure(): Illegal custom field type: ' .
        $field['field_type'], E_USER_ERROR
    );

    // Check the custom field name.
    if (!preg_match('/^[a-z][\w_]*$/i', $field['name'])) {
        return phorum_api_error(
            PHORUM_ERRNO_INVALIDINPUT,
            'Field names can only contain letters, numbers and ' .
            'underscores (_) and they must start with a letter.'
        );
    }

    // Check if the custom field name isn't an internally used name.
    // This is either one of the reserved names or a field that is
    // already used as a user data field.
    if (in_array($field['name'], $PHORUM['API']['cpf_reserved']) ||
        isset($GLOBALS['PHORUM']['API']['user_fields'][$field['name']])) {
        return phorum_api_error(
            PHORUM_ERRNO_INVALIDINPUT,
            "The name \"{$field['name']}\" is reserved for internal use " .
            'by Phorum. Please choose a different name for your custom field.'
        );
    }

    // Check the bounds for the field length.
    if ($field['length'] > PHORUM_MAX_CUSTOM_FIELD_LENGTH) {
        return phorum_api_error(
            PHORUM_ERRNO_INVALIDINPUT,
            "The length \"{$field['length']}\" for the custom " .
            'field is too large. The maximum length that can be used ' .
            'is ' . PHORUM_MAX_CUSTOM_FIELD_LENGTH . '.'
        );
    }
    if ($field['length'] <= 0) {
        return phorum_api_error(
            PHORUM_ERRNO_INVALIDINPUT,
            "The length for the custom field must be above zero."
        );
    }

    // For new fields, check if the name isn't already in use.
    if ($field['id'] === NULL &&
        phorum_api_custom_field_byname($field['name'], $field['field_type'])) {
        return phorum_api_error(
            PHORUM_ERRNO_INVALIDINPUT,
            "A custom field with the name \"{$field['name']}\" " .
            'already exists. Please choose a different name for your ' .
            'custom field.'
        );
    }

    // Setup the field configuration in the database.
    $field['id'] = $PHORUM['DB']->custom_field_config_set($field);
    phorum_api_custom_field_rebuild_cache();

    return $field;
}
// }}}

// {{{ Function: phorum_api_custom_field_byname
/**
 * Retrieve the information for a custom field by its name.
 *
 * @param string $name
 *     The name of the custom field to lookup.
 *
 * @param integer $field_type
 *     The type of custom field. This is one of
 *     {@link PHORUM_CUSTOM_FIELD_USER},
 *     {@link PHORUM_CUSTOM_FIELD_FORUM} or
 *     {@link PHORUM_CUSTOM_FIELD_MESSAGE}.
 *
 * @return mixed
 *     If no custom field could be found for the name, then NULL will
 *     be returned. Otherwise the field configuration will be returned.
 *     The field configuration is an array, containing the fields:
 *     "id", "name", "length", "html_disabled", "show_in_admin" and "deleted".
 *
 *     If the field was marked as deleted by the
 *     {@link phorum_api_custom_field_delete()} function, then the
 *     field "deleted" will be set to a true value.
 */
function phorum_api_custom_field_byname($name, $field_type)
{
    global $PHORUM;

    if ($field_type === NULL) trigger_error(
        'phorum_api_custom_field_byname(): $field_type param cannot be NULL',
        E_USER_ERROR
    );

    settype($field_type, "int");

    if ($field_type !== PHORUM_CUSTOM_FIELD_USER &&
        $field_type !== PHORUM_CUSTOM_FIELD_FORUM &&
        $field_type !== PHORUM_CUSTOM_FIELD_MESSAGE) trigger_error(
        'phorum_api_custom_field_byname(): Illegal custom field type: ' .
        $field_type, E_USER_ERROR
    );

    if (isset($PHORUM['CUSTOM_FIELDS_REV'][$field_type][$name])) {
        $id = $PHORUM['CUSTOM_FIELDS_REV'][$field_type][$name];
        return $PHORUM['CUSTOM_FIELDS'][$field_type][$id];
    } else {
        return NULL;
    }
}
// }}}

// {{{ Function: phorum_api_custom_field_delete
/**
 * Delete a custom field.
 *
 * @param int $id
 *     The id of the custom field to delete.
 *
 * @param bool $hard_delete
 *     If this parameter is set to a false value (the default), then the
 *     custom field will only be marked as deleted. The configuration
 *     will be kept intact in the database. This way, we can help admins
 *     in restoring fields that were deleted by accident.
 *
 *     If it is set to a true value, then the configuration will be
 *     fully deleted.
 */
function phorum_api_custom_field_delete($id, $hard_delete = FALSE)
{
    global $PHORUM;

    settype($id, "int");
    settype($hard_delete, "bool");

    // Only act if we really have something to delete.
    $field = $PHORUM['DB']->custom_field_config_get($id);
    if ($field !== NULL)
    {
        if ($hard_delete) {
            $PHORUM['DB']->custom_field_config_delete($id);
        } else {
            $field['deleted'] = TRUE;
            $PHORUM['DB']->custom_field_config_set($field);
        }
        phorum_api_custom_field_rebuild_cache();
    }
}
// }}}

// {{{ Function: phorum_api_custom_field_restore
/**
 * Restore a previously deleted custom field.
 *
 * If a custom field is deleted, it's settings and data are not deleted.
 * The field is only flagged as deleted. This function can be used for
 * reverting the delete action.
 *
 * @param int $id
 *     The id of the custom field to restore.
 *
 * @return bool
 *     TRUE if the restore was successfull or FALSE if there was an error.
 *     The functions {@link phorum_api_error_message()} and
 *     {@link phorum_api_error_code()} can be used to retrieve information
 *     about the error that occurred.
 */
function phorum_api_custom_field_restore($id)
{
    global $PHORUM;

    settype($id, "int");

    $field = $PHORUM['DB']->custom_field_config_get($id);
    if ($field !== NULL)
    {
        $field['deleted'] = FALSE;
        $PHORUM['DB']->custom_field_config_set($field);
        phorum_api_custom_field_rebuild_cache();
    }
    else return phorum_api_error(
        PHORUM_ERRNO_NOTFOUND,
        "Unable to restore custom field $id: no configuration found."
    );

    return TRUE;
}
// }}}

// {{{ Function: phorum_api_custom_field_apply()
/**
 * Retrieve custom fields and add/apply them to the provided objects.
 *
 * @param int $field_type
 *     The type of the custom fields to retrieve for the input array
 *
 * @param array $data_array
 *     The data array where the custom fields should be added to.
 *     Keys should be the ids to retrieve custom fields for.
 *     The values are the objects to add the custom field data to.
 *
 * @param boolean $raw_data
 *     When this parameter is TRUE (default is FALSE), then custom fields
 *     that are configured with html_disabled will not be HTML encoded in
 *     the return data.
 *
 * @return array
 *     Returns the input array with the custom fields added.
 */
function phorum_api_custom_field_apply(
    $field_type = NULL, $data_array, $raw_data = FALSE)
{
    global $PHORUM;

    if ($field_type === NULL) trigger_error(
        'phorum_api_custom_field_apply(): $field_type param cannot be NULL',
        E_USER_ERROR
    );

    settype($field_type, 'int');

    if ($field_type !== PHORUM_CUSTOM_FIELD_USER &&
        $field_type !== PHORUM_CUSTOM_FIELD_FORUM &&
        $field_type !== PHORUM_CUSTOM_FIELD_MESSAGE) trigger_error(
        'phorum_api_custom_field_apply(): Illegal custom field type: ' .
        $field_type, E_USER_ERROR
    );

    // If no custom fields are defined for the type, then we are done.
    if (empty($PHORUM['CUSTOM_FIELDS'][$field_type])) {
        return $data_array;
    }

    // Retrieve the custom field data from the database.
    $custom_fields = $PHORUM['DB']->get_custom_fields(
        $field_type,
        array_keys($data_array),
        $raw_data
    );

    // Add custom fields to the objects.
    foreach ($custom_fields as $id => $fields) {
        foreach ($fields as $fieldname => $fielddata) {
            $data_array[$id][$fieldname] = $fielddata;
        }
    }

    return $data_array;
}
// }}}

// {{{ Function: phorum_api_custom_field_rebuild_cache()
/**
 * Rebuild the cached custom field data (internal use only).
 */
function phorum_api_custom_field_rebuild_cache()
{
    global $PHORUM;

    // Rebuild the cached custom fields data.
    $PHORUM['CUSTOM_FIELDS'] = array(
        PHORUM_CUSTOM_FIELD_USER    => array(),
        PHORUM_CUSTOM_FIELD_FORUM   => array(),
        PHORUM_CUSTOM_FIELD_MESSAGE => array()
    );

    // Rebuild the name => id map data.
    $PHORUM['CUSTOM_FIELDS_REV'] = array(
        PHORUM_CUSTOM_FIELD_USER    => array(),
        PHORUM_CUSTOM_FIELD_FORUM   => array(),
        PHORUM_CUSTOM_FIELD_MESSAGE => array()
    );

    foreach ($PHORUM['DB']->custom_field_config_get() as $field) {
        $store =& $PHORUM['CUSTOM_FIELDS'][$field['field_type']];
        $store[$field['id']] = $field;

        $revstore =& $PHORUM['CUSTOM_FIELDS_REV'][$field['field_type']];
        $revstore[$field['name']] = $field['id'];
    }

    $PHORUM['DB']->update_settings(array(
        'CUSTOM_FIELDS'     => $PHORUM['CUSTOM_FIELDS'],
        'CUSTOM_FIELDS_REV' => $PHORUM['CUSTOM_FIELDS_REV']
    ));
}
// }}}

// {{{ Function: phorum_api_custom_field_checkconfig()
/**
 * Check and fix the custom field configuration (internal use only).
 *
 * This function was implemented for fixing problems that
 * were introduced by modules that created custom fields on their own.
 * Besides that, it was also written to upgrade the PROFILE_FIELDS
 * configuration. In Phorum 5.3 and up, this function is no longer
 * in active used, besides the use in the upgrade scripts.
 */
function phorum_api_custom_field_checkconfig()
{
    global $PHORUM;

    // upgrading from pre 5.3-code
    if(is_array($PHORUM['PROFILE_FIELDS'])) {
           if(isset($PHORUM['PROFILE_FIELDS']['num_fields'])) {
               $new_fields = array(PHORUM_CUSTOM_FIELD_USER => $PHORUM['PROFILE_FIELDS']);
               $PHORUM['PROFILE_FIELDS'] = $new_fields;
           } else {
               $first = current($PHORUM['PROFILE_FIELDS']);
               if(isset($first['name'])) {
                    $new_fields = array(PHORUM_CUSTOM_FIELD_USER => $PHORUM['PROFILE_FIELDS']);
                    $PHORUM['PROFILE_FIELDS']=$new_fields;
               }
           }
    }

    foreach($PHORUM['PROFILE_FIELDS'] as $field_type => $fields) {

        // Used to find the real maximum used field id.
        $max_id = isset($PHORUM['PROFILE_FIELDS'][$field_type]['num_fields'])
                ? (int) $PHORUM['PROFILE_FIELDS'][$field_type]['num_fields'] : 0;



        foreach ($fields as $id => $config)
        {
            if ($id == 'num_fields') continue;

            // Keep track of the highest id that we see.
            if ($id > $max_id) $max_id = $id;

            // The least that should be in the config, is the name of the
            // field. If there is no name, then we don't bother at all.
            if (!isset($config['name']) || $config['name'] == '') continue;

            // 5.2 includes the id in the field configuration.
            if (empty($config['id'])) {
                $PHORUM['PROFILE_FIELDS'][$field_type][$id]['id'] = $id;
            }

            // Some default values.
            if (!array_key_exists('length', $config)) {
                $PHORUM['PROFILE_FIELDS'][$field_type][$id]['length'] = 255;
            }
            if (!array_key_exists('html_disabled', $config)) {
                $PHORUM['PROFILE_FIELDS'][$field_type][$id]['html_disabled'] = TRUE;
            }
            if (!array_key_exists('show_in_admin', $config)) {
                $PHORUM['PROFILE_FIELDS'][$field_type][$id]['show_in_admin'] = FALSE;
            }

            // Some typecasting won't hurt.
            settype($PHORUM['PROFILE_FIELDS'][$field_type][$id]['id'],            'int');
            settype($PHORUM['PROFILE_FIELDS'][$field_type][$id]['name'],          'string');
            settype($PHORUM['PROFILE_FIELDS'][$field_type][$id]['length'],        'int');
            settype($PHORUM['PROFILE_FIELDS'][$field_type][$id]['html_disabled'], 'bool');
            settype($PHORUM['PROFILE_FIELDS'][$field_type][$id]['show_in_admin'], 'bool');
        }

        // Set the maximum field id that we've seen.
        $PHORUM['PROFILE_FIELDS'][$field_type]['num_fields'] = $max_id;

    }

    // Save the custom field settings to the database.
    $PHORUM['DB']->update_settings(array(
        'PROFILE_FIELDS' => $PHORUM['PROFILE_FIELDS']
    ));
}
// }}}

?>
