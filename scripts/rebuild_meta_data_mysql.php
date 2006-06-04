<?php
// rebuild meta-data
// this script rebuilds the meta-data for the messages

// this needs some time, please make sure that its really needed
// i.e. in case of errors, required updates etc.

// it only works with the mysql-layer.

// YOU NEED TO MOVE THIS SCRIPT TO YOUR PHORUM-DIRECTORY

define('phorum_page', 'rebuild_meta_data');

if(!file_exists('./common.php')) {
    echo "You didn't move this script to your phorum-directory!\n";
    exit();
}

include './common.php';
include './include/thread_info.php';

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

echo "Rebuilding meta-data ...\n";

$conn = phorum_db_mysql_connect();

// this should be enabled if you switched to utf-8
/*
mysql_query( "SET NAMES 'utf8'", $conn);
mysql_query( "SET CHARACTER SET utf8", $conn);
 */

$res = mysql_query("SELECT message_id, forum_id FROM {$PHORUM["message_table"]} WHERE parent_id = 0 and message_id = thread",$conn);

$rebuild = 0;
while($row = mysql_fetch_row($res)) {
    $PHORUM['forum_id'] = $row[1];
    phorum_update_thread_info($row[0]);
    $rebuild++;
}

flush();
echo "$rebuild messages done.\n";
echo "Rebuilding meta-data finished successfully if no errors were logged above.\n";


?>
