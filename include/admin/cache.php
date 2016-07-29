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

if (!defined( "PHORUM_ADMIN")) return;

$error = "";
$okmsg = "";

if ( count( $_POST ) ) {
    $new_settings = array();
    // set the defaults
    foreach( $_POST as $field => $value ) {
        switch ( $field ) {
            case "cache_rss":
            case "cache_users":
            case "cache_messages":
            case "cache_banlists":
            case "cache_newflags":
            case "cache_css":
            case "cache_javascript":

                if ( empty( $value ) ) {
                    $new_settings[$field] = 0;
                } else {
                    $new_settings[$field] = 1;
                }

        }

        if ( $error ) break;
    }

    if ( empty( $error ) ) {
        unset( $_POST["module"] );

        if ( $PHORUM['DB']->update_settings( $new_settings ) ) {
            $okmsg = "Settings updated";
            // reset those to the global array
            foreach($new_settings as $key => $val) {
                $PHORUM[$key]=$val;
            }
        } else {
            $error = "Database error while updating settings.";
        }
    }
}

if ( $error ) {
    phorum_admin_error( $error );
} elseif( $okmsg ) {
    phorum_admin_okmsg ( $okmsg);
}

require_once './include/admin/PhorumInputForm.php';

$frm = new PhorumInputForm ( "", "post" );
$frm->hidden( "module", "cache" );
$frm->addbreak( "Phorum Cache Settings" );

$frm->addbreak("Which template data to cache (uses cache directory)");

$row=$frm->addrow( "Enable Caching stylesheet data (CSS):", $frm->select_tag( "cache_css", array( "No", "Yes" ), $PHORUM["cache_css"] ) );
$frm->addhelp($row, "Cache stylesheet",
    "Phorum uses a system where modules can add data to the main stylesheet
     for the pages. By enabling this feature, Phorum can cache the dynamically
     generated data and improve caching of the stylesheet data in the browsers
     of your visitors. Enabling this feature is highly recommended. Only
     disable it if you are having problems with it."
);

$row=$frm->addrow( "Enable Caching JavaScript code:", $frm->select_tag( "cache_javascript", array( "No", "Yes" ), $PHORUM["cache_javascript"] ) );
$frm->addhelp($row, "Cache JavaScript",
    "Phorum uses a system where modules can provide JavaScript code for the
     pages. By enabling this feature, Phorum can cache the dynamically
     generated data and improve caching of the JavaScript data in the browsers
     of your visitors. Enabling this feature is highly recommended. Only
     disable it if you are having problems with it."
);

$frm->addbreak("Which data to cache (uses selected cache layer)");

$row=$frm->addrow( "Enable Caching Userdata:", $frm->select_tag( "cache_users", array( "No", "Yes" ), $PHORUM["cache_users"] ) );
$row=$frm->addrow( "Enable Caching Newflags:", $frm->select_tag( "cache_newflags", array( "No", "Yes" ), $PHORUM["cache_newflags"] ) );
$row=$frm->addrow( "Enable Caching Messages:", $frm->select_tag( "cache_messages", array( "No", "Yes" ), $PHORUM["cache_messages"] ) );
$row=$frm->addrow( "Enable Caching Banlists:", $frm->select_tag( "cache_banlists", array( "No", "Yes" ), $PHORUM["cache_banlists"] ) );
$row=$frm->addrow( "Enable Caching RSS-Feeds:", $frm->select_tag( "cache_rss", array( "No", "Yes" ), $PHORUM["cache_rss"] ) );

$frm->addbreak("Settings that are set from include/config/cache.php");

$row=$frm->addrow( "Cache Directory", $PHORUM['CACHECONFIG']['directory']);
$frm->addhelp($row, "Cache Directory",
    "Caching is used to take some load off the database and web server.
     The cache directory is used for caching preprocessed Phorum templates
     and for caching data in case \"file\" is set as the cache layer.<br/>
     <br/>
     For most installations, it will be fine to use the default temp directory
     for the server (/tmp on UNIX systems and C:\\Windows\\Temp for Windows
     systems).<br/>
     <br/>
     If your server has PHP Safe Mode enabled, you will need to create a
     directory under your Phorum directory and make it writable by the web
     server (you can use the directory \"./cache\" which was included in the
     Phorum distribution for this purpose)."
);
$frm->addrow("Cache-Layer", $PHORUM['CACHECONFIG']['type']);

// calling mods
$frm=phorum_api_hook("admin_cache", $frm);

$frm->show();

?>

