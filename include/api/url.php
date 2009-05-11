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
 * This script implements functions for working with Phorum URLs.
 *
 * @package    PhorumAPI
 * @subpackage URL
 * @copyright  2008, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

global $PHORUM;

// {{{ Variable definitions

/**
 * Descriptions of standard Phorum page URL types and their options.
 * The keys in this array describe the type of Phorum URL.
 * The values are arrays, containing the following three elements:
 * - The name of the Phorum page to link to;
 * - A constant, telling whether the forum_id has to be added to the URL;
 * - A boolean, telling whether the GET vars have to be added to the URL.
 */
$PHORUM['API']['url_patterns'] = array
(
    PHORUM_BASE_URL                 => array("",           PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_CHANGES_URL              => array("changes",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_CONTROLCENTER_ACTION_URL => array("control",    PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_CONTROLCENTER_URL        => array("control",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_CSS_URL                  => array("css",        PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_JAVASCRIPT_URL           => array("javascript", PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_FEED_URL                 => array("feed",       PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_FOLLOW_ACTION_URL        => array("follow",     PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_FOLLOW_URL               => array("follow",     PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_INDEX_URL                => array("index",      PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_LIST_URL                 => array("list",       PHORUM_URL_COND_FORUM_ID, true),
    PHORUM_LOGIN_ACTION_URL         => array("login",      PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_LOGIN_URL                => array("login",      PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_MODERATION_ACTION_URL    => array("moderation", PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_MODERATION_URL           => array("moderation", PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_PM_ACTION_URL            => array("pm",         PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_PM_URL                   => array("pm",         PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_POSTING_URL              => array("posting",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_POSTING_ACTION_URL       => array("posting",    PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_PROFILE_URL              => array("profile",    PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_REDIRECT_URL             => array("redirect",   PHORUM_URL_NO_FORUM_ID,   true),
    PHORUM_REGISTER_ACTION_URL      => array("register",   PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_REGISTER_URL             => array("register",   PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_REPORT_URL               => array("report",     PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_SEARCH_ACTION_URL        => array("search",     PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_SEARCH_URL               => array("search",     PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_SUBSCRIBE_URL            => array("subscribe",  PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_ADDON_URL                => array("addon",      PHORUM_URL_ADD_FORUM_ID,  true),
    PHORUM_AJAX_URL                 => array("ajax",       PHORUM_URL_NO_FORUM_ID,   false),
    PHORUM_OPENID_URL               => array("openid",     PHORUM_URL_NO_FORUM_ID,   false),
);

// }}}

// {{{ Function: phorum_api_url_get()
/**
 * Generate a Phorum URL.
 */
function phorum_api_url_get()
{
    global $PHORUM;

    $argv = func_get_args();

    $url = "";
    $suffix = "";
    $pathinfo = NULL;
    $add_forum_id = false;
    $add_get_vars = true;

    $type = array_shift($argv);

    if (!isset($PHORUM['API']['url_patterns'][$type]))
    {
        // these URL types need extra care
        // please do not add anything to this unless it is a last resort

        switch($type)
        {
            case PHORUM_READ_URL:
                $name = "read";
                $add_forum_id = true;
                $add_get_vars = true;
                if (!empty( $argv[1]) &&
                    (is_numeric($argv[1]) || $argv[1] == '%message_id%')) {
                    $suffix = "#msg-$argv[1]";
                }
                break;

            case PHORUM_REPLY_URL:
                if (isset($PHORUM["reply_on_read_page"]) &&
                    $PHORUM["reply_on_read_page"])
                {
                    $name = "read";
                    $suffix = "#REPLY";
                }
                else
                {
                    $name = "posting";
                    // For reply on a separate page, we call posting.php on
                    // its own. In that case argv[0] is the editor mode we
                    // want to use (reply in this case). Currently, the thread
                    // id is in argv[0], but we don't need that one for
                    // posting.php. So we simply replace argv[0] with the
                    // correct argument.
                    $argv[0] = "reply";
                }
                $add_get_vars = true;
                $add_forum_id = true;
                break;

            case PHORUM_FOREIGN_READ_URL:
                $name = "read";
                $add_forum_id = false;
                $add_get_vars = true;
                if (!empty($argv[2]) && is_numeric($argv[2])) {
                    $suffix = "#msg-$argv[2]";
                }
                break;

            case PHORUM_FILE_URL:
                $name = "file";
                $add_forum_id = true;

                // If a filename=... parameter is set, then change that
                // parameter to a URL path, unless this feature is not
                // enabled in the admin setup.
                $unset = array();
                if (!empty($PHORUM['file_url_uses_pathinfo']))
                {
                    $file_id  = NULL;
                    $filename = NULL;
                    $download = '';

                    foreach ($argv as $id => $arg)
                    {
                        if (substr($arg, 0, 5) == 'file=')
                        {
                            $file_id = substr($arg, 5);
                            // %file_id% is sometimes used for creating URL
                            // templates, so we should not mangle that one.
                            if ($file_id != '%file_id%') {
                                settype($file_id, 'int');
                            }
                            $unset[] = $id;
                        } elseif (substr($arg, 0, 9) == 'filename=') {
                            $filename = urldecode(substr($arg, 9));
                            // %file_name% is sometimes used for creating URL
                            // templates, so we should not mangle that one.
                            if ($filename != '%file_name%') {
                                $filename = preg_replace(
                                    '/[^\w\_\-\.]/', '_', $filename);
                                $filename = preg_replace(
                                    '/_+/', '_', $filename);
                            }
                            $unset[] = $id;
                        } elseif (substr($arg, 0, 9) == 'download=') {
                            $download = 'download/';
                            $unset[] = $id;
                        }
                    }
                    if ($file_id !== NULL && $filename !== NULL) {
                        foreach ($unset as $id) unset($argv[$id]);
                        $add_forum_id = false;
                        $pathinfo = "/$download{$PHORUM['forum_id']}/" .
                                    "$file_id/$filename";
                    }
                }
                break;

            // this is for adding own generic urls
            case PHORUM_CUSTOM_URL:
                // first arg is our page
                $name = array_shift($argv);
                // second arg determines if we should add the forum_id
                $add_forum_id = (bool) array_shift($argv);
                break;

            default:
                trigger_error(
                    "phorum_api_url_get(): Illegal URL type " .
                    "\"$type\" used",
                    E_USER_ERROR
                );
                break;
        }
    }
    else
    {
        list ($name, $add_forum_id, $add_get_vars) =
            $PHORUM['API']['url_patterns'][$type];

        // Add forum id if setting is conditional and there are no params.
        if ($add_forum_id==PHORUM_URL_COND_FORUM_ID && count($argv) == 0) {
            $add_forum_id=PHORUM_URL_ADD_FORUM_ID;
        }
    }

    $query_string = '';

    $url = $PHORUM['http_path'] . '/';

    if ($name) {
        $url .= $name . '.' . PHORUM_FILE_EXTENSION;
    }

    if ($add_forum_id == PHORUM_URL_ADD_FORUM_ID) {
        $query_string = $PHORUM["forum_id"] . ",";
    }

    if (count($argv) > 0) {
        $query_string .= implode(",", $argv ) . ",";
    }

    if ($add_get_vars && !empty($PHORUM["DATA"]["GET_VARS"])) {
        $query_string .= implode(",", $PHORUM["DATA"]["GET_VARS"]) . ",";
    }

    if ($query_string) {
        $query_string = substr($query_string, 0, -1 ); // trim off ending ,
    }

    /**
     * @todo document the 'url_build' hook.
     */
    if (isset($PHORUM['hooks']['url_build'])) {
        $query_items = explode(',', $query_string);
        $url = phorum_hook(
            'url_build', NULL,
            $name, $query_items, $suffix, $pathinfo
        );
        if ($url) return $url;
    }

    // Allow full overriding of the URL building mechanism by
    // implementing the function "phorum_custom_get_url()".
    // This is a legacy solution (a hook avant la lettre).
    // When writing new code, then please use the "url_build"
    // hook instead.
    if (function_exists('phorum_custom_get_url')) {
        $query_items = $query_string == ''
                     ? array() : explode(',', $query_string);
        $url = phorum_custom_get_url(
            $name, $query_items, $suffix, $pathinfo
        );
    }
    // The default URL construction.
    else
    {
        if ($pathinfo !== null) $url .= $pathinfo;
        if ($query_string) $url .= "?" . $query_string;
        if (!empty($suffix)) $url .= $suffix;
    }

    return $url;
}
// }}}

// {{{ Function: phorum_api_url_base()
/**
 * Returns the Phorum base URL.
 *
 * @return string
 *     The base URL.
 */
function phorum_api_url_base()
{
    return phorum_api_url_get(PHORUM_BASE_URL);
}
// }}}

// {{{ Function: phorum_api_url_current()
/**
 * Determines the current page's URL
 *
 * At several places in code, we need to produce the current URL for use in
 * redirects and forms. This function does that to the best of our ability
 *
 * @param boolean $include_query_string
 *     If TRUE, the query string is appended to the URL.
 *     If FALSE the query string is left off.
 *
 * @return string
 *     The current URL.
 */
function phorum_api_url_current($include_query_string = TRUE)
{
    $url = "";

    if (isset($_SERVER['SCRIPT_URI']))
    {
        $url = $_SERVER['SCRIPT_URI'];
    }
    else
    {
        // On some systems, the port is also in the HTTP_HOST, so we
        // need to strip the port if it appears to be in there.
        if (preg_match('/^(.+):(.+)$/', $_SERVER['HTTP_HOST'], $m))
        {
            $host = $m[1];
            if (!isset($_SERVER['SERVER_PORT'])) {
                $_SERVER['SERVER_PORT'] = $m[2];
            }
        } else {
            $host = $_SERVER['HTTP_HOST'];
        }

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!="off")
                  ? "https" : "http";
        $port = ($_SERVER["SERVER_PORT"]!=443 && $_SERVER["SERVER_PORT"]!=80)
              ? ':'.$_SERVER["SERVER_PORT"] : "";
        $url = $protocol.'://'.$host.$port.$_SERVER['PHP_SELF'];
    }

    if ($include_query_string && !empty($_SERVER["QUERY_STRING"])) {
        $url .= "?" . $_SERVER["QUERY_STRING"];
    }

    return $url;
}
// }}}

?>
