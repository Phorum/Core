<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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
 * This file bootstraps the Phorum API.
 *
 * @package    PhorumAPI
 * @subpackage Core
 * @copyright  2011, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 *
 * @todo Write examples for API calls inline in the API call documentation.
 *       Examples that are in separate files (like the user API examples
 *       that we have now) are not practical.
 *
 * @todo Extend the Phorum::API() singleton documentation, to explain
 *       that this singleton is the OO API entry point.
 *
 * @todo Normalize authorization functionality.
 */

// ----------------------------------------------------------------------
// Initialize variables and constants
// ----------------------------------------------------------------------

// Initialize the global $PHORUM variable, which holds all Phorum data.
global $PHORUM;
$PHORUM = array
(
    // The DATA member holds template variables.
    'DATA' => array
    (
        'LOGGEDIN'     => FALSE,

        // For collecting query variables for a next request.
        'GET_VARS'     => array(),
        'POST_VARS'    => '',

        // For adding extra tags to the <head> section.
        'HEAD_TAGS'    => '',

        // Normally set from the language files. We provide some initial
        // values here, in case we need access to these variables
        // before the language file is loaded.
        'CHARSET'      => 'UTF-8',
        'HCHARSET'     => '',
        'MAILENCODING' => '8bit'
    ),

    // The TMP member hold template {DEFINE ..} definitions, temporary
    // arrays and such in template code.
    'TMP'  => array(),

    // Query arguments (this array is filled by phorum_api_request_parse()).
    'args' => array(),

    // The active forum id.
    'forum_id' => 0,

    // Data for the active user.
    'user'     => array(),

    // Storage space for internal API data.
    'API' => array
    (
        // Initialize data for the ErrorHandling API.
        'errno' => NULL,
        'error' => NULL
    ),

    // The database layer object.
    'DB' => NULL
);

// Load the Phorum constants.
require_once dirname(__FILE__).'/api/constants.php';

// Scripts should define the "phorum_page" constant, but we'll help out script
// authors that forget to do so here, to prevent PHP warnings later on.
defined('phorum_page') or define('phorum_page', 'unknown');

// ----------------------------------------------------------------------
// PHP extension compatibility
// ----------------------------------------------------------------------

// For some functionality, we are depending on PHP extensions and
// functions, that are not necessarily availably on any given PHP
// installation. To work around this, we have implemented compatibility
// modules that can be installed to provide the missing functionality in
// pure PHP code. This will make the execution a little bit slower on
// systems that do not have the required extension installed, but
// it will at least make it possible to run Phorum.
//
// We load these special compatibility modules at an early stage, so
// we can safely use them from the rest of the code.

