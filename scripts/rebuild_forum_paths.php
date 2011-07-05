#!/usr/bin/php
<?php
// rebuild path info
// this script rebuilds the path info data for all forums 

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define('phorum_page', 'rebuild_forum_paths');

chdir(dirname(__FILE__) . "/..");
require_once './common.php';
include_once( "./include/admin_functions.php" );

// Make sure that the output is not buffered.
phorum_ob_clean();

print "\nRebuilding forum path info ...\n";

$forums = phorum_admin_build_path_array();
unset($forums[0]);

foreach($forums as $fid => $forumpath)
{
    $update_forum = array('forum_id'=>$fid, 'forum_path'=>$forumpath);
    phorum_db_update_forum($update_forum);
}

print "\n";

?>
