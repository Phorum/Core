<?php
// rebuild path info
// this script rebuilds the path info data for all forums

// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
    echo "This script cannot be run from a browser.";
    return;
}

define('phorum_page', 'rebuild_forum_paths');

chdir(dirname(__FILE__) . "/..");
require_once './common.php';
require_once './include/api/forums.php';

// Make sure that the output is not buffered.
phorum_ob_clean();

print "\nRebuilding forum path info ...\n";

$forums = phorum_api_forums_build_path();
unset($forums[0]);

foreach($forums as $fid => $forumpath)
{
    phorum_db_update_forum(array(
        'forum_id'   => $fid,
        'forum_path' => $forumpath
    ));
}

print "\n";

?>
