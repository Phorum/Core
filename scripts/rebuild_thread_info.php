#!/usr/bin/php
<?php
// rebuild thread info
// this script rebuilds the thread info data for all threads 

// this needs some time, please make sure that its really needed
// i.e. in case of errors, required updates etc.

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define('phorum_page', 'rebuild_thread_info');
define('PHORUM_ADMIN', 1);

chdir(dirname(__FILE__) . "/..");
require_once './common.php';
include './include/thread_info.php';

// Make sure that the output is not buffered.
phorum_ob_clean();

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","128M");
}

print "\nRebuilding thread info meta data ...\n";

$count_total = phorum_db_interact(
    DB_RETURN_VALUE,
    "SELECT count(*)
     FROM   {$PHORUM["message_table"]}
     WHERE  parent_id = 0 AND
            message_id = thread"
);

$res = phorum_db_interact(
    DB_RETURN_RES,
    "SELECT message_id, forum_id
     FROM   {$PHORUM["message_table"]}
     WHERE  parent_id = 0 AND
            message_id = thread"
);

$size = strlen($count_total);
$count = 0;
while ($row = phorum_db_fetch_row($res, DB_RETURN_ROW)) {
    $PHORUM['forum_id'] = $row[1];
    phorum_update_thread_info($row[0]);

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
