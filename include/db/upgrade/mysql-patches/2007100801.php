<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['forums_table']}
     ADD COLUMN count_views_per_thread int unsigned NOT NULL default '0'";
?>
