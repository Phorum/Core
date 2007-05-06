<?php

// Because the display_name is considered to be stored as valid HTML
// in the database, we need to escape HTML characters. We run this only
// for the users that really have HTML characters in their name.

// Search users.
$ids = phorum_db_user_check_field(
    array('display_name', 'display_name', 'display_name', 'display_name'),
    array('>', '<', '&', '"'),
    array('*', '*', '*', '*'),
    TRUE, "OR"
);

// Update the found users.
if (!empty($ids)) {
    foreach ($ids as $id) {
        $user = phorum_db_user_get($id);
        phorum_db_user_save(array(
            "user_id" => $id,
            "display_name" => htmlspecialchars($user["display_name"])
        ));
    }
}

?>
