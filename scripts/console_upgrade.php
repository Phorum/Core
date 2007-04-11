<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2007  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
///////////////////////////////////////////////////////////////////////////////

// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
   echo $PHORUM["DATA"]["LANG"]["CannotBeRunFromBrowser"];
   return;
}

define("phorum_page", "console_upgrade");
define("PHORUM_ADMIN", 1);

echo "\n";
echo "Phorum console based database upgrade\n";
echo "-------------------------------------\n";
echo "\n";

// I guess the phorum-directory is one level up. if you move the script to
// somewhere else you'll need to change that.
$PHORUM_DIRECTORY = dirname(__FILE__) . "/../";

// change directory to the main-dir so we can use common.php
if(file_exists($PHORUM_DIRECTORY."/common.php")) {
    chdir($PHORUM_DIRECTORY);
    if (!is_readable("./common.php")) {
        fprintf(STDERR,
            "Unable to read common.php from directory $PHORUM_DIRECTORY\n");
        exit(1);
    }
} else {
    fprintf(STDERR, 
        "Unable to find Phorum file \"common.php\".\n" .
        "Please check the \$PHORUM_DIRECTORY in " . basename(__FILE__) ."\n");
    exit(1);
}

// include required files
include_once './common.php';
include_once './include/users.php';
include_once './include/version_functions.php';

// Open the database connection.
if(!phorum_db_check_connection()){
    fprintf(STDERR,
         "A database connection could not be established.\n" .
         "Please edit include/db/config.php.\n");
    exit(1);
}

// Run upgrades until we are up to date.
$upgrades = phorum_dbupgrade_getupgrades();
$total = count($upgrades);
$index = 0;
foreach ($upgrades as $upgrade)
{
    $index++;

    echo "Press ENTER to run upgrade $index of $total or CTRL+C to stop > ";
    fgets(STDIN);

    // Run the upgrade.
    $message = phorum_dbupgrade_run($upgrade);

    // Strip HTML code from the returned upgrade message,
    // so we can display it on the console.
    $message = strip_tags($message);
    print $message;
    print "\n";
}

echo "Your install is up-to-date.\n\n";
exit(0);
?>
