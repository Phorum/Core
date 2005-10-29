<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2005  Phorum Development Team                              //
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

// Check for a new version of the Phorum software. If there's a new version,
// inform the admin about this.

if(!defined("PHORUM_ADMIN")) return;

require_once("./include/version_functions.php");

print '<div class="PhorumAdminTitle">Check for new Phorum version</div>';
print '<br/>';

// Show the current software version.
list ($release_type, $dummy) = phorum_parse_version(PHORUM);
print "You are currently running the $release_type version " . PHORUM .
      " of the Phorum software.<br/>";

// Find and display available upgrades. If no releases can be found
// for some reason, we ignore this and simply pretend the installation
// is up-to-date.
$releases = phorum_find_upgrades("5.0.19b");
$found_upgrade = false;
foreach (array("stable","development") as $type) {
    if (isset($releases[$type]) && $releases[$type]["upgrade"])
    {
        $found_upgrade = true;

        print "<br/>";
        print "<h3 class=\"input-form-th\">A new $type release (version {$releases[$type]["version"]}) " .
              "is available</h3>";
        print "This release can be downloaded from:<br/><ul>";
        foreach ($releases["$type"]["locations"] as $url) {
            print "<li><a href=\"". htmlspecialchars($url) . "\">" .
                  htmlspecialchars($url) . "</a></li>";
        }
        print "</ul>";
    }
}

if (! $found_upgrade) {
    print "<br/><h3 class=\"input-form-th\">Your Phorum installation is up to date</h3>";
}
?>