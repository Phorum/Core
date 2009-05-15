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

/**
 * This script implements functions related to Phorum output.
 *
 * @package    PhorumAPI
 * @subpackage Output
 * @copyright  2008, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// The date format to use in HTTP headers.
define('HTTPDATE', 'D, d M Y H:i:s \G\M\T');

// {{{ Function: phorum_api_output_last_modify_time()
/**
 * Check if an If-Modified-Since header is in the request. If yes, then
 * check if the provided content modification time lies before the time
 * from that header.
 *
 * If yes, then the content did not change and we return
 * a HTTP 304 status (Not Modified) to notify the browser about this.
 * The browser can then use the cached content.
 *
 * If no, then a Last-Modified header is sent.
 *
 * @param int $last_modified
 *     Epoch timestamp for the last time that the content changed.
 */
function phorum_api_output_last_modify_time($last_modified)
{
    if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) 
    {
        $header = preg_replace('/;.*$/','',$_SERVER['HTTP_IF_MODIFIED_SINCE']);
        $modified_since = strtotime($header);
        
        if ($modified_since >= $last_modified)
        {
            $proto = empty($_SERVER['SERVER_PROTOCOL'])
                   ? 'HTTP/1.0' : $_SERVER['SERVER_PROTOCOL'];
            header("$proto 304 Not Modified");
            header('Status: 304');
            exit(0);
        }
    }

    // Set the Last-Modified header, so the browser can use that
    // on the next request to bootstrap this client side caching mechanism.
    header("Last-Modified: " . date("r", $last_modified));
}
// }}}

// {{{ Function: phorum_api_output_cache_max_age()
/**
 * Send headers to tell the browser that the output can be cached
 * and for how long.
 *
 * @param integer $max_age
 *     The number of seconds that the content may be cached by the browser.
 */
function phorum_api_output_cache_max_age($max_age)
{
    settype($max_age, 'int');    

    header('Cache-Control: max-age='.$max_age);
    header('Expires: ' . gmdate(HTTPDATE, time()+$max_age));
}
// }}}

// {{{ Function: phorum_api_output_cache_disable()
/**
 * Send headers to tell the browser that the output should not be cached.
 */
function phorum_api_output_cache_disable()
{
    // Set an expire date in the past.
    header('Expires: ' . gmdate(HTTPDATE, time() - 99999));

    // Always modified by now.
    header('Last-Modified: ' . gmdate(HTTPDATE, time()));

    // HTTP/1.1
    header('cache-Control: no-store, no-cache, must-revalidate');
    header('cache-Control: post-check=0, pre-check=0', FALSE);

    // HTTP/1.0
    header('Pragma: no-cache');
}
// }}}

?>
