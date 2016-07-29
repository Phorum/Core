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

// Execute file purging.
if(count($_POST))
{
    print "<h2>Purging the cache now.<br/>One moment please...</h2>";
    ob_flush();

    // The standard cache system that is in use should handle its own
    // cache cleanup if needed. It can do so by implementing the
    // phorum_api_cache_purge() function. If the required function is not
    // available, then the caching layer purge will be ignored.
    if (function_exists("phorum_api_cache_purge")) {
        $full_purge = isset($_POST["purge_all"]) && $_POST["purge_all"];
        $report = phorum_api_cache_purge($full_purge);
        print $report . "<br/>";
    }

    // Cleanup compiled templates.
    $purged = 0;
    $dh = opendir($PHORUM['CACHECONFIG']['directory']);
    if (! $dh) die ("Can't opendir " . htmlspecialchars($PHORUM['CACHECONFIG']['directory']));
    while ($entry = readdir($dh)) {
        if (preg_match('/^tpl-.*[a-f0-9]{32}\.php(-stage2)?$/', $entry)) {
            $compiled_tpl = $PHORUM['CACHECONFIG']['directory']. "/$entry";
            $size = filesize($compiled_tpl);
            if (@unlink($compiled_tpl)) {
                $purged += $size;
            }
        }
    }
    print "Finished purging compiled Phorum templates<br/>\n" .
          "Purged " . phorum_api_format_filesize($purged) . "<br/>";

    print "<br/>";
    print "DONE<br/><br/>";
}

require_once './include/admin/PhorumInputForm.php';
$frm = new PhorumInputForm ("", "post", "Purge cache");
$frm->hidden("module", "cache_purge");

$frm->addbreak("Purging the Phorum cache");
$frm->addmessage("For improving performance, Phorum uses caching techniques for taking some load of the database and webserver. After running Phorum for some time, the amount of cached data will grow though. Using this maintenance tool, you can purge stale data from the Phorum cache to bring it back in size. Purging the cache will also cleanup all compiled template files.");

$frm->addrow("Cleanup all cache items, not only the expired ones", $frm->select_tag("purge_all", array("No", "Yes"), 0));


$frm->show();

?>
