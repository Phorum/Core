<?php
if(!defined("PHORUM_ADMIN")) return;

include_once "./include/admin_functions.php";

// rebuild the forum-paths for each and every forum in the tree
$forums = phorum_admin_build_path_array();
unset($forums[0]);

foreach($forums as $fid => $forumpath) {

    $update_forum=array('forum_id'=>$fid, 'forum_path'=>$forumpath);
    phorum_db_update_forum($update_forum);

}

?>
