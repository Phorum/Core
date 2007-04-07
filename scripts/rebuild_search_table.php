<?php
// rebuild search-table
// this script rebuilds the search-table

// this needs some time, please make sure that its really needed
// i.e. in case of errors, required updates etc.

// YOU NEED TO MOVE THIS SCRIPT TO YOUR PHORUM-DIRECTORY

define('phorum_page', 'rebuild_search_table');

if(!file_exists('./common.php')) {
    echo "You didn't move this script to your phorum-directory!\n";
    exit();
}

define("phorum_page", "rebuild_search_table");
define("PHORUM_ADMIN", 1);

include './common.php';

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

echo "Rebuilding search-table ...\n";

phorum_db_rebuild_search_data();

echo "If no errors were logged above,\n" .
     "then the search table was successfully rebuilt.\n";

?>
