<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER IGNORE TABLE {$PHORUM['user_table']}
     ADD COLUMN real_name VARCHAR(255) NOT NULL DEFAULT ''";

$upgrade_queries[] =
    "CREATE INDEX real_name ON {$PHORUM['user_table']} (real_name)";

// Set the real_name to the username of the users.
$upgrade_queries[] = 
    "UPDATE {$PHORUM["user_table"]} SET real_name = username";

?>
