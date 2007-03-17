<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2006  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM_ADMIN")) return;

if(!phorum_db_check_connection()){
    print "A database connection could not be established. " .
          "Please edit include/db/config.php.";
    return;
}

include_once "./include/admin/PhorumInputForm.php";
include_once "./include/version_functions.php";

// Find the upgrade step that we have to run.
$step = empty($_POST["step"]) ? 0 : $_POST["step"];

// If the database upgrades are all done, then force the script
// into step 2 (finish) of the upgrading process.
if (isset($PHORUM['internal_version']) && 
    $PHORUM['internal_version'] == PHORUM_SCHEMA_VERSION &&
    isset($PHORUM['internal_patchlevel']) &&
    $PHORUM['internal_patchlevel'] == PHORUM_SCHEMA_PATCHLEVEL) {
    $step = 2;
}

switch ($step) {

    // Step 0: this step occurs at the very start of the upgrade process.
    // It shows a message to the admin about the upcoming upgrade.
    case 0:

        $frm = new PhorumInputForm ("", "post", "Continue -&gt;");
        $frm->addbreak("Phorum Upgrade");
        $frm->addmessage("
            This wizard will upgrade Phorum on your server.<br/>
            Phorum has confirmed that it can connect to your database.<br/>
            Press continue when you are ready to start the upgrade.");
        $frm->hidden("module", "upgrade");
        $frm->hidden("step", "1");
        $frm->show();

        break;

    // Step 1: this step performs the actual upgrading steps.
    case 1:

        // Executing large, long running scripts from a browser can result
        // into problems, in case the script hits PHP resource boundaries.
        // Here we try to prepare the PHP environment for the upgrade.
        // Unfortunately, if the server is running with safe_mode enabled,
        // we cannot change the execution time and memory limits.
        if (! ini_get('safe_mode')) {
            set_time_limit(0);
            ini_set("memory_limit","64M");
        }

        // The internal_patchlevel can be unset, because this setting was
        // added in 5.2. When upgrading from 5.1, this settings is not yet
        // available. To make things work, we'll fake a value for this
        // setting which will always be lower than the available patch ids.
        if (!isset($PHORUM["internal_patchlevel"])) {
            $PHORUM["internal_patchlevel"] = "1111111111";
        }

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

        // See if we are fully done with upgrading now. This determines
        // the next step that we have to run in the upgrade process.
        $next_step = 1;
        if ($PHORUM['internal_version'] == PHORUM_SCHEMA_VERSION &&
            $PHORUM['internal_patchlevel'] == PHORUM_SCHEMA_PATCHLEVEL) {
            $next_step = 2;
        }

        $frm = new PhorumInputForm ("", "post", "Continue -&gt;");
        $frm->addbreak("Upgrading tables (multiple steps possible) ...");
        $frm->addmessage($message);
        $frm->hidden("step", $next_step);
        $frm->hidden("module", "upgrade");
        $frm->show();

        break;

    // Step 2: the upgrade has been completed.
    case 2:
        print "The upgrade is complete. You may want to look through " .
              "the <a href=\"$_SERVER[PHP_SELF]\">the admin interface</a> " .
              "for any new features in this version.";

        break;

    // Safety net for illegal step values.
    default:
        print "Internal error: illegal upgrading step " .
              htmlspecialchars($step) . " requested.";
        return;

}

?>
