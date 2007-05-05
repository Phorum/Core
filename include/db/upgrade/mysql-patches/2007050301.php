<?php

// Find out if we have a real_name custom user profile field.
$real_name_field_id = NULL;
foreach ($PHORUM["PROFILE_FIELDS"] as $id => $data) {
    if (!is_array($data) || !empty($data['deleted'])) continue;
    if ($data["name"] == "real_name") {
        $real_name_field_id = $id;
        break;
    }
}

// If we do, then copy all available real_names to the new real_name
// field in the user table.
$ids = phorum_db_get_custom_field_users($real_name_field_id, '%', TRUE);
if (!empty($ids)) {
    foreach ($ids as $id) {
        $user = phorum_db_user_get($id);
        phorum_db_user_save(array(
            "user_id" => $id,
            "real_name" => $user["real_name"]
        ));
    }
}

// Now we can delete the existing real_name field.
include('./include/api/base.php');
include('./include/api/custom_profile_fields.php');
phorum_api_custom_profile_field_delete($real_name_field_id, TRUE);

?>
