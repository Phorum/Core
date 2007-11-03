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

// Check that this file is not loaded directly.
if ( basename( __FILE__ ) == basename( $_SERVER["PHP_SELF"] ) ) exit();

// the Phorum version
define( "PHORUM", "5.2-dev" );

// our database schema version in format of year-month-day-serial
define( "PHORUM_SCHEMA_VERSION", "2007031400" );

// our database patch level in format of year-month-day-serial
define( "PHORUM_SCHEMA_PATCHLEVEL", "2007102800" );

// The required version of the Phorum PHP extension. This version is updated
// if internal changes of Phorum require the extension library to be upgraded
// for compatibility. We follow PHP's schema of using the date at which an
// important internal change  was done as the extension's version number.
// This version number should match the one in the php_phorum.h header file
// for the module.
define( "PHORUM_EXTENSION_VERSION", "20070522" );

// all other constants in ./include/constants.php
include_once( "./include/constants.php" );

// setup the PHORUM var
global $PHORUM;
$PHORUM = array();

// API code
include_once("./include/api/base.php");
include_once("./include/api/user.php");

// the TMP member holds template {DEFINE ..} definitions and temporary
// arrays and such in template code
$PHORUM["TMP"] = array();

// the DATA member contains the data that templates can access
$PHORUM["DATA"] = array();
$PHORUM["DATA"]["GET_VARS"] = array();
$PHORUM["DATA"]["POST_VARS"] = "";

// get the forum id if set with a request parameter
if ( isset( $_REQUEST["forum_id"] ) && is_numeric( $_REQUEST["forum_id"] ) ) {
    $PHORUM["forum_id"] = $_REQUEST["forum_id"];
}

// strip the slashes off of POST data if magic_quotes is on
if ( get_magic_quotes_gpc() && count( $_REQUEST ) ) {
    foreach( $_POST as $key => $value ) {
        if ( !is_array( $value ) )
            $_POST[$key] = stripslashes( $value );
        else
            $_POST[$key] = phorum_recursive_stripslashes( $value );
    }
    foreach( $_GET as $key => $value ) {
        if ( !is_array( $value ) )
            $_GET[$key] = stripslashes( $value );
        else
            $_GET[$key] = phorum_recursive_stripslashes( $value );
    }
}

// look for and parse the QUERY_STRING
// this only applies to urls that we create using phorum_get_url()
// scripts using urls from forms (search) should use $_GET or $_POST
if (!defined("PHORUM_ADMIN") && (isset($_SERVER["QUERY_STRING"]) || isset($GLOBALS["PHORUM_CUSTOM_QUERY_STRING"])))
{
    $Q_STR = empty( $GLOBALS["PHORUM_CUSTOM_QUERY_STRING"] )
           ? $_SERVER["QUERY_STRING"]
           : $GLOBALS["PHORUM_CUSTOM_QUERY_STRING"];

    // ignore stuff past a #
    if ( strstr( $Q_STR, "#" ) ) list( $Q_STR, $other ) = explode( "#", $Q_STR, 2 );

    // explode it on comma
    $PHORUM["args"] = explode( ",", $Q_STR );

    // check for any assigned values
    if ( strstr( $Q_STR, "=" ) ) {
        foreach( $PHORUM["args"] as $key => $arg ) {

            // if an arg has an = create an element in args
            // with left part as key and right part as value
            if ( strstr( $arg, "=" ) ) {
                list( $var, $value ) = explode( "=", $arg, 2 );
                // get rid of the numbered arg, it is useless.
                unset( $PHORUM["args"][$key] );
                // add the named arg
                // TODO: Why is urldecode() used here? IMO this can be omitted.
                $PHORUM["args"][$var] = urldecode( $value );
            }
        }
    }

    // Handle path info based URLs for the file script.
    if (phorum_page == 'file' &&
        !empty($_SERVER['PATH_INFO']) &&
        preg_match('!^/(\d+)/(\d+)/!', $_SERVER['PATH_INFO'], $m))
    {
        $PHORUM['args']['file'] = $m[2];
        $PHORUM['forum_id'] = $m[1];
    }

    // set forum_id if not set already by a forum_id request parameter
    if ( empty( $PHORUM["forum_id"] ) && isset( $PHORUM["args"][0] ) ) {
        $PHORUM["forum_id"] = ( int )$PHORUM["args"][0];
    }
}

// set the forum_id to 0 if not set by now.
if ( empty( $PHORUM["forum_id"] ) ) $PHORUM["forum_id"] = 0;

// Get the database settings. It is possible to override the database
// settings by defining a global variable $PHORUM_ALT_DBCONFIG which
// overrides $PHORUM["DBCONFIG"] (from include/db/config.php). This is
// only allowed if "PHORUM_WRAPPER" is defined and if the alternative
// configuration wasn't passed as a request parameter (which could
// set $PHORUM_ALT_DBCONFIG if register_globals is enabled for PHP).
if (empty( $GLOBALS["PHORUM_ALT_DBCONFIG"] ) || $GLOBALS["PHORUM_ALT_DBCONFIG"]==$_REQUEST["PHORUM_ALT_DBCONFIG"] || !defined("PHORUM_WRAPPER")) {
    // Backup display_errors setting.
    $orig = ini_get("display_errors");
    @ini_set("display_errors", 0);

    // Load configuration.
    if (! include_once( "./include/db/config.php" )) {
        print '<html><head><title>Phorum error</title></head><body>';
        print '<h2>Phorum database configuration error</h2>';

        // No database configuration found.
        if (!file_exists("./include/db/config.php")) { ?>
            Phorum has been installed on this server, but the configuration<br/>
            for the database connection has not yet been made. Please read<br/>
            <a href="docs/install.txt">docs/install.txt</a> for installation instructions. <?php
        } else {
            $fp = fopen("./include/db/config.php", "r");
            // Unable to read the configuration file.
            if (!$fp) { ?>
                A database configuration file was found in ./include/db/config.php,<br/>
                but Phorum was unable to read it. Please check the file permissions<br/>
                for this file. <?php
            // Unknown error.
            } else {
                fclose($fp); ?>
                A database configuration file was found in ./include/dbconfig.php,<br/>
                but it could not be loaded. It possibly contains one or more errors.<br/>
                Please check your configuration file. <?php
            }
        }

        print '</body></html>';
        exit(1);
    }

    // Restore original display_errors setting.
    @ini_set("display_errors", $orig);
} else {
    $PHORUM["DBCONFIG"] = $GLOBALS["PHORUM_ALT_DBCONFIG"];
}

