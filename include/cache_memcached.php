<?php
/*
 * Memcached-based caching-layer
 * Memcached -> http://www.danga.com/memcached/
 * using the pecl-module for accessing memcached
 * -> http://pecl.php.net/package/memcache/
 */
if(!defined("PHORUM")) return;
 
$PHORUM['memcache_obj'] = memcache_connect('127.0.0.1', 11211);



/*
 * This function returns the cached data for the given key 
 * or NULL if no data is cached for this key
 */
function phorum_cache_get($type,$key) {

    $ret=memcache_get($GLOBALS['PHORUM']['memcache_obj'],$type."_".$key);
    if($ret === false) 
    	$ret=NULL;
    
    return $ret;
    
}

/*
 * Puts some data into the cache 
 * returns number of bytes written (something 'true') or false ... 
 * depending of the success of the function
 */
function phorum_cache_put($type,$key,$data,$ttl=PHORUM_CACHE_DEFAULT_TTL) {
	
	$ret=memcache_set($GLOBALS['PHORUM']['memcache_obj'], $type."_".$key, $data, 0, $ttl);
    return $ret;   
}


/*
 * Removes a key from the cache
 */
function phorum_cache_remove($type,$key) {

    $ret=memcache_delete($GLOBALS['PHORUM']['memcache_obj'], $type."_".$key, 0);
    
    return $ret;
} 

/*
 * Clears all data from the cache
 */
function phorum_cache_clear() {
    $ret=memcache_flush($GLOBALS['PHORUM']['memcache_obj']);
    
    return $ret;   
}

/*
 type can be nearly each value to specify a group of data
 used are currently:
 'user'
*/


?>