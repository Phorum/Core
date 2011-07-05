#!/usr/bin/php
<?php

// Rebuild all real name information in the database from scratch.
// This can take a while, so only run this script if needed.

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define("PHORUM_ADMIN", 1);
define('phorum_page', 'rebuild_real_names');

chdir(dirname(__FILE__) . "/..");
require_once './common.php';

// Make sure that the output is not buffered.
phorum_ob_clean();

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

$count_total = phorum_db_user_count();
$res = phorum_db_user_get_all();

print "\nRebuilding display name information ...\n";

$size = strlen($count_total);
$count = 0;
while ($user = phorum_db_fetch_row($res, DB_RETURN_ASSOC))
{
    // We save an empty user, to make sure that the display name in the
    // database is up-to-date. This will already run needed updates in
    // case the display name changed ...
    phorum_api_user_save(array("user_id" => $user["user_id"]));

    // ... but still we run the name updates here, so inconsistencies 
    // are flattened out.
    $user = phorum_api_user_get($user["user_id"]);
    phorum_db_user_display_name_updates(array(
        "user_id"      => $user["user_id"],
        "display_name" => $user["display_name"]
    ));

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
