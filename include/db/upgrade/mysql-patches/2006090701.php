<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]="alter table {$PHORUM['forums_table']} add column cache_version int UNSIGNED NOT NULL DEFAULT 0";

?>
