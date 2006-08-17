<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]= "ALTER TABLE {$PHORUM["user_newflags_table"]} ADD INDEX `move` ( `message_id`, `forum_id` ) ";

?>
