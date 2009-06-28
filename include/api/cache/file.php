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

/*
 * Simple file-based caching-layer
 * Recommended are some more sophisticated solutions, like
 * memcached-, apc-layer
 */
if (!defined("PHORUM")) return;

/* Only load the caching mechanism if we have a cache directory configured. */
if(!isset($PHORUM["cache"])) return;


/* initializing our real cache-dir */
$PHORUM['real_cache']=$PHORUM['cache']."/".md5(__FILE__);

/**
 * Retrieve an object from the cache.
 *
 * @param string $type 
 *     A name for the group of data that is being cached.
 *     Examples are "user" and "message".
 *
 * @param string $key
 *     A unique key that identifies the object to retrieve.
 *     This could for example be the user_id of a cached user.
 *
 * @param integer $version
 *     The version of the object to retrieve. If the cached object's
 *     version is older than the requested version, then no object
 *     will be returned.
 *
 * @return mixed
 *     This function returns the cached object for the given key
 *     or NULL if no data is cached or if the cached data has expired.
 */
function phorum_api_cache_get($type,$key,$version=NULL) {

    $partpath=$GLOBALS['PHORUM']['real_cache']."/".$type;

    if(is_array($key)) {
        $ret=array();
        foreach($key as $realkey) {
            $path=$partpath."/".wordwrap(md5($realkey), phorum_api_cache_SPLIT, "/", true)."/data.php";
            if(file_exists($path)) {
                // the data is: array($ttl_time,$data,$version)
                // $version might not be set.
                $retval=unserialize(@file_get_contents($path));

                // timeout?
                if($retval[0] < time()) {
                    @unlink($path);
                // version expired?
                } elseif ($version != NULL &&
                          (!isset($retval[2]) || $retval[2] != $version)) {
                    @unlink($path);
                } else {
                    $ret[$realkey]=$retval[1];
                }

                unset($retval);
            }
        }

        if(count($ret) == 0) $ret = NULL;

    } else {
        $path=$partpath."/".wordwrap(md5($key), phorum_api_cache_SPLIT, "/", true)."/data.php";
        if(!file_exists($path)){
            $ret=NULL;
        } else {
            // the data is: array($ttl_time,$data,$version)
            // $version might not be set.
            $retval=unserialize(@file_get_contents($path));

            // timeout?
            if($retval[0] < time()) {
                $ret = NULL;
                @unlink($path);
            // version expired?
            } elseif ($version != NULL &&
                      (!isset($retval[2]) || $retval[2]<$version)) {
                $ret = NULL;
                @unlink($path);
            } else {
                $ret = $retval[1];
            }

            unset($retval);
        }
    }

    return $ret;
}

/**
 * Puts some data into the cache.
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
function phorum_api_cache_put($type,$key,$data,$ttl=phorum_api_cache_DEFAULT_TTL,$version = NULL) {

    $path=$GLOBALS['PHORUM']['real_cache']."/$type/".wordwrap(md5($key), phorum_api_cache_SPLIT, "/", true);
    if(!file_exists($path)){
        phorum_api_cache_mkdir($path);
    }
    $file=$path."/data.php";
    $ttl_time=time()+$ttl;
    $fp=fopen($file,"w");
    $ret=fwrite($fp,serialize(array($ttl_time,$data,$version)));
    fclose($fp);

    return $ret;
}

/**
 * Removes an object from the cache
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
function phorum_api_cache_remove($type,$key) {

    $ret  =true;
    $path=$GLOBALS['PHORUM']['real_cache']."/$type/".wordwrap(md5($key), phorum_api_cache_SPLIT, "/", true)."/data.php";
    if(file_exists($path)) {
        $ret=@unlink($path);
    }

    return $ret;
}

/**
 * Delete all expired objects from the cache.
 *
 * @param boolean $full
 *     If true, then the full cache will be expired, not only the
 *     expired part of the cache.
 *
 * @return string
 *     A string describing the result status. This is used by the
 *     cache purging screen in the admin interface to show the result.
 */
function phorum_api_cache_purge($full = false)
{
    list ($total, $purged, $dummy) =
      phorum_api_cache_purge_recursive($GLOBALS['PHORUM']['real_cache'], "", 0, 0, $full);

    // Return a report about the purging action.
    return "Finished purging the file based data cache<br/>\n" .
           "Purged " . phorum_api_format_filesize($purged) . " of " .
           phorum_api_format_filesize($total) . "<br/>\n";
}

