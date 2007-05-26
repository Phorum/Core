<?php
// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
    echo "This script cannot be run from a browser.";
        return;
}

chdir(dirname(__FILE__) . "/..");

system("/bin/rm -R docs/api/*");
system("phpdoc -d include/api/ -t docs/api -ti \"Phorum API Documentation\" -j on -o HTML:frames:DOM/earthli -dn PhorumAPI");

?>

