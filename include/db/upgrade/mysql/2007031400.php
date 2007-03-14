<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]= "ALTER TABLE {$PHORUM['forum_group_xref_table']} ADD INDEX `group_id` ( `group_id` )";

?>
