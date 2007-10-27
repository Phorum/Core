<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "CREATE INDEX admin ON {$PHORUM['user_table']} (admin)";
?>
