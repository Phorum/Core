<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if (!defined("PHORUM_ADMIN")) return;

if (!$PHORUM['DB']->check_connection()) {
    print "A database connection could not be established. " .
          "Please edit include/config/database.php.";
    return;
}

require_once './include/admin/PhorumInputForm.php';
require_once './include/version_functions.php';
require_once './include/misc/polyfill-each.php';

$is_module_upgrade = isset($_POST['is_module_upgrade'])
      ? ($_POST['is_module_upgrade'] ? 1 : 0)
      : (defined('MODULE_DATABASE_UPGRADE') ? 1 : 0);

// Find and count the upgrades that have to be run.
if ($is_module_upgrade) {
    require_once './include/api/modules.php';
    $upgrades = phorum_api_modules_check_updated_dblayer();
} else {
    $upgrades = phorum_dbupgrade_getupgrades();
}
$upgradecount = count($upgrades);

// Find the upgrade step that we have to run.
$step = empty($_POST["step"]) ? 0 : $_POST["step"];

// If the database upgrades are all done, then force the script
// into step 2 (finish) of the upgrading process.
if ($upgradecount == 0) $step = 2;


// Run sanity checks prior to installing Phorum. Here we do some
// checks to see if the environment is setup correctly for running
// Phorum.
if ($step == 0 && !isset($_POST["sanity_checks_done"]))
{
    // Setup some fake environment data for the checks.
    $PHORUM["real_cache"] = $PHORUM['CACHECONFIG']['directory'] . "/install_tmp_sanity_check_cache_dir";

    // Load and run all available checks.
    include "./include/admin/sanity_checks.php";

    ?>
    <h1>Checking your system</h1>

    Prior to installing Phorum, your system will be checked to see
    if there are any problems that might prevent Phorum from running
    correctly. Below you will find the results of the checks. Warnings
    indicate that some problem needs attention, but that the problem
    will not keep Phorum from running. Errors indicate critical
    problems, which need to be fixed before running Phorum.
    <br/><br/>

    <script type="text/javascript">
    function toggle_sanity_info(check_id)
    {
        info_div = document.getElementById("sanity_info_" + check_id);
        info_link = document.getElementById("sanity_info_link_" + check_id);
        if (info_div && info_link) {
            if (info_div.style.display == "block") {
                info_div.style.display = "none";
                info_link.innerHTML = "show problem info";
            } else {
                info_div.style.display = "block";
                info_link.innerHTML = "hide problem info";
            }
        }
    }
    </script>
    <?php

    // Display the results of the sanity checks.
    $got_crit = false;
    $got_warn = false;
    foreach ($PHORUM["SANITY_CHECKS"]["CHECKS"] as $check)
    {
        if ($check["status"] == PHORUM_SANITY_SKIP) continue;
        if ($check["status"] == PHORUM_SANITY_CRIT) $got_crit = true;
        if ($check["status"] == PHORUM_SANITY_WARN) $got_warn = true;
        $display = $status2display[$check["status"]];
        print "<div style=\"padding: 10px; background-color:#f5f5f5;border: 1px solid #ccc; margin-bottom: 5px;\">";
        print "<div style=\"float:left; text-align:center; margin-right: 10px; width:100px; border: 1px solid #444; background-color:{$display[0]}; color:{$display[1]}\">{$display[2]}</div>";
        print '<b>' . $check["description"] . '</b>';

        if ($check["status"] != PHORUM_SANITY_OK)
        {
            print " (<a id=\"sanity_info_link_{$check["id"]}\" href=\"javascript:toggle_sanity_info('{$check["id"]}')\">show problem info</a>)";
            print "<div id=\"sanity_info_{$check["id"]}\" style=\"display: none; padding-top: 15px\">";
            print "<b>Problem:</b><br/>";
            print $check["error"];
            print "<br/><br/><b>Possible solution:</b><br/>";
            print $check["solution"];
            print "</div>";
        }
        print "</div>";
    }

    // Display navigation options, based on the check results.
    ?>
    <form method="post" action="<?php print $_SERVER["PHP_SELF"] ?>">
    <input type="hidden" name="module" value="upgrade" />
    <input type="hidden" name="is_module_upgrade" value="<?php print $is_module_upgrade;?>" />
    <?php
    if ($got_crit) {
        ?>
        <br/>
        One or more critical errors were encountered while checking
        your system. To see what is causing these errors and what you
        can do about them, click the "show problem info" links.
        Please fix these errors and restart the system checks.
        <br/><br/>
        <input type="submit" value="Restart the system checks" />
        <?php

    } elseif ($got_warn) {
        ?>
        <br/>
        One or more warnings were encountered while checking
        your system. To see what is causing these warnings and what you
        can do about them, click the "show problem info" links.
        Phorum probably will run without fixing the warnings, but
        it's a good idea to fix them anyway for ensuring optimal
        performance.
        <br/><br/>
        <input type="submit" value="Restart the system checks" />
        <input type="submit" name="sanity_checks_done" value="Continue without fixing the warnings -&gt;" />
        <?php
    } else {
        ?>
        <br/>
        No problems were encountered while checking your system.
        You can now continue with the Phorum installation.
        <br/><br/>
        <input type="submit" name="sanity_checks_done" value="Continue -&gt;" />
        <?php
    }

    ?>
    </form>
    <?php

    return;
}

switch ($step) {

    // Step 0: this step occurs at the very start of the upgrade process.
    // It shows a message to the admin about the upcoming upgrade.
    case 0:

        $frm = new PhorumInputForm ("", "post", "Continue -&gt;");

        $frm->addbreak(
            $is_module_upgrade
            ? "Phorum Module Upgrade"
            : "Phorum Upgrade"
        );
        $frm->addmessage(
            $is_module_upgrade
            ? "Upgrades are available for one or more Phorum Modules.<br/>
               This wizard will handle these upgrades."
            : "Upgrades are available for Phorum.<br/>
               This wizard will handle these upgrades."
        );
        $frm->addmessage(
            "Phorum has confirmed that it can connect to your database.<br/>
             Press continue when you are ready to start the upgrade."
        );
        $frm->hidden("module", "upgrade");
        $frm->hidden("is_module_upgrade", $is_module_upgrade);
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
        $frm->addbreak("Upgrading Phorum (multiple steps possible) ...");
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
        $frm->hidden("is_module_upgrade", $is_module_upgrade);
        $frm->hidden("upgradeindex", $index);
        $frm->hidden("upgradecount", $count);
        $frm->show();

        break;

    // Step 2: the upgrade has been completed.
    case 2:

        // Show the results.
        $base_url = phorum_admin_build_url();
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
