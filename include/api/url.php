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
 * This script implements functions for working with Phorum URLs.
 *
 * @package    PhorumAPI
 * @subpackage URL
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Variable and constant definitions

define('PHORUM_LIST_URL',                  1);
define('PHORUM_READ_URL',                  2);
define('PHORUM_FOREIGN_READ_URL',          3);
define('PHORUM_REPLY_URL',                 4);
define('PHORUM_REDIRECT_URL',              5);
define('PHORUM_SEARCH_URL',                6);
define('PHORUM_SEARCH_ACTION_URL',         7);
define('PHORUM_USER_URL',                  8);
define('PHORUM_INDEX_URL',                 9);
define('PHORUM_LOGIN_URL',                10);
define('PHORUM_LOGIN_ACTION_URL',         11);
define('PHORUM_REGISTER_URL',             12);
define('PHORUM_REGISTER_ACTION_URL',      13);
define('PHORUM_PROFILE_URL',              14);
define('PHORUM_SUBSCRIBE_URL',            15);
define('PHORUM_MODERATION_URL',           16);
define('PHORUM_MODERATION_ACTION_URL',    17);
define('PHORUM_CONTROLCENTER_URL',        18);
define('PHORUM_CONTROLCENTER_ACTION_URL', 19);
define('PHORUM_PM_URL',                   20);
define('PHORUM_PM_ACTION_URL',            21);
define('PHORUM_FILE_URL',                 22);
define('PHORUM_GROUP_MODERATION_URL',     23);
define('PHORUM_FOLLOW_URL',               24);
define('PHORUM_FOLLOW_ACTION_URL',        25);
define('PHORUM_REPORT_URL',               26);
define('PHORUM_FEED_URL',                 27);
define('PHORUM_CUSTOM_URL',               28);
define('PHORUM_BASE_URL',                 29);
define('PHORUM_ADDON_URL',                30);
define('PHORUM_CHANGES_URL',              31);
define('PHORUM_CSS_URL',                  32);
define('PHORUM_POSTING_URL',              33);
define('PHORUM_POSTING_ACTION_URL',       34);
define('PHORUM_JAVASCRIPT_URL',           35);
define('PHORUM_AJAX_URL',                 36);
define('PHORUM_FOREIGN_PM_URL',           37);

global $PHORUM;

/**
 * Descriptions of standard Phorum page URL types and their options.
 * The keys in this array describe the type of Phorum URL.
 * The values are arrays, containing the following five elements:
 *
 * - The name of the Phorum page to link to;
 *
 * - A constant, telling whether the forum_id has to be added to the URL.
 *   Options for this constant are:
 *   1 = no
 *   2 = yes
 *   3 = conditional (only if there are no other args in $argv)
 *   4 = add forum_id and (if available) ref_thread_id and ref_message_id
 *       (these are respectively the referring thread and message id,
 *       which are used by the breadcrumbs system to keep track of
 *       thread and message)
 *
 * - A boolean, telling whether the GET vars have to be added to the URL.
 *
 * - An URL suffix to add
 *
 * - The id of the $argv field to append to the suffix. This is only done
 *   if the field contents are numerical or starting with a "%" (so we can
 *   use %template% variables in it). If both the suffix and this id field
 *   are used, but no matching field contents are found, then no suffix is
 *   added at all.
 */
