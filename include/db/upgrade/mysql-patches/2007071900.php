<?php
if(!defined("PHORUM_ADMIN")) return;

// Refresh all forum statistics for making bringing thread_count up to date.
require_once('./include/api/forums.php');
$forums = phorum_db_get_forums(
    NULL, NULL, NULL, NULL,false,2,true
);
foreach ($forums as $forum) {
    $GLOBALS["PHORUM"]["forum_id"] = $forum["forum_id"];
    phorum_db_update_forum_stats(true);
}
?>