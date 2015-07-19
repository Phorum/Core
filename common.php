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

// Check that this file is not loaded directly.
if ( basename( __FILE__ ) == basename( $_SERVER["PHP_SELF"] ) ) exit();


// ----------------------------------------------------------------------
// Initialize variables and constants and load required libraries
// ----------------------------------------------------------------------

// the Phorum version
define( "PHORUM", "5.2.20" );

// our database schema version in format of year-month-day-serial
define( "PHORUM_SCHEMA_VERSION", "2010101500" );

// our database patch level in format of year-month-day-serial
define( "PHORUM_SCHEMA_PATCHLEVEL", "2012030800" );

// Initialize the global $PHORUM variable, which holds all Phorum data.
global $PHORUM;
$PHORUM = array
(
    // The DATA member holds the template variables.
    'DATA' => array(
        'GET_VARS'  => array(),
        'POST_VARS' => ''
    ),

    // The TMP member hold template {DEFINE ..} definitions, temporary
    // arrays and such in template code.
    'TMP'  => array(),

    // Query arguments.
    'args' => array(),

    // The active forum id.
    'forum_id' => 0
);

// Load all constants from ./include/constants.php
require_once( "./include/constants.php" );

// Load the API code that is required for all pages.
require_once("./include/api/base.php");
require_once("./include/api/user.php");

// ----------------------------------------------------------------------
// Load the database layer and setup a connection
// ----------------------------------------------------------------------

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

    // Use output buffering so we don't get header errors if there's
    // some additional output in the database config file (e.g. a UTF-8
    // byte order marker).
    ob_start();

    // Load configuration.
    if (! include_once( "./include/db/config.php" )) {
        print '<html><head><title>Phorum error</title></head><body>';
        print '<h2>Phorum database configuration error</h2>';

        // No database configuration found.
        if (!file_exists("./include/db/config.php")) { ?>
            Phorum has been installed on this server, but the configuration<br/>
            for the database connection has not yet been made. Please read<br/>
            <a href="docs/install.txt">docs/install.txt</a> for installation
            instructions. <?php
        } else {
            $fp = fopen("./include/db/config.php", "r");
            // Unable to read the configuration file.
            if (!$fp) { ?>
                A database configuration file was found in
                ./include/db/config.php,<br/>but Phorum was unable to read it.
                Please check the file permissions<br/>for this file. <?php
            // Unknown error.
            } else {
                fclose($fp); ?>
                A database configuration file was found in
                ./include/dbconfig.php,<br/>but it could not be loaded.
                It possibly contains one or more errors.<br/>Please check
                your configuration file. <?php
            }
        }

        print '</body></html>';
        exit(1);
    }

    // Clean up the output buffer.
    ob_end_clean();

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
require_once( "./include/db/{$PHORUM['DBCONFIG']['type']}.php" );

// Try to setup a connection to the database.
if(!phorum_db_check_connection()){
    if(isset($PHORUM["DBCONFIG"]["down_page"])){
        phorum_redirect_by_url($PHORUM["DBCONFIG"]["down_page"]);
        exit();
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo "The database connection failed. Please check your database configuration in include/db/config.php. If the configuration is okay, check if the database server is running.";
        exit();
    }
}


// ----------------------------------------------------------------------
// Load and process the Phorum settings
// ----------------------------------------------------------------------

// Load the Phorum settings from the database.
phorum_db_load_settings();

// checking for upgrade or new install
if (!defined('PHORUM_ADMIN')) {
    if (!isset($PHORUM['internal_version']))
    {
        echo "<html><head><title>Phorum error</title></head><body>No Phorum settings were found. Either this is a brand new installation of Phorum or there is a problem with your database server. If this is a new install, please <a href=\"admin.php\">go to the admin page</a> to complete the installation. If not, check your database server.</body></html>";
        exit();
    } elseif ($PHORUM['internal_version'] < PHORUM_SCHEMA_VERSION ||
              !isset($PHORUM['internal_patchlevel']) ||
              $PHORUM['internal_patchlevel'] < PHORUM_SCHEMA_PATCHLEVEL) {
        if (isset($PHORUM["DBCONFIG"]["upgrade_page"])) {
            phorum_redirect_by_url($PHORUM["DBCONFIG"]["upgrade_page"]);
            exit();
        }
        echo "<html><head><title>Upgrade notification</title></head><body>It looks like you have installed a new version of Phorum.<br/>Please visit the admin page to complete the upgrade!</body></html>";
        exit();
    }
}

// For command line scripts, disable caching.
// The command line user is often different from the web server
// user, possibly causing permission problems on the cache.
if (defined('PHORUM_SCRIPT'))
{
    $PHORUM['cache_banlists']   = 0;
    $PHORUM['cache_css']        = 0;
    $PHORUM['cache_javascript'] = 0;
    $PHORUM['cache_layer']      = 0;
    $PHORUM['cache_messages']   = 0;
    $PHORUM['cache_newflags']   = 0;
    $PHORUM['cache_rss']        = 0;
    $PHORUM['cache_users']      = 0;
}

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

// Determine the caching layer to load.
if(!isset($PHORUM['cache_layer']) || empty($PHORUM['cache_layer'])) {
    $PHORUM['cache_layer'] = 'file';
} else {
    // Safeguard for wrongly selected cache-layers.
    // Falling back to file-layer if descriptive functions aren't existing.
    if($PHORUM['cache_layer'] == 'memcached' && !function_exists('memcache_connect')) {
        $PHORUM['cache_layer'] = 'file';
    } elseif($PHORUM['cache_layer'] == 'apc' && !function_exists('apc_fetch')) {
        $PHORUM['cache_layer'] = 'file';
    }
}

// Load the caching-layer. You can specify a different one in the settings.
// One caching layer *needs* to be loaded.
$PHORUM['cache_layer'] = basename($PHORUM['cache_layer']);
require_once( "./include/cache/$PHORUM[cache_layer].php" );

// Load phorum_get_url().
// This function is used for generating all Phorum URLs.
require_once("./include/phorum_get_url.php");

// Setup the template path and http path. These are put in a variable to give
// module authors a chance to override them. This can be especially useful
// for distibuting a module that contains a full Phorum template as well.
// For switching, the function phorum_switch_template() can be used.
$PHORUM['template_path'] = './templates';
$PHORUM['template_http_path'] = $PHORUM['http_path'].'/templates';

