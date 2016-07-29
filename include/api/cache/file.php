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


/**
 * This script implements the file caching backend for Phorum.
 *
 * This is a simple file-based caching-layer.
 * Recommended are some more sophisticated solutions, like
 * the memcached-layer or the APC-layer.
 *
 * @package    PhorumAPI
 * @subpackage CacheFile
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */
if (!defined("PHORUM")) return;

/**
 * The depth of the file cache. This determines the number of subdirectories
 * that is used for the cache file structure. The default should work on
 * most modern systems. On some really old systems, the depth might need
 * to be higher, to prevent performance issues with large directories, but
 * if that is the case, we'd suggest to upgrade the system or to not
 * use the file cache, instead of tweaking this parameter.
 *
 * Note: when you change this parameter, be sure to understand the logic
 * from phorum_api_cache_mkpath(), because it does not carry protection
 * against nonsense settings.
 */
define('PHORUM_FILE_CACHE_DEPTH', 4);

/**
 * The length of the directory names to use for the cache file structure.
 * Changing this value changes the distribution of directories and files
 * in the cache structure (lower values result in less directories, but
 * more cache files at the deepest level.)
 *
 * Note: when you change this parameter, be sure to understand the logic
 * from phorum_api_cache_mkpath(), because it does not carry protection
 * against nonsense settings.
 */
define('PHORUM_FILE_CACHE_DIRLEN', 6);

// {{{ Function: phorum_api_cache_get()
/**
 * Retrieve an object from the cache.
 *
 * @param string $type
 *     A name for the group of data that is being cached.
 *     Examples are "user" and "message".
 *
 * @param string|array $key
 *     A unique key that identifies the object to retrieve.
 *     This could for example be the user_id of a cached user.
 *     This can also be an array of keys.
 *
 * @param integer $version
 *     The version of the object to retrieve. If the cached object's
 *     version is older than the requested version, then no object
 *     will be returned.
 *
 * @return mixed
 *     This function returns the cached object for the given key
 *     or NULL if no data is cached or if the cached data has expired.
 *
 *     When an array of keys is provided in the $key argument, then
 *     an array will be returned. The keys in this array are the cache keys
 *     for which cached data is available. If no cached data is available
 *     for any of the keys, then NULL is returned.
 */
function phorum_api_cache_get($type, $key, $version=NULL)
{
    global $PHORUM;

    $ret       = array();
    $checkkeys = is_array($key) ? $key : array($key);

    // Retrieve the cached data.
    foreach ($checkkeys as $checkkey)
    {
        // Generate the path for the file that is used to cache the data.
        list ($path, $file) = phorum_api_cache_mkpath($type, $checkkey);
        $path = "$path/$file";

        if (file_exists($path))
        {
            // the data is: array($ttl_time, $data, $version)
            // $version might not be set.
            $retval = unserialize(@file_get_contents($path));

            // broken data?
            if (!is_array($retval)) {
                @unlink($path);
            }
            // timeout?
            elseif($retval[0] < time()) {
                @unlink($path);
            }
            // version expired?
            elseif (
                $version != NULL &&
                (!isset($retval[2]) || $retval[2] != $version)
            ) {
                @unlink($path);
            }
            // cache data is valid
            else {
                $ret[$checkkey] = $retval[1];
            }

            unset($retval);
        }
    }

    // Return the result.
    if (is_array($key)) {
      return count($ret) ? $ret : NULL;
    } else {
      return array_key_exists($key, $ret) ? $ret[$key] : NULL;
    }
}
// }}}

