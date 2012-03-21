#!/usr/bin/php
<?php
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define("PHORUM_ADMIN", 1);
define('phorum_page', 'rebuild_forum_stats');

require_once(dirname(__FILE__).'/../include/api.php');

// Make sure that the output is not buffered.
phorum_api_buffer_clear();

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

print "\nRebuild forum stats ...\n";

$forums = phorum_api_forums_get(
    NULL, NULL, NULL, NULL,
    PHORUM_FLAG_INCLUDE_INACTIVE | PHORUM_FLAG_FORUMS
);

$count_total = count($forums);
$size = strlen($count_total);
$count = 0;

foreach ($forums as $fid => $fdata)
{
    $PHORUM['forum_id'] = $fid;
    $PHORUM['DB']->update_forum_stats(true);

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
