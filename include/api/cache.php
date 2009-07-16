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
 * This script implements the caching functions for Phorum.
 * There are multiple caching backends available for using  
 * different applications like memcached, apc or the traditional
 * file based backend.
 *
 * @package    PhorumAPI
 * @subpackage Cache
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

global $PHORUM;

// The location of the configuration file.
$cacheconfig = PHORUM_PATH.'/include/config/cache.php';

// Initialize the cache config to setup the default configuration and
// to avoid parameter injection.
$PHORUM['CACHECONFIG'] = array(
    'type'      => 'file',
    'directory' => NULL, // will be filled with a default later if needed
    'server'    => '127.0.0.1',
    'port'      => '11211',
    'user'      => '',
    'password'  => '',
);

// Load the configuration if available. If no configuration is available,
// the default settings will be used.
if (file_exists($cacheconfig) && ! include_once $cacheconfig)
{
    print '<html><head><title>Phorum error</title></head><body>';
    print '<h2>Phorum cache configuration error</h2>';

    // No database configuration found.
    if (!file_exists($cacheconfig)) { ?>
            Phorum has been installed on this server, but the 
            configuration<br />for the caching systen has not yet been made. 
            Please read<br /> <a href="docs/install.txt">docs/install.txt</a>
            for installation instructions.
    <?php
    } else {
        $fp = fopen($cacheconfig, 'r');
        // Unable to read the configuration file.
        if (!$fp) { ?>
            A cache configuration file was found in {phorum
            dir}/include/config/cache.php,<br />
            but Phorum was unable to read it. Please check the file 
            permissions<br />for this file.
        <?php
        // Unknown error.
        } else {
            fclose($fp); ?>
            A cache configuration file was found in 
            {phorum dir}/include/config/cache.php,<br />but it could not be 
            loaded. It possibly contains one or more syntax errors.<br />
            Please check your configuration file.
            <?php
        }
    }

    print '</body></html>';
    exit(1);
}

// Apply default cache directory if no specific directory is set
// from the cache configuration file.
if ($PHORUM['CACHECONFIG']['type'] == 'file' &&
    $PHORUM['CACHECONFIG']['directory'] === NULL) {
    $PHORUM['CACHECONFIG']['directory'] =
        substr(__FILE__, 0, 1) == '/' ? '/tmp' : 'C:\\Windows\\Temp';
}

// For command line scripts, we use the NULL caching layer in case file
// caching is in use. The command line user is often different from the web
// server user, causing permission problems on the cache.
if ((defined('PHORUM_SCRIPT') || PHP_SAPI == 'cli') &&
    $PHORUM['CACHECONFIG']['type'] == 'file') {
    $PHORUM['CACHECONFIG']['type'] = 'null';
}

// Load the caching layer.
$PHORUM['CACHECONFIG']['type'] = basename($PHORUM['CACHECONFIG']['type']);

// Safeguard for wrongly selected cache-layers.
// Falling back to file-layer if descriptive functions aren't existing.
if ($PHORUM['CACHECONFIG']['type'] == 'memcached' &&
    !function_exists('memcache_connect')) {
    $PHORUM['CACHECONFIG']['type'] = 'file';
} elseif ($PHORUM['CACHECONFIG']['type'] == 'apc' &&
    !function_exists('apc_fetch')) {
    $PHORUM['CACHECONFIG']['type'] = 'file';
}

$cacheapi_filename =
     PHORUM_PATH.'/include/api/cache/'.$PHORUM['CACHECONFIG']['type'].'.php';
if (file_exists($cacheapi_filename)) {
    require_once $cacheapi_filename;
} else {
    echo "The defined cache backend couldn't be found. Please check that you 
          uploaded all files and your settings in include/config/cache.php.";
    exit();
}

// Check if the cache system is working.
if (!phorum_api_cache_check())
{
    echo "The cache test has failed. Please check your cache configuration in 
          include/config/cache.php. If the configuration is okay, check if the 
          application used for caching is running.";
    exit();
}   
?>
