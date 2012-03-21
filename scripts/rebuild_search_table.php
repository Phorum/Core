#!/usr/bin/php
<?php
// rebuild search-table
// this script rebuilds the search-table

// this needs some time, please make sure that its really needed
// i.e. in case of errors, required updates etc.

define('phorum_page', 'rebuild_search_table');

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define("PHORUM_ADMIN", 1);

require_once(dirname(__FILE__).'/../include/api.php');

// Make sure that the output is not buffered.
phorum_api_buffer_clear();

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

echo "\nRebuilding search-table ...\n";

$PHORUM['DB']->rebuild_search_data();

echo "If no errors were logged above,\n" .
     "then the search table was successfully rebuilt.\n\n";

?>
