<?php
// rebuild path info
// this script rebuilds the path info data for all forums

// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
    echo "This script cannot be run from a browser.";
    return;
}

define('phorum_page', 'rebuild_forum_paths');

require_once(dirname(__FILE__).'/../include/api.php');
$phorum = Phorum::API();

// Make sure that the output is not buffered.
$phorum->buffer->clear();

print "\nRebuilding forum path info ...\n";

$forums = $phorum->forums->build_path();
unset($forums[0]);

foreach($forums as $fid => $forumpath)
{
    $phorum->db->update_forum(array(
        'forum_id'   => $fid,
        'forum_path' => $forumpath
    ));
}

print "\n";

?>
