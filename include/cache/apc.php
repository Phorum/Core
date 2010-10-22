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
////////////////////////////////////////////////////////////////////////////////
// original version of this cache-layer provided by john wards                //
// modified by thomas seifert to work with multi-gets                         //
////////////////////////////////////////////////////////////////////////////////


/*
 * This function returns the cached data for the given key
 * or NULL if no data is cached for this key
 */
function phorum_cache_get($type,$key,$version=NULL) {
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

/*
 * Puts some data into the cache
 * returns number of bytes written (something 'true') or false ...
 * depending of the success of the function
 */
function phorum_cache_put($type,$key,$data,$ttl=PHORUM_CACHE_DEFAULT_TTL,$version=NULL) {

    $ret=apc_store($type."_".$key, array($data,$version), $ttl);
    return $ret;
}


/*
 * Removes a key from the cache
 */
function phorum_cache_remove($type,$key) {

    $ret=apc_delete( $type."_".$key);

    return $ret;
}

function phorum_cache_purge($full = false) {
    phorum_cache_clear();
    return "APC cache purged";
}

/*
 * Clears all data from the cache
 */
function phorum_cache_clear() {

    $ret=apc_clear_cache("user");

    return $ret;
}

/*
 type can be nearly each value to specify a group of data
 used are currently:
 'user'
 'message'
*/


?>
