<?php
if(!defined("PHORUM_ADMIN")) return;

// fill the new min_id table with data from the old newflags table
$upgrade_queries[] = "INSERT INTO {$PHORUM['user_newflags_min_id_table']}
      SELECT user_id,forum_id,min(message_id) FROM {$PHORUM['user_newflags_table']}
      GROUP BY user_id,forum_id";

?>
