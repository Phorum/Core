<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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

/**
 * This script handles the top level Phorum API initialization.
 * This will setup the environment to be able to use the Phorum API.
 *
 * The variable $phorum is set by include/api.php. We need to
 * do this, because this code is included during initialization of
 * the Phorum object, so we cannot yet access the object otherwise.
 */

// ----------------------------------------------------------------------
// Initialize variables and constants
// ----------------------------------------------------------------------

// Initialize the global $PHORUM variable, which holds all Phorum data.
global $PHORUM;
$PHORUM = array
(
    // The DATA member holds the template variables.
    'DATA' => array
    (
        'LOGGEDIN'  => FALSE,

        // For collecting query variables for a next request.
        'GET_VARS'  => array(),
        'POST_VARS' => '',
    ),

    // The TMP member hold template {DEFINE ..} definitions, temporary
    // arrays and such in template code.
    'TMP'  => array(),

    // Query arguments.
    'args' => array(),

    // The active forum id.
    'forum_id' => 0,

    // Data for the active user.
    'user'     => array(),

    // Storage space for internal API data. 
    'API' => array()
);

// Load all API constants from ./include/api/constants.php
require_once $phorum->getPath('include/api/constants.php');

// Scripts should define the "phorum_page" constant, but we'll help out script
// authors that forget to do so here, to prevent PHP warnings later on.
defined('phorum_page') or define('phorum_page', 'unknown');

// ----------------------------------------------------------------------
// PHP extension compatibility 
// ----------------------------------------------------------------------

// For some functionality, we are depending on PHP extensions, that
// are not necessarily availably on any given PHP installation. To
// work around this, we have implemented compatibility modules that
// can be installed to provide the missing functionality in pure
// PHP code. This will make the execution a little bit slower on
// systems that do not have the required extension installed, but
// it will at least make it possible to run Phorum.
//
// We load these special compatibility modules at an early stage, so
// we can safely use them from the rest of the code.

$compat_modules = array(
    'mb_substr'   => 'mbstring',
    'json_encode' => 'json',
    'json_decode' => 'json',
    'iconv'       => 'iconv'
);

$missing_compat = array();
foreach ($compat_modules as $function => $ext) {
    if (!function_exists($function)) {
        $module_file = PHORUM_PATH."/mods/compat_$ext/compat_$ext.php";
        if (file_exists($module_file)) {
            require_once $module_file;
            if (!function_exists($function)) { ?>
                <html><head><title>Phorum error</title></head><body>
                <h2>Phorum Error:</h2>
                Compatibility module compat_<?php print $ext ?>
                does not implement function <?php print $function ?>().
                </body></html><?php
                exit;
            }
        } else {
            if (empty($missing_compat[$ext])) {
                $missing_compat[$ext] = array($function);
            } else {
                $missing_compat[$ext][] = $function;
            }
        }
    }
}

if (!empty($missing_compat)) { ?>
    <html><head><title>Phorum error</title></head><body>
    <style type="text/css">
    table { border-collapse: collapse; }
    td { padding: 0.2em 1em; border: 1px solid black; }
    </style>
    <h2>Phorum Error: PHP extension(s) missing on your system:</h2>
    <ul><?php
    foreach ($missing_compat as $extension => $functions) {
        print '<li>'.$extension.' ';
        print ' (needed for function'.(count($functions)==1?'':'s').': ' .
              implode(', ', $functions).')</li>';
    } ?>
    </ul>
    <h2>Solution:</h2>
    <ul>
    <li>Install the required extensions in PHP (ask your host if you
        do not own the system) or</li>
    <li>download and install compatibility modules from the
        <a href="http://www.phorum.org/downloads.php">Phorum website</a>.</li>
    </ul>
    <?php
    exit;
}

// ----------------------------------------------------------------------
// Load the database layer and setup a connection
// ----------------------------------------------------------------------

