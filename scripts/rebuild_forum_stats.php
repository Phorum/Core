#!/usr/bin/php
<?php

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define("PHORUM_ADMIN", 1);
define('phorum_page', 'rebuild_forum_stats');

chdir(dirname(__FILE__) . "/..");
require_once './common.php';

// Make sure that the output is not buffered.
phorum_ob_clean();

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

print "\nRebuild forum stats ...\n";

// we need to rebuild the forumstats
$forums = phorum_db_get_forums();

$count_total = count($forums);
$size = strlen($count_total);
$count = 0;

foreach ($forums as $fid => $fdata)
{
    if ($fdata['folder_flag'] == 0) {
        $PHORUM['forum_id'] = $fid;
        phorum_db_update_forum_stats(true);
    }

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

print "\n\n";

?>
