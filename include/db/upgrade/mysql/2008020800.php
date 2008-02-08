<?php
if(!defined("PHORUM_ADMIN")) return;

// Create a new, more extended index for improving some queries on the index.
$upgrade_queries[] =
    "CREATE INDEX folder_index
     ON {$PHORUM['forums_table']} (parent_id, vroot, active, folder_flag)";

// Drop the old "group_id" index, which is covered by the new index.
$upgrade_queries[] =
    "DROP INDEX group_id
     ON {$PHORUM['forums_table']}";

// The "active" index isn't really needed anymore either.
$upgrade_queries[] =
    "DROP INDEX active
     ON {$PHORUM['forums_table']}";

?>
