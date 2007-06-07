<?php
// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
    echo "This script cannot be run from a browser.";
        return;
}

chdir(dirname(__FILE__) . "/..");

$files = array(
#    'include/db/mysql.php',
    'include/api/base.php',
    'include/api/custom_profile_fields.php',
    'include/api/file_storage.php',
    'include/api/user.php',
);

system("/bin/rm -R docs/api");
system("mkdir docs/api");
system("phpdoc -f ".implode(",",$files)." -t docs/api -ti \"Phorum API Documentation\" -o HTML:frames:DOM/earthli -dn PhorumAPI");

?>

