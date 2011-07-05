#!/usr/bin/php
<?php
///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2010  Phorum Development Team                               //
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
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// I guess the phorum-directory is one level up. if you move the script to
// somewhere else you'll need to change that.

if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

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

// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
   echo "This script cannot be run from a browser.";
   return;
}

// Load Phorum core code.
define("phorum_page", "console_upgrade");
define("PHORUM_ADMIN", 1);
require_once('./common.php');
require_once('./include/version_functions.php');

// Fetch command line arguments. We could rely on $argv here, but that
// is not registered on all systems (needs a php.ini setting).
$argv = $_SERVER['argv'];
$cmd  = basename(array_shift($argv));

function usage()
{
    print "\n";
    print "Usage: $cmd [-h] [-a] [-f <upgrade file>]\n";
    print "\n";
    print "   -h: show this help\n";
    print "   -a: run all upgrades without confirmation prompts\n";
    print "   -f: run the upgrade from the provided upgrade file. This\n";
    print "       one can be useful to re-run a specific upgrade in case\n";
    print "       it failed when running it the first time from the\n";
    print "       standard upgrading process.\n";
    print "       The upgrade file to run should be provided relative to\n";
    print "       the directory \"include/db/upgrade\" below the Phorum\n";
    print "       directory.\n";
    print "\n";
    print "   example -f usage:\n";
    print "\n";
    print "   $ php console_upgrade.php -f mysql-patches/2008012500.php\n";
    print "\n";
    exit(0);
}

$noprompt = FALSE;
$file = NULL;
while (!empty($argv))
{
    $opt = array_shift($argv);

    switch ($opt)
    {
        case "-h":
            usage();
            exit;
            break;

        case "-a":
            $noprompt = TRUE;
            break;

        case "-f":
            if (empty($argv)) {
                die("Missing value for -f argument.\n");
            }
            $file = array_shift($argv);
            $file = "./include/db/upgrade/$file";
            if (! file_exists($file)) {
                die("Upgrade file \"$file\" not found.\n");
            }
            break;

        default:
            die("Illegal command line option \"$opt\".\nUse -h for help.\n");
            break;
    }
}

// Make sure that the output is not buffered.
phorum_ob_clean();

echo "\n";
echo "Phorum console based database upgrade\n";
echo "-------------------------------------\n";
echo "\n";

// Open the database connection.
if(!phorum_db_check_connection()){
    fprintf(STDERR,
         "A database connection could not be established.\n" .
         "Please edit include/db/config.php.\n");
    exit(1);
}

// Prepare single file upgrade.
if ($file)
{
    $type = (strstr($file, '-patches')) ? 'patch' : 'schema';
    if (preg_match('!/(\d{10})\.php$!', $file, $m)) {
        $version = $m[1];
    } else {
        die("Upgrade filename does not look like a db patch filename.\n");
    }
    $upgrades = array(
        $file => array(
            "version" => $version,
            "type"    => $type,
            "file"    => $file
        )
    );
}
// Prepare standard upgrade
else {
    $upgrades = phorum_dbupgrade_getupgrades();
}

// Run upgrades until we are up to date.
$total = count($upgrades);
$index = 0;
foreach ($upgrades as $id => $upgrade)
{
    $index++;

    if (!$noprompt) {
        if ($total == 1) {
            echo "Upgrade: $id\n";
        } else {
            echo "Next upgrade: $id ($index of $total)\n";
        }
        echo "Press ENTER to run this upgrade or CTRL+C to stop > ";
        fgets(STDIN);
    } else {
        if ($total == 1) {
            echo "Running upgrade: $id\n";
        } else {
            echo "Running upgrade $index of $total\n";
        }
    }

    // Run the upgrade.
    $update_internal_version = $file ? FALSE : TRUE;
    $message = phorum_dbupgrade_run($upgrade, $update_internal_version);

    // Strip HTML code from the returned upgrade message,
    // so we can display it on the console.
    $message = strip_tags($message);
    print $message;
    print "\n";
}

echo "Your install is up-to-date.\n\n";
exit(0);
?>
