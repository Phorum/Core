<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]="alter table {$PHORUM['forums_table']} add column forum_path text NOT NULL";

?>
