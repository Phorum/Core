<?php

/**
 * This file supplies a store backend for OpenID servers and
 * consumers using the Phorum caching functions.
 *
 * PHP versions 4 and 5
 */

/**
 * Require base class for creating a new interface.
 */
require_once 'Auth/OpenID.php';
require_once 'Auth/OpenID/Interface.php';
require_once 'Auth/OpenID/HMAC.php';
require_once 'Auth/OpenID/Nonce.php';

/**
 * This is a store for OpenID associations and nonces which uses the
 * Phorum caching functions.
 *
 * Most of the methods of this class are implementation details.
 * People wishing to just use this store need only pay attention to
 * the constructor.
 *
 * @package OpenID
 */
class Auth_OpenID_PhorumStore extends Auth_OpenID_OpenIDStore {

    var $namespace;
    var $nonce_key;
    var $association_key;

    /**
     * Initializes a new {@link Auth_OpenID_PhorumStore}.
     *
     */
    function Auth_OpenID_PhorumStore($namespace="openid")
    {

        $this->active = true;

        $this->namespace = $namespace;

        $this->nonce_key = "nonces";

        $this->association_dir = "associations";

    }

    function destroy()
    {
        $this->active = false;
    }


    function cleanupNonces()
    {
        global $Auth_OpenID_SKEW;

        $removed = 0;

        $cacheobj = phorum_api_cache_get("openid", $server_url);

        if(isset($cacheobj->nonces)){

            $nonces = $cacheobj->nonces;

            $now = time();

            // Check all nonces for expiry
            foreach ($nonces as $salt=>$timestamp) {
                $timestamp = intval($timestamp, 16);
                if (abs($timestamp - $now) > $Auth_OpenID_SKEW) {
                    unset($cacheobj->nonces[$salt]);
                    $removed += 1;
                }
            }
            if($removed){
                if(!phorum_api_cache_put("openid", $server_url, $cacheobj)){
                    // save failed, none removed
                    $removed = 0;
                }
            }
        }

        return $removed;
    }


    /**
     * Store an association
     */
    function storeAssociation($server_url, $association)
    {

        if (!$this->active) {
            trigger_error("FileStore no longer active", E_USER_ERROR);
            return false;
        }

        $cacheobj = phorum_api_cache_get("openid", $server_url);

        $cacheobj->associations[$association->handle] = $association;

        return phorum_api_cache_put("openid", $server_url, $cacheobj);
    }

    /**
     * Retrieve an association. If no handle is specified, return the
     * association with the most recent issue time.
     *
     * @return mixed $association
     */
    function getAssociation($server_url, $handle = "")
    {
        if (!$this->active) {
            trigger_error("FileStore no longer active", E_USER_ERROR);
            return null;
        }

        $cacheobj = phorum_api_cache_get("openid", $server_url);
        if (!$cacheobj) return null;

        // find the highest issued param
        if(empty($handle)){
            $max = "";
            foreach($cacheobj->associations as $key=>$tmp_assoc){
                if(empty($max) || $max<$tmp_assoc->issued){
                    $handle = $key;
                }
            }
        }

        if(empty($handle) || empty($cacheobj->associations[$handle])){
            return null;
        } else {
            return $cacheobj->associations[$handle];
        }
    }


    /**
     * Remove an association if it exists. Do nothing if it does not.
     *
     * @return bool $success
     */
    function removeAssociation($server_url, $handle)
    {
        if (!$this->active) {
            trigger_error("FileStore no longer active", E_USER_ERROR);
            return null;
        }

        $cacheobj = phorum_api_cache_get("openid", $server_url);

        $ret = true;

        if(isset($cacheobj->associations[$handle])){
            unset($cacheobj->associations[$handle]);
            $ret = phorum_api_cache_put("openid", $server_url, $cacheobj);
        }

        return $ret;
    }

    /**
     * Return whether this nonce is present. As a side effect, mark it
     * as no longer present.
     *
     * @return bool $present
     */
    function useNonce($server_url, $timestamp, $salt)
    {
        global $Auth_OpenID_SKEW;

        if (!$this->active) {
            trigger_error("FileStore no longer active", E_USER_ERROR);
            return null;
        }

        if ( abs($timestamp - time()) > $Auth_OpenID_SKEW ) {
            return False;
        }

        $cacheobj = phorum_api_cache_get("openid", $server_url);

        $ret = true;

        if(!isset($cacheobj->nonces[$salt])){
            $cacheobj->nonces[$salt]=$timestamp;
            $ret = phorum_api_cache_put("openid", $server_url, $cacheobj);
        } else {
            $ret = false;
        }

        return $ret;

    }


    function clean()
    {
        if (!$this->active) {
            trigger_error("FileStore no longer active", E_USER_ERROR);
            return null;
        }

        $cacheobj = phorum_api_cache_get("openid", $server_url);

        $change = false;
        $ret = true;

        if(isset($cacheobj->nonces)){
            unset($cacheobj->nonces);
            $change = true;
        }

        if(isset($cacheobj->associations)){
            unset($cacheobj->associations);
            $change = true;
        }

        if($change){
            $ret = phorum_api_cache_put("openid", $server_url, $cacheobj);
        }

        return $ret;
    }



    function cleanupAssociations()
    {
        $removed = 0;

        $cacheobj = phorum_api_cache_get("openid", $server_url);

        if(!empty($cacheobj->associations)){

            foreach ($cacheobj->associations as $key=>$assoc) {
                if ($assoc->getExpiresIn() == 0) {
                    unset($cacheobj->associations[$key]);
                    $removed += 1;
                }
            }

            if($removed){
                if(!phorum_api_cache_put("openid", $server_url, $cacheobj)){
                    // save failed, none removed
                    $removed = 0;
                }
            }
        }

        return $removed;
    }

}

?>
