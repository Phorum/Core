<?php

$upgrade_queries[]= "alter table {$PHORUM['message_table']} add `moved` tinyint(1) NOT NULL default '0'";
$upgrade_queries[]= "update {$PHORUM['message_table']} set moved=1 where parent_id=0 and thread!=message_id";
$upgrade_queries[]= "alter table {$PHORUM['message_table']} add key new_count (forum_id, status, moved, message_id)";
$upgrade_queries[]= "alter table {$PHORUM['message_table']} drop key post_count, add key new_threads (forum_id, status, parent_id, moved, message_id)";

?>
