<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER IGNORE TABLE {$PHORUM['user_table']}
     ADD COLUMN display_name VARCHAR(255) NOT NULL DEFAULT ''";

// Set the display_name to the username of the users.
$upgrade_queries[] =
    "UPDATE {$PHORUM["user_table"]} SET display_name = username";

?>
