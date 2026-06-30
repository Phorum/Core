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

// Check if the PHP version is 8.2 or higher.
// Phorum requires PHP 8.2+; older versions are end-of-life.

$phorum_check = "PHP version";

function phorum_check_php_version()
{
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        return array(
            PHORUM_SANITY_CRIT,
            "Your server is running PHP version " . PHP_VERSION . ", however
             PHP 8.2 or higher is required for running Phorum. Versions
             older than 8.2 have reached end-of-life and no longer receive
             security updates.",
            "Upgrade PHP to version 8.2 or higher. If you are hosted with
             a company, please contact them to do this for you."
        );
    }

    return array(PHORUM_SANITY_OK, NULL, NULL);
}
?>
