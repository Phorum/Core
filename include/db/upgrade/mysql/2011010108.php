<?php
if(!defined("PHORUM_ADMIN")) return;

// Add indexes for improving newflag counting queries on the index.
$upgrade_queries[] =
  "ALTER TABLE {$PHORUM['message_table']} " .

  "ADD INDEX forum_thread_count" .
  "(forum_id, parent_id, status, moved, message_id)," .

  "ADD INDEX forum_message_count" .
  "(forum_id, status, moved, message_id)";
?>