$compat_modules = array(
    'mb_substr'    => 'mbstring',
    'json_encode'  => 'json',
    'json_decode'  => 'json',
    'iconv'        => 'iconv',
    'random_bytes' => 'random',
    'random_int'   => 'random',
    'stripos'      => 'stripos'
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
// Load the cache layer
// ----------------------------------------------------------------------

// Load the caching API.
require_once PHORUM_PATH.'/include/api/cache.php';

// ----------------------------------------------------------------------
// Load the database layer and setup a connection
// ----------------------------------------------------------------------

// Get the database settings. It is possible to override the database
// settings by defining a global variable $PHORUM_ALT_DBCONFIG which
// overrides $PHORUM["DBCONFIG"] (from include/config/database.php). This is
// only allowed if "PHORUM_WRAPPER" is defined and if the alternative
// configuration wasn't passed as a request parameter (which could
// set $PHORUM_ALT_DBCONFIG if register_globals is enabled for PHP).
if (empty($GLOBALS['PHORUM_ALT_DBCONFIG']) ||
    (isset($_REQUEST['PHORUM_ALT_DBCONFIG']) && $GLOBALS['PHORUM_ALT_DBCONFIG'] == $_REQUEST['PHORUM_ALT_DBCONFIG']) ||
    !defined('PHORUM_WRAPPER')) {

    // Backup display_errors setting.
    $orig = ini_get('display_errors');
    @ini_set('display_errors', 0);

    // Use output buffering so we don't get header errors if there's
    // some additional output in the database config file (e.g. a UTF-8
    // byte order marker).
    ob_start();

    // Load configuration.
    $dbconfig = PHORUM_PATH.'/include/config/database.php';
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
                {phorum dir}/include/config/database.php,<br/>
                but Phorum was unable to read it. Please check the
                file permissions<br/>
                for this file. <?php
            // Unknown error.
            } else {
                fclose($fp); ?>
                A database configuration file was found in
                {phorum dir}/include/config/database.php,<br/>
                but it could not be loaded. It possibly contains
                one or more syntax errors.<br/>
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

// could be unset in Phorum < 5.2.7
if (!isset($PHORUM['DBCONFIG']['socket'])) $PHORUM['DBCONFIG']['socket'] = NULL;
if (!isset($PHORUM['DBCONFIG']['port']))   $PHORUM['DBCONFIG']['port']   = NULL;

// Backward compatbility: the "mysqli" layer was merged with the "mysql"
// layer, but people might still be using "mysqli" as their configured
// database type. When "mysqli" must be used, it can be configured using
// the "mysql_extension" setting in the database configuration file.
if ($PHORUM['DBCONFIG']['type'] == 'mysqli') {
    $PHORUM['DBCONFIG']['type'] = 'mysql';
}

// Load the database layer. Class and filename are derived
// from the format "Phorum<Type>DB".
$PHORUM['DBCONFIG']['type'] = basename($PHORUM['DBCONFIG']['type']);
$db_class = 'Phorum' . ucfirst($PHORUM['DBCONFIG']['type']) . 'DB';
require_once PHORUM_PATH.'/include/db/PhorumDB.php';
require_once PHORUM_PATH."/include/db/{$db_class}.php";

// Initialize the database layer object.
$PHORUM['DB'] = new $db_class;

// Try to setup a connection to the database.
if (!$PHORUM['DB']->check_connection())
{
    if(isset($PHORUM['DBCONFIG']['down_page'])){
        phorum_api_redirect($PHORUM['DBCONFIG']['down_page']);
    } else {
        echo "The database connection failed. Please check your database configuration in include/config/database.php. If the configuration is okay, check if the database server is running.";
        exit();
    }
}

// Load the functional database layer, for backward compatibility.
require_once PHORUM_PATH.'/include/db/functional_layer.php';

// ----------------------------------------------------------------------
// Load code that is used by most scripts.
// ----------------------------------------------------------------------

require_once PHORUM_PATH . '/include/api/deprecated.php';
require_once PHORUM_PATH . '/include/api/error.php';
require_once PHORUM_PATH . '/include/api/hook.php';
require_once PHORUM_PATH . '/include/api/url.php';
require_once PHORUM_PATH . '/include/api/request.php';
require_once PHORUM_PATH . '/include/api/redirect.php';
require_once PHORUM_PATH . '/include/api/user.php';
require_once PHORUM_PATH . '/include/api/forums.php';
require_once PHORUM_PATH . '/include/api/format.php';
require_once PHORUM_PATH . '/include/api/format/htmlspecialchars.php';
require_once PHORUM_PATH . '/include/api/format/users.php';
require_once PHORUM_PATH . '/include/api/format/wordwrap.php';
require_once PHORUM_PATH . '/include/api/template.php';
require_once PHORUM_PATH . '/include/api/output.php';
require_once PHORUM_PATH . '/include/api/buffer.php';

// ----------------------------------------------------------------------
// Register the Phorum shutdown function
// ----------------------------------------------------------------------

/**
 * The Phorum shutdown function, which will always be called when a
 * Phorum script ends.
 */
function phorum_shutdown()
{
    global $PHORUM;

    // Strange things happen during shutdown
    // Make sure that we are in the Phorum dir.
    /**
     * @todo Still needed now we include files using absolute paths?
     */
    $working_dir = getcwd();
    chdir(PHORUM_PATH);

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
        phorum_api_hook("shutdown");
    }

    // Shutdown the database connection.
    $PHORUM['DB']->close_connection();

    if ($working_dir !== FALSE) {
        chdir($working_dir);
    }
}

register_shutdown_function('phorum_shutdown');

// ----------------------------------------------------------------------
// Other initialization tasks
// ----------------------------------------------------------------------

// Set the anonymous user as our initial user.
// Authentication / session handling can override this
// later on when appropriate.
phorum_api_user_set_active_user(PHORUM_FORUM_SESSION, NULL);

// Load the Phorum settings from the database.
$PHORUM['DB']->load_settings();

// Allow the activated cache layer to check if it is working correctly.
if (function_exists('phorum_api_cache_check'))
{
    $error = phorum_api_cache_check();
    if ($error)
    {
        echo "The cache test has failed. Please check your cache " .
             "configuration in include/config/cache.php. If the " .
             "configuration is okay, check if the application used " .
             "for caching is running.<br/><br/>" .
             "The error as returned by the cache layer is:<br/>" .
             "<b>" . phorum_api_format_htmlspecialchars($error) . "</b>";
        exit();
    }
}

// Check for an upgrade or a new install.
if (!defined('PHORUM_ADMIN'))
{
    if (!isset($PHORUM['internal_version']))
    {
        echo "<html><head><title>Phorum error</title></head><body>
              <h2>No Phorum settings were found</h2>
              Either this is a brand new installation of Phorum<br/>
              or there is a problem with your database server.<br/>
              <br/>
              If this is a new install, please
              <a href=\"admin.php\">go to the admin page</a> to complete
              the installation.<br/>
              If not, then check your database server.
              </body></html>";
        exit();
    } elseif ($PHORUM['internal_version'] < PHORUM_SCHEMA_VERSION ||
              !isset($PHORUM['internal_patchlevel']) ||
              $PHORUM['internal_patchlevel'] < PHORUM_SCHEMA_PATCHLEVEL) {
        if (isset($PHORUM["DBCONFIG"]["upgrade_page"])) {
            phorum_api_redirect($PHORUM["DBCONFIG"]["upgrade_page"]);
        } else {
            echo "<html><head><title>Upgrade notification</title></head><body>
                  <h2>Phorum upgrade</h2>
                  It looks like you have installed a new version of Phorum.<br/>
                  Please visit the admin page to complete the upgrade!
                  </body></html>";
            exit();
        }
    }
}

// The internal_patchlevel can be unset, because this setting was
// added in 5.2. When upgrading from 5.1, this settings is not yet
// available. To make things work, we'll fake a value for this
// setting which will always be lower than the available patch ids.
if (!isset($PHORUM["internal_patchlevel"])) {
    $PHORUM["internal_patchlevel"] = "1111111111";
}

// If we have no private key for signing data, generate one now,
// but only if we are not in the middle of a fresh install.
if (isset($PHORUM['internal_version']) &&
    $PHORUM['internal_version'] >= PHORUM_SCHEMA_VERSION &&
    empty($PHORUM['private_key'])) {
    require_once PHORUM_PATH.'/include/api/generate.php';
    $PHORUM['private_key'] = phorum_api_generate_key();
    $PHORUM['DB']->update_settings(array(
        'private_key' => $PHORUM['private_key']
    ));
}


// Check if the Phorum extension is loaded. If yes, then show
// a warning. The extension is no longer supported and should
// be considered deprecated.
if (extension_loaded('phorum')) {
    print "
        <html><head>
        <title>The Phorum PHP Extension is deprecated</title></head>
        <body>
        <h2>Phorum Error</h2>
        The Phorum PHP extension was loaded, but this extension has<br/>
        been deprecated. Please make sure that the extension is not<br/>
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
// The Phorum API call router class.
// ----------------------------------------------------------------------

/**
 * The Phorum API call router class.
 *
 * This class implements a one-object-wraps-all class, which provides
 * access to all Phorum API functionality, by means of autoloading the
 * required implementation files and calling the API functions.
 *
 * This class is not used in Phorum's core code, because plain function
 * calls are faster than using this object oriented approach. However,
 * when doing some external programming against Phorum, using this
 * class might make the coding somewhat easier, because the programmer
 * does not need to be worried about doing the correct includes himself.
 *
 * Example usage:
 * <code>
 * require_once '/path/to/phorum/include/api.php';
 * $phorum = Phorum::API();
 *
 * $user = $phorum->user->get(1234);
 * $user['real_name'] = 'John Doe';
 * $phorum->user->save($user);
 * </code>
 *
 * @package    PhorumAPI
 * @subpackage Core
 */
class Phorum
{
    private $phorum_path;
    private $func_prefix;
    private $node_file;
    private $node_path;

    private static $instance;

    /**
     * Generate a full file system path to a Phorum file.
     *
     * @param string $file
     *     The file path, relative to the Phorum root.
     *
     * @param string
     *     The absolute file system path to the file.
     */
    public function getPath($file = '')
    {
        // The Phorum installation path.
        // We cannot used the PHORUM_PATH constant from constants.php,
        // because include/api/constants.php might not be loaded yet.
        if (empty($this->phorum_path)) {
            $this->phorum_path = realpath(dirname(__FILE__).'/../');
        }

        return $this->phorum_path . ($file == '' ? '' : '/') . $file;
    }

    /**
     * The Phorum constructor.
     *
     * Creates a node in the Phorum API routing tree.
     *
     * This method is defined as a private method, to enforce the
     * singleton design pattern. To grab an instance of the Phorum
     * class, one should call the {@link Phorum::API()} static method.
     *
     * @param array $node_path
     *     The fileystem path for the constructed API node.
     *
     * @param array $func_prefix
     *     The prefix that is used for functions below this node.
     *     There is no need to call this parameter directly.
     *     It is used internally by the Phorum object to create subnodes.
     */
    private function __construct($node_path = NULL, $func_prefix = NULL)
    {
        // The filesystem path for the constructed API node.
        if ($node_path === NULL) $node_path = 'include/api';
        $this->node_path = $node_path;

        // The prefix that is used for functions below this node.
        if ($func_prefix === NULL) $func_prefix = 'phorum_api_';
        $this->func_prefix = $func_prefix;

        // Determine the file in which the functions for this node are
        // defined. For the root level API node, we do not need to
        // load a script.
        if ($func_prefix == 'phorum_api_') {
            $file = NULL;
        } else {
            $file = $this->getPath($node_path.'.php');
        }

        // Load the API layer file.
        if ($file !== NULL) {
            if (file_exists($file)) {
                global $PHORUM;
                $phorum = $this; // to reference $phorum from included code
                require_once $file;
                $this->node_file = $file;
            } else trigger_error(
                "Phorum API layer file \"$file\" not available for " .
                "loading the \"{$this->func_prefix}*\" functions",
                E_USER_ERROR
            );
        }
    }

    /**
     * This method is defined as private to prevent cloning of the
     * Phorum API object.
     */
    private function __clone() { }

    /**
     * Magic method for automatically initializing Phorum API nodes
     * when they are accessed for the first time.
     *
     * @param string $what
     *     The name of the API node (e.g. "user", "file").
     *
     * @return Phorum $node
     *     The Phorum node object for the requested node name.
     */
    public function __get($what)
    {
        $what = basename($what);
        return $this->$what = new Phorum(
            $this->node_path . '/' . $what,
            $this->func_prefix . $what . '_'
        );
    }

    /**
     * Magic method for calling Phorum API functions.
     *
     * @param string $what
     *     The name of the function to call.
     *
     * @param array
     *     An array of arguments for the function call.
     *
     * @return mixed
     *     The return value of the function call.
     */
    public function __call($what, $args)
    {
        $function = $this->func_prefix . $what;
        if (!function_exists($function))
        {
            // Check for an API layer, named $what.
            // Check if the function prefix{$what}() exists.
            // If yes, then we'll redirect to that function.
            // E.g. phorum_api_url() will be handled by phorum_api_url()
            // from the include/api/url.php API library file.
            $this->$what; // forces loading the layer.
            $function = $this->func_prefix . $what;

            // Out of luck.
            if (!function_exists($function)) trigger_error(
                "Phorum API file \"{$this->node_file}\" does not implement " .
                "API function $function()",
                E_USER_ERROR
            );
        }

        return call_user_func_array($function, $args);
    }

    /**
     * Generate a full API function name based on a method for this object.
     * Used in the phorum_api_error_backtrace() function.
     */
    public function getFunctionName($what)
    {
        return $this->func_prefix . $what;
    }

    /**
     * Singleton implementation.
     *
     * This will instantiate one instance of the Phorum class and return
     * it. On subsequent calls, the same instance is returned.
     *
     * Usage: $phorum = Phorum::API();
     *
     * @return Phorum
     */
    public static function API ()
    {
        if (!isset(Phorum::$instance)) Phorum::$instance = new Phorum();
        return Phorum::$instance;
    }
}

?>