$PHORUM['API']['url_patterns'] = array
(
    PHORUM_BASE_URL                 => array('',           1, TRUE,  '', NULL),
    PHORUM_CHANGES_URL              => array('changes',    2, TRUE,  '', NULL),
    PHORUM_CONTROLCENTER_ACTION_URL => array('control',    1, FALSE, '', NULL),
    PHORUM_CONTROLCENTER_URL        => array('control',    4, TRUE,  '', NULL),
    PHORUM_CSS_URL                  => array('css',        2, TRUE,  '', NULL),
    PHORUM_JAVASCRIPT_URL           => array('javascript', 2, TRUE,  '', NULL),
    PHORUM_FEED_URL                 => array('feed',       1, TRUE,  '', NULL),
    PHORUM_FOLLOW_ACTION_URL        => array('follow',     1, FALSE, '', NULL),
    PHORUM_FOLLOW_URL               => array('follow',     4, TRUE,  '', NULL),
    PHORUM_INDEX_URL                => array('index',      1, TRUE,  '', NULL),
    PHORUM_LIST_URL                 => array('list',       3, TRUE,  '', NULL),
    PHORUM_LOGIN_ACTION_URL         => array('login',      1, FALSE, '', NULL),
    PHORUM_LOGIN_URL                => array('login',      4, TRUE,  '', NULL),
    PHORUM_MODERATION_ACTION_URL    => array('moderation', 1, FALSE, '', NULL),
    PHORUM_MODERATION_URL           => array('moderation', 2, TRUE,  '', NULL),
    PHORUM_PM_ACTION_URL            => array('pm',         1, FALSE, '', NULL),
    PHORUM_PM_URL                   => array('pm',         4, TRUE,  '', NULL),
    PHORUM_FOREIGN_PM_URL           => array('pm',         1, TRUE,  '', NULL),
    PHORUM_POSTING_URL              => array('posting',    2, TRUE,  '', NULL),
    PHORUM_POSTING_ACTION_URL       => array('posting',    1, FALSE, '', NULL),
    PHORUM_PROFILE_URL              => array('profile',    4, TRUE,  '', NULL),
    PHORUM_REDIRECT_URL             => array('redirect',   1, TRUE,  '', NULL),
    PHORUM_REGISTER_ACTION_URL      => array('register',   1, FALSE, '', NULL),
    PHORUM_REGISTER_URL             => array('register',   4, TRUE,  '', NULL),
    PHORUM_REPORT_URL               => array('report',     4, TRUE,  '', NULL),
    PHORUM_SEARCH_ACTION_URL        => array('search',     1, FALSE, '', NULL),
    PHORUM_SEARCH_URL               => array('search',     4, TRUE,  '', NULL),
    PHORUM_SUBSCRIBE_URL            => array('subscribe',  2, TRUE,  '', NULL),
    PHORUM_ADDON_URL                => array('addon',      2, TRUE,  '', NULL),
    PHORUM_AJAX_URL                 => array('ajax',       1, FALSE, '', NULL),
    PHORUM_READ_URL                 => array('read',       2, TRUE, '#msg-', 2),
    PHORUM_FOREIGN_READ_URL         => array('read',       1, TRUE, '#msg-', 3),
);

// }}}

// {{{ Function phorum_api_url()
/**
 * Generate a Phorum URL.
 */
