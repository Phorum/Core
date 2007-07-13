<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]="alter table {$PHORUM['user_table']} add column settings_data mediumtext NOT NULL";

?>
