<?php

// Find out if we have an active real_name custom user profile field.
if (empty($PHORUM['PROFILE_FIELDS'])) return;
$real_name_field = NULL;
foreach ($PHORUM['PROFILE_FIELDS'] as $id => $field)
{
    if ($id === 'num_fields') continue;

    if ($field['name'] == 'real_name') {
        $field['id'] = $id;
        $real_name_field = $field;
        break;
    }
}
if (empty($real_name_field) || !empty($real_name_field['deleted'])) return;

// If we do, then copy all available real_names to the new real_name
// field in the user table.
$sth = $PHORUM['DB']->interact(
    DB_RETURN_RES,
    "SELECT * FROM {$PHORUM['DB']->prefix}_user_custom_fields
     WHERE  type = {$real_name_field['id']}"
);
while ($row = $PHORUM['DB']->fetch_row($sth, DB_RETURN_ASSOC))
{
    $user = phorum_api_user_get($row['user_id']);
    if ($user) {
        phorum_api_user_save_raw(array(
            'user_id'   => $row['user_id'],
            'real_name' => $row['data']
        ));
    }
}

// Now we delete the existing real_name custom field.
// We only mark it as deleted. We keep the original data around for
// reference (just in case this upgrade failed in a terrible way)
$field =& $PHORUM['PROFILE_FIELDS'][$real_name_field['id']];
$field['deleted'] = 1;
$PHORUM['DB']->update_settings(array(
    'PROFILE_FIELDS' => $PHORUM['PROFILE_FIELDS']
));

?>
