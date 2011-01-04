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

/*
 * Simple file-based caching-layer
 * Recommended are some more sophisticated solutions, like
 * memcached-, apc-layer
 */
if (!defined("PHORUM")) return;

/* initializing our real cache-dir */
$PHORUM['real_cache'] = $PHORUM['CACHECONFIG']['directory']."/".md5(__FILE__);

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

    $partpath = $PHORUM['real_cache'] . "/" . $type;

    // Retrieve the cached data.
    foreach ($checkkeys as $checkkey)
    {
        // Generate the path for the file that is used to cache the data.
        $path = $partpath . "/" .
                wordwrap(md5($checkkey), PHORUM_CACHE_SPLIT, "/", TRUE) .
                "/data.php";

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

    // Generate the path for the directory that is used to cache the data.
    $path = $PHORUM['real_cache'] . "/$type/" .
            wordwrap(md5($key), PHORUM_CACHE_SPLIT, "/", TRUE);

    // Create the cache path if it does not already exist.
    if (!file_exists($path))
    {
        if (!phorum_api_cache_mkdir($path)) {
            return FALSE;
        }
    }

    // Write the cache file.
    $file     = $path . "/data.php";
    $ttl_time = time() + $ttl;
    if (!($fp = @fopen($file, "w"))) { 
        return FALSE; 
    } 
    $ret = fwrite($fp, serialize(array($ttl_time, $data, $version)));
    if (!$ret || !fclose($fp)) {
      @unlink($file);
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

    $path = $PHORUM['real_cache'] . "/$type/" .
            wordwrap(md5($key), PHORUM_CACHE_SPLIT, "/", TRUE) . "/data.php";
    if (file_exists($path)) {
        $ret = @unlink($path);
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

    list ($total, $purged, $dummy) =
      phorum_api_cache_purge_recursive($PHORUM['real_cache'], "", 0, 0, $full);

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

    $dir = $PHORUM['real_cache'];
    $ret = TRUE;

    if (!empty($dir) && $dir != "/") {
        $ret = phorum_api_cache_rmdir($dir);
    }

    return $ret;
}
// }}}

// {{{ Function: phorum_api_cache_check()
/**
 * Check the cache functionality
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_check()
{
    $data = time();
    $ret  = FALSE;

    $retval = phorum_api_cache_get('check','connection');

    // only retry the cache check if last check was more than 1 hour ago
    if ($retval === NULL || $retval < ($data-3600))
    {
        phorum_api_cache_put('check','connection',$data,7200);

        $gotten_data = phorum_api_cache_get('check','connection');

        if ($gotten_data === $data) {
            $ret = TRUE;
        }
    }
    else {
        $ret = TRUE;
    }

    return $ret;
}
// }}}

// ----------------------------------------------------------------------
// Functions below here are helper functions that are not part of the
// file storage API.
// ----------------------------------------------------------------------

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
        elseif ($entry == "data.php" && is_file("$dir/$subdir/$entry"))
        {
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