// {{{ Function: phorum_api_cache_put()
/**
 * Store an object in the cache.
 *
 * @param string $type
 *     A name for the group of data that is being cached.
 *     Examples are "user" and "message".
 *
 * @param string $key
 *     A unique key that identifies the object that is cached.
 *     This could for example be the user_id of a user that is being cached.
 *     Existing data with the same $key is overwritten.
 *
 * @param integer $ttl
 *     The maximum time (in seconds) that the data lives in the cache.
 *     After this time, the data is expired.
 *
 * @param integer $version
 *     The version to store along with the cached data. This version is
 *     used by the {@link phorum_api_cache_get()} function to check whether
 *     the cached data has expired or not.
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_put(
    $type, $key, $data, $ttl = PHORUM_CACHE_DEFAULT_TTL, $version = NULL)
{
    global $PHORUM;

    // Generate the path for the file that is used to cache the data.
    list ($path, $file) = phorum_api_cache_mkpath($type, $key);

    // Create the cache path if it does not already exist.
    if (!file_exists($path))
    {
        if (!phorum_api_cache_mkdir($path)) {
            return FALSE;
        }
    }

    // Write the cache file.
    $ttl_time = time() + $ttl;
    if (!($fp = @fopen("$path/$file", "w"))) {
        return FALSE;
    }
    $ret = fwrite($fp, serialize(array($ttl_time, $data, $version)));
    if (!$ret || !fclose($fp)) {
      @unlink("$path/$file");
      return FALSE;
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_api_cache_remove()
/**
 * Remove an object from the cache
 *
 * @param string $type
 *     A name for the group of data that is being cached.
 *     Examples are "user" and "message".
 *
 * @param string $key
 *     A unique key that identifies the object that has to be removed.
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_remove($type, $key)
{
    global $PHORUM;

    $ret  = TRUE;

    // Generate the path for the file that is used to cache the data.
    list ($path, $file) = phorum_api_cache_mkpath($type, $key);

    if (file_exists("$path/$file")) {
        $ret = @unlink("$path/$file");
    }

    return $ret;
}
// }}}

// {{{ Function: phorum_api_cache_purge()
/**
 * Remove all expired objects from the cache.
 *
 * @param boolean $full
 *     If TRUE, then the full cache will be expired, not only the
 *     expired part of the cache.
 *
 * @return string
 *     A string describing the result status. This is used by the
 *     cache purging screen in the admin interface to show the result.
 */
function phorum_api_cache_purge($full = FALSE)
{
    global $PHORUM;

    $cache_path = phorum_api_cache_mkpath();
    list ($total, $purged, $dummy) =
      phorum_api_cache_purge_recursive($cache_path, "", 0, 0, $full);

    // Return a report about the purging action.
    return "Finished purging the file based data cache<br/>\n" .
           "Purged " . phorum_api_format_filesize($purged) . " of " .
           phorum_api_format_filesize($total) . "<br/>\n";
}
// }}}

// {{{ Function: phorum_api_cache_clear()
/**
 * Remove all objects from the cache.
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_clear()
{
    global $PHORUM;

    $cache_path = phorum_api_cache_mkpath();
    $ret = TRUE;

    if (!empty($cache_path) && $cache_path != "/") {
        $ret = phorum_api_cache_rmdir($cache_path);
    }

    return $ret;
}
// }}}

// {{{ Function: phorum_api_cache_check()
/**
 * Check the cache functionality
 *
 * @return NULL|string
 *     This function returns NULL if no problems are found or a string
 *     describing the problem when one is found.
 */
function phorum_api_cache_check()
{
    $retval = phorum_api_cache_get('check','connection');

    // only retry the cache check if last check was more than 1 hour ago
    $data = time();
    if ($retval === NULL || $retval < ($data - 3600))
    {
        phorum_api_cache_put('check', 'connection', $data, 7200);

        $gotten_data = phorum_api_cache_get('check', 'connection');

        if ($gotten_data !== $data) {
            return "Data that was put in the file cache could not be " .
                   "retrieved successfully afterwards.";
        }
    }

    return NULL;
}
// }}}

// ----------------------------------------------------------------------
// Functions below here are helper functions that are not part of the
// file cache layer.
// ----------------------------------------------------------------------

// {{{ Function: phorum_api_cache_mkpath()
/**
 * Generate the storage path for a given cache type + key or the storage
 * path for all data.
 *
 * @param NULL|string $type
 *     The type of cache data. When NULL (the default), the path to
 *     the directory where all data is cached is returned.
 * @param NULL|string $key
 *     The unique key for the cache data.
 * @return string|array
 *     An array containing the path and the filename for the cache file
 *     or the path to all cached data when $type was NULL.
 */
function phorum_api_cache_mkpath($type = NULL, $key = NULL)
{
    global $PHORUM;

    $md5     = md5($key);
    $pathlen = PHORUM_FILE_CACHE_DEPTH * PHORUM_FILE_CACHE_DIRLEN;

    $sepkey  = $PHORUM['CACHECONFIG']['separation_key'];
    $path    = $PHORUM['CACHECONFIG']['directory'] . '/' . md5($sepkey);

    if ($type === NULL) return $path;

    $path .= "/$type/" . wordwrap(
        substr($md5, 0, $pathlen),
        PHORUM_FILE_CACHE_DIRLEN, '/', TRUE
    );

    $file    = substr($md5, $pathlen) . '.php';

    return array($path, $file);
}
// }}}

