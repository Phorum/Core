<?php
if(!defined("PHORUM_ADMIN")) return;

require_once('./include/api/forums.php');

// Rebuild the forum-paths for each and every forum in the tree.
$forums = phorum_api_forums_build_path();
unset($forums[0]);

foreach($forums as $fid => $forumpath)
{
    $PHORUM['DB']->update_forum(array(
        'forum_id'   => $fid,
        'forum_path' => $forumpath
    ));
}

?>
