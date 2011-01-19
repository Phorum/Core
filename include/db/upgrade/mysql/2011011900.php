<?php
if (!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['message_table']}
     ADD COLUMN moved_hide_period tinyint(4) NOT NULL default '0'";