// Get the database settings. It is possible to override the database
// settings by defining a global variable $PHORUM_ALT_DBCONFIG which
// overrides $PHORUM["DBCONFIG"] (from include/db/config.php). This is
// only allowed if "PHORUM_WRAPPER" is defined and if the alternative
// configuration wasn't passed as a request parameter (which could
// set $PHORUM_ALT_DBCONFIG if register_globals is enabled for PHP).
if (empty($GLOBALS['PHORUM_ALT_DBCONFIG']) ||
    $GLOBALS['PHORUM_ALT_DBCONFIG'] == $_REQUEST['PHORUM_ALT_DBCONFIG'] ||
    !defined('PHORUM_WRAPPER')) {

    // Backup display_errors setting.
    $orig = ini_get('display_errors');
    @ini_set('display_errors', 0);

    // Use output buffering so we don't get header errors if there's
    // some additional output in the database config file (e.g. a UTF-8
    // byte order marker).
    ob_start();

    // Load configuration.
    $dbconfig = PHORUM_PATH.'/include/db/config.php';
    if (! include_once $dbconfig) {
        print '<html><head><title>Phorum error</title></head><body>';
        print '<h2>Phorum database configuration error</h2>';

        // No database configuration found.
        if (!file_exists($dbconfig)) { ?>
            Phorum has been installed on this server, but the configuration<br/>
            for the database connection has not yet been made. Please read<br/>
            <a href="docs/install.txt">docs/install.txt</a> for installation
            instructions. <?php
        } else {
            $fp = fopen($dbconfig, 'r');
            // Unable to read the configuration file.
            if (!$fp) { ?>
                A database configuration file was found in
                {phorum dir}/include/db/config.php,<br/>but Phorum was
                unable to read it. Please check the file permissions<br/>
                for this file. <?php
            // Unknown error.
            } else {
                fclose($fp); ?>
                A database configuration file was found in
                {phorum dir}/include/dbconfig.php,<br/>but it could not be
                loaded. It possibly contains one or more syntax errors.<br/>
                Please check your configuration file. <?php
            }
        }

        print '</body></html>';
        exit(1);
    }

    // Clear output buffer.
    ob_end_clean();

    // Restore original display_errors setting.
    @ini_set('display_errors', $orig);
} else {
    $PHORUM['DBCONFIG'] = $GLOBALS['PHORUM_ALT_DBCONFIG'];
}

// Backward compatbility: the "mysqli" layer was merged with the "mysql"
// layer, but people might still be using "mysqli" as their configured
// database type.
if ($PHORUM['DBCONFIG']['type'] == 'mysqli') {
    $PHORUM['DBCONFIG']['type'] = 'mysql';
}

// Load the database layer.
$PHORUM['DBCONFIG']['type'] = basename($PHORUM['DBCONFIG']['type']);
$phorum->db = new Phorum(
    'include/db/'.$PHORUM['DBCONFIG']['type'],
    'phorum_db_'
);

// Try to setup a connection to the database.
if (!$phorum->db->check_connection())
{
    if(isset($PHORUM['DBCONFIG']['down_page'])){
        $phorum->redirect($PHORUM['DBCONFIG']['down_page']);
    } else {
        echo "The database connection failed. Please check your database configuration in include/db/config.php. If the configuration is okay, check if the database server is running.";
        exit();
    }
}

// ----------------------------------------------------------------------
// Other initialization tasks
// ----------------------------------------------------------------------

// Set the anonymous user as our initial user.
// Authentication / session handling will override this
// later on when appropriate.
$phorum->user->set_active_user(PHORUM_FORUM_SESSION, NULL);

// Load the Phorum settings from the database.
$phorum->db->load_settings();

// For command line scripts, disable caching.
// The command line user is often different from the web server
// user, possibly causing permission problems on the cache.
if (defined('PHORUM_SCRIPT'))
{
    $PHORUM['cache_banlists']   = FALSE;
    $PHORUM['cache_css']        = FALSE;
    $PHORUM['cache_javascript'] = FALSE;
    $PHORUM['cache_layer']      = FALSE;
    $PHORUM['cache_messages']   = FALSE;
    $PHORUM['cache_newflags']   = FALSE;
    $PHORUM['cache_rss']        = FALSE;
    $PHORUM['cache_users']      = FALSE;
}

// Defaults for missing settings (these can be needed after upgrading, in
// case the admin did not yet save a newly added Phorum setting).
if (!isset($PHORUM['default_feed']))   $PHORUM['default_feed']   = 'rss';
if (!isset($PHORUM['cache_newflags'])) $PHORUM['cache_newflags'] = FALSE;
if (!isset($PHORUM['cache_messages'])) $PHORUM['cache_messages'] = FALSE;

