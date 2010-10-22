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
//                                                                            //
// original version of this cache-layer provided by john wards                //
// modified by thomas seifert to work with multi-gets                         //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


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
    if(is_array($key)) {
        $ret=array();
        foreach($key as $realkey) {
            $getkey=$type."_".$realkey;
            $data = apc_fetch($getkey);

            if($data !== false) {
                if ($version == NULL ||
                    ($data[1] != NULL && $data[1] == $version)) {
                    $ret[$realkey]=$data[0];
                }
            }
        }
    } else {
        $getkey=$type."_".$key;
        $data = apc_fetch($getkey);

        if($data !== false) {
            if ($version == NULL ||
                ($data[1] != NULL && $data[1] == $version)) {
                $ret=$data[0];
            }
        } else {
            $ret = false;
        }
    }

    if($ret === false || (is_array($ret) && count($ret) == 0))
        $ret=NULL;

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
function phorum_api_cache_put($type,$key,$data,$ttl=PHORUM_CACHE_DEFAULT_TTL,$version=NULL) {

    $ret=apc_store($type."_".$key, array($data,$version), $ttl);
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

    $ret=apc_delete( $type."_".$key);

    return $ret;
}
/**
 * Delete all expired objects from the cache.
 *
 * Note: for the apc cache, we have no option to only purge
 * the expired objects. Instead, the full cache will be flushed.
 * 
 * @param boolean $full
 *     If true, then the full cache will be expired, not only the
 *     expired part of the cache.
 *
 * @return string
 *     A string describing the result status. This is used by the
 *     cache purging screen in the admin interface to show the result.
 */
function phorum_api_cache_purge($full = false) {
    phorum_api_cache_clear();
    return "APC cache purged";
}

/**
 * Removes all objects from the cache.
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_clear() {

    $ret=apc_clear_cache("user");

    return $ret;
}

/**
 * Checks the cache functionality
 *
 * @return boolean
 *     This function returns TRUE on success or FALSE on failure.
 */
function phorum_api_cache_check() {
    
    $data = time();
    $ret  = false;
    
    $retval = phorum_api_cache_get('check','connection');
    
    // only retry the cache check if last check was more than 1 hour ago
    if($retval === NULL || $retval < ($data-3600)) {
    
        phorum_api_cache_put('check','connection',$data,7200);
        
        $gotten_data = phorum_api_cache_get('check','connection');
        
        if($gotten_data === $data) {
            $ret = true;
        }
    
    } else {
        $ret = true;
    }
    
    return $ret;
}



?>