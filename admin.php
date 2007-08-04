<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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

    // Phorum 5 Admin

    define("PHORUM_ADMIN", 1);

    // set a sane error level for our admin.
    // this will make the coding time faster and
    // the code run faster.
    error_reporting  (E_ERROR | E_WARNING | E_PARSE);

    include_once "./common.php";
    include_once "./include/admin_functions.php";

    // determine absolute URI for the admin
    if(isset($_SERVER["SCRIPT_URI"])){
        $PHORUM["admin_http_path"] = $_SERVER["SCRIPT_URI"];
    } else {
        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!="off") ? "https" : "http";
        $port = ($_SERVER["SERVER_PORT"]!=443 && $_SERVER["SERVER_PORT"]!=80) ? ":".$_SERVER["SERVER_PORT"] : "";
        $PHORUM["admin_http_path"] = $protocol."://".$_SERVER["HTTP_HOST"].$port.$_SERVER["PHP_SELF"];
    }

    // determine http_path (at install time; after that it's in the settings)
    if(!isset($PHORUM["http_path"])){
        $PHORUM["http_path"] = dirname($_SERVER["PHP_SELF"]);
    }

    // if we are installing or upgrading, we don't need to check for a session
    // 2005081000 was the internal version that introduced the installed flag
    if(!isset($PHORUM['internal_version']) || (!isset($PHORUM['installed']) && $PHORUM['internal_version']>='2005081000')) {

        // this is an install
        $module="install";

    } elseif ( (isset($_REQUEST["module"]) && $_REQUEST["module"]=="upgrade") ||
               $PHORUM['internal_version'] < PHORUM_SCHEMA_VERSION ||
               !isset($PHORUM['internal_patchlevel']) ||
               $PHORUM['internal_patchlevel'] < PHORUM_SCHEMA_PATCHLEVEL ) {

        // this is an upgrade
        $module="upgrade";

    } else {

        // Try to restore an admin session.
        phorum_api_user_session_restore(PHORUM_ADMIN_SESSION);

        if(!isset($GLOBALS["PHORUM"]["user"]) || !$GLOBALS["PHORUM"]["user"]["admin"]){
            // if not an admin
            unset($GLOBALS["PHORUM"]["user"]);
            $module="login";
        } else {
            // load the default module if none is specified
            if(!empty($_REQUEST["module"]) && is_string($_REQUEST["module"])){
                $module = @basename($_REQUEST["module"]);
            } else {
                $module = "default";
            }

        }

    }

    $module = phorum_hook( "admin_pre", $module );
    ob_start();
    if($module!="help") include_once "./include/admin/header.php";
    include_once "./include/admin/$module.php";
    if($module!="help") include_once "./include/admin/footer.php";
    ob_end_flush();




?>