/**
 * Removes all objects from the cache.
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_clear() {
    $dir = $GLOBALS['PHORUM']['real_cache'];
    $ret = false;

    if(!empty($dir) && $dir != "/") {
        phorum_api_cache_rmdir($dir);
    }

    return $ret;
}

/**
 * Checks the cache functionality
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_check() {
    
    $data = "123";
    $ret  = false;
    
    phorum_api_cache_put('check','connection',$data,10);
    
    $gotten_data = phorum_api_cache_get('check','connection');
    
    if($gotten_data === $data) {
        $ret = true;
    }
    
    return $ret;
}

// helper functions

/**
 * Helper function of the file based caching backend - 
 *      recursively deletes all files/dirs in a directory 
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
function phorum_api_cache_purge_recursive($dir, $subdir, $total, $purged, $full) {
    
	// return at once if the given path isn't a directory
	if (!is_dir("$dir/$subdir")) {
    	return array($total, $purged, false);
    }
    
    $dh = opendir ("$dir/$subdir");
    // check if we could open that directory
    if (! $dh) {
    	die ("Can't open the directory " . htmlspecialchars("$dir/$subdir"));
    }
    
    $subdirs = array();
    $did_purge = false;
    while ($entry = readdir($dh)) {
    	// ignore the common entries
        if ($entry == "." || $entry == "..") continue;
        
        // store subdirectories for later recursive deletion
        if (is_dir("$dir/$subdir/$entry")) {
            $subdirs[] = "$subdir/$entry";
        } elseif ($entry == "data.php" && is_file("$dir/$subdir/$entry")) {
            $contents = @file_get_contents("$dir/$subdir/$entry");
            $total += strlen($contents);
            $data = unserialize($contents);
            
            // check if the data is expired or everything is supposed to be 
            // deleted anyway
            if ( $full || ($data[0] < time()) ) {
                @unlink("$dir/$subdir/$entry");
                $did_purge = true;
                $purged += strlen($contents);
            }
        }
        
    }
    closedir($dh);

    // go through the subdirectories as found before
    foreach ($subdirs as $s) {
        list ($total, $purged, $sub_did_purge) =
            phorum_api_cache_purge_recursive($dir, $s, $total, $purged, $full);
        if ($sub_did_purge) $did_purge = true;
    }

    // Now just see if we can remove the subdir. We'll be able to
    // in case the directory is empty. This will effectively clean up
    // stale directories.
    if ($did_purge) {
        @rmdir("$dir/$subdir");
    }
    return array($total, $purged, $did_purge);
}

/**
 * Helper function of the file based caching backend -
 *      recursively creates a directory-tree
 * 
 * @param string $path
 *          The path to create.
 *  
 * @return boolean 
 *          A flag reporting success or failure of the mkdir
 */
function phorum_api_cache_mkdir($path) {
    if(empty($path)) return false;
    if(is_dir($path)) return true;
    if (!phorum_api_cache_mkdir(dirname($path))) return false;
    @mkdir($path);
    return true;
}

/**
 * Helper function of the file based caching backend -
 *      recursively deletes all files/dirs in a directory.
 *      
 * We suspend the event logging module if it is enabled here,
 * because we might be trying to remove non empty directories,
 * resulting in harmless PHP warnings.*      
 *      
 * @param string $path
 *          The path to remove.
 *          
 * @return boolean
 *          A flag currently reporting always true.
 */
function phorum_api_cache_rmdir( $path )
{
    if (defined('EVENT_LOGGING')) phorum_mod_event_logging_suspend();

    $stack[]=$path;
    $dirs[]=$path;

    while(count($stack)){
        $path=array_shift($stack);
        $dir = opendir( $path ) ;
        while ( $entry = readdir( $dir ) ) {
            if ( is_file( $path . "/" . $entry ) ) {
                @unlink($path."/".$entry);
            } elseif ( is_dir( $path . "/" . $entry ) && $entry != '.' && $entry != '..' ) {
                array_unshift($dirs, $path . "/" . $entry)  ;
                $stack[]=$path . "/" . $entry  ;
            }
        }
        closedir( $dir ) ;
    }
    foreach($dirs as $dir){
        @rmdir($dir);
    }

    if (defined('EVENT_LOGGING')) phorum_mod_event_logging_resume();
    return true;
}

?>
