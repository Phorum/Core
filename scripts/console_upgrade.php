<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2006  Phorum Development Team                               //
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

echo "> reading the Phorum configuration ... ";

// I guess the phorum-directory is one level up. if you move the script to
// somewhere else you'll need to change that.
$PHORUM_DIRECTORY = dirname(__FILE__) . "/../";

// change directory to the main-dir so we can use common.php
if(file_exists($PHORUM_DIRECTORY."/common.php")) {
    chdir($PHORUM_DIRECTORY);
    if (!is_readable("./common.php")) {
        echo "ERROR\n";
        fprintf(STDERR,
            "Unable to read common.php from directory $PHORUM_DIRECTORY\n");
        exit(1);
    }
} else {
    echo "ERROR\n";
    fprintf(STDERR, 
        "Unable to find Phorum file \"common.php\".\n" .
        "Please check the \$PHORUM_DIRECTORY in " . basename(__FILE__) ."\n");
    exit(1);
}

// include required files
include_once './common.php';
include_once './include/users.php';
include_once './include/version_functions.php';

echo "OK\n";

// Open the database connection.
echo "> establishing the database connection ... ";
if(!phorum_db_check_connection()){
    print "ERROR\n";
    fprintf(STDERR,
         "A database connection could not be established.\n" .
         "Please edit include/db/config.php.\n");
    exit(1);
} else {
    print "OK\n";
    flush();
}

// Executing large, long running scripts can result into problems,
// in case the script hits PHP resource boundaries. Here we try to
// prepare the PHP environment for the upgrade. Unfortunately, if
// safe_mode is enabled, we cannot change the execution time and
// memory limits.
if (! ini_get('safe_mode')) {
    echo "> disabling script timeout and raising the memory-limit ... ";
    set_time_limit(0);
    ini_set("memory_limit","64M");
    echo "OK\n";
}

// The internal_patchlevel can be unset, because this setting was
// added in 5.2. When upgrading from 5.1, this settings is not yet
// available. To make things work, we'll fake a value for this
// setting which will always be lower than the available patch ids.
if (!isset($PHORUM["internal_patchlevel"])) {
    $PHORUM["internal_patchlevel"] = "1111111111";
}

// Run upgrades until we are up to date.
$count = 0;
for (;;)
{
    $uptodate = 
        isset($PHORUM['internal_version']) && 
        $PHORUM['internal_version'] == PHORUM_SCHEMA_VERSION &&
        isset($PHORUM['internal_patchlevel']) &&
        $PHORUM['internal_patchlevel'] == PHORUM_SCHEMA_PATCHLEVEL;

    if ($uptodate) {
        if ($count == 0) echo "\n";
        echo "Your install is up-to-date.\n\n";
        exit(0);
    }

    $count ++;

    if ($count == 1) {
        echo "> Running required upgrade(s) ...\n\n";
    }

    echo "Press enter to continue or CTRL+C to stop > ";
    fgets(STDIN);

    // For upgrading, we first run all availabe schema patches. Only
    // after all patches have been applied, we continue with
    // running the schema upgrades.

    if ($PHORUM["internal_patchlevel"] < PHORUM_SCHEMA_PATCHLEVEL) {
        $message = phorum_upgrade_tables(
            $PHORUM["internal_patchlevel"],
            PHORUM_SCHEMA_PATCHLEVEL,
            "patch"
        );
    } else {
        $message = phorum_upgrade_tables(
            $PHORUM["internal_version"],
            PHORUM_SCHEMA_PATCHLEVEL,
            "schema"
        );
    }

    // Strip HTML code from the returned upgrade message,
    // so we can display it on the console.
    $message = strip_tags($message);
    print $message;
    print "\n";
}

?>
