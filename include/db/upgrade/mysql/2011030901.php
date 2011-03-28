<?php
if (!defined("PHORUM_ADMIN")) return;

require_once PHORUM_PATH . '/include/api/custom_field.php';

// Beat the custom field configuration array $PHORUM['PROFILE_FIELDS']
// into shape and handle upgrading data when needed.
phorum_api_custom_field_checkconfig();

// Convert the $PHORUM['PROFILE_FIELDS'] data into configuration data that
// is stored in the database.
foreach ($PHORUM['PROFILE_FIELDS'] as $type => $configs)
{
    foreach ($configs as $id => $config)
    {
        if ($id == 'num_fields') continue;

        // The field "type" is named "field_type" from now on.
        unset($config['type']); // Note: not necessarily set
        $config['field_type'] = $type;

        $PHORUM['DB']->custom_field_config_set($config);
    }
}

// Regenerate the cached profile field data.
// When there were custom fields, then this will already be done by now.
// This call is here to accommodate for the case when no custom fields were
// configured at all.
phorum_api_custom_field_rebuild_cache();