// ----------------------------------------------------------------------
// Parse and handle request data
// ----------------------------------------------------------------------

// Thanks a lot for magic quotes :-/
// In PHP6, magic quotes are (finally) removed, so we have to check for
// the get_magic_quotes_gpc() function here. The "@" is for suppressing
// deprecation warnings that are spawned by PHP 5.3 and higher when
// using the get_magic_quotes_gpc() function.
if ( function_exists('get_magic_quotes_gpc') &&
     @get_magic_quotes_gpc() && count( $_REQUEST ) ) {
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
// Also make sure that magic_quotes_runtime is disabled.
if (function_exists('set_magic_quotes_runtime')) {
    @set_magic_quotes_runtime(FALSE);
}

// Thanks a lot for configurable argument separators :-/
// In some cases we compose GET based URLs, with & and = as respectively
// argument and key/value separators. On some systems, the "&" character
// is not configured as a valid separator. For those systems, we have
// to parse the query string ourselves.
if (isset($_SERVER['QUERY_STRING']) &&
    strpos($_SERVER['QUERY_STRING'], '&') !== FALSE)
{
    $separator = get_cfg_var('arg_separator.input');
    if ($separator !== FALSE && strpos($separator, '&') === FALSE)
    {
        $parts = explode('&', $_SERVER['QUERY_STRING']);
        $_GET = array();
        foreach ($parts as $part)
        {
            list ($key, $val) = explode('=', rawurldecode($part), 2);

            // Handle array[] style GET arguments.
            if (preg_match('/^(.+)\[(.*)\]$/', $key, $m))
            {
                if (!isset($_GET[$m[1]]) || !is_array($_GET[$m[1]])) {
                    $_GET[$m[1]] = array();
                }
                if ($m[2] == '') {
                    $_GET[$m[1]][] = $val;
                } else {
                    $_GET[$m[1]][$m[2]] = $val;
                }
            }
            // Handle standard GET arguments.
            else
            {
                $_GET[$key] = $val;
                $_REQUEST[$key] = $val;
            }
        }
    }
}

/*
 * [hook]
 *     parse_request
 *
 * [description]
 *     This hook gives modules a chance to tweak the request environment,
 *     before Phorum parses and handles the request data. For tweaking the
 *     request environment, some of the options are:
 *     <ul>
 *       <li>
 *         Changing the value of <literal>$_REQUEST["forum_id"]</literal>
 *         to override the used forum_id.
 *       </li>
 *       <li>
 *         Changing the value of <literal>$_SERVER["QUERY_STRING"]</literal>
 *         or setting the global override variable
 *         <literal>$PHORUM_CUSTOM_QUERY_STRING</literal> to feed Phorum a
 *         different query string than the one provided by the webserver.
 *       </li>
 *     </ul>
 *     Tweaking the request data should result in data that Phorum can handle.
 *
 * [category]
 *     Request initialization
 *
 * [when]
 *     Right before Phorum runs the request parsing code in
 *     <filename>common.php</filename>.
 *
 * [input]
 *     No input.
 *
 * [output]
 *     No output.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_parse_request()
 *     {
 *         // Override the query string.
 *         global $PHORUM_CUSTOM_QUERY_STRING
 *         $PHORUM_CUSTOM_QUERY_STRING = "1,some,phorum,query=string";
 *
 *         // Override the forum_id.
 *         $_SERVER['forum_id'] = "1234";
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["parse_request"])) {
    phorum_hook("parse_request");
}

// Get the forum id if set using a request parameter.
if ( isset( $_REQUEST["forum_id"] ) && is_numeric( $_REQUEST["forum_id"] ) ) {
    $PHORUM["forum_id"] = $_REQUEST["forum_id"];
}
// Look for and parse the QUERY_STRING.
// This only applies to URLs that we create using phorum_get_url().
// Scripts using data originating from standard HTML forms (e.g. search)
// will have to use $_GET or $_POST.
if (!defined("PHORUM_ADMIN") && (isset($_SERVER["QUERY_STRING"]) || isset($GLOBALS["PHORUM_CUSTOM_QUERY_STRING"]))) {

    $Q_STR = empty( $GLOBALS["PHORUM_CUSTOM_QUERY_STRING"] )
             ? $_SERVER["QUERY_STRING"]
             : $GLOBALS["PHORUM_CUSTOM_QUERY_STRING"];

    if(strpos($Q_STR, "&")!==false){

        $PHORUM["args"] = $_GET;

    } else {

        // ignore stuff past a #
        if ( strstr( $Q_STR, "#" ) ) list( $Q_STR, $other ) = explode( "#", $Q_STR, 2 );

        // explode it on comma
        $PHORUM["args"] = $Q_STR == '' ? array() : explode( ",", $Q_STR );

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
    }

    // Handle path info based URLs for the file script.
    if (phorum_page == 'file' &&
        !empty($_SERVER['PATH_INFO']) &&
        preg_match('!^/(download/)?(\d+)/(\d+)/!', $_SERVER['PATH_INFO'], $m))
    {
        $PHORUM['args']['file'] = $m[3];
        $PHORUM['args'][0] = $PHORUM['forum_id'] = $m[2];
        $PHORUM['args']['download'] = empty($m[1]) ? 0 : 1;
    }

    // set forum_id if not set already by a forum_id request parameter
    if ( empty( $PHORUM["forum_id"] ) && isset( $PHORUM["args"][0] ) ) {
        $PHORUM["forum_id"] = ( int )$PHORUM["args"][0];
    }
}

// set the forum_id to 0 if not set by now.
if ( empty( $PHORUM["forum_id"] ) ) $PHORUM["forum_id"] = 0;

/*
 * [hook]
 *     common_pre
 *
 * [description]
 *     This hook can be used for overriding settings that were loaded and
 *     setup at the start of the <filename>common.php</filename> script.
 *     If you want to dynamically assign and tweak certain settings, then
 *     this is the designated hook to use for that.<sbr/>
 *     <sbr/>
 *     Because the hook was put after the request parsing phase, you can
 *     make use of the request data that is stored in the global variables
 *     <literal>$PHORUM['forum_id']</literal> and
 *     <literal>$PHORUM['args']</literal>.
 *
 * [category]
 *     Request initialization
 *
 * [when]
 *     Right after loading the settings from the database and parsing the
 *     request, but before making descisions on user, language and template.
 *
 * [input]
 *     No input.
 *
 * [output]
 *     No output.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_common_pre()
 *     {
 *         global $PHORUM;
 *
 *         // If we are in the forum with id = 10, we set the administrator
 *         // email information to a different value than the one configured
 *         // in the general settings.
 *         if ($PHORUM["forum_id"] == 10)
 *         {
 *             $PHORUM["system_email_from_name"] = "John Doe";
 *             $PHORUM["system_email_from_address"] = "John.Doe@example.com";
 *         }
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["common_pre"])) {
    phorum_hook("common_pre", "");
}

// ----------------------------------------------------------------------
// Setup data for standard (not admin) pages
// ----------------------------------------------------------------------

// TODO: Do we ever need this in admin? If not, it can go inside the block.
// stick some stuff from the settings into the DATA member
$PHORUM["DATA"]["TITLE"] = ( isset( $PHORUM["title"] ) ) ? $PHORUM["title"] : "";
$PHORUM["DATA"]["DESCRIPTION"] = ( isset( $PHORUM["description"] ) ) ? $PHORUM["description"] : "";
$PHORUM["DATA"]["HTML_TITLE"] = ( !empty( $PHORUM["html_title"] ) ) ? $PHORUM["html_title"] : $PHORUM["DATA"]["TITLE"];
$PHORUM["DATA"]["HEAD_TAGS"] = ( isset( $PHORUM["head_tags"] ) ) ? $PHORUM["head_tags"] : "";
$PHORUM["DATA"]["FORUM_ID"] = $PHORUM["forum_id"];

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

    // load the forum's settings
    if(!empty($PHORUM["forum_id"])){

        $forum_settings = phorum_db_get_forums( $PHORUM["forum_id"] );

        if ( !isset($forum_settings[$PHORUM["forum_id"]]) )
        {
            /*
             * [hook]
             *     common_no_forum
             *
             * [description]
             *     This hook is called in case a forum_id is requested for
             *     an unknown or inaccessible forum. It can be used for
             *     doing things like logging the bad requests or fully
             *     overriding Phorum's default behavior for these cases
             *     (which is redirecting the user back to the index page).
             *
             * [category]
             *     Request initialization
             *
             * [when]
             *     In <filename>common.php</filename>, right after detecting
             *     that a requested forum does not exist or is inaccessible
             *     and right before redirecting the user back to the Phorum
             *     index page.
             *
             * [input]
             *     No input.
             *
             * [output]
             *     No output.
             *
             * [example]
             *     <hookcode>
             *     function phorum_mod_foo_common_no_forum()
             *     {
             *         // Return a 404 Not found error instead of redirecting
             *         // the user back to the index.
             *         header("HTTP/1.0 404 Not Found");
             *         print "<html><head>\n";
             *         print "  <title>404 - Not Found</title>\n";
             *         print "</head><body>";
             *         print "  <h1>404 - Forum Not Found</h1>";
             *         print "</body></html>";
             *         exit();
             *     }
             *     </hookcode>
             */
            if (isset($PHORUM["hooks"]["common_no_forum"])) {
                phorum_hook("common_no_forum", "");
            }

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
    $PHORUM["DATA"]["HTML_DESCRIPTION"] = ( isset( $PHORUM["description"]  ) ) ? preg_replace("!\s+!", " ", $PHORUM["description"]) : "";
    $PHORUM["DATA"]["DESCRIPTION"] = strip_tags($PHORUM["DATA"]["HTML_DESCRIPTION"]);
    // clean up some more stuff in the description without html
    $search_arr  = array('\'','"');
    $replace_arr = array('','');
    $PHORUM["DATA"]["DESCRIPTION"]=str_replace($search_arr,$replace_arr,$PHORUM["DATA"]["DESCRIPTION"]);

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

    /*
     * [hook]
     *     common_post_user
     *
     * [description]
     *     This hook gives modules a chance to override Phorum variables
     *     and settings, after the active user has been loaded. The settings
     *     for the active forum are also loaded before this hook is called,
     *     therefore this hook can be used for overriding general settings,
     *     forum settings and user settings.
     *
     * [category]
     *     Request initialization
     *
     * [when]
     *     Right after loading the data for the active user in
     *     <filename>common.php</filename>, but before deciding on the
     *     language and template to use.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_common_post_user()
     *     {
     *         global $PHORUM;
     *
     *         // Switch the read mode for admin users to threaded.
     *         if ($PHORUM['user']['user_id'] && $PHORUM['user']['admin']) {
     *             $PHORUM['threaded_read'] = PHORUM_THREADED_ON;
     *         }
     *
     *         // Disable "float_to_top" for anonymous users.
     *         if (!$PHORUM['user']['user_id']) {
     *             $PHORUM['float_to_top'] = 0;
     *         }
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["common_post_user"])) {
         phorum_hook("common_post_user", "");
    }

    // only do those parts if the forum is not set to fixed view
    if ( !isset( $PHORUM['display_fixed'] ) || !$PHORUM['display_fixed'] ) {

        // check for a template being passed on the url
        // only use valid template names
        if(!empty( $PHORUM["args"]["template"] ) ) {
            $template = basename( $PHORUM["args"]["template"] );
            if ($template != '..') {
                $PHORUM["template"] = $template;
                $PHORUM['DATA']['GET_VARS'][]="template=".urlencode($template);
                $PHORUM['DATA']['POST_VARS'].="<input type=\"hidden\" name=\"template\" value=\"".htmlspecialchars($template)."\" />\n";
            }
        }

        // get the language file
        if(isset( $PHORUM['user']['user_language'] ) && !empty($PHORUM['user']['user_language']) ) {
            $PHORUM['language'] = $PHORUM['user']['user_language'];
        }

        if( isset( $PHORUM['user']['user_template'] ) && !empty($PHORUM['user']['user_template']) &&
            (!isset( $PHORUM["user_template"] ) || !empty($PHORUM['user_template']))
        ) {
            $PHORUM['template'] = $PHORUM['user']['user_template'];
        }

    }

    if ( !isset( $PHORUM["language"] ) || empty( $PHORUM["language"] ) || !file_exists( "./include/lang/$PHORUM[language].php" ) )
        $PHORUM["language"] = $PHORUM["default_forum_options"]["language"];
    if ( !file_exists("./include/lang/$PHORUM[language].php") ) {
        $PHORUM["language"] = PHORUM_DEFAULT_LANGUAGE;
    }

    // Use output buffering so we don't get header errors if there's
    // some additional output in the upcoming included files (e.g. UTF-8
    // byte order markers).
    ob_start();

    // Not loaded if we are running an external or scheduled script
    if (! defined('PHORUM_SCRIPT')) {
        require_once( phorum_get_template( "settings" ) );
        $PHORUM["DATA"]["TEMPLATE"] = htmlspecialchars($PHORUM['template']);
        $PHORUM["DATA"]["URL"]["TEMPLATE"] = htmlspecialchars("$PHORUM[template_http_path]/$PHORUM[template]");
        $PHORUM["DATA"]["URL"]["CSS"] = phorum_get_url(PHORUM_CSS_URL, "css");
        $PHORUM["DATA"]["URL"]["CSS_PRINT"] = phorum_get_url(PHORUM_CSS_URL, "css_print");
        $PHORUM["DATA"]["URL"]["JAVASCRIPT"] = phorum_get_url(PHORUM_JAVASCRIPT_URL);
        $PHORUM["DATA"]["URL"]["AJAX"] = phorum_get_url(PHORUM_AJAX_URL);
    }

    $PHORUM['language'] = basename($PHORUM['language']);
    if ( file_exists( "./include/lang/$PHORUM[language].php" ) ) {
        require_once( "./include/lang/$PHORUM[language].php" );
    }
    // load languages for localized modules
    if ( isset( $PHORUM["hooks"]["lang"] ) && is_array($PHORUM["hooks"]["lang"]) ) {
        foreach( $PHORUM["hooks"]["lang"]["mods"] as $mod )
        {
            // load mods for this hook
            $mod = basename($mod);
            if ( file_exists( "./mods/$mod/lang/$PHORUM[language].php" ) ) {
                require_once "./mods/$mod/lang/$PHORUM[language].php";
            }
            elseif ( file_exists( "./mods/$mod/lang/".PHORUM_DEFAULT_LANGUAGE.".php" ) ) {
                require_once "./mods/$mod/lang/".PHORUM_DEFAULT_LANGUAGE.".php";
            }
        }
    }

    // Clean up the output buffer.
    ob_end_clean();

    // load the locale from the language file into the template vars
    $PHORUM["DATA"]["LOCALE"] = ( isset( $PHORUM["locale"] ) ) ? $PHORUM["locale"] : "";

    // If there is no HCHARSET (used by the htmlspecialchars() calls), then
    // use the CHARSET for it instead.
    if (empty($PHORUM["DATA"]["HCHARSET"])) {
        $PHORUM["DATA"]["HCHARSET"] = $PHORUM["DATA"]["CHARSET"];
    }

    // HTML titles can't contain HTML code, so we strip HTML tags
    // and HTML escape the title.
    $PHORUM["DATA"]["HTML_TITLE"] = htmlspecialchars(strip_tags($PHORUM["DATA"]["HTML_TITLE"]), ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

    // if the Phorum is disabled, display a message.
    if( empty($PHORUM["user"]["admin"]) ) {
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

        } elseif(isset($PHORUM["status"]) && $PHORUM["status"]==PHORUM_MASTER_STATUS_READ_ONLY){
            $PHORUM["DATA"]["GLOBAL_ERROR"]=$PHORUM["DATA"]["LANG"]["ReadOnlyMessage"];
            $PHORUM["user"] = array("user_id" => 0, "username" => "", "admin" => false, "newinfo" => array(),"tz_offset" => -99);
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

    /*
     * [hook]
     *     common
     *
     * [description]
     *     This hook gives modules a chance to override Phorum variables
     *     and settings near the end of the <filename>common.php</filename>
     *     script. This can be used to override the Phorum (settings)
     *     variables that are setup during this script.
     *
     * [category]
     *     Request initialization
     *
     * [when]
     *     At the end of <filename>common.php</filename>.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_common()
     *     {
     *         global $PHORUM;
     *
     *         // Override the admin email address.
     *         $PHORUM["system_email_from_name"] = "John Doe";
     *         $PHORUM["system_email_from_address"] = "John.Doe@example.com";
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["common"])) {
        phorum_hook("common", "");
    }

    /*
     * [hook]
     *     page_<phorum_page>
     *
     * [availability]
     *     Phorum 5 >= 5.2.7
     *
     *
     * [description]
     *     This hook gives modules a chance to run hook code for a specific
     *     Phorum page near the end of the the <filename>common.php</filename>
     *     script.<sbr/>
     *     <sbr/>
     *     It gives modules a chance to override Phorum variables
     *     and settings near the end of the <filename>common.php</filename>
     *     script. This can be used to override the Phorum (settings)
     *     variables that are setup during this script.
     *     <sbr/>
     *     The <literal>phorum_page</literal> definition that is set
     *     for each script is used to construct the name of the hook that will
     *     be called. For example the <filename>index.php</filename> script
     *     uses phorum_page <literal>index</literal>, which means that the
     *     called hook will be <literal>page_index</literal>.
     *
     * [category]
     *     Request initialization
     *
     * [when]
     *     At the end of <filename>common.php</filename>, right after the
     *     <hook>common</hook> hook is called.<sbr/>
     *     <sbr/>
     *     You can look at this as if the hook is called at the start of the
     *     called script, since including <filename>common.php</filename>
     *     is about the first thing that a Phorum script does.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_page_list()
     *     {
     *         global $PHORUM;
     *
     *         // Set the type of list page to use, based on a cookie.
     *         if (empty($_COOKIE['list_style'])) {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_DEFAULT;
     *         } elseif ($_COOKIE['list_style'] == 'threaded') {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_ON;
     *         } elseif ($_COOKIE['list_style'] == 'flat') {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_OFF;
     *         } elseif ($_COOKIE['list_style'] == 'hybrid') {
     *             $PHORUM['threaded_list'] = PHORUM_THREADED_HYBRID;
     *         }
     *     }
     *     </hookcode>
     */
    $page_hook = 'page_'.phorum_page;
    if (isset($PHORUM["hooks"][$page_hook])) {
        phorum_hook($page_hook, "");
    }

    $formatted = phorum_api_user_format(array($PHORUM['user']));
    $PHORUM['DATA']['USER'] = $formatted[0];
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

    if(!empty($PHORUM['forum_path']) && !is_array($PHORUM['forum_path']))
        $PHORUM['forum_path'] = unserialize($PHORUM['forum_path']);

    $PHORUM['DATA']['BREADCRUMBS']=array();

    // Add the current forum path to the breadcrumbs.
    $index_page_url_template = phorum_get_url(PHORUM_INDEX_URL, '%forum_id%');

    if(empty($PHORUM['forum_path']) || $PHORUM['forum_id'] == $PHORUM['vroot']) {
        $id = $PHORUM['forum_id'];
        $url = empty($id)?  phorum_get_url(PHORUM_INDEX_URL) : str_replace('%forum_id%',$id,$index_page_url_template);

        $PHORUM['DATA']['BREADCRUMBS'][]=array(
            'URL'  => $url,
            'TEXT' => $PHORUM['DATA']['LANG']['Home'],
            'ID'   => $id,
            'TYPE' => 'root'
        );
    } else {
        $track = NULL;
        foreach ($PHORUM['forum_path'] as $id => $name)
        {
            if ($track === NULL) {
                $name = $PHORUM['DATA']['LANG']['Home'];
                $type = 'root';
                $first = FALSE;
            } else {
                $type = 'folder';
            }

            if(empty($id)) {
                $url = phorum_get_url(PHORUM_INDEX_URL);
            } else {
                $url = str_replace('%forum_id%',$id,$index_page_url_template);
            }
            // Note: $id key is not required in general. Only used for
            // fixing up the last entry's TYPE.
            $PHORUM['DATA']['BREADCRUMBS'][$id]=array(
                'URL'  => $url,
                'TEXT' => strip_tags($name),
                'ID'   => $id,
                'TYPE' => $type
            );
            $track = $id;
        }
        if (!$PHORUM['folder_flag']) {
            $PHORUM['DATA']['BREADCRUMBS'][$track]['TYPE'] = 'forum';
            $PHORUM['DATA']['BREADCRUMBS'][$track]['URL'] = phorum_get_url(PHORUM_LIST_URL, $track);
        }
    }
}

