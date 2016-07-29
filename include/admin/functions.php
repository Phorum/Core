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

if (!defined("PHORUM_ADMIN")) return;

require_once './include/api/forums.php';

/**
 * Display an ok message for the admin interface.
 *
 * @param string $error
 *   The error message to show. The caller must take care of escaping HTML.
 */
function phorum_admin_error($error)
{
    echo "<div class=\"PhorumAdminError\">$error</div>\n";
}

/**
 * Display an ok message for the admin interface.
 *
 * @param string $error
 *   The ok message to show. The caller must take care of escaping HTML.
 */
function phorum_admin_okmsg($error)
{
    echo "<div class=\"PhorumAdminOkMsg\">$error</div>\n";
}

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
 * @param boolean $return_raw
 *   When FALSE (the default), then the returned URL is HTML encoded.
 *   When TRUE, then the raw URL is returned.
 *
 * @return string
 *   The URL for the admin interface.
 */
function phorum_admin_build_url($input_args = NULL, $return_raw = FALSE)
{
    global $PHORUM;

    $url = $PHORUM["admin_http_path"];

    // The base URL was requested.
    if ($input_args === NULL || $input_args === '') {
        return $return_raw ? $url : htmlspecialchars($url);
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

    return $return_raw ? $url : htmlspecialchars($url);
}

/**
 * @param integer $flag
 *   $flag can be 0, 1, 2 or 3
 *   0 = all forums / folders
 *   1 = all forums
 *   2 = only forums + vroot-folders (used in banlists)
 *   3 = only vroot-folders
 *
 * @param integer $vroot
 *   This parameter can be -1, 0 or > 0
 *    -1 returns forums from all vroots
 *     0 returns only forums / folders from the root (vroot = 0)
 *   > 0 returns only forums / folders within the given vroot
 */
function phorum_get_forum_info($flag = 0, $vroot = -1)
{
    $folders = array();

    $forums = phorum_api_forums_get(
        NULL, NULL, NULL, NULL,
        PHORUM_FLAG_INCLUDE_INACTIVE
    );

    foreach ($forums as $forum)
    {
        if ((
             $flag == 0 ||
             ($forum['folder_flag'] == 0 && $flag != 3) ||
             ($flag==2 && $forum['vroot'] > 0 && $forum['vroot'] == $forum['forum_id']) ||
             ($flag==3 && $forum['vroot'] == $forum['forum_id'])
            )
            && ($vroot == -1 || $vroot == $forum['vroot'])
           )
        {
            // Build a string path for the current forum record.
            $parts = $forum['forum_path'];
            array_shift($parts);
            $path = implode('::', $parts);

            // When we are not requesting vroot folders specifically,
            // then add an indication when the current forum record
            // is a vroot folder.
            if ($flag != 3 &&
                $forum['vroot'] &&
                $forum['vroot'] == $forum['forum_id']) {
                $path.=" (Virtual Root)";
            }

            $folders[$forum["forum_id"]] = $path;
        }
    }

    asort($folders, SORT_STRING);

    return $folders;
}

?>
