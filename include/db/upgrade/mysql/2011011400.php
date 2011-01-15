<?php
if (!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['user_table']}
     DROP COLUMN moderator_data";

?>