// ----------------------------------------------------------------------
// Setup data for admin pages
// ----------------------------------------------------------------------

else {

    // The admin interface is not localized, but we might need language
    // strings at some point after all, for example if we reset the
    // author name in messages for deleted users to "anonymous".
    $PHORUM["language"] = basename($PHORUM["default_forum_options"]["language"]);
    if (file_exists("./include/lang/$PHORUM[language].php")) {
        require_once("./include/lang/$PHORUM[language].php");
    }
}


// ----------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------

/**
 * Shutdown function
 */
function phorum_shutdown()
{
    global $PHORUM;

    // Strange things happen during shutdown
    // make sure we are still in the Phorum dir
    chdir(dirname(__FILE__));

    /*
     * [hook]
     *     phorum_shutdown
     *
     * [description]
     *     This hook gives modules a chance to easily hook into
     *     PHP's <phpfunc>register_shutdown_function</phpfunc>
     *     functionality.<sbr/>
     *     <sbr/>
     *     Code that you put in a phorum_shutdown hook will be run after
     *     running a Phorum script finishes. This hook can be considered
     *     an expert hook. Only use it if you really need it and if you
     *     are aware of implementation details of PHP's shutdown
     *     functionality.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After running a Phorum script finishes.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     */
    if (isset($PHORUM["hooks"]["shutdown"])) {
        phorum_hook("shutdown");
    }

    // Shutdown the database connection.
    phorum_db_close_connection();
}
register_shutdown_function("phorum_shutdown");

