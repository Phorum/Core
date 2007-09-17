<?php

require_once('./include/api/custom_profile_fields.php');

// Find out if we have an active real_name custom user profile field.
$field = phorum_api_custom_profile_field_byname('real_name');
if (empty($field) || !empty($field['deleted'])) return;

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

// Now we can delete the existing real_name field.
phorum_api_custom_profile_field_delete($real_name_field_id, TRUE);

?>
