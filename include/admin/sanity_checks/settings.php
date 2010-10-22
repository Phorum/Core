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

// Check some of the settings for validity

$phorum_check = "Valid Phorum settings";

function phorum_check_settings($is_install = FALSE)
{
    global $PHORUM;

    if(!function_exists("finfo_open") && !empty($PHORUM['file_fileinfo_ext'])) {
        return array (
            PHORUM_SANITY_WARN,
            "You have \"Use the fileinfo extension for mime-type detection\" enabled but the fileinfo extension isn't installed.",
            "Install the fileinfo extension or disable this General Setting."
        );
    }

    return array (PHORUM_SANITY_OK, NULL, NULL);
}
?>