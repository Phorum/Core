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
 * This script implements utility functions for handling bans.
 *
 * @package    PhorumAPI
 * @subpackage Bans
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Variable definitions

global $PHORUM;

/**
 * A maping from ban type -> error message to return on match.
 */
$PHORUM['API']['ban']['type2error'] = array(
    PHORUM_BAD_NAMES      => 'ErrBannedName',
    PHORUM_BAD_EMAILS     => 'ErrBannedEmail',
    PHORUM_BAD_USERID     => 'ErrBannedUser',
    PHORUM_BAD_IPS        => 'ErrBannedIP',
    PHORUM_BAD_SPAM_WORDS => 'ErrBannedContent'
);

// These language strings are set dynamically, so the language
// tool won't recognize them automatically. Therefore they are
// mentioned here.
// $PHORUM["DATA"]["LANG"]["ErrBannedName"]
// $PHORUM["DATA"]["LANG"]["ErrBannedEmail"]
// $PHORUM["DATA"]["LANG"]["ErrBannedUser"]
// $PHORUM["DATA"]["LANG"]["ErrBannedIP"]
// $PHORUM["DATA"]["LANG"]["ErrBannedContent"]

// }}}

// {{{ Function: phorum_api_ban_list()
/**
 * Retrieve all or a specific ban list for the current forum.
 *
 * The id of the current forum is taken from $PHORUM['forum_id'].
 *
 * @param integer|NULL $type
 *     The type of ban list to return. If $type is NULL (default), then all
 *     ban lists are returned. The available types are identified by
 *     the following constants:
 *     - {@link PHORUM_BAD_IPS}
 *     - {@link PHORUM_BAD_NAMES}
 *     - {@link PHORUM_BAD_EMAILS}
 *     - {@link PHORUM_BAD_WORDS}
 *     - {@link PHORUM_BAD_USERID}
 *     - {@link PHORUM_BAD_SPAM_WORDS}
 *
 * @return array
 *     The array of ban list items.
 */
function phorum_api_ban_list($type = NULL)
{
    global $PHORUM;
    static $loaded_banlists = array();

    // Check if we have the banlists for the current forum in
    // our request cache.
    if (!isset($loaded_banlists[$PHORUM['forum_id']]))
    {
        // Try to retrieve the ban lists from cache.
        $banlists = NULL;
        if (!empty($PHORUM['cache_banlists']) &&
            !empty($PHORUM['banlist_version'])) {
            $banlists = phorum_api_cache_get(
                'banlist', $PHORUM['forum_id'], $PHORUM['banlist_version']
            );
        }

        // No ban lists available in the cache.
        if ($banlists === NULL)
        {
            // Retrieve them from the database.
            $banlists = $PHORUM['DB']->get_banlists();

            // Nothing in the db either? Then use an empty array.
            if (empty($banlists)) {
                $banlists = array();
            }

            // Make sure that we have a ban list array for each type.
            foreach (array(PHORUM_BAD_IPS, PHORUM_BAD_NAMES,
                           PHORUM_BAD_EMAILS, PHORUM_BAD_WORDS,
                           PHORUM_BAD_USERID, PHORUM_BAD_SPAM_WORDS) as $t) {
                if (empty($banlists[$t])) $banlists[$t] = array();
            }

            // Cache the ban lists.
            if (!empty($PHORUM['cache_banlists']) &&
                !empty($PHORUM['banlist_version'])) {
                phorum_api_cache_put(
                    'banlist', $PHORUM['forum_id'],
                    $banlists, 7200, $PHORUM['banlist_version']
                );
            }
        }

        // Cache the ban list during the request.
        $loaded_banlists[$PHORUM['forum_id']] = $banlists;
    }

    // Return a single ban list.
    if ($type !== NULL) {
        if (empty($loaded_banlists[$PHORUM['forum_id']][$type])) {
            return array();
        } else {
            return $loaded_banlists[$PHORUM['forum_id']][$type];
        }
    }

    // Return all ban lists.
    return $loaded_banlists[$PHORUM['forum_id']];
}
// }}}

