<?php

$upgrade_queries[]= "alter table {$PHORUM['messages_table']} add `moved` tinyint(1) NOT NULL default '0'";
$upgrade_queries[]= "update {$PHORUM['messages_table']} set moved=1 where parent_id=0 and thread!=message_id";


?>
