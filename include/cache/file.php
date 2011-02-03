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
 * memcached-, mmcache/eaccelerator-layer
 */
if (!defined("PHORUM")) return;

/* Only load the caching mechanism if we have a cache directory configured. */
if(!isset($PHORUM["cache"])) return;


/* initializing our real cache-dir */
$PHORUM['real_cache']=$PHORUM['cache']."/".md5(__FILE__);

/*
 * This function returns the cached data for the given key(s)
 * or NULL if no data is cached.
 */
function phorum_cache_get($type,$key,$version=NULL) {

    $partpath=$GLOBALS['PHORUM']['real_cache']."/".$type;

    if(is_array($key)) {
        $ret=array();
        foreach($key as $realkey) {
            $path=$partpath."/".wordwrap(md5($realkey), PHORUM_CACHE_SPLIT, "/", true)."/data.php";
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
        $path=$partpath."/".wordwrap(md5($key), PHORUM_CACHE_SPLIT, "/", true)."/data.php";
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

/*
 * Puts some data into the cache
 * returns number of bytes written (something 'true') or false ...
 * depending of the success of the function
 */
function phorum_cache_put($type,$key,$data,$ttl=PHORUM_CACHE_DEFAULT_TTL,$version = NULL) {

    $path=$GLOBALS['PHORUM']['real_cache']."/$type/".wordwrap(md5($key), PHORUM_CACHE_SPLIT, "/", true);
    if(!file_exists($path)){
        phorum_cache_mkdir($path);
    }
    $file=$path."/data.php";
    $ttl_time=time()+$ttl;
    if (!($fp=fopen($file,"w"))) { 
        return false; 
    } 
    $ret=fwrite($fp,serialize(array($ttl_time,$data,$version)));
    fclose($fp);

    return $ret;
}

/*
 * Removes a key from the cache
 */
function phorum_cache_remove($type,$key) {

    $ret  =true;
    $path=$GLOBALS['PHORUM']['real_cache']."/$type/".wordwrap(md5($key), PHORUM_CACHE_SPLIT, "/", true)."/data.php";
    if(file_exists($path)) {
        $ret=@unlink($path);
    }

    return $ret;
}

/*
 * Clears all data from the cache
 */
function phorum_cache_clear() {
    $dir = $GLOBALS['PHORUM']['real_cache'];
    $ret = false;

    if(!empty($dir) && $dir != "/") {
        phorum_cache_rmdir($dir);
    }

    return $ret;
}

/*
 * Purges stale entries from the cache (mainly used by the admin panel)
 */
function phorum_cache_purge($full = false) {
    list ($total, $purged, $dummy) =
      phorum_cache_purge_recursive($GLOBALS['PHORUM']['real_cache'], "", 0, 0, $full);

    // Return a report about the purging action.
    require_once('./include/format_functions.php');
    return "Finished purging the file based data cache<br/>\n" .
           "Purged " . phorum_filesize($purged) . " of " .
           phorum_filesize($total) . "<br/>\n";
}
function phorum_cache_purge_recursive($dir, $subdir, $total, $purged, $full) {
    if (!is_dir("$dir/$subdir")) return array($total, $purged, false);
    $dh = opendir ("$dir/$subdir");
    if (! $dh) die ("Can't opendir " . htmlspecialchars("$dir/$subdir"));
    $subdirs = array();
    $did_purge = false;
    while ($entry = readdir($dh)) {
        if ($entry == "." || $entry == "..") continue;
        if (is_dir("$dir/$subdir/$entry")) {
            $subdirs[] = "$subdir/$entry";
        } elseif ($entry == "data.php" && is_file("$dir/$subdir/$entry")) {
            $contents = @file_get_contents("$dir/$subdir/$entry");
            $total += strlen($contents);
            $data = unserialize($contents);
            if ( $full || ($data[0] < time()) ) {
                @unlink("$dir/$subdir/$entry");
                $did_purge = true;
                $purged += strlen($contents);
            }
        }
    }
    closedir($dh);

    foreach ($subdirs as $s) {
        list ($total, $purged, $sub_did_purge) =
            phorum_cache_purge_recursive($dir, $s, $total, $purged, $full);
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

/*
 type can be nearly each value to specify a group of data
 used are currently:
 'user'
*/

// helper functions

// recursively deletes all files/dirs in a directory

// recursively creates a directory-tree
function phorum_cache_mkdir($path) {
    if(empty($path)) return false;
    if(is_dir($path)) return true;
    if (!phorum_cache_mkdir(dirname($path))) return false;
    @mkdir($path);
    return true;
}

// Recursively deletes all files/dirs in a directory.
// We suspend the event logging module if it is enabled here,
// because we might be trying to remove non empty directories,
// resulting in harmless PHP warnings.
function phorum_cache_rmdir( $path )
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
    return;
}

?>
