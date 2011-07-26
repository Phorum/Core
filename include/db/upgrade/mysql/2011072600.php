<?php
if (!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['user_table']}
     ADD COLUMN pm_new_count INT UNSIGNED NOT NULL default 0";

