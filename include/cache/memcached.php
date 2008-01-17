<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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

/*
 * Memcached-based caching-layer
 * Memcached -> http://www.danga.com/memcached/
 * using the pecl-module for accessing memcached
 * -> http://pecl.php.net/package/memcache/
 */
if(!defined("PHORUM")) return;

$PHORUM['memcache_obj'] = new Memcache;
$PHORUM['memcache_obj']->connect('127.0.0.1', 11211);
//$PHORUM['memcache_obj'] = memcache_connect('127.0.0.1', 11211);



/*
 * This function returns the cached data for the given key
 * or NULL if no data is cached for this key
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

/*
 * Puts some data into the cache
 * returns number of bytes written (something 'true') or false ...
 * depending of the success of the function
 */
function phorum_cache_put($type,$key,$data,$ttl=PHORUM_CACHE_DEFAULT_TTL,$version=NULL) {
    @$ret=$GLOBALS['PHORUM']['memcache_obj']->set($type."_".$key, array($data,$version), 0, $ttl);
    return $ret;
}


/*
 * Removes a key from the cache
 */
function phorum_cache_remove($type,$key) {

    @$ret=$GLOBALS['PHORUM']['memcache_obj']->delete( $type."_".$key, 0);

    return $ret;
}

function phorum_cache_purge($full = false) {
    phorum_cache_clear();
    return "Memcached cache purged";
}

/*
 * Clears all data from the cache
 */
function phorum_cache_clear() {

    @$ret=$GLOBALS['PHORUM']['memcache_obj']->flush();

    return $ret;
}

/*
 type can be nearly each value to specify a group of data
 used are currently:
 'user'
 'message'
*/


?>
