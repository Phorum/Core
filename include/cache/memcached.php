<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2008  Phorum Development Team                              //
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
 * This script implements the Phorum memcached-based caching-layer.
 *
 * To use this layer, both a memcached server and the "memcache"
 * PHP pecl module are required.
 *
 * For Memcached, see http://www.danga.com/memcached/
 *
 * For the pecl module, see http://pecl.php.net/package/memcache/
 */
if(!defined("PHORUM")) return;

$PHORUM['memcache_obj'] = new Memcache;
@$PHORUM['memcache_obj']->connect('127.0.0.1', 11211);

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
function phorum_cache_get($type,$key,$version=NULL) {
    if(is_array($key)) {
        $getkey=array();
        foreach($key as $realkey) {
            $getkey[]=$type."_".$realkey;
        }
    } else {
        $getkey=$type."_".$key;
    }

    @$ret = $GLOBALS['PHORUM']['memcache_obj']->get($getkey);

    if($ret!==false){

        if(is_array($getkey)) {
            // rewriting them as we need to strip out the type :(
            $typelen=(strlen($type)+1);
            foreach($ret as $retkey => $retdata) {
                if ($version == NULL ||
                    ($retdata[1] != NULL && $retdata[1] == $version))
                        $ret[substr($retkey,$typelen)]=$retdata[0];

                unset($ret[$retkey]);
            }
        } else {
            if ( is_array($ret) && count($ret) != 0 &&
                 ($version == NULL || ($ret[1] != NULL && $ret[1] == $version)) )
                $ret = $ret[0];
            else
                $ret = NULL;
        }

    } else {

        $ret = NULL;
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
 *     used by the {@link phorum_cache_get()} function to check whether
 *     the cached data has expired or not.
 * 
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_cache_put($type,$key,$data,$ttl=PHORUM_CACHE_DEFAULT_TTL,$version=NULL) {
    @return $GLOBALS['PHORUM']['memcache_obj']->set(
        $type."_".$key, array($data,$version), 0, $ttl
    );
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
 */
function phorum_cache_remove($type,$key) {

    @$ret=$GLOBALS['PHORUM']['memcache_obj']->delete( $type."_".$key, 0);

    return $ret;
}

function phorum_cache_purge($full = false) {
    phorum_cache_clear();
    return "Memcached cache purged";
}

/**
 * Removes all objects from the cache.
 */
function phorum_cache_clear() {

    @$ret=$GLOBALS['PHORUM']['memcache_obj']->flush();

    return $ret;
}


?>
