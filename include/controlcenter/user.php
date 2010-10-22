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

if(!defined("PHORUM_CONTROL_CENTER")) return;

if(count($_POST)) {
    list($error,$okmsg) = phorum_controlcenter_user_save($panel);
}

// need their names for the later check
$profile_field_names=array();
if(is_array($PHORUM["PROFILE_FIELDS"])) {
    foreach ($PHORUM["PROFILE_FIELDS"] as $id => $fieldinfo) {
        $profile_field_names[$fieldinfo['name']]=$fieldinfo['name'];
    }
}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["EditUserinfo"];
$PHORUM['DATA']['PROFILE']['USERPROFILE'] = 1;
$template = "cc_usersettings";

?>
