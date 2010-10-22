<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

// Find and count the upgrades that have to be run.
$upgrades = phorum_dbupgrade_getupgrades();
$upgradecount = count($upgrades);

// Find the upgrade step that we have to run.
$step = empty($_POST["step"]) ? 0 : $_POST["step"];

// If the database upgrades are all done, then force the script
// into step 2 (finish) of the upgrading process.
if ($upgradecount == 0) $step = 2;

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
        $frm->hidden("upgradecount", $upgradecount);
        $frm->show();

        break;

    // Step 1: this step performs the actual upgrading steps.
    case 1:

        // For some extra status information to the user.
        $index = isset($_POST["upgradeindex"])
               ? $_POST["upgradeindex"]+1 : 1;
        $count = isset($_POST["upgradecount"])
               ? $_POST["upgradecount"] : $upgradecount;
        // Make sure that the visual feedback doesn't turn up weird
        // if the admin does some clicking back and forth in the browser.
        if ($index > $count) {
            $index = 1;
            $count = $upgradecount;
        }

        // Run the first upgrade from the list of available upgrades.
        list ($dummy, $upgrade) = each($upgrades);
        $message = phorum_dbupgrade_run($upgrade);

        // Show the results.
        $frm = new PhorumInputForm ("", "post", "Continue -&gt;");
        $frm->addbreak("Upgrading tables (multiple steps possible) ...");
        $w = floor(($index/$count)*100);
        $frm->addmessage(
            '<table><tr><td>' .
            '<div style="height:20px;width:300px; border:1px solid black">' .
            '<div style="height:20px;width:'.$w.'%; background-color:green">' .
            '</div></div></td><td style="padding-left:10px">' .
            'upgrade ' . $index . " of " . $count .
            '</td></tr></table>'
        );
        $frm->addmessage($message);
        $frm->hidden("step", 1);
        $frm->hidden("module", "upgrade");
        $frm->hidden("upgradeindex", $index);
        $frm->hidden("upgradecount", $count);
        $frm->show();

        break;

    // Step 2: the upgrade has been completed.
    case 2:

        // Show the results.
        $base_url = phorum_admin_build_url('');
        $frm = new PhorumInputForm ("", "post", "Finish");
        $frm->addbreak("The upgrade is complete");
        $frm->addmessage(
              "You may want to look through the " .
              "<a href=\"$base_url\">the admin interface</a> " .
              "for any new features in this version."
        );
        $frm->show();

        break;

    // Safety net for illegal step values.
    default:
        print "Internal error: illegal upgrading step " .
              htmlspecialchars($step) . " requested.";
        return;

}

?>