/**
 * Require that the user is logged in.
 *
 * A check is done to see if the user is logged in.
 * If not, then the user is redirected to the login page.
 */
function phorum_require_login()
{
    $PHORUM = $GLOBALS['PHORUM'];
    if ( !$PHORUM["user"]["user_id"] ) {
        $url = phorum_get_url(
            PHORUM_LOGIN_URL, "redir=" . urlencode(phorum_get_current_url())
        );
        phorum_redirect_by_url( $url );
        exit();
    }
}

/**
 * Check if the active user has read permission for a forum page.
 * * If the user does not have read permission for the currently active
 * forum, then an error message is shown. What message to show depends
 * on the exact case. Possible cases are:
 *
 * - The user is logged in: final missing read permission message;
 * - The user is not logged in, but wouldn't be allowed to read the
 *   forum, even if he were logged in: final missing read permission message;
 * - The user is not logged in, but could be allowed to read the
 *   forum if he were logged in: please login message.
 *
 * @return boolean
 *     TRUE in case the active user is allowed to read the forum,
 *     FALSE otherwise.
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
 * Switch to a different template(path).
 *
 * This function can be used to setup the data that is needed for activating
 * a different template or template storage path. This can be especially
 * useful for modules that can use this function to switch Phorum to a
 * template that is stored inside the module's directory (so no file copying
 * required to get the module's template tree into place). If for example
 * module "Foo" has a template directory "./mods/foo/templates/bar", then
 * the module could use this code to make sure that this template is used.
 * <code>
 *   phorum_switch_template(
 *       "bar",
 *       "./mods/foo/templates",
 *       $PHORUM['http_path']."/mods/foo/templates"
 *   );
 * </code>
 *
 * Beware that after doing this, the module's template directory is expected
 * to carry a full standard Phorum template and not only templates that are
 * required by the module for access through the "foo::templatename"
 * construction. Therefore, this template needs to have an info.php that
 * describes the template and a copy of all other template files that
 * Phorum normally uses.
 *
 * @param string $template
 *     The name of the template to active (e.g. "emerald", "lightweight", etc.)
 *     If this parameter is NULL, then no change will be done to the
 *     currently activated template.
 *
 * @param string $template_path
 *     The path to the base of the template directory. By default,
 *     this is "./templates". If this parameter is NULL, then
 *     no change will be done to the currenctly configured path.
 *
 * @param string $template_http_path
 *     The URL to the base of the template directory. By default,
 *     this is "<http_path>/templates". If this parameter is NULL, then
 *     no change will be done to the currenctly configured http path.
 *
 */
