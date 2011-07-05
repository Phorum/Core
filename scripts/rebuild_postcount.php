#!/usr/bin/php
<?php
/*

This is just a simple script for updating the post-count of each user, which
is shown in the user's profile. It can be run multiple times, but should at
least be run once after a conversion from Phorum 3 to Phorum 5.

Depending on the number of messages and users, it may take some time.

*/

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define("PHORUM_ADMIN", 1);
define('phorum_page', 'rebuild_postcount');

chdir(dirname(__FILE__) . "/..");
require_once './common.php';

// Make sure that the output is not buffered.
phorum_ob_clean();

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

print "\nCounting the posts for all users ...\n";
$postcounts = phorum_db_interact(
    DB_RETURN_ROWS,
    "SELECT user_id, count(*) 
     FROM   {$PHORUM["message_table"]}
     WHERE  user_id != 0
     GROUP  BY user_id"
);

print "Updating the post counts ...\n";

$count_total = count($postcounts);
$size = strlen($count_total);
$count = 0;
foreach ($postcounts as $row) {
    phorum_api_user_save_raw(array(
        "user_id" => $row[0],
        "posts"   => $row[1]
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