// Backward compatbility: the "mysqli" layer was merged with the "mysql"
// layer, but people might still be using "mysqli" as their configured
// database type.
if ($PHORUM["DBCONFIG"]["type"] == "mysqli" &&
    !file_exists("./include/db/mysqli.php")) {
    $PHORUM["DBCONFIG"]["type"] = "mysql";
}

// Load the database layer.
$PHORUM['DBCONFIG']['type'] = basename($PHORUM['DBCONFIG']['type']);
include_once( "./include/db/{$PHORUM['DBCONFIG']['type']}.php" );

if(!phorum_db_check_connection()){
    if(isset($PHORUM["DBCONFIG"]["down_page"])){
        phorum_redirect_by_url($PHORUM["DBCONFIG"]["down_page"]);
        exit();
    } else {
        echo "The database connection failed. Please check your database configuration in include/db/config.php. If the configuration is okay, check if the database server is running.";
        exit();
    }
}

// get the Phorum settings
phorum_db_load_settings();

// Try to load the Phorum PHP extension, if has been enabled in the admin.
// As a precaution, never load it from the admin code (so the extension
// support can be disabled at all time if something unexpected happens).
if (!defined('PHORUM_ADMIN') && !empty($PHORUM["php_phorum_extension"]))
{
    // Load the extension library.
    if (! extension_loaded('phorum')) {
        @dl('phorum.so');
    }

    // Check if the version of the PHP extension matches the Phorum installation.
    if (extension_loaded('phorum')) {
        $ext_ver = phorum_ext_version();
        if ($ext_ver != PHORUM_EXTENSION_VERSION) {
            // The version does not match. Disable the extension support.
            phorum_db_update_settings(array("php_phorum_extension" => 0));
            print "<html><head><title>Phorum Extension Error</title></head><body>";
            print "<h1>Phorum Extension Error</h1>" .
                  "The Phorum PHP extension was loaded, but its version<br/>" .
                  "does not match the Phorum version. Therefore, the<br/>" .
                  "extension has now be disabled. Reload this page to continue.";
            print "</body></html>";
            exit(0);
        }
    }
}

// Setup phorum_get_url(): this function is used for generating all Phorum
// related URL's. It is loaded conditionally, to make it possible to override
// it from the phorum PHP extension.
if (!function_exists('phorum_get_url')) {
    include_once("./include/phorum_get_url.php");
}

// Defaults for missing settings (these can be needed after upgrading, in
// case the admin did not yet save a newly added Phorum setting).
if (! isset($PHORUM["default_feed"])) $PHORUM["default_feed"] = "rss";

// If we have no private key for signing data, generate one now,
// but only if it's not a fresh install.
if ( isset($PHORUM['internal_version']) && $PHORUM['internal_version'] >= PHORUM_SCHEMA_VERSION && (!isset($PHORUM["private_key"]) || empty($PHORUM["private_key"]))) {
   $chars = "0123456789!@#$%&abcdefghijklmnopqr".
            "stuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
   $private_key = "";
   for ($i = 0; $i<40; $i++) {
       $private_key .= substr($chars, rand(0, strlen($chars)-1), 1);
   }
   $PHORUM["private_key"] = $private_key;
   phorum_db_update_settings(array("private_key" => $PHORUM["private_key"]));
}

// determine the caching layer to load
if(!isset($PHORUM['cache_layer']) || empty($PHORUM['cache_layer'])) {
    $PHORUM['cache_layer'] = 'file';
} else {
    // safeguard for wrongly selected cache-layers

    // falling back to file-layer if descriptive functions aren't existing

    if($PHORUM['cache_layer'] == 'memcached' && !function_exists('memcache_connect')) {
        $PHORUM['cache_layer'] = 'file';
    } elseif($PHORUM['cache_layer'] == 'apc' && !function_exists('apc_fetch')) {
        $PHORUM['cache_layer'] = 'file';
    }
}

// load the caching-layer - you can specify a different one in the settings
// one caching layer *needs* to be loaded
$PHORUM['cache_layer'] = basename($PHORUM['cache_layer']);
include_once( "./include/cache/$PHORUM[cache_layer].php" );

// a hook for rewriting vars at the beginning of common.php,
//right after loading the settings from the database
if (isset($PHORUM["hooks"]["common_pre"])) {
    phorum_hook( "common_pre", "" );
}

// stick some stuff from the settings into the DATA member
$PHORUM["DATA"]["TITLE"] = ( isset( $PHORUM["title"] ) ) ? $PHORUM["title"] : "";
$PHORUM["DATA"]["DESCRIPTION"] = ( isset( $PHORUM["description"] ) ) ? $PHORUM["description"] : "";
$PHORUM["DATA"]["HTML_TITLE"] = ( !empty( $PHORUM["html_title"] ) ) ? $PHORUM["html_title"] : $PHORUM["DATA"]["TITLE"];
$PHORUM["DATA"]["HEAD_TAGS"] = ( isset( $PHORUM["head_tags"] ) ) ? $PHORUM["head_tags"] : "";
$PHORUM["DATA"]["FORUM_ID"] = $PHORUM["forum_id"];

////////////////////////////////////////////////////////////
// only do this stuff if we are not in the admin

