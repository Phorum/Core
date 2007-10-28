<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "CREATE INDEX updated_threads ON {$PHORUM['message_table']} (status, parent_id, modifystamp)";
?>
