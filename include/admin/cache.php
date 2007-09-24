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

if ( !defined( "PHORUM_ADMIN" ) ) return;

$error = "";
$okmsg = "";

if ( count( $_POST ) ) {
    $new_settings = array();
    // set the defaults
    foreach( $_POST as $field => $value ) {
        switch ( $field ) {

            case "cache":

                if ( empty( $value ) ) {
                    $new_settings[$field] = "/tmp";
                } elseif ( !file_exists( $value ) ) {
                    $error = "This cache directory does not exist.  Please create it with the proper permissions.";
                } else {
                    $new_settings[$field] = $value;
                }

                break;

            case "cache_layer":

                if ( empty( $value ) ) {
                    $new_settings[$field] = "file";
                } elseif ( !file_exists( './include/cache/'.$value.'.php' ) ) {
                    $error = "This cache layer ($value) does not exist.";
                } else {
                    $new_settings[$field] = $value;
                }

                break;
            case "cache_rss":

            case "cache_users":

            case "cache_messages":
            	
			case "cache_banlists":            	

            case "cache_newflags":
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

        //print_var($new_settings);

        if ( phorum_db_update_settings( $new_settings ) ) {
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

include_once "./include/admin/PhorumInputForm.php";

$frm = &new PhorumInputForm ( "", "post" );
$frm->hidden( "module", "cache" );
$frm->addbreak( "Phorum Cache Settings" );
$row=$frm->addrow( "Cache Directory", $frm->text_box( "cache", $PHORUM["cache"], 30 ) );
$frm->addhelp($row, "Cache Directory", "Phorum caches its templates for faster use later. Additionally, some data can be stored in the cache, to take some load of the database and web server. This setting determines the directory where Phorum will build its cache. Most users will be fine using their server's temp directory. If your server uses PHP Safe Mode, you will need to create a directory under your Phorum directory and make it writable by the web server (you can use the directory ./cache which was included in the Phorum distribution for this purpose)." );

$frm->addbreak("Which data to cache");
$row=$frm->addrow( "Enable Caching Userdata:", $frm->select_tag( "cache_users", array( "No", "Yes" ), $PHORUM["cache_users"] ) );
$row=$frm->addrow( "Enable Caching Newflags:", $frm->select_tag( "cache_newflags", array( "No", "Yes" ), $PHORUM["cache_newflags"] ) );
$row=$frm->addrow( "Enable Caching Messages:", $frm->select_tag( "cache_messages", array( "No", "Yes" ), $PHORUM["cache_messages"] ) );
$row=$frm->addrow( "Enable Caching Banlists:", $frm->select_tag( "cache_banlists", array( "No", "Yes" ), $PHORUM["cache_banlists"] ) );
$row=$frm->addrow( "Enable Caching RSS-Feeds:", $frm->select_tag( "cache_rss", array( "No", "Yes" ), $PHORUM["cache_rss"] ) );

$frm->addbreak("Cache-Layer - make sure you have the prerequesites for the layer installed");

$layer_check = "";
if($PHORUM['cache_layer'] == 'memcached') {
    if(function_exists('memcache_connect')) {
        $layer_check = "( Memcached extension found )";
    } else {
        $layer_check = "<strong>( Memcached extension NOT found )</strong>";
    }
}
if($PHORUM['cache_layer'] == 'apc') {
    if(function_exists('apc_fetch')) {
        $layer_check = "( APC extension found )";
    } else {
        $layer_check = "<strong>( APC extension NOT found )</strong>";
    }
}

$row=$frm->addrow( "Select the cache layer to use:", $frm->select_tag( "cache_layer", array( "file" => 'file system based', "memcached" => 'memcached based', "apc" => 'APC based'), $PHORUM["cache_layer"] )." $layer_check" );

// calling mods
$frm=phorum_hook("admin_cache", $frm);

$frm->show();

?>

