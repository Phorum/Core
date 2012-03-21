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

require_once(dirname(__FILE__).'/../include/api.php');

// Make sure that the output is not buffered.
phorum_api_buffer_clear();

print "\nRebuilding forum path info ...\n";

$forums = phorum_api_forums_build_path();
unset($forums[0]);

foreach($forums as $fid => $forumpath)
{
    $PHORUM['DB']->update_forum(array(
        'forum_id'   => $fid,
        'forum_path' => $forumpath
    ));
}

print "\n";

?>