if ( !defined( "PHORUM_ADMIN" ) ) {

    // if the Phorum is disabled, display a message.
    if(isset($PHORUM["status"]) && $PHORUM["status"]==PHORUM_MASTER_STATUS_DISABLED){
        if(!empty($PHORUM["disabled_url"])){
            header("Location: ".$PHORUM["disabled_url"]);
            exit();
        } else {
            echo "This Phorum is currently disabled.  Please contact the web site owner at ".$PHORUM['system_email_from_address']." for more information.\n";
            exit();
        }
    }

    // checking for upgrade or new install
    if ( !isset( $PHORUM['internal_version'] ) ) {
        echo "<html><head><title>Phorum error</title></head><body>No Phorum settings were found. Either this is a brand new installation of Phorum or there is a problem with your database server. If this is a new install, please <a href=\"admin.php\">go to the admin page</a> to complete the installation. If not, check your database server.</body></html>";
        exit();
    } elseif ( $PHORUM['internal_version'] < PHORUM_SCHEMA_VERSION ||
               !isset($PHORUM['internal_patchlevel']) ||
               $PHORUM['internal_patchlevel'] < PHORUM_SCHEMA_PATCHLEVEL ) {
        if(isset($PHORUM["DBCONFIG"]["upgrade_page"])){
            phorum_redirect_by_url($PHORUM["DBCONFIG"]["upgrade_page"]);
            exit();
        }
        echo "<html><head><title>Upgrade notification</title></head><body>It looks like you have installed a new version of Phorum.<br/>Please visit the admin page to complete the upgrade!</body></html>";
        exit();
    }

    // load the forum's settings
    if(!empty($PHORUM["forum_id"])){

        $forum_settings = phorum_db_get_forums( $PHORUM["forum_id"] );

        if ( !isset( $forum_settings[$PHORUM["forum_id"]] ) ) {
            phorum_hook( "common_no_forum", "" );
            phorum_redirect_by_url( phorum_get_url( PHORUM_INDEX_URL ) );
            exit();
        }

        $PHORUM = array_merge( $PHORUM, $forum_settings[$PHORUM["forum_id"]] );

    } elseif(isset($PHORUM["forum_id"]) && $PHORUM["forum_id"]==0){

        $PHORUM = array_merge( $PHORUM, $PHORUM["default_forum_options"] );

        // some hard settings are needed if we are looking at forum_id 0
        $PHORUM['vroot']=0;
        $PHORUM['parent_id']=0;
        $PHORUM['active']=1;
        $PHORUM['folder_flag']=1;
        $PHORUM['cache_version']=0;

    }


    // handling vroots
    if(!empty($PHORUM['vroot'])) {
        $vroot_folders = phorum_db_get_forums($PHORUM['vroot']);

        $PHORUM["title"] = $vroot_folders[$PHORUM['vroot']]['name'];
        $PHORUM["DATA"]["TITLE"] = $PHORUM["title"];
        $PHORUM["DATA"]["HTML_TITLE"] = $PHORUM["title"];

        if($PHORUM['vroot'] == $PHORUM['forum_id']) {
            // unset the forum-name if we are in the vroot-index
            // otherwise the NAME and TITLE would be the same and still shown twice
            unset($PHORUM['name']);
        }
    }

    // stick some stuff from the settings into the DATA member
    $PHORUM["DATA"]["NAME"] = ( isset( $PHORUM["name"] ) ) ? $PHORUM["name"] : "";
    $PHORUM["DATA"]["DESCRIPTION"] = ( isset( $PHORUM["description"]  ) ) ? strip_tags( preg_replace("!\s+!", " ", $PHORUM["description"]) ) : "";
    $PHORUM["DATA"]["ENABLE_PM"] = ( isset( $PHORUM["enable_pm"] ) ) ? $PHORUM["enable_pm"] : "";
    if ( !empty( $PHORUM["DATA"]["HTML_TITLE"] ) && !empty( $PHORUM["DATA"]["NAME"] ) ) {
        $PHORUM["DATA"]["HTML_TITLE"] .= PHORUM_SEPARATOR;
    }
    $PHORUM["DATA"]["HTML_TITLE"] .= $PHORUM["DATA"]["NAME"];

    // Try to restore a user session.
    if (phorum_api_user_session_restore(PHORUM_FORUM_SESSION))
    {
        // if the user has overridden thread settings, change it here.
        if ( !isset( $PHORUM['display_fixed'] ) || !$PHORUM['display_fixed'] ) {
            if ( $PHORUM["user"]["threaded_list"] == PHORUM_THREADED_ON ) {
                $PHORUM["threaded_list"] = true;
            } elseif ( $PHORUM["user"]["threaded_list"] == PHORUM_THREADED_OFF ) {
                $PHORUM["threaded_list"] = false;
            }
            if ( $PHORUM["user"]["threaded_read"] == PHORUM_THREADED_ON ) {
                $PHORUM["threaded_read"] = 1;
            } elseif ( $PHORUM["user"]["threaded_read"] == PHORUM_THREADED_OFF ) {
                $PHORUM["threaded_read"] = 0;
            } elseif ( $PHORUM["user"]["threaded_read"] == PHORUM_THREADED_HYBRID ) {
                $PHORUM["threaded_read"] = 2;
            }
        }

        // check if the user has new private messages
        if (!empty($PHORUM["enable_new_pm_count"]) &&
            !empty($PHORUM["enable_pm"])) {
            $PHORUM['user']['new_private_messages'] =
                phorum_db_pm_checknew($PHORUM['user']['user_id']);
        }
    }

    // a hook for rewriting vars in common.php after loading the user
    if (isset($PHORUM["hooks"]["common_post_user"]))
         phorum_hook( "common_post_user", "" );

    // set up the template

    // check for a template being passed on the url
    // only use valid template names
    if ( !empty( $PHORUM["args"]["template"] ) ) {
        $template = basename( $PHORUM["args"]["template"] );
        if ($template != '..') {
            $PHORUM["template"] = $template;
        }
    }

    // get the language file
    if ( ( !isset( $PHORUM['display_fixed'] ) || !$PHORUM['display_fixed'] ) &&
            isset( $PHORUM['user']['user_language'] ) && !empty($PHORUM['user']['user_language']) )
        $PHORUM['language'] = $PHORUM['user']['user_language'];

    if ( !isset( $PHORUM["language"] ) || empty( $PHORUM["language"] ) || !file_exists( "./include/lang/$PHORUM[language].php" ) )
        $PHORUM["language"] = $PHORUM["default_forum_options"]["language"];

    // set the user-selected template
    if ( ( !isset( $PHORUM['display_fixed'] ) || !$PHORUM['display_fixed'] ) &&
            isset( $PHORUM['user']['user_template'] ) && !empty($PHORUM['user']['user_template']) &&
            (!isset( $PHORUM["user_template"] )  || !empty($PHORUM['user_template']))
         ) {

        $PHORUM['template'] = $PHORUM['user']['user_template'];
    }

    // user output buffering so we don't get header errors
    // not loaded if we are running an external or scheduled script
    if (! defined('PHORUM_SCRIPT')) {
        ob_start();
        include_once( phorum_get_template( "settings" ) );
        $PHORUM["DATA"]["TEMPLATE"] = $PHORUM['template'];
        $PHORUM["DATA"]["URL"]["TEMPLATE"] = "{$PHORUM["http_path"]}/templates/{$PHORUM["template"]}";
        $PHORUM["DATA"]["URL"]["CSS"] = phorum_get_url(PHORUM_CSS_URL, "css");
        $PHORUM["DATA"]["URL"]["CSS_PRINT"] = phorum_get_url(PHORUM_CSS_URL, "css_print");
        ob_end_clean();
    }

    $PHORUM['language'] = basename($PHORUM['language']);
    if ( file_exists( "./include/lang/$PHORUM[language].php" ) ) {
        include_once( "./include/lang/$PHORUM[language].php" );
    }
    // load languages for localized modules
    if ( isset( $PHORUM["hooks"]["lang"] ) && is_array($PHORUM["hooks"]["lang"]) ) {
        foreach( $PHORUM["hooks"]["lang"]["mods"] as $mod )
        {
            // load mods for this hook
            $mod = basename($mod);
            if ( file_exists( "./mods/$mod/lang/$PHORUM[language].php" ) ) {
                include_once "./mods/$mod/lang/$PHORUM[language].php";
            }
            elseif ( file_exists( "./mods/$mod/lang/english.php" ) ) {
                include_once "./mods/$mod/lang/english.php";
            }
        }
    }

    // load the locale from the language file into the template vars
    $PHORUM["DATA"]["LOCALE"] = ( isset( $PHORUM["locale"] ) ) ? $PHORUM["locale"] : "";

    // If there is no HCHARSET (used by the htmlspecialchars() calls), then
    // use the CHARSET for it instead.
    if (empty($PHORUM["DATA"]["HCHARSET"])) {
        $PHORUM["DATA"]["HCHARSET"] = $PHORUM["DATA"]["CHARSET"];
    }

    // just setting this up for upgraded installs where this might not be set up
    if(!isset($PHORUM['cache_newflags'])) {
        $PHORUM['cache_newflags'] = 0;
    }

    if(!isset($PHORUM['cache_messages'])) {
        $PHORUM['cache_messages'] = 0;
    }

    // HTML titles can't contain HTML code, so we strip HTML tags
    // and HTML escape the title.
    $PHORUM["DATA"]["HTML_TITLE"] = htmlspecialchars(strip_tags($PHORUM["DATA"]["HTML_TITLE"]), ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

    // if the Phorum is disabled, display a message.
    if( !$PHORUM["user"]["admin"] ) {
        if(isset($PHORUM["status"]) && $PHORUM["status"]==PHORUM_MASTER_STATUS_ADMIN_ONLY && phorum_page != 'css' && phorum_page != 'javascript'){
            // set all our URL's
            phorum_build_common_urls();

            $PHORUM["DATA"]["OKMSG"]=$PHORUM["DATA"]["LANG"]["AdminOnlyMessage"];
        $PHORUM["user"] = array("user_id" => 0, "username" => "", "admin" => false, "newinfo" => array());
        $PHORUM["DATA"]["LOGGEDIN"] = false;

            if (phorum_page != 'login') {

                phorum_output("message");
                exit();
            }

        } elseif($PHORUM["status"]==PHORUM_MASTER_STATUS_READ_ONLY){
            $PHORUM["DATA"]["GLOBAL_ERROR"]=$PHORUM["DATA"]["LANG"]["ReadOnlyMessage"];
            $PHORUM["user"] = array("user_id" => 0, "username" => "", "admin" => false, "newinfo" => array());
            $PHORUM["DATA"]["LOGGEDIN"] = false;
        }
    }

    // If moderator notifications are on and the person is a mod,
    // lets find out if anything is new.

    $PHORUM["user"]["NOTICE"]["MESSAGES"] = false;
    $PHORUM["user"]["NOTICE"]["USERS"] = false;
    $PHORUM["user"]["NOTICE"]["GROUPS"] = false;

    if ( $PHORUM["DATA"]["LOGGEDIN"] ) {

        // By default, only bug the user on the list, index and cc pages.
        // The template can override this behaviour by setting a comma
        // separated list of phorum_page names in a template define statement
        // like this: {DEFINE show_notify_for_pages "page 1,page 2,..,page n"}
        if (isset($PHORUM["TMP"]["show_notify_for_pages"])) {
            $show_notify_for_pages = explode(",", $PHORUM["TMP"]["show_notify_for_pages"]);
        } else {
            $show_notify_for_pages = array('index','list','cc');
        }

        if ( in_array(phorum_page, $show_notify_for_pages) ) {

            if ( $PHORUM["enable_moderator_notifications"] ) {
                $forummodlist = phorum_api_user_check_access(
                    PHORUM_USER_ALLOW_MODERATE_MESSAGES, PHORUM_ACCESS_LIST
                );
                if ( count( $forummodlist ) > 0 ) {
                    $PHORUM["user"]["NOTICE"]["MESSAGES"] = ( phorum_db_get_unapproved_list( $forummodlist, true, 0, true) > 0 );
                    $PHORUM["DATA"]["URL"]["NOTICE"]["MESSAGES"] = phorum_get_url( PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED );
                }
                if ( phorum_api_user_check_access( PHORUM_USER_ALLOW_MODERATE_USERS ) ) {
                    $PHORUM["user"]["NOTICE"]["USERS"] = ( count( phorum_db_user_get_unapproved() ) > 0 );
                    $PHORUM["DATA"]["URL"]["NOTICE"]["USERS"] = phorum_get_url( PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS );
                }
                $groups = phorum_api_user_check_group_access(PHORUM_USER_GROUP_MODERATOR, PHORUM_ACCESS_LIST);
                if (count($groups) > 0) {
                    $PHORUM["user"]["NOTICE"]["GROUPS"] = count( phorum_db_get_group_members( array_keys( $groups ), PHORUM_USER_GROUP_UNAPPROVED ) );
                    $PHORUM["DATA"]["URL"]["NOTICE"]["GROUPS"] = phorum_get_url( PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MODERATION );
                }
            }

            $PHORUM["user"]["NOTICE"]["SHOW"] = $PHORUM["user"]["NOTICE"]["MESSAGES"] || $PHORUM["user"]["NOTICE"]["USERS"] || $PHORUM["user"]["NOTICE"]["GROUPS"];
        }
    }

    // a hook for rewriting vars at the end of common.php
    if (isset($PHORUM["hooks"]["common"]))
        phorum_hook( "common", "" );

    $PHORUM['DATA']['USER'] = $PHORUM['user'];
    $PHORUM['DATA']['USER']["username"] = htmlspecialchars($PHORUM['DATA']['USER']["username"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    if (isset($PHORUM['DATA']['USER']['real_name']))
        $PHORUM['DATA']['USER']["real_name"] = htmlspecialchars($PHORUM['DATA']['USER']["real_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    if (isset($PHORUM['DATA']['USER']['display_name']))
        $PHORUM['DATA']['USER']["display_name"] = htmlspecialchars($PHORUM['DATA']['USER']["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    if(isset($PHORUM['DATA']['USER']["email"])) $PHORUM['DATA']['USER']["email"] = htmlspecialchars($PHORUM['DATA']['USER']["email"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    if(isset($PHORUM['DATA']['USER']["signature"])) $PHORUM['DATA']['USER']["signature"] = htmlspecialchars($PHORUM['DATA']['USER']["signature"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

    $PHORUM['DATA']['PHORUM_PAGE'] = phorum_page;
    $PHORUM['DATA']['USERTRACK'] = $PHORUM['track_user_activity'];
    $PHORUM['DATA']['VROOT'] = $PHORUM['vroot'];
    // used in all forms as it seems
    $PHORUM['DATA']['POST_VARS'].="<input type=\"hidden\" name=\"forum_id\" value=\"{$PHORUM["forum_id"]}\" />\n";

    if(isset($PHORUM['use_rss']) && $PHORUM['use_rss']){
        if($PHORUM["default_feed"]=="rss"){
            $PHORUM["DATA"]["FEED"] = $PHORUM["DATA"]["LANG"]["RSS"];
            $PHORUM["DATA"]["FEED_CONTENT_TYPE"] = "application/rss+xml";
        } else {
            $PHORUM["DATA"]["FEED"] = $PHORUM["DATA"]["LANG"]["ATOM"];
            $PHORUM["DATA"]["FEED_CONTENT_TYPE"] = "application/atom+xml";
        }
    }


}

////////////////////////////////////////////////////////////
// only do this stuff if we are in the admin

else {

    // The admin interface is not localized, but we might need language
    // strings at some point after all, for example if we reset the
    // author name in messages for deleted users to "anonymous".
    $PHORUM["language"] = basename($PHORUM["default_forum_options"]["language"]);
    if (file_exists("./include/lang/$PHORUM[language].php")) {
        include_once("./include/lang/$PHORUM[language].php");
    }
}

//////////////////////////////////////////////////////////
// functions


/**
 * Shutdown function
 */
function phorum_shutdown() {
    if (isset($PHORUM["hooks"]["shutdown"]))
        phorum_hook( "shutdown" );
}
register_shutdown_function("phorum_shutdown");

/**
 * A common function to check that a user is logged in
 */
function phorum_require_login()
{
    $PHORUM = $GLOBALS['PHORUM'];
    if ( !$PHORUM["user"]["user_id"] ) {
        $url = phorum_get_url( PHORUM_LOGIN_URL, "redir=" . urlencode( $PHORUM["http_path"] . "/" . basename( $_SERVER["PHP_SELF"] ) . "?" . $_SERVER["QUERY_STRING"] ) );
        phorum_redirect_by_url( $url );
        exit();
    }
}

/**
 * A common function for checking the read-permissions for a forum-page
 * returns false if access is not allowed and an error page-was output
 */
function phorum_check_read_common()
{
    $PHORUM = $GLOBALS['PHORUM'];

    $retval = true;

    if ( $PHORUM["forum_id"] > 0 && !$PHORUM["folder_flag"] && !phorum_api_user_check_access( PHORUM_USER_ALLOW_READ ) ) {
        if ( $PHORUM["DATA"]["LOGGEDIN"] ) {
            // if they are logged in and not allowed, they don't have rights
            $GLOBALS['PHORUM']["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["NoRead"];
        } else {
            // check if they could read if logged in.
            // if so, let them know to log in.
            if ( ( empty( $PHORUM["DATA"]["POST"]["parentid"] ) && $PHORUM["reg_perms"] &PHORUM_USER_ALLOW_READ ) ) {
                $GLOBALS['PHORUM']["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["PleaseLoginRead"];
            } else {
                $GLOBALS['PHORUM']["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["NoRead"];
            }
        }

        phorum_build_common_urls();

        phorum_output("message");

        $retval = false;
    }

    return $retval;
}

/**
 * Find out what input and output files to use for a template file.
 *
 * @param string $page
 *     The template base name (e.g. "header", "css", etc.).
 *
 * @param string $module
 *     The module to load the template from or NULL if the
 *     main Phorum templates directory must be used.
 *
 * @return array
 *     This function returns an array, containing two elements:
 *     - The PHP file to include for the template base name.
 *     - The file to use as template input. In case there's no
 *       .tpl file to pre-process, the value will be NULL. In that
 *       case, the $phpfile return value can be included directly.
 */
function phorum_get_template_file( $page )
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Check for a module reference in the page name.
    $fullpage = $page;
    $module = null;
    if (($pos = strpos($fullpage, "::", 1)) !== false) {
        $module = substr($fullpage, 0, $pos);
        $page = substr($fullpage, $pos+2);
    }

    $page = basename($page);

    if ($module === NULL) {
        $prefix = "./templates";
        // The postfix is used for checking if the template directory
        // contains at least the mandatory info.php file. Otherwise, it
        // could be an incomplete or empty template.
        $postfix = "/info.php";
    } else {
        $prefix = "./mods/" . basename($module) . "/templates";
        $postfix = "";
    }

    // If no user template is set or if the template file cannot be found,
    // fallback to the configured default template. If that one can also
    // not be found, then fallback to the default template.
    if (empty($PHORUM["template"]) || !file_exists("$prefix/{$PHORUM['template']}$postfix")) {
        $PHORUM["template"] = $PHORUM["default_forum_options"]["template"];
        if ($PHORUM["template"] != PHORUM_DEFAULT_TEMPLATE && !file_exists("$prefix/{$PHORUM['template']}$postfix")) {
            $PHORUM["template"] = PHORUM_DEFAULT_TEMPLATE;
        }

        // If we're not handling a module template, then we can change the
        // global template to remember the fallback template and to make
        // sure that {URL->TEMPLATE} and {TEMPLATE} aren't pointing to a
        // non-existant template in the end..
        if ($module === NULL) {
            $GLOBALS["PHORUM"]["template"] = $PHORUM["template"];
        }
    }

    $tplbase = "$prefix/$PHORUM[template]/$page";

    // check for straight PHP file
    if ( file_exists( "$tplbase.php" ) ) {
        return array("$tplbase.php", NULL);
    // not there, look for a template
    } else {
        $tplfile = "$tplbase.tpl";
        $safetemplate = str_replace(array("-",":"), array("_","_"), $PHORUM["template"]);
        if ($module !== NULL) $page = "$module::$page";
        $safepage = str_replace(array("-",":"), array("_","_"), $page);
        $phpfile = "{$PHORUM["cache"]}/tpl-$safetemplate-$safepage-" .
               md5(dirname(__FILE__) . $prefix) . ".php";

        return array($phpfile, $tplfile);
    }
}

/**
 * Wrapper function to handle most common output scenarios.
 *
 * @param mixed $template
 *     If string, that template is included.
 *     If array, all the templates are included in the order of the array.
 */
function phorum_output($templates) {

    if(!is_array($templates)){
        $templates = array($templates);
    }

    if (isset($GLOBALS["PHORUM"]["hooks"]["start_output"]))
        phorum_hook("start_output");

    // Copy only what we need into the current scope. We do this at
    // this point and not earlier, so the start_output hook can be
    // used for changing values in the $PHORUM data.
    $PHORUM = array(
        "DATA"   => $GLOBALS["PHORUM"]["DATA"],
        "locale" => $GLOBALS["PHORUM"]["locale"],
        "hooks"  => $GLOBALS["PHORUM"]["hooks"]
    );

    include phorum_get_template("header");

    if (isset($PHORUM["hooks"]["after_header"]))
        phorum_hook("after_header");

    foreach($templates as $template){
        include phorum_get_template($template);
    }

    if (isset($PHORUM["hooks"]["before_footer"]))
        phorum_hook("before_footer");

    include phorum_get_template("footer");

    if (isset($PHORUM["hooks"]["end_output"]))
        phorum_hook("end_output");
}

/**
 * Returns the PHP file to include for a template file. This function will
 * automatically compile .tpl files if no compiled template is available.
 *
 * If the format for the template file is <module>::<template>, then
 * the template is loaded from the module's directory. The directory
 * structure for storing module templates is the same as for the
 * main templates directory, only it is stored within a module's
 * directory:
 *
 * <phorum_dir>/mods/templates/<template name>/<page>.tpl
 *
 * @param $page - The template base name (e.g. "header", "css", etc.).
 * @return $phpfile - The PHP file to include for showing the template.
 */
function phorum_get_template( $page )
{
    // This might for example happen if a template contains code like
    // {INCLUDE template} instead of {INCLUDE "template"}.
    if ($page === NULL || $page == "") {
        print "<h1>Phorum Template Error</h1>";
        print "phorum_get_template() was called with an empty page name.<br/>";
        print "This might indicate a template problem.<br/>";
        if (function_exists('debug_print_backtrace')) {
            print "Here's a backtrace that might help finding the error:";
            print "<pre>";
            debug_print_backtrace();
            print "</pre>";
        }
        exit(1);
    }

    list ($phpfile, $tplfile) = phorum_get_template_file($page);

    // No template to pre-process.
    if ($tplfile == NULL) return $phpfile;

    // Pre-process template if the output file isn't available.
    if (! file_exists($phpfile)) {
        include_once "./include/templates.php";
        phorum_import_template($page, $tplfile, $phpfile);
    }

    return $phpfile;
}

// creates URLs used on most pages
function phorum_build_common_urls()
{
    $PHORUM=$GLOBALS['PHORUM'];

    $GLOBALS["PHORUM"]["DATA"]["URL"]["BASE"] = phorum_get_url( PHORUM_BASE_URL );

    $GLOBALS["PHORUM"]["DATA"]["URL"]["LIST"] = phorum_get_url( PHORUM_LIST_URL );

    // those links are only needed in forums, not in folders
    if(isset($PHORUM['folder_flag']) && !$PHORUM['folder_flag']) {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["POST"] = phorum_get_url( PHORUM_POSTING_URL );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["SUBSCRIBE"] = phorum_get_url( PHORUM_SUBSCRIBE_URL );
    }

    // those are general urls, needed nearly everywhere
    $GLOBALS["PHORUM"]["DATA"]["URL"]["SEARCH"] = phorum_get_url( PHORUM_SEARCH_URL );

    $index_id=-1;
    // in a folder

    if( $PHORUM['folder_flag'] && phorum_page != 'index'
    && ($PHORUM['forum_id'] == 0 || $PHORUM['vroot'] == $PHORUM['forum_id'])) {
        // folder where we usually don't show the index-link but on
        // additional pages like search and login its shown
        $index_id=$PHORUM['forum_id'];

    } elseif( ( $PHORUM['folder_flag'] &&
    ($PHORUM['forum_id'] != 0 && $PHORUM['vroot'] != $PHORUM['forum_id'])) ||
    (!$PHORUM['folder_flag'] && $PHORUM['active'])) {
        // either a folder where the link should be shown (not vroot or root)
        // or an active forum where the link should be shown

        if(isset($PHORUM["use_new_folder_style"]) && $PHORUM["use_new_folder_style"] ) {
            // go to root or vroot
            $index_id=$PHORUM["vroot"]; // vroot is either 0 (root) or another id

        } else {
            // go to parent
            $index_id=$PHORUM["parent_id"]; // parent_id is always set now

        }

    }
    if($index_id > -1) {
        // check if its the full root, avoid adding an id in this case (SE-optimized ;))
        if (!empty($index_id))
            $GLOBALS["PHORUM"]["DATA"]["URL"]["INDEX"] = phorum_get_url( PHORUM_INDEX_URL, $index_id );
        else
            $GLOBALS["PHORUM"]["DATA"]["URL"]["INDEX"] = phorum_get_url( PHORUM_INDEX_URL );
    }

    // these urls depend on the login-status of a user
    if ( $GLOBALS["PHORUM"]["DATA"]["LOGGEDIN"] ) {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["LOGINOUT"] = phorum_get_url( PHORUM_LOGIN_URL, "logout=1" );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["REGISTERPROFILE"] = phorum_get_url( PHORUM_CONTROLCENTER_URL );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["PM"] = phorum_get_url( PHORUM_PM_URL );
    } else {
        $GLOBALS["PHORUM"]["DATA"]["URL"]["LOGINOUT"] = phorum_get_url( PHORUM_LOGIN_URL );
        $GLOBALS["PHORUM"]["DATA"]["URL"]["REGISTERPROFILE"] = phorum_get_url( PHORUM_REGISTER_URL );
    }
}

// calls phorum mod functions
function phorum_hook( $hook )
{
    $PHORUM = $GLOBALS["PHORUM"];

    // get arguments passed to the function
    $args = func_get_args();

    // shift off hook name
    array_shift($args);

    if ( isset( $PHORUM["hooks"][$hook] ) && is_array($PHORUM["hooks"][$hook])) {

        foreach( $PHORUM["hooks"][$hook]["mods"] as $mod ) {
            // load mods for this hook
            $mod = basename($mod);
            if ( file_exists( "./mods/$mod/$mod.php" ) ) {
                include_once "./mods/$mod/$mod.php";
            } elseif ( file_exists( "./mods/$mod.php" ) ) {
                include_once "./mods/$mod.php";
            }
        }

        foreach( $PHORUM["hooks"][$hook]["funcs"] as $func ) {
            // call functions for this hook
            if ( function_exists( $func ) ) {
                if(count($args)){
                    $args[0] = call_user_func_array( $func, $args );
                } else {
                    call_user_func( $func );
                }
            }
        }
    }

    if(isset($args[0])){
        return $args[0];
    }
}

// HTML encodes a string
function phorum_html_encode( $string )
{
    $ret_string = "";
    $len = strlen( $string );
    for( $x = 0;$x < $len;$x++ ) {
        $ord = ord( $string[$x] );
        $ret_string .= "&#$ord;";
    }
    return $ret_string;
}

// removes slashes from all array-entries
function phorum_recursive_stripslashes( $array )
{
    if ( !is_array( $array ) ) {
        return $array;
    } else {
        foreach( $array as $key => $value ) {
            if ( !is_array( $value ) )
                $array[$key] = stripslashes( $value );
            else
                $array[$key] = phorum_recursive_stripslashes( $value );
        }
    }
    return $array;
}

// returns the available templates as an array
function phorum_get_template_info()
{
    $tpls = array();

    $d = dir( "./templates" );
    while ( false !== ( $entry = $d->read() ) ) {
        if ( $entry != "." && $entry != ".." && file_exists( "./templates/$entry/info.php" ) ) {
            include "./templates/$entry/info.php";
            if ( !isset( $template_hide ) || empty( $template_hide ) || defined( "PHORUM_ADMIN" ) ) {
                $tpls[$entry] = "$name $version";
            } else {
                unset( $template_hide );
            }
        }
    }

    return $tpls;
}

// returns the available languages as an array
function phorum_get_language_info()
{
    // to make some language-files happy which are using $PHORUM-variables
    $PHORUM = $GLOBALS['PHORUM'];

    $langs = array();

    $d = dir( "./include/lang" );
    while ( false !== ( $entry = $d->read() ) ) {
        if ( substr( $entry, -4 ) == ".php" && is_file( "./include/lang/$entry" ) ) {
            @include "./include/lang/$entry";
            if ( !isset( $language_hide ) || empty( $language_hide ) || defined( "PHORUM_ADMIN" ) ) {
                $langs[str_replace( ".php", "", $entry )] = $language;
            } else {
                unset( $language_hide );
            }
        }
    }

    asort($langs, SORT_STRING);

    return $langs;
}

function phorum_redirect_by_url( $redir_url )
{
    // Some browsers strip the anchor from the URL in case we redirect
    // from a POSTed page :-/. So here we wrap the redirect,
    // to work around that problem.
    if (count($_POST) && strstr($redir_url, "#")) {
        $redir_url = phorum_get_url(
            PHORUM_REDIRECT_URL,
            'phorum_redirect_to=' . urlencode($redir_url)
        );
    }

    // check for response splitting and valid http(s) URLs
    if(preg_match("/\s/", $redir_url) || !preg_match("!^https?://!i", $redir_url)){
        $redir_url = phorum_get_url(PHORUM_INDEX_URL);
    }

    if ( stristr( $_SERVER['SERVER_SOFTWARE'], "Microsoft-IIS" ) ) {
        // the ugly IIS-hack to avoid crashing IIS
        print "<html><head>\n<title>Redirecting ...</title>\n";
        print "<meta http-equiv=\"refresh\" content=\"0; URL=$redir_url\">";
        print "</head>\n";
        print "<body><a href=\"$redir_url\">Redirecting ...</a></body>\n";
        print "</html>";
    } else {
        // our standard-way
        header( "Location: $redir_url" );
    }
    exit(0);
}

// might remove these, might not.  Need it for debugging.
function print_var( $var, $admin_only = FALSE )
{
    if ($admin_only && ! $GLOBALS["PHORUM"]["user"]["admin"]) return;

    if(PHP_SAPI!="cli"){
        echo "<pre>";
    }
    echo "\n";
    echo "type:  ".gettype($var)."\n";
    echo "value: ";
    $val = print_r($var, true);
    echo trim(str_replace("\n", "\n       ", $val));
    if(PHP_SAPI!="cli"){
        echo "\n</pre>";
    }
    echo "\n";

}

/**
 * Generates an MD5 signature for a piece of data using Phorum's secret
 * private key. This can be used to sign data which travels an unsafe path
 * (for example data that is sent to a user's browser and then back to
 * Phorum) and for which tampering should be prevented.
 *
 * @param $data The data to sign.
 * @return $signature The signature for the data.
 */
function phorum_generate_data_signature($data)
{
   $signature = md5($data . $GLOBALS["PHORUM"]["private_key"]);
   return $signature;
}

/**
 * Checks whether the signature for a piece of data is valid.
 *
 * @param $data The signed data.
 * @param $signature The signature for the data.
 * @return True in case the signature is okay, false otherwise.
 */
function phorum_check_data_signature($data, $signature)
{
    return md5($data . $GLOBALS["PHORUM"]["private_key"]) == $signature;
}

/**
 * Generate a debug back trace.
 *
 * @param $skip       - The amount of back trace levels to skip. The call
 *                      to this function is skipped by default, so you don't
 *                      have to count that in.
 * @param $hidepath   - NULL to not hide paths or a string to replace the
 *                      Phorum path with.
 *
 * @return $backtrace - The back trace in text format or NULL if no back trace
 *                      was generated.
 */
function phorum_generate_backtrace($skip = 0, $hidepath = "{path to Phorum}")
{
    // Allthough Phorum 4.3.0 is the required PHP version
    // for Phorum at the time of writing, people might still be running
    // Phorum on older PHP versions. For those people, we'll skip
    // creation of a back trace.

    $backtrace = NULL;

    if (function_exists("debug_backtrace"))
    {
        $bt = debug_backtrace();
        $mypath = dirname(__FILE__);
        $backtrace = '';

        foreach ($bt as $id => $step)
        {
            // Don't include the call to this function.
            if ($id == 0) continue;

            // Skip the required number of steps.
            if ($id <= $skip) continue;

            if ($hidepath !== NULL && isset($step["file"])) {
                $file = str_replace($mypath, $hidepath, $step["file"]);
            }
            $backtrace .= "Function " . $step["function"] . " called" .
                          (!empty($step["line"])
                           ? " at\n" .  $file . ":" . $step["line"]
                           : "") . "\n----\n";
        }
    }

    return $backtrace;
}

function phorum_ob_clean()
{
    // Clear out all output that PHP buffered up to now.
    for(;;) {
        $status = ob_get_status();
        if (!$status ||
            $status['name'] == 'ob_gzhandler' ||
            !$status['del']) break;
        ob_end_clean();
    }
}

/**
 * Database error handling function.
 *
 * @param $error - The error message.
 */
function phorum_database_error($error)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Flush output that we buffered so far (for displaying a
    // clean page in the admin interface).
    phorum_ob_clean();

    // Give modules a chance to handle or process the database error.
    phorum_hook("database_error", $error);

    // Find out what type of error handling is required.
    $logopt = isset($PHORUM["error_logging"])
            ? $PHORUM["error_logging"]
            : 'screen';

    // Create a backtrace report, so it's easier to find out where a problem
    // is coming from.
    $backtrace = phorum_generate_backtrace(0);

    // Start the error page.
    ?>
    <html>
    <head><title>Phorum database error</title></head>
    <body>
    <h1>Phorum Database Error</h1>

    Sorry, a Phorum database error occurred.<br/>
    <?php

    // In admin scripts, we will always include the
    // error message inside a comment in the page.
    if (defined("PHORUM_ADMIN")) {
        print "<!-- " .  htmlspecialchars($error, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) .  " -->";
    }

    switch ($logopt)
    {
        // Log the database error to a logfile.
        case "file":

            $cache_dir  = $PHORUM["cache"];

            $fp = fopen($cache_dir."/phorum-sql-errors.log", "a");
            fputs($fp,
                "Time: " . time() . "\n" .
                "Error: $error\n" .
                ($backtrace !== NULL
                 ? "Back trace:\n$backtrace\n\n"
                 : "")
            );
            fclose($fp);

            print "The error message has been written<br/>" .
                  "to the phorum-sql-errors.log error log.<br/>" .
                  "Please try again later!";
            break;

        // Display the database error on screen.
        case "screen":

            $htmlbacktrace = $backtrace === NULL
                           ? NULLL
                           : nl2br(htmlspecialchars($backtrace, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]));

            print "Please try again later!" .
                  "<h3>Error:</h3>" .
                  htmlspecialchars($error, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) .
                  ($backtrace !== NULL
                   ? "<h3>Backtrace:</h3>\n$htmlbacktrace"
                   : "");
            break;

        // Send a mail to the administrator about the database error.
        case "mail":
        default:

            include_once("./include/email_functions.php");

            $data = array(
              "mailmessage" =>
                  "A database error occured in your Phorum installation.\n".
                  "\n" .
                  "Error message:\n" .
                  "--------------\n" .
                  "\n" .
                  "$error\n".
                  "\n" .
                  ($backtrace !== NULL
                   ? "Backtrace:\n----------\n\n$backtrace"
                   : ""),
              "mailsubject" =>
                  "Phorum: A database error occured"
            );

            $adminmail = $PHORUM["system_email_from_address"];
            phorum_email_user(array($adminmail), $data);

            print "The administrator of this forum has been<br/>" .
                  "notified by email about the error.<br/>" .
                  "Please try again later!";
            break;
    }

    // Finish the error page.
    ?>
    </body>
    </html>
    <?php

    exit();
}

?>
