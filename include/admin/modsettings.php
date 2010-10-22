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

    $mod = basename($_REQUEST["mod"]);

    if(file_exists("./mods/$mod/settings.php")){
        if (!isset($PHORUM["mods"][$mod]) || !$PHORUM["mods"][$mod]) {
            $text = "This module is not enabled yet. You can change its settings but the module is only active if you enable it on the previous page.";
            phorum_admin_error($text);
        }
        include_once("./mods/$mod/settings.php");

    } else {

        echo "There are no settings for this module.";

    }


?>
