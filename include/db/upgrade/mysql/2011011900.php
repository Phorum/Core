<?php
if (!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['message_table']}
     ADD COLUMN hide_period int unsigned NOT NULL default '0'";

