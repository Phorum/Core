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

if(!defined("PHORUM")) return;

// The place where our sanity checking modules are.
$sanity_checks_dir = "./include/admin/sanity_checks";

// Mapping of status to display representation.
$status2display = array(
//  STATUS                       BACKGROUND    FONT     TEXT
    PHORUM_SANITY_OK    => array('green',      'white', 'ALL IS OK'),
    PHORUM_SANITY_WARN  => array('darkorange', 'white', 'WARNING'),
    PHORUM_SANITY_CRIT  => array('red',        'white', 'ERROR'),
);

// ========================================================================
// Load in the available sanity checks.
// ========================================================================

$sanity_checks = array();
$dh = opendir ($sanity_checks_dir);
if (!$dh) trigger_error("Could not open sanity checks directory",E_USER_ERROR);
while ($file = readdir($dh)) {
    if (preg_match('/^(.+)\.php$/', $file, $m)) {
        unset($phorum_check);
        include("$sanity_checks_dir/$file");
        $func = "phorum_check_$m[1]";
        if (!isset($phorum_check)||!function_exists($func)) trigger_error(
            "$sanity_checks_dir/$file is no valid check file! " .
            "Either \$phorum_check is not set or the " .
            "function " . htmlspecialchars($func) . " does not exist",
            E_USER_ERROR
        );

        $sanity_checks[] = array (
            'function'    => $func,
            'id'          => $m[1],
            'description' => $phorum_check,
        );
    }
}

// Give module writers a possiblity to write custom sanity checks.
$sanity_checks = phorum_hook("sanity_checks", $sanity_checks);

// ========================================================================
// Run the sanity checks.
// ========================================================================

// Initialize the results array.
$PHORUM["SANITY_CHECKS"] = array(
    "CHECKS"   => array(),
    "COUNTERS" => array(
        PHORUM_SANITY_OK   => 0,
        PHORUM_SANITY_WARN => 0,
        PHORUM_SANITY_CRIT => 0,
        PHORUM_SANITY_SKIP => 0
    )
);

// Make using $php_errormsg possible for the checks.
ini_set('track_errors', 1);

// Run all available sanity checks.
foreach ($sanity_checks as $check)
{
    // Call the sanity check function. This function is expected
    // to return an array containing the following elements:
    //
    // [1] A status, which can be one of
    //     PHORUM_SANITY_OK      No problem found
    //     PHORUM_SANITY_WARN    Problem found, but no fatal one
    //     PHORUM_SANITY_CRIT    Critical problem found
    //     PHORUM_SANITY_SKIP    No check was done 
    //
    // [2] A description of the problem that was found or NULL.
    //
    // [3] A solution for the problem or NULL.
    //
    $is_install = $module == "install";
    list($status, $error, $solution) = call_user_func($check["function"], $is_install);

    $PHORUM["SANITY_CHECKS"]["CHECKS"][] = array(
        'id'          => $check["id"],
        'description' => $check["description"],
        'status'      => $status,
        'error'       => $error,
        'solution'    => $solution,
    );

    $PHORUM["SANITY_CHECKS"]["COUNTERS"][$status] ++;
}

// If the sanity checks are called from the installation,
// the we're done.
if ($module == "install") return;

// ========================================================================
// Build the sanity checking admin page.
// ========================================================================

include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Restart sanity checks");

$frm->hidden("module", "sanity_checks");
$frm->addbreak("Phorum System Sanity Checks");
$frm->addmessage(
    "Below you will find the results for a number of sanity checks
     that have been performed on your system. If you see any
     warnings or errors, then read the comments for them and 
     try to resolve the issues."
);

// Show sanity check results.
foreach ($PHORUM["SANITY_CHECKS"]["CHECKS"] as $check)
{
    if ($check["status"] != PHORUM_SANITY_SKIP)
    {
        if (isset($check["error"])) {
            $check["error"] = str_replace("\n", " ", $check["error"]);
        }
        if (isset($check["solution"])) {
            $check["solution"] = str_replace("\n", " ", $check["solution"]);
        }
        $display = $status2display[$check["status"]];
        $block = "<div style=\"color:{$display[1]};background-color:{$display[0]};text-align:center;border:1px solid black;\">{$display[2]}</div>";
        $row = $frm->addrow($check['description'], $block);
        if (! empty($check["error"])) {
            if (! empty($check["solution"]))
                $check["error"] .= 
                          "<br/><br/>" .
                          "<strong>Possible solution:</strong>" .
                          "<br/><br/>" .
                          $check["solution"];
            $frm->addhelp($row,"Sanity check failed",$check["error"]);
        }
    }
}

$frm->show();

?>
