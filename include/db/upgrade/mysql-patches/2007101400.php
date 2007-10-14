<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['subscribers_table']}
     CHANGE COLUMN sub_type sub_type TINYINT NOT NULL DEFAULT '0'";

?>
