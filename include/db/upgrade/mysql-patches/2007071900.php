<?php
if(!defined("PHORUM_ADMIN")) return;

// Refresh all forum statistics for making bringing thread_count up to date.
require_once('./include/api/forums.php');
$forums = phorum_api_forums_get(
    NULL, NULL, NULL, NULL,
    PHORUM_FLAG_INCLUDE_INACTIVE | PHORUM_FLAG_FORUMS
);
foreach ($forums as $forum) {
    $GLOBALS["PHORUM"]["forum_id"] = $forum["forum_id"];
    phorum_db_update_forum_stats(true);
}
?>