function phorum_switch_template($template = NULL, $template_path = NULL, $template_http_path = NULL)
{
    global $PHORUM;

    if ($template !== NULL) {
        $PHORUM['template'] = basename($template);
    }
    if ($template_path !== NULL) {
        $PHORUM['template_path'] = $template_path;
    }
    if ($template_http_path !== NULL) {
        $PHORUM['template_http_path'] = $template_http_path;
    }

    $PHORUM["DATA"]["TEMPLATE"] = htmlspecialchars($PHORUM['template']);
    $PHORUM["DATA"]["URL"]["TEMPLATE"] =
        htmlspecialchars("$PHORUM[template_http_path]/$PHORUM[template]");

    ob_start();
    include(phorum_get_template('settings'));
    ob_end_clean();
}

/**
 * Find out what input and output files to use for a template.
 *
 * @param string $page
 *     The template name (e.g. "header", "css", "foobar::frontpage", etc.).
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
    global $PHORUM;

    $page = basename($page);

    /*
     * [hook]
     *     get_template_file
     *
     * [availability]
     *     Phorum 5 >= 5.2.11
     *
     * [description]
     *     Allow modules to have influence on the results of the
     *     phorum_get_template_file() function. This function translates
     *     a page name (e.g. <literal>list</literal>) into a filename
     *     to use as the template source for that page (e.g.
     *      <filename>/path/to/phorum/templates/emerald/list.tpl</filename>).
     *
     * [category]
     *     Page output
     *
     * [when]
     *     At the start of the phorum_get_template_file() function
     *     from <filename>common.php</filename>.
     *
     * [input]
     *     An array containing two elements:
     *     <ul>
     *       <li>page:
     *           The page that was requested.</li>
     *       <li>source:
     *           The file that has to be used as the source for the page.
     *           This one is initialized as NULL.</li>
     *     </ul>
     *
     * [output]
     *     Same as input. Modules can override either or both of the array
     *     elements. When the "source" element is set after running the
     *     hook, then the file named in this element is directly used as
     *     the template source. It must end in either ".php" or ".tpl" to
     *     be accepted as a template source. Phorum does not do any additional
     *     checking on this source file name. It is the module's duty to
     *     provide a correct source file name.<sbr/>
     *     Otherwise, the template source file is determined based on
     *     the value of the "page" element, following the standard
     *     Phorum template resolving rules.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_get_template_file($data)
     *     {
     *         // Override the index template with a custom template
     *         // from the "foo" module.
     *         if ($data['page'] == 'index_new') {
     *             $data['page'] = 'foo::index_new';
     *         }
     *
     *         // Point the "pm" template directly at a custom PHP script.
     *         if ($data['page'] == 'pm') {
     *             $data['source'] = './mods/foo/pm_output_handler.php';
     *         }
     *
     *         return $data;
     *     }
     *     </hookcode>
     */
    $tplbase = NULL;
    $template = NULL;
    if (isset($GLOBALS["PHORUM"]["hooks"]["get_template_file"])) {
        $res = phorum_hook("get_template_file", array(
            'page'   => $page,
            'source' => NULL
        ));
        $page = basename($res['page']);
        if ($res['source'] !== NULL && strlen($res['source']) > 4)
        {
            // PHP source can be returned right away. These will be included
            // directly by the template handling code.
            if (substr($res['source'], -4, 4) == '.php') {
                return array($page, $res['source'], NULL);
            }
            // For .tpl files, we continue running this function, because
            // a cache file name has to be compiled for storing the
            // compiled template data.
            if (substr($res['source'], -4, 4) == '.tpl') {
                $tplbase = substr($res['source'], 0, -4);
            }
        }
        $template = 'set_from_module';
    }

    // No template source set by a module? Then continue by finding
    // a template based on the provided template page name.
    if ($tplbase === NULL)
    {
        // Check for a module reference in the page name.
        $fullpage = $page;
        $module = NULL;
        if (($pos = strpos($fullpage, "::", 1)) !== FALSE) {
            $module = substr($fullpage, 0, $pos);
            $page = substr($fullpage, $pos+2);
        }

        if ($module === NULL) {
            $prefix = $PHORUM['template_path'];
            // The postfix is used for checking if the template directory
            // contains at least the mandatory info.php file. Otherwise, it
            // could be an incomplete or empty template.
            $postfix = '/info.php';
        } else {
            $prefix = './mods/'.basename($module).'/templates';
            $postfix = '';
        }

        // If no user template is set or if the template cannot be found,
        // fallback to the configured default template. If that one can also
        // not be found, then fallback to the hard-coded default template.
        if (empty($PHORUM["template"]) ||
            !file_exists("$prefix/{$PHORUM['template']}$postfix"))
        {
            $template = $PHORUM["default_forum_options"]["template"];
            if ($template != PHORUM_DEFAULT_TEMPLATE &&
                !file_exists("$prefix/$template$postfix")) {
                $template = PHORUM_DEFAULT_TEMPLATE;
            }

            // If we're not handling a module template, then we can change the
            // global template to remember the fallback template and to make
            // sure that {URL->TEMPLATE} and {TEMPLATE} aren't pointing to a
            // non-existent template in the end..
            if ($module === NULL) { $PHORUM["template"] = $template; }
        } else {
            $template = $PHORUM['template'];
        }

        $tplbase = "$prefix/$template/$page";

        // check for straight PHP file
        if (file_exists("$tplbase.php")) {
            return array($page, "$tplbase.php", NULL);
        }
    }

    // Build the compiled template and template input file names.
    $tplfile = "$tplbase.tpl";
    $safetemplate = str_replace(array("-",":"), array("_","_"), $template);
    if (isset($module)) $page = "$module::$page";
    $safepage = str_replace(array("-",":"), array("_","_"), $page);
    $phpfile = "{$PHORUM["cache"]}/tpl-$safetemplate-$safepage-" .
           md5(dirname(__FILE__) . $tplfile) . ".php";

    return array($page, $phpfile, $tplfile);
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

    /*
     * [hook]
     *     start_output
     *
     * [description]
     *     This hook gives modules a chance to apply some last minute
     *     changes to the Phorum data. You can also use this hook to
     *     call <phpfunc>ob_start</phpfunc> if you need to buffer Phorum's
     *     full output (e.g. to do some post processing on the data
     *     from the <hook>end_output</hook> hook.<sbr/>
     *     <sbr/>
     *     Note: this hook is only called for standard pages (the ones
     *     that are constructed using a header, body and footer) and not
     *     for output from scripts that do raw output like
     *     <filename>file.php</filename>, <filename>javascript.php</filename>,
     *     <filename>css.php</filename> and <filename>rss.php</filename>.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After setting up all Phorum data, right before sending the
     *     page header template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_start_output()
     *     {
     *         global $PHORUM;
     *
     *         // Add some custom data to the page title.
     *         $title = $PHORUM['DATA']['HTML_TITLE'];
     *         $PHORUM['DATA']['HTML_TITLE'] = "-=| Phorum Rocks! |=- $title";
     *     }
     *     </hookcode>
     */
    if (isset($GLOBALS["PHORUM"]["hooks"]["start_output"])) {
        phorum_hook("start_output");
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     start_output_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>start_output</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After setting up all Phorum data, right before sending the
     *     page header template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     */
    if (isset($GLOBALS['PHORUM']['hooks']['start_output_' . phorum_page])) {
        phorum_hook('start_output_' . phorum_page);
    }

    // Copy only what we need into the current scope. We do this at
    // this point and not earlier, so the start_output hook can be
    // used for changing values in the $PHORUM data.
    $PHORUM = array(
        "DATA"   => $GLOBALS["PHORUM"]["DATA"],
        "locale" => $GLOBALS["PHORUM"]["locale"],
        "hooks"  => $GLOBALS["PHORUM"]["hooks"]
    );

    include phorum_get_template("header");

    /*
     * [hook]
     *     after_header
     *
     * [description]
     *     This hook can be used for adding content to the pages that is
     *     displayed after the page header template, but before the main
     *     page content.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page header template, but before sending the
     *     main page content.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_after_header()
     *     {
     *         // Only add data after the header for the index and list pages.
     *         if (phorum_page != 'index' && phorum_page != 'list') return;
     *
     *         // Add some static notification after the header.
     *         print '<div style="border:1px solid orange; padding: 1em">';
     *         print 'Welcome to our forums!';
     *         print '</div>';
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["after_header"])) {
        phorum_hook("after_header");
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     after_header_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>after_header</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page header template, but before sending the
     *     main page content.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     */
    if (isset($GLOBALS['PHORUM']['hooks']['after_header_' . phorum_page])) {
        phorum_hook('after_header_' . phorum_page);
    }

    /*
     * [hook]
     *     output_templates
     *
     * [description]
     *     This hook can be used to alter the list of templates that
     *     will be displayed by the phorum_api_output() call.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page header template, but before sending the
     *     main page content.
     *
     * [input]
     *     An array, containing the names of the templates to display
     *     in the page body (between the header and footer template).
     *
     * [output]
     *     Same as input, possibly modified.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_output_templates($templates)
     *     {
     *         // Add some advertisements at the top and bottom of the page.
     *         array_unshift($templates, "foo::top_advertisement);
     *         array_push($templates, "foo::bottom_advertisement);
     *
     *         return $templates;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM['hooks']['output_templates'])) {
        $templates = phorum_hook('output_templates', $templates);
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     output_templates_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>output_templates</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page header template, but before sending the
     *     main page content.
     *
     * [input]
     *     An array, containing the names of the templates to display
     *     in the page body (between the header and footer template).
     *
     * [output]
     *     Same as input, possibly modified.
     */
    if (isset($GLOBALS['PHORUM']['hooks']['output_templates' . phorum_page])) {
        $templates = phorum_hook(
          'output_templates' . phorum_page, $templates);
    }

    foreach($templates as $template){
        include phorum_get_template($template);
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     before_footer_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>before_footer</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the main page content, but before sending the
     *     page footer template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     */
    if (isset($GLOBALS['PHORUM']['hooks']['before_footer_' . phorum_page])) {
        phorum_hook('before_footer_' . phorum_page);
    }

    /*
     * [hook]
     *     before_footer
     *
     * [description]
     *     This hook can be used for adding content to the pages that is
     *     displayed after the main page content, but before the page footer.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the main page content, but before sending the
     *     page footer template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_before_footer()
     *     {
     *         // Add some static notification before the footer.
     *         print '<div style="font-size: 90%">';
     *         print '  For technical support, please send a mail to ';
     *         print '  <a href="mailto:tech@example.com">the webmaster</a>.';
     *         print '</div>';
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["before_footer"])) {
        phorum_hook("before_footer");
    }

    include phorum_get_template("footer");

    /*
     * [hook]
     *     end_output
     *
     * [description]
     *     This hook can be used for performing post output tasks.
     *     One of the things that you could use this for, is for
     *     reading in buffered output using <phpfunc>ob_get_contents</phpfunc>
     *     in case you started buffering using <phpfunc>ob_start</phpfunc>
     *     from the <hook>start_output</hook> hook.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page footer template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_end_output()
     *     {
     *         // Some made up call to some fake statistics package.
     *         include("/usr/share/lib/footracker.php");
     *         footracker_register_request();
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["end_output"])) {
        phorum_hook("end_output");
    }

    /*
     * [availability]
     *     Phorum 5 >= 5.2.16
     *
     * [hook]
     *     end_output_<page>
     *
     * [description]
     *     This hook provides the same functionality as the
     *     <hook>end_output</hook> hook. The difference is that this
     *     hook is called for a specific phorum_page, which makes
     *     this a lightweight hook if you only need to do processing
     *     for a single phorum_page.
     *
     * [category]
     *     Page output
     *
     * [when]
     *     After sending the page footer template.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     */
    if (isset($GLOBALS['PHORUM']['hooks']['end_output_' . phorum_page])) {
        phorum_hook('end_output_' . phorum_page);
    }
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

    list ($page, $phpfile, $tplfile) = phorum_get_template_file($page);

    // No template to pre-process.
    if ($tplfile == NULL) return $phpfile;

    // Pre-process template if the output file isn't available.
    if (! file_exists($phpfile)) {
        require_once "./include/templates.php";
        phorum_import_template($page, $tplfile, $phpfile);
    }

    return $phpfile;
}

// creates URLs used on most pages
function phorum_build_common_urls()
{
    $PHORUM=$GLOBALS['PHORUM'];

    $GLOBALS["PHORUM"]["DATA"]["URL"]["BASE"] = phorum_get_url( PHORUM_BASE_URL );
    $GLOBALS["PHORUM"]["DATA"]["URL"]["HTTP_PATH"] = $PHORUM['http_path'];

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
                require_once "./mods/$mod/$mod.php";
            } elseif ( file_exists( "./mods/$mod.php" ) ) {
                require_once "./mods/$mod.php";
            }
        }

        $called = array();

        foreach( $PHORUM["hooks"][$hook]["funcs"] as $func ) {

            // don't call a function twice in case it gets
            // put into the hook twice somehow
            if(isset($called[$func])) continue;
            $called[$func] = true;

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
    global $PHORUM;

    $tpls = array();

    $d = dir( $PHORUM['template_path'] );
    while ( false !== ( $entry = $d->read() ) ) {
        if ($entry[0] !== '.' && file_exists($PHORUM['template_path'].'/'.$entry.'/info.php')) {
            include $PHORUM['template_path'].'/'.$entry.'/info.php';
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
        $redir_url = htmlentities($redir_url);
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
   global $PHORUM;
   $signature = md5($data . $PHORUM["private_key"]);
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
    global $PHORUM;
    return md5($data . $PHORUM["private_key"]) == $signature;
}

/**
 * Setup and check posting tokens for form POST requests.
 *
 * For protecting forms against CSRF attacks, a signed posting token
 * is utilized. This posting token must be included in the POST request.
 * Without the token, Phorum will not accept the POST data.
 *
 * This function will check whether we are handling a POST request.
 * If yes, then check if an anti-CSRF token is provided in the POST data.
 * If no token is available or if the token does not match the expected
 * token, then the POST request is rejected.
 *
 * As a side effect, the required token is added to the {POST_VARS}
 * template variable. This facilitates protecting scripts. As
 * long as the template variable is added to the <form> for the
 * script, it will be automatically protected.
 *
 * @param string $target_page
 *     The page for which to check a posting token. When no target
 *     page is provided, then the constant "phorum_page" is used instead.
 *
 * @return string
 *     The expected posting token.
 */
function phorum_check_posting_token($target_page = NULL)
{
    global $PHORUM;

    if ($target_page === NULL) $target_page = phorum_page;

    // Generate the posting token.
    $posting_token = md5(
        ($target_page !== NULL ? $target_page : phorum_page) . '/' .
        (
          $PHORUM['user']['user_id']
          ? $PHORUM['user']['password'].'/'.$PHORUM['user']['sessid_lt']
          : (
              isset($_SERVER['HTTP_USER_AGENT'])
              ? $_SERVER['HTTP_USER_AGENT']
              : 'unknown'
            )
        ) . '/' .
        $PHORUM['private_key']
    );

    // Add the posting token to the {POST_VARS}.
    $PHORUM['DATA']['POST_VARS'] .=
        "<input type=\"hidden\" name=\"posting_token:$target_page\" " .
        "value=\"$posting_token\"/>\n";

    // Check the posting token if a form post is done.
    if (!empty($_POST))
    {
        if (!isset($_POST["posting_token:$target_page"]) ||
            $_POST["posting_token:$target_page"] != $posting_token) {
            $PHORUM['DATA']['ERROR'] =
                'Possible hack attempt detected. ' .
                'The posted form data was rejected.';
            phorum_build_common_urls();
            phorum_output("message");
            exit();
        }
    }

    return $posting_token;
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

    /*
     * [hook]
     *     database_error
     *
     * [description]
     *     Give modules a chance to handle or process database errors.
     *     This can be useful to implement addional logging backends and/or
     *     alerting mechanisms. Another option is to fully override Phorum's
     *     default database error handling by handling the error and then
     *     calling exit() from the hook to prevent the default Phorum code
     *     from running.<sbr/>
     *     <sbr/>
     *     Note: If you decide to use the full override scenario, then
     *     it is best to make your module run the database_error hook
     *     last, so other modules can still run their hook handling
     *     before the script exits. To accomplish this, add this to your
     *     module info:
     *     <programlisting>
     *     priority: run hook database_error after *
     *     </programlisting>
     *
     * [category]
     *     Miscellaneous
     *
     * [when]
     *     At the start of the function
     *     <literal>phorum_database_error</literal> (which you can find in
     *     <filename>common.php</filename>). This function is called from
     *     the database layer when some database error occurs.
     *
     * [input]
     *     The error message that was returned from the database layer.
     *     This error is not HTML escaped, so if you send it to the browser,
     *     be sure to preprocess it using <phpfunc>htmlspecialchars</phpfunc>.
     *
     * [output]
     *     Same as input.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_database_error($error)
     *     {
     *         // Log database errors to syslog facility "LOCAL0".
     *         openlog("Phorum", LOG_PID | LOG_PERROR, LOG_LOCAL0);
     *         syslog(LOG_ERR, $error);
     *
     *         return $error;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["database_error"])) {
        phorum_hook("database_error", $error);
    }

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
                           ? NULL
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

            require_once("./include/email_functions.php");

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

// For safely doing substr() operations on strings that contain
// multi-byte characters (which can happen when using UTF-8), we
// need mb_substr(). Unfortunately, this function is part of a
// PHP extension and we can therefore not be sure that it is
// available in the PHP installation.
//
// Here we implement mb_substr() ourselves in case it is not
// available, so we can safely use it in our code.
if (!function_exists('mb_substr'))
{
    // For now, we implement mb_substr simply as substr().
    // We need to think about adding a check for broken multi-byte
    // characters at the end of the resulting substring.
    function mb_substr($str, $start, $length = NULL, $encoding = NULL)
    {
        if ($length) {
            return substr($str, $start, $length);
        } else {
            return substr($str, $start);
        }
    }
}

?>