function phorum_api_url()
{
    global $PHORUM;

    // So we do not call function_exists() for each phorum_api_url() call.
    static $do_custom_url = NULL;
    if ($do_custom_url === NULL) {
        $do_custom_url = function_exists('phorum_custom_get_url');
    }

    $argv = func_get_args();

    $url          = '';
    $suffix       = '';
    $pathinfo     = NULL;
    $add_forum_id = 1;
    $add_get_vars = TRUE;

    $type = array_shift($argv);

    if (!isset($PHORUM['API']['url_patterns'][$type]))
    {
        // these URL types need extra care
        // please do not add anything to this unless it is a last resort

        switch($type)
        {
            case PHORUM_REPLY_URL:
                $add_get_vars = TRUE;
                $add_forum_id = 2;
                // The reply URL depends on how the reply form is handled.
                if (!empty($PHORUM['reply_on_read_page'])) {
                    $name = 'read';
                    $suffix = '#REPLY';
                } else {
                    $name = 'posting';
                }
                break;

            case PHORUM_FILE_URL:
                $name = 'file';
                $add_forum_id = 2;

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
                        $add_forum_id = 2;
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
                $add_forum_id = (bool) array_shift($argv) ? 2 : 0;
                break;

            default:
                trigger_error(
                    "phorum_api_url(): Illegal URL type " .
                    "\"$type\" used",
                    E_USER_ERROR
                );
                break;
        }
    }
    else
    {
        list ($name, $add_forum_id, $add_get_vars, $suffix_p, $suffix_fld) =
            $PHORUM['API']['url_patterns'][$type];

        if ($suffix_p !== NULL)
        {
            if (empty($suffix_fld)) {
                $suffix = $suffix_p;
            } else {
                $suffix_fld --; // because we shift()ed $argv before.
                if (!empty($argv[$suffix_fld]) &&
                    (is_numeric($argv[$suffix_fld]) ||
                     strpos($argv[$suffix_fld], '%') === 0)) {
                    $suffix = $suffix_p . $argv[$suffix_fld];
                }
            }
        }
    }

    // Build the URL.
    $url = $PHORUM['http_path'] . '/';
    if ($name) $url .= $name . '.' . PHORUM_FILE_EXTENSION;

    // Build the query parameters to add.

    // Add forum id if requested.
    if ($add_forum_id === 2) {
        array_unshift($argv, $PHORUM['forum_id']);
    }

    // Add forum id if setting is conditional and there are no params.
    if ($add_forum_id === 3 & count($argv) == 0) {
        array_unshift($argv, $PHORUM['forum_id']);
    }

    // Add forum id and (if available) the thread and message id.
    if ($add_forum_id === 4) {
        if (!empty($PHORUM['ref_message_id'])) {
            array_push($argv, 'ref_message_id=' . $PHORUM['ref_message_id']);
        }
        if (!empty($PHORUM['ref_thread_id'])) {
            array_push($argv, 'ref_thread_id=' . $PHORUM['ref_thread_id']);
        }
        array_unshift($argv, $PHORUM['forum_id']);
    }

    // Add GET vars if requested.
    if ($add_get_vars) {
        $query_params = array_merge($argv, $PHORUM['DATA']['GET_VARS']);
    } else {
        $query_params = $argv;
    }

    /**
     * @todo document the 'url_build' hook.
     */
    if (isset($PHORUM['hooks']['url_build'])) {
        $hook_url = phorum_api_hook(
            'url_build', NULL,
            $name, $query_params, $suffix, $pathinfo
        );
        if ($hook_url !== NULL) return $hook_url;
    }

    // Allow full overriding of the URL building mechanism by
    // implementing the function "phorum_custom_get_url()".
    // This is a legacy solution (a hook avant la lettre).
    // When writing new code, then please use the "url_build"
    // hook instead.
    if ($do_custom_url) {
        $url = phorum_custom_get_url(
            $name, $query_params, $suffix, $pathinfo
        );
    }
    // The default URL construction.
    else
    {
        if ($pathinfo !== null) $url .= $pathinfo;
        if (!empty($query_params)) $url .= '?' . implode(',', $query_params);
        if ($suffix) $url .= $suffix;
    }

    return $url;
}
// }}}

// {{{ Function: phorum_api_url_no_uri_auth()
/**
 * Generate a Phorum URL, without any URI authentication information in it.
 */
function phorum_api_url_no_uri_auth()
{
    global $PHORUM;

    $uri_auth = NULL;
    if (isset($PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM])) {
        $uri_auth = $PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM];
        unset($PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM]);
    }

    $argv = func_get_args();

    $url = call_user_func_array('phorum_api_url', $argv);

    if ($uri_auth !== NULL) {
        $PHORUM['DATA']['GET_VARS'][PHORUM_SESSION_LONG_TERM] = $uri_auth;
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
    return phorum_api_url(PHORUM_BASE_URL);
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
    $url = '';

    // On some systems, the SCRIPT_URI is set, but using a different host
    // name than the one in HTTP_HOST (probably due to some mass virtual
    // hosting request rewriting). If that happens, we do not trust
    // the SCRIPT_URI. Otherwise, we use the SCRIPT_URI as the current URL.
    if (isset($_SERVER["SCRIPT_URI"]) &&
        (!isset($_SERVER['HTTP_HOST']) ||
         strpos($_SERVER['SCRIPT_URI'], $_SERVER['HTTP_HOST']) !== FALSE)) {

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

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
                  ? 'https' : 'http';
        $port = ($_SERVER['SERVER_PORT']!=443 && $_SERVER['SERVER_PORT']!=80)
              ? ':'.$_SERVER['SERVER_PORT'] : '';
        $url = $protocol.'://'.$host.$port.$_SERVER['PHP_SELF'];
    }

    if ($include_query_string && !empty($_SERVER['QUERY_STRING'])) {
        $url .= '?' . $_SERVER['QUERY_STRING'];
    }

    return $url;
}
// }}}

?>
