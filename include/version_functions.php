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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if (!defined("PHORUM")) return;

// The internal_patchlevel can be unset, because this setting was
// added in 5.2. When upgrading from 5.1, this settings is not yet
// available. To make things work, we'll fake a value for this
// setting which will always be lower than the available patch ids.
if (!isset($PHORUM["internal_patchlevel"])) {
    $PHORUM["internal_patchlevel"] = "1111111111";
}

/**
 * Parses a Phorum version number.
 *
 * The following version numberings are recognized:
 *
 * - Snapshot release, e.g. "phorum5-svn-2007121315".
 *   We only have one version element for these.
 *   The returned release type will be "snapshot".
 *
 * - Development release from the downloads section at phorum.org,
 *   e.g. "5.1.10-alpha", "5.2.1-beta" or "5.2.2-RC1". The returned
 *   release type will be "development" and the version will contain
 *   three elements.
 *
 * - Stable release, e.g. "5.1.20" or "5.1.16a". A letter can be appended
 *   to indicate a quick fix release. We let the letter come back as a
 *   numerical value in the fourth element of the returned version array,
 *   (where a = 1, b = 2, etc) or 0 (zero) if no quick fix version is
 *   available. Normally, we shouldn't get further than an "a" or "b"
 *   quick fix release. The returned release type will be "stable".
 *
 * - Development release from the subversion repository, e.g. "5.2-dev".
 *   The version will have two elements. The returned release type
 *   will be "repository".
 *
 * If the version number cannot be parsed, then the returned release
 * type will be "unknown" and the parsed version will be an empty array.
 * This case should never happen of course.
 *
 * @param string $version
 *     The version number to parse.
 *
 * @return array
 *     An array containing three elements:
 *     - The release type, which can be "unknown" (parse failed),
 *       "alpha", "beta", "development" (if something else than the
 *       previous two were used for a development version), "snapshot",
 *       "repository", "candidate" or "stable".
 *     - An array containing the parsed version. This version is an
 *       array containing a split up version number, with zero to five
 *       elements in it (only relevant version parts are added).
 *     - The version number that was parsed.
 */
function phorum_parse_version($version)
{
    // Snapshot release, e.g. "phorum5-svn-2007121315".
    if (preg_match('/^phorum(\d+)-svn-\d+$/', $version, $m)) {
        $release = 'snapshot';
        $parsed_version = array($m[1]);
    // Stable release, e.g. "5.1.20" or "5.1.16a".
    } elseif (preg_match('/^(\d+)\.(\d+).(\d+)([a-z])?$/', $version, $m)) {
        $release = 'stable';
        $subrelease = empty($m[4]) ? 0 : ord($m[4])-96; // ord('a') = 97;
        $parsed_version = array($m[1], $m[2], $m[3], $subrelease);
    // Release candidate, e.g. "5.1.26-RC1" or "5.2.10a-RC2".
    } elseif (preg_match('/^(\d+)\.(\d+).(\d+)([a-z])?-RC(\d+)$/', $version, $m)) {
        $release = 'candidate';
        $subrelease = empty($m[4]) ? 0 : ord($m[4])-96; // ord('a') = 97;
        $parsed_version = array($m[1], $m[2], $m[3], $subrelease, $m[5]);
    // Development release from a subversion tree, e.g. "5.2-dev".
    } elseif (preg_match('/^(\d+)\.(\d+)(-\w+)?$/', $version, $m)) {
        $release = 'repository';
        $parsed_version = array($m[1], $m[2]);
    // Development release, e.g. "5.1.10-alpha", "5.2.1a-beta".
    } elseif (preg_match('/^(\d+)\.(\d+).(\d+)([a-z])?-(\w+)$/', $version, $m)) {
        if ($m[5] == 'alpha')    $release = 'alpha';
        elseif ($m[5] == 'beta') $release = 'beta';
        else                     $release = 'development';
        $subrelease = empty($m[4]) ? 0 : ord($m[4])-96; // ord('a') = 97;
        $parsed_version = array($m[1], $m[2], $m[3], $subrelease);
    // We should never get here.
    } else {
        $release = 'unknown';
        $parsed_version = array();
    }

    return array($release, $parsed_version, $version);
}

/**
 * Compares two version numbers.
 *
 * This function will tell which of two version numbers is higher.
 *
 * @param array version1
 *     The first version number. Either the version number or the
 *     return array from phorum_parse_version().
 *
 * @param array version2
 *     The second version number. Either the version number or the
 *     return array from phorum_parse_version().
 *
 * @return integer
 *      1 if version1 is higher than version2.
 *      0 if they are equal.
 *     -1 if version1 is lower lower than version2.
 */