// {{{ Function: phorum_api_ban_check()
/**
 * Evaluate a value against the ban list for the current forum
 * to see if there is a match.
 *
 * The id of the current forum is taken from $PHORUM['forum_id'].
 *
 * @param mixed $value
 *     The value to check.
 *     In case a check is run against the {@link PHORUM_BAD_IPS} type,
 *     then this parameter can be NULL. This funciton will then
 *     automatically use the IP address of the remote host.
 *
 * @param integer $type
 *     The type of banlist to check against. This is one of:
 *     - {@link PHORUM_BAD_NAMES}
 *     - {@link PHORUM_BAD_EMAILS}
 *     - {@link PHORUM_BAD_USERID}
 *     - {@link PHORUM_BAD_IPS}
 *     - {@link PHORUM_BAD_SPAM_WORDS}
 *
 * @return bool
 *     An error message in case the value matches the banlist, NULL otherwise.
 */
function phorum_api_ban_check($value, $type)
{
    global $PHORUM;
    $values = array($value);

    // Retrieve the ban list for the requisted type of ban.
    $list = phorum_api_ban_list($type);
    if (empty($list)) return NULL;

    // For IP bans, a value of NULL is allowed for checking. In that
    // case, we will use the IP address of the remote host automatically.
    if ($value === NULL && $type == PHORUM_BAD_IPS) {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $value = $_SERVER['REMOTE_ADDR'];
            $values = array($value);
        } else {
            return NULL;
        }
    }

    // If the value is empty, then the decision is easy.
    $value = trim($value);
    if ($value == '') return NULL;

    // When an IP-address is used for a PHORUM_BAD_IPS check, then we
    // do a hostname lookup. The host or domain name might be blacklisted too.
    if (!empty($PHORUM['dns_lookup']) && $type == PHORUM_BAD_IPS &&
        preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $value)) {
        $resolved = @gethostbyaddr($value);
        if (!empty($resolved) && $resolved != $value) {
            $values[] = $resolved;
        }
    }

    // Check if we can find a matching ban list item.
    $match = NULL;
    foreach ($list as $item)
    {
        foreach ($values as $value)
        {
            if ($item['string'] == '') continue;

            // Handle regular expression matching.
            if ($item['pcre']) {
                if (@preg_match('/\b'.$item['string'].'\b/i', $value)) {
                    $match = $value; break 2;
                }
            }
            // Handle matching a user id (which must always be an exact match).
            elseif ($type == PHORUM_BAD_USERID) {
                if ($value == $item['string']) {
                    $match = $value; break 2;
                }
            }
            // Handle partial string matching.
            else {
                if (stristr($value, $item['string'])) {
                    $match = $value; break 2;
                }
            }
        }
    }

    if (!$match) return NULL;

    $langkey = $PHORUM['API']['ban']['type2error'][$type];
    $message = $PHORUM['DATA']['LANG'][$langkey];
    $message = str_replace('%name%', htmlspecialchars($match), $message);
    return $message;
}
// }}}

// {{{ Function: phorum_api_ban_check_multi()
/**
 * This function can be used to run multiple ban list checks in one go.
 *
 * The first check for which a match is found, will make the function
 * return an error for the match. If all checks are passed, then NULL
 * will be returned.
 *
 * @param array $checks
 *     An array of checks to run. Each element in this array is an
 *     array itself, with two elements in it:
 *     - the value to check
 *     - the banlist type to check against.
 *
 * @return bool
 *     An error message in case a value matches a banlist, NULL otherwise.
 */
function phorum_api_ban_check_multi($checks)
{
    foreach ($checks as $check) {
        $message = phorum_api_ban_check($check[0], $check[1]);
        if ($message !== NULL) return $message;
    }
    return NULL;
}
// }}}

?>