// If we have no private key for signing data, generate one now,
// but only if it's not a fresh install.
if (isset($PHORUM['internal_version']) &&
    $PHORUM['internal_version'] >= PHORUM_SCHEMA_VERSION &&
    (!isset($PHORUM['private_key']) || empty($PHORUM['private_key']))) {
   $chars = "0123456789!@#$%&abcdefghijklmnopqr".
            "stuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
   $private_key = "";
   for ($i = 0; $i<40; $i++) {
       $private_key .= substr($chars, rand(0, strlen($chars)-1), 1);
   }
   $PHORUM['private_key'] = $private_key;
   $phorum->db->update_settings(array('private_key' => $PHORUM['private_key']));
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
require_once PHORUM_PATH."/include/cache/$PHORUM[cache_layer].php";

// Check if the Phorum extension is loaded. If yes, then show
// a warning. The extension is no longer supported and should
// be considered deprecated.
if (extension_loaded('phorum')) {
    print "
        <html><head>
        <title>The Phorum PHP Extension is deprecated</title></head>
        <body>
        <h1>Phorum Error</h1>
        The Phorum PHP extension was loaded, but this extension has
        been deprecated. Please make sure that the extension is not
        loaded from your PHP configuration anymore.
        </body>
        </html>";
    exit(0);
}

// Setup the template path and http path. These are put in a variable to give
// module authors a chance to override them. This can be especially useful
// for distibuting a module that contains a full Phorum template as well.
// For switching, the function phorum_switch_template() can be used.
$PHORUM['template_path'] = PHORUM_PATH.'/templates';
$PHORUM['template_http_path'] = $PHORUM['http_path'].'/templates';

// ----------------------------------------------------------------------
// Error handling functionality
// ----------------------------------------------------------------------

/**
 * Set a Phorum API error.
 *
 * @param integer $errno
 *     The errno value for the error that occurred. There are several
 *     specific errno values available, but for a generic error message
 *     that does not need a specific errno, {@link PHORUM_ERRNO_ERROR} can be
 *     used.
 *
 * @param string $error
 *     This is the error message, describing the error that occurred.
 *     if this parameter is omitted or NULL, then the message will be
 *     set to a generic message for the {@link $errno} that was used.
 *
 * @return bool
 *     This function will always return FALSE as its return value,
 *     so a construction like "return phorum_api_error_set(...)" can
 *     be used for setting an error and returning FALSE at the same time.
 */
function phorum_api_error_set($errno, $error = NULL)
{
    if ($error === NULL) {
        if (isset($GLOBALS["PHORUM"]["API"]["errormessages"][$errno])) {
            $error = $GLOBALS["PHORUM"]["API"]["errormessages"][$errno];
        } else {
            $error = "Unknown errno value ($errno).";
        }
    }

    $GLOBALS["PHORUM"]["API"]["errno"] = $errno;
    $GLOBALS["PHORUM"]["API"]["error"] = $error;

    return FALSE;
}

/**
 * Retrieve the error code for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error code or NULL if no error was set.
 */
function phorum_api_errno()
{
    global $PHORUM;

    if ($PHORUM["API"]["errno"] === NULL) {
        return NULL;
    } else {
        return $PHORUM["API"]["errno"];
    }
}

/**
 * Retrieve the error message for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error message or NULL if no error was set.
 */
function phorum_api_strerror()
{
    if ($GLOBALS["PHORUM"]["API"]["error"] === NULL) {
        return NULL;
    } else {
        return $GLOBALS["PHORUM"]["API"]["error"];
    }
}

# ----------------------------------------------------------------------
# Backward compatibility
# ----------------------------------------------------------------------

// These are functions that existed in Phorum, before their functionality
// was moved to the API layers. These functions are provided to allow
// modules to use the old function calls. These deprecated functions might
// be removed from future versions of Phorum.

function phorum_get_url() {
    Phorum::API()->url; // make sure the URL API layer code is loaded.
    $argv = func_get_args();
    return call_user_func_array('phorum_api_url_get', $argv);
}

function phorum_get_current_url($include_query_string = TRUE) {
    return Phorum::API()->url->current($include_query_string);
}

function phorum_redirect_by_url($url) {
    return Phorum::API()->redirect($url);
}

function phorum_hook() {
    Phorum::API()->modules; // make sure the Modules API layer code is loaded.
    $argv = func_get_args();
    return call_user_func_array('phorum_api_modules_hook', $argv);
}

function phorum_date($picture, $ts) {
    return Phorum::API()->format->date($picture, $ts);
}

function phorum_relative_date($ts) {
    return Phorum::API()->format->relative_date($ts);
}

function phorum_filesize($sz) {
    return Phorum::API()->format->filesize($sz);
}

?>