function phorum_compare_version($version1, $version2)
{
    // Parse version numbers if no parsed arrays were provided.
    if (!is_array($version1)) $version1 = phorum_parse_version($version1);
    if (!is_array($version2)) $version2 = phorum_parse_version($version2);

    // Compare relevant parts of the parsed version numbers to see
    // what version is higher.
    for ($s=0; $s<=4; $s++) {
        if (!isset($version1[1][$s]) || !isset($version2[1][$s])) break;
        if ($version1[1][$s] > $version2[1][$s]) return +1;
        if ($version1[1][$s] < $version2[1][$s]) return -1;
    }

    // No difference was found. In this case, we consider the release type.
    // Repository can of course be a lower release than a stable one,
    // but we always see it as a higher release. People that use development
    // releases should know what they are doing.
    $order = array(
        'unknown'     => 0,
        'alpha'       => 1,
        'beta'        => 2,
        'development' => 3,
        'candidate'   => 4,
        'stable'      => 5,
        'snapshot'    => 6,
        'repository'  => 7
    );

    $t1 = $order[$version1[0]];
    $t2 = $order[$version2[0]];

    if ($t1 == $t2)    return 0;
    elseif ($t1 < $t2) return -1;
    else               return +1;
}

/**
 * Retrieves the available software versions from the Phorum website.
 * The format of the data returned from the server is two lines. The first
 * line is for the stable version and the second for the development version.
 * Each line contains pipe separated values, with the following fields in it:
 * <version>|<release date>|<downloadloc 1>|<downloadloc 2>|...|<downloadloc n>
 *
 * @return releases - An array of releases for release types
 *                    "stable" and "development".
 */
function phorum_available_releases()
{
    $releases = array();
    $fp = @fopen("http://phorum.org/version.php", "r");
    if ($fp) {
        foreach (array("stable", "development") as $release) {
            $line = fgets($fp, 1024);
            if (strstr($line, '|')) {
                $fields = explode('|', $line);
                if (count($fields) >= 3) {
                    // See if we can parse the version and if the parsed
                    // release type matches the release type we're expecting.
                    $parsed_version = phorum_parse_version($fields[0]);
                    if ($parsed_version[0] == $release) {
                        $releases[$release] = array(
                            "version"   => array_shift($fields),
                            "pversion"  => $parsed_version,
                            "date"      => array_shift($fields),
                            "locations" => $fields
                        );
                    }
                }
            }
        }
        fclose($fp);
    }

    return $releases;
}

/**
 * Finds out if there are any upgrades available for a version of Phorum.
 *
 * @param version - the version to check for (default is the running version)
 * @return releases - An array of available releases with the
 *         "upgrade" field set in case the release would be an
 *         upgrade for the currently running Phorum software.
 */
function phorum_find_upgrades($version = PHORUM)
{
    // Parse the running version of phorum.
    $running_version = phorum_parse_version($version);

    // Retrieve the available releases.
    $releases = phorum_available_releases();

    // Check if an upgrade is available for the running release.
    // If we're running a stable version, we only compare to the current
    // stable release. If we're running a development version, we compare both
    // stable and development.
    if (isset($releases["stable"])) {
        $avail_version = $releases["stable"]["pversion"];
        if (phorum_compare_version($running_version, $avail_version) == -1) {
            $releases["stable"]["upgrade"] = true;
        } else {
            $releases["stable"]["upgrade"] = false;
        }
    }
    if (($running_version[0] == 'development' ||
         $running_version[0] == 'snapshot') &&
         isset($releases["development"])) {
        $avail_version = $releases["development"]["pversion"];
        if (phorum_compare_version($running_version, $avail_version) == -1) {
            $releases["development"]["upgrade"] = true;
        } else {
            $releases["development"]["upgrade"] = false;
        }
    }

    return $releases;
}

/**
 * Retrieves all database patches and upgrades that have not yet
 * been processed.
 *
 * @return array $upgradefiles
 *     An array of upgradefiles. The keys in the array are "<version>-<type>",
 *     where <type> is either "patch" or "schema". The values are arrays with
 *     the following fields set:
 *       - version: the version of the upgrade file
 *       - type: the type of upgrade ("patch" or "schema")
 *       - file: the path to the upgrade file
 *     The array is sorted by version number.
 */
