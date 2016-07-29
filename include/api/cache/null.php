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
 * This script implements the NULL caching backend for Phorum.
 *
 * This is a dummy caching layer that does not handle any caching at all.
 * It is automatically used by include/api.php in case a command line script
 * is run in combination with file caching. Because of file permission
 * issues (due to the fact that most webservers run under a different userid
 * than the owner of the website), it's best to not use file caching.
 *
 * @package    PhorumAPI
 * @subpackage Cache
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

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
function phorum_api_cache_get($type, $key, $version = NULL)
{
    return NULL;
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
function phorum_api_cache_remove($type,$key)
{
    return TRUE;
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
    return "No purging is done, because the NULL cache layer is in use.";
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
    return TRUE;
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
    return NULL;
}
// }}}

?>