// {{{ Function: phorum_api_cache_purge_recursive()
/**
 * Recursively delete all files/dirs in a directory.
 *
 * This is a helper function of the file based caching backend.
 *
 * @param string $dir
 *      A string holding the base directory.
 * @param string $subdir
 *      A string holding the subdirectory to go through for deletion.
 * @param integer $total
 *      A counter which stores how much data was found.
 * @param integer $purged
 *      A counter which stores how much data was deleted.
 * @param boolean $full
 *      A flag to tell that all contents not depending on if they are expired
 *      or not are to be deleted.
 * @return array
 *     An array containing three elements:
 *     - The total size of data found.
 *     - The total size of purged data.
 *     - A flag telling if there was something purged at all.
 */
function phorum_api_cache_purge_recursive(
    $dir, $subdir, $total, $purged, $full)
{
    // return at once if the given path isn't a directory
    if (!is_dir("$dir/$subdir")) {
        return array($total, $purged, FALSE);
    }

    $dh = opendir ("$dir/$subdir");
    // check if we could open that directory
    if (! $dh) {
        die ("Can't open the directory " . htmlspecialchars("$dir/$subdir"));
    }

    $subdirs = array();
    $did_purge = FALSE;
    while ($entry = readdir($dh))
    {
        // ignore the common entries
        if ($entry == "." || $entry == "..") continue;

        // store subdirectories for later recursive deletion
        if (is_dir("$dir/$subdir/$entry")) {
            $subdirs[] = "$subdir/$entry";
        }
        elseif (
            substr($entry, -4, 4) === ".php" &&
            is_file("$dir/$subdir/$entry")
        ) {
            $contents = @file_get_contents("$dir/$subdir/$entry");
            $total += strlen($contents);
            $data = unserialize($contents);

            // check if the data is expired or everything is supposed to be
            // deleted anyway
            if ( $full || ($data[0] < time()) ) {
                @unlink("$dir/$subdir/$entry");
                $did_purge = TRUE;
                $purged += strlen($contents);
            }
        }
    }

    closedir($dh);

    // go through the subdirectories as found before
    foreach ($subdirs as $s)
    {
        list ($total, $purged, $sub_did_purge) =
            phorum_api_cache_purge_recursive($dir, $s, $total, $purged, $full);
        if ($sub_did_purge) $did_purge = TRUE;
    }

    // Now just see if we can remove the subdir. We'll be able to
    // in case the directory is empty. This will effectively clean up
    // stale directories.
    if ($did_purge) {
        @rmdir("$dir/$subdir");
    }

    return array($total, $purged, $did_purge);
}
// }}}

// {{{ Function: phorum_api_cache_mkdir()
/**
 * Recursively create a directory tree.
 *
 * This is a helper function of the file based caching backend.
 *
 * @param string $path
 *          The path to create.
 *
 * @return boolean
 *          A flag reporting success or failure of the mkdir
 */
function phorum_api_cache_mkdir($path)
{
    if (empty($path))  return FALSE;
    if (is_dir($path)) return TRUE;
    if (!phorum_api_cache_mkdir(dirname($path))) return FALSE;
    @mkdir($path);
    return TRUE;
}
// }}}

// {{{ Function: phorum_api_cache_rmdir()
/**
 * Recursively delete all files/dirs in a directory.
 *
 * This is a helper function of the file based caching backend.
 *
 * We suspend the event logging module if it is enabled here,
 * because we might be trying to remove non empty directories,
 * resulting in harmless PHP warnings.*
 *
 * @param string $path
 *          The path to remove.
 *
 * @return boolean
 *          A flag currently reporting always TRUE.
 */
function phorum_api_cache_rmdir( $path )
{
    if (defined('EVENT_LOGGING')) phorum_mod_event_logging_suspend();

    $stack[] = $path;
    $dirs[]  = $path;

    while (count($stack))
    {
        $path = array_shift($stack);
        $dir  = opendir($path);
        while ($entry = readdir($dir))
        {
            if (is_file($path . "/" . $entry)) {
                @unlink($path."/".$entry);
            }
            elseif (is_dir($path . "/" . $entry) &&
                    $entry != '.' && $entry != '..')
            {
                array_unshift($dirs, $path . "/" . $entry);
                $stack[] = $path . "/" . $entry;
            }
        }
        closedir($dir);
    }

    foreach($dirs as $dir){
        @rmdir($dir);
    }

    if (defined('EVENT_LOGGING')) phorum_mod_event_logging_resume();
    return TRUE;
}
// }}}

?>
