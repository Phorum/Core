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

// For phorum_check_ban_lists().
include_once("./include/profile_functions.php");

// A mapping from bantype -> errormessage.
$bantype2error = array(
    PHORUM_BAD_NAMES  => "ErrBannedName",
    PHORUM_BAD_EMAILS => "ErrBannedEmail",
    PHORUM_BAD_USERID => "ErrBannedUser",
    PHORUM_BAD_IPS    => "ErrBannedIP",
);

// Create a list of the bans that we want to check.
$bans = array();

if ($PHORUM["DATA"]["LOGGEDIN"]) { // Checks for registered users.
    $bans[] = array($PHORUM["user"]["username"], PHORUM_BAD_NAMES);
    $bans[] = array($PHORUM["user"]["email"], PHORUM_BAD_EMAILS);
    $bans[] = array($PHORUM["user"]["user_id"], PHORUM_BAD_USERID);
} else { // Checks for unregistered users.
    $bans[] = array($message["author"], PHORUM_BAD_NAMES);
    $bans[] = array($message["email"], PHORUM_BAD_EMAILS);
}

// Check the IP-address for blacklisting. Also check the hostname
// for blacklisting if dns_lookup was enabled.
$bans[] = array($_SERVER["REMOTE_ADDR"], PHORUM_BAD_IPS, "ErrBannedIP");
if ($PHORUM["dns_lookup"]) {
    $REMOTE_ADDR = @gethostbyaddr($_SERVER["REMOTE_ADDR"]);
    if ($REMOTE_ADDR != $_SERVER["REMOTE_ADDR"])
        $bans[] = array($REMOTE_ADDR, PHORUM_BAD_IPS, "ErrBannedIP");
} else {
    $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
}

// Load the ban lists
$PHORUM["banlists"] = phorum_db_get_banlists();

// Run the checks.
foreach ($bans as $ban)
{
    if (! phorum_check_ban_lists($ban[0], $ban[1]))
    {
        $msg = $PHORUM["DATA"]["LANG"][$bantype2error[$ban[1]]];
        $PHORUM["DATA"]["MESSAGE"] = $msg;
        $error_flag = true;
        break;
    }
}

?>