function phorum_dbupgrade_getupgrades()
{
    global $PHORUM;

    // Find the core type for the used db layer. By default, the core type
    // is the same as the db layer type. A db layer can however override the
    // core type to hike along with database upgrades from another database
    // layer. This allows for example to have the mysqli and mysql layers
    // share the same core type (since their only real difference is not in
    // the database schema/data, but in the use of different PHP calls).
    $core_type = isset($PHORUM['DBCONFIG']['core_type'])
               ? $PHORUM['DBCONFIG']['core_type']
               : $PHORUM['DBCONFIG']['type'];
    $core_type = basename($core_type);

    // Go over both the patches and schema upgrades and find all
    // upgrades that have not yet been processed.
    $upgrades = array();
    foreach (array('patch', 'schema') as $type)
    {
        $upgradepath =
            "./include/db/upgrade/$core_type" .
            ($type == 'patch' ? '-patches' : '');

        $curversion = $type == 'patch'
                    ? $PHORUM['internal_patchlevel']
                    : $PHORUM['internal_version'];

        $wantversion = $type == 'patch'
                    ? PHORUM_SCHEMA_PATCHLEVEL
                    : PHORUM_SCHEMA_VERSION;

        // Find all available upgrade files in the upgrade directory.
        // Upgrade file are in the format YYYYMMDDSS.php, where
        // Y = year, M = month, D = day, S = serial.
        // Example: "2007031700.php".
        if (($dh =@opendir($upgradepath)) === FALSE) die (
            "phorum_dbupgrade_getupgrades(): unable to open the upgrade " .
            "directory " . htmlspecialchars($upgradepath)
        );
        while (($file = readdir ($dh)) !== FALSE) {
            if (preg_match('/^(\d{10})\.php$/', $file, $m)) {
                $version = $m[1];
                if ($version > $curversion && $version <= $wantversion) {
                    $upgrades["$version-$type"] = array(
                        "version" => $version,
                        "type"    => $type,
                        "file"    => "$upgradepath/$file"
                    );
                }
            }
        }
        unset($file);
        closedir($dh);
    }

    // Sort the upgradefiles. We can use a standard sort here,
    // since they are in the strict YYYYMMDDSS-<type> format.
    // The version numbers will be leading for the sort. If the
    // same version is available as a patch and a schema upgrade,
    // then the patch will come before the schema upgrade.
    asort($upgrades);

    return $upgrades;
}

/**
 * Perform the upgrade for a single upgrade file.
 *
 * @param $upgrades - An upgrade description. One element from the array
 *                    as returned by phorum_dbupgrade_getupgrades().
 * @param $update_internal_version - whether to update the internal version
 *                    for Phorum or not. This one is TRUE by default.
 *                    It can be used by scripts that have to re-run an old
 *                    single upgrade file and for which the internal version
 *                    should not be put back to an old value.
 * @return $msg - Describes the results of the upgrade.
 */
function phorum_dbupgrade_run($upgrade, $update_internal_version = TRUE)
{
    $PHORUM      = $GLOBALS["PHORUM"];
    $version     = $upgrade["version"];
    $type        = $upgrade["type"];
    $upgradefile = $upgrade["file"];

    $versionvar = $type == 'patch' ? 'internal_patchlevel':'internal_version';

    // Find the version from which we're upgrading.
    $fromversion = $PHORUM[$versionvar];

    // Executing large, long running scripts can result in problems,
    // in case the script hits PHP resource boundaries. Here we try
    // to prepare the PHP environment for the upgrade. Unfortunately,
    // if the server is running with safe_mode enabled, we cannot
    // change the execution time and memory limits.
    if (! ini_get('safe_mode')) {
        set_time_limit(0);
        ini_set("memory_limit","64M");
    }

    // Check if the upgradefile is readable.
    if (file_exists($upgradefile) && is_readable($upgradefile))
    {
        // Initialize the return message.
        if (!$update_internal_version) {
            $msg = "Installing patch $version ...<br/>\n";
        }
        // Patch level 1111111111 is a special value that is used by
        // phorum if there is no patch level stored in the database.
        // So this is the first time a patch is installed.
        elseif ($fromversion == '1111111111') {
            $msg = "Upgrading to patch level $version ...<br/>\n";
        } else {
            $msg = "Upgrading from " .
                   ($type == "patch"?"patch level ":"database version ") .
                   "$fromversion to $version ...<br/>\n";
        }

        // Load the upgrade file. The upgrade file should fill the
        // $upgrade_queries array with the necessary queries to run.
        $upgrade_queries = array();
        include($upgradefile);

        // Run the upgrade queries.
        $err = phorum_db_run_queries($upgrade_queries);
        if($err !== NULL){
            $msg.= "An error occured during this upgrade:<br/><br/>\n" .
                   "<span style=\"color:red\">$err</span><br/><br/>\n" .
                   "Please make note of this error and contact the " .
                   "Phorum Dev Team for help.\nYou can try to continue " .
                   "with the rest of the upgrade.<br/>\n";
        } else {
            $msg.= "The upgrade was successful.<br/>\n";
        }

        // Update the upgrade version info.
        if ($update_internal_version) {
            $GLOBALS["PHORUM"][$versionvar] = $version;
            phorum_db_update_settings(array($versionvar => $version));
        }

        return $msg;

    } else {
        return "The upgrade file ".htmlspecialchars($upgradefile)." " .
               "cannot be opened by Phorum for reading. Please check " .
               "the file permissions for this file and try again.";
    }
}

?>
