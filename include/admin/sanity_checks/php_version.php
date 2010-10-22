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

// Check if the PHP version is 5 or higher.
// Phorum will not run on PHP4 anymore.

$phorum_check = "PHP version";

function phorum_check_php_version()
{
    if (version_compare(PHP_VERSION, '5.0.0', '<')) {
        return array(
            PHORUM_SANITY_CRIT,
            "You server is running PHP version ".PHP_VERSION.", however
             PHP version 5 or higher is required for running Phorum.",
            "Upgrade PHP to version 5. If you are hosting with a 
             company, please contact them to do this for you. Sometimes,
             PHP5 can be enabled by placing an .htaccess file with some
             special directives in your web directory. Your hosting
             company should be able to help you with this too."
        );
    }

    return array (PHORUM_SANITY_OK, NULL, NULL);
}
?>
