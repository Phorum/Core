<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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
 * This script implements functions that are used in the admin area.
 *
 * @package    PhorumAPI
 * @subpackage Admin
 * @copyright  2011, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function phorum_api_admin_url()
/**
 * Build a URL for the admin interface.
 *
 * When building admin interface pages, internal URLs should always be generated
 * using this method, since it will take care of adding the CSRF protection
 * token to the URL automatically.
 *
 * @param mixed $input_args
 *   NULL (the default) to retrieve the base URL for the admin interface.
 *   Otherwise a single query parameter string or an array of query parameters
 *   to add to the URL. Note that urlencoding must be handled by the caller.
 *
 * @return string
 *   The URL for the admin interface.
 */
function phorum_api_admin_url($input_args = NULL)
{
    global $PHORUM;

    $url = $PHORUM["admin_http_path"];

    // The base URL was requested. 
    if ($input_args === NULL) {
        return $url;
    }

    // Add a set of request parameters.
    if (is_array($input_args) && count($input_args)) {
        $url .= "?" . implode("&", $input_args);
    }
    // Add a single parameter.
    elseif (!is_array($input_args) && trim($input_args) !== '') {
        $url .= "?" . $input_args;
    }

    // Delete an existing admin token from the URL.
    if (preg_match("!([\&\?]?)(phorum_admin_token=(?:[A-Za-z0-9]*))!", $url, $m)) {
        $separator = $m[1];
        $url = str_replace($m[1].$m[2], '', $url);
    } else {
        $separator = strpos($url, '?') === FALSE ? '?' : '&';
    }

    // Put in a new token when available.
    if (!empty($PHORUM['admin_token'])) {
        $url .= $separator . "phorum_admin_token=" . $PHORUM['admin_token'];
    }

    return $url;
}
// }}}

?>
