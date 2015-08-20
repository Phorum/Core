#!/usr/bin/php
<?php
// delete stale messages
// this script deletes all stale messages (reply messages without an
// existing parent thread) from the database

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}
define('phorum_page', 'delete_stale_messages');
define('PHORUM_ADMIN', 1);
require_once(dirname(__FILE__).'/../include/api.php');

// Make sure that the output is not buffered.
phorum_api_buffer_clear();

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","128M");
}

print "\nChecking for stale messages ...\n";

$stale_messages = $PHORUM['DB']->list_stale_messages();
$count_total = count($stale_messages);

if ($count_total == 0) {
    print "No stale messages found, exiting\n\n";
    exit;
}

print "\nDeleting stale messages ...\n";

$forums = array();
$size = strlen($count_total);
$count = 0;
foreach ($stale_messages as $message)
{
print_r($message);
    $forums[$message['forum_id']] = $message['forum_id'];
    $PHORUM['DB']->delete_message($message['message_id']);

    $count ++;

    $perc = floor(($count/$count_total)*100);
    $barlen = floor(20*($perc/100));
    $bar = "[";
    $bar .= str_repeat("=", $barlen);
    $bar .= str_repeat(" ", (20-$barlen));
    $bar .= "]";
    printf("updating %{$size}d / %{$size}d  %s (%d%%)\r",
           $count, $count_total, $bar, $perc);
}

print "\n\n" . count($forums) . " forum(s) affected\n";
print "Updating forum statistics for the affected forums ...\n";

foreach ($forums as $forum_id)
{
    $PHORUM['forum_id'] = $forum_id;
    $PHORUM['DB']->update_forum_stats(true);
}

print "\n";

?>
