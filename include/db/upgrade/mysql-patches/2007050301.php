<?php

// Find out if we have an active real_name custom user profile field.
if (empty($PHORUM['PROFILE_FIELDS'])) return;
$real_name_field = NULL;
foreach ($PHORUM['PROFILE_FIELDS'] as $id => $field)
{
    if ($id == 'num_fields') continue;

    if ($field['name'] == 'real_name') {
        $real_name_field = $field;
        break;
    }
}
if (empty($real_name_field) || !empty($real_name_field['deleted'])) return;

// If we do, then copy all available real_names to the new real_name
// field in the user table.
$ids = phorum_api_user_search_custom_profile_field($field['id'],'','*',TRUE);
if (!empty($ids)) {
    foreach ($ids as $id) {
        $user = phorum_api_user_get($id);
        phorum_api_user_save_raw(array(
            "user_id" => $id,
            "real_name" => $user["real_name"]
        ));
    }
}

// Now we move the existing real_name field out of the way.
// We keep it around for reference.
$field =& $PHORUM['PROFILE_FIELDS'][$field['id']];
$field['name'] = 'real_name_old';
$PHORUM['DB']->update_settings(array(
    'PROFILE_FIELDS' => $PHORUM['PROFILE_FIELDS']
));

?>
