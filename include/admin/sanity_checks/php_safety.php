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

    // Check if the cache directory is available and if
    // files and directories can be created in it. Also
    // do a basic check on Phorums caching API.

    $phorum_check = "PHP safety issues";

    function phorum_check_php_safety(){

        // Check if register_globals is on.
        if (ini_get('register_globals')) return array(
            PHORUM_SANITY_WARN,
            "The PHP configuration setting \"register_globals\" is set
             to \"On\". It is generally recommended to disable this
             setting, because it can introduce security problems for
             scripts that are not carefully written. See also
             http://www.php.net/register_globals for more info on this
             subject.<br/><br/>
             Although the Phorum development team always tries to write code
             which cannot be affected by this setting in any way, we can never 
             guarantee that it will be 100% safe.",
            "Disable \"register_globals\" in the PHP configuration file
             \"php.ini\" on your webserver. If you are hosting with a 
             company, please contact them to do this for you."
        );

        return array (PHORUM_SANITY_OK, NULL, NULL);
    }
?>
