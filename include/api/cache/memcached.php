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
 * This script implements the Memcached caching backend for Phorum.
 *
 * To use this layer, both a memcached server and the "memcache"
 * PHP pecl module are required.
 *
 * For Memcached, see http://www.danga.com/memcached/
 *
 * For the pecl module, see http://pecl.php.net/package/memcache/
 *
 * @package    PhorumAPI
 * @subpackage CacheMemcached
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

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
    $sepkey  = $PHORUM['CACHECONFIG']['separation_key'];

    if (empty($PHORUM['memcache_obj'])) return NULL;

    // Prepare the cache keys to retrieve data for.
    if (is_array($key))
    {
        $getkey = array();
        foreach ($key as $realkey)
        {
            $realkey = md5($sepkey . $realkey);
            $getkey[] = $type . "_" . $realkey;
        }
    }
    else
    {
        $key = md5($sepkey . $key);
        $getkey = $type . "_" . $key;
    }

    // Retrieve data from the Memcache server.
    $ret = @$PHORUM['memcache_obj']->get($getkey);

    // Process the result data if we got some.
    if ($ret !== FALSE)
    {
        if (is_array($getkey))
        {
            // rewriting them as we need to strip out the type :(
            $typelen = strlen($type) + 1;
            foreach ($ret as $retkey => $retdata)
            {
                if (
                    $version == NULL ||
                    ($retdata[1] != NULL && $retdata[1] == $version)
                ) {
                    $ret[substr($retkey,$typelen)] = $retdata[0];
                }

                unset($ret[$retkey]);
            }
        }
        else
        {
            if (
                is_array($ret) && count($ret) != 0 &&
                ($version == NULL || ($ret[1] != NULL && $ret[1] == $version))
            ) {
                $ret = $ret[0];
            } else {
                $ret = NULL;
            }
        }
    }
    // No results received.
    else
    {
        $ret = NULL;
    }

    return $ret;
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
    $type, $key, $data, $ttl = PHORUM_CACHE_DEFAULT_TTL, $version=NULL)
{
    global $PHORUM;

    $sepkey = $PHORUM['CACHECONFIG']['separation_key'];
    $key    = md5($sepkey . $key);

    if (empty($PHORUM['memcache_obj'])) return FALSE;
    return @$PHORUM['memcache_obj']->set(
        $type . "_" . $key, array($data, $version), 0, $ttl
    );
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

    if (empty($PHORUM['memcache_obj'])) return FALSE;
    $sepkey = $PHORUM['CACHECONFIG']['separation_key'];
    $key    = md5($sepkey . $key);
    return @$PHORUM['memcache_obj']->delete($type . "_" . $key, 0);
}
// }}}

// {{{ Function: phorum_api_cache_purge()
/**
 * Remove all expired objects from the cache.
 *
 * Note: for the memcached cache, we have no option to only purge
 * the expired objects. Instead, the full cache will be flushed.
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

    if (empty($PHORUM['memcache_obj'])) {
        return "Memcached cache not purged, connection to memcached failed.";
    }
    phorum_api_cache_clear();
    return "Memcached cache purged";
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

    if (empty($PHORUM['memcache_obj'])) return FALSE;
    return @$PHORUM['memcache_obj']->flush();
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
    global $PHORUM;

    if (!function_exists('memcache_connect')) {
        return "The function memcache_connect() is not available. " .
               "The PHP installation does not have the Memcache " .
               "PECL module enabled.";
    }

    // Connect to the memcached server. If the connection fails, then
    // destroy the memcache object. In this case, caching will not be
    // used during the remaining of the request. We will not return
    // an error for this case, since this might occur when restarting
    // the memcached server. We don't want to drop nasty error messages
    // on the users of the system in such case.
    if (empty($PHORUM['CACHECONFIG']['server'])) {
        $PHORUM['CACHECONFIG']['server'] = 'localhost';
    }
    $PHORUM['memcache_obj'] = new Memcache;
    if (!@$PHORUM['memcache_obj']->connect(
        $PHORUM['CACHECONFIG']['server'],
        $PHORUM['CACHECONFIG']['port']
    )) {
        unset($PHORUM['memcache_obj']);
        return NULL;
    }

    $retval = phorum_api_cache_get('check', 'connection');

    // only retry the cache check if last check was more than 1 hour ago
    $data = time();
    if ($retval === NULL || $retval < ($data - 3600))
    {
        phorum_api_cache_put('check', 'connection', $data, 7200);

        $gotten_data = phorum_api_cache_get('check', 'connection');

        if ($gotten_data !== $data) {
            return "Data that was put in the memcached cache could not be " .
                   "retrieved successfully afterwards.";
        }
    }

    return NULL;
}
// }}}

?>
