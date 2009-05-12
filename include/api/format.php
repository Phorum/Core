<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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
 * This script implements utility functions for formatting data.
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Function: phorum_api_format_date()
/**
 * Formats an epoch timestamp to a date/time for displaying on screen.
 *
 * @param picture - The time formatting to use, in strftime() format
 * @param ts - The epoch timestamp to format
 * @return datetime - The formatted date/time string
 */
function phorum_api_format_date($picture, $ts)
{
    global $PHORUM;

    // Setting locale if no locale was set in the language file.
    if (!isset($PHORUM['locale'])) {
        $PHORUM['locale'] = "EN";
    }
    setlocale(LC_TIME, $PHORUM['locale']);

    // Format the date.
    if ($PHORUM["user_time_zone"] &&
        isset($PHORUM["user"]["tz_offset"]) &&
        $PHORUM["user"]["tz_offset"] != -99) {
        $ts += $PHORUM["user"]["tz_offset"] * 3600;
        return gmstrftime($picture, $ts);
    } else {
        $ts += $PHORUM["tz_offset"] * 3600;
        return strftime($picture, $ts);
    }
}
// }}}

// {{{ Function: phorum_api_format_relative_date()
/**
 * Formats an epoch timestamp to a relative time phrase
 * (yesterday, 6 days ago, 4 months ago).
 *
 * @param integer ts
 *     The epoch timestamp to format
 *
 * @return phrase
 *     The formatted phrase that describes the relative time.
 */
function phorum_api_format_relative_date($time)
{
    global $PHORUM;
    $phorum = Phorum::API();

    $today = strtotime($phorum->format->date('%Y-%m-%d', time()));

    $reldays = ($time - $today)/86400;

    if ($reldays >= 0 && $reldays < 1) {

        return $PHORUM["DATA"]["LANG"]["relative_today"];

    } else if ($reldays >= 1 && $reldays < 2) {

        return $PHORUM["DATA"]["LANG"]["relative_tomorrow"];

    } else if ($reldays >= -1 && $reldays < 0) {

        return $PHORUM["DATA"]["LANG"]["relative_yesterday"];
    }

    if (abs($reldays) < 30) {

        // less than a month

        $reldays = floor($reldays);

        if($reldays==1){
            $return = $PHORUM["DATA"]["LANG"]["relative_one_day"];
        } else {
            $return = abs($reldays)." ".$PHORUM["DATA"]["LANG"]["relative_days"];
        }

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;

    } elseif (abs($reldays) < 60) {

        // weeks ago

        $relweeks = floor(abs($reldays/7));

        $return = $relweeks." ".$PHORUM["DATA"]["LANG"]["relative_weeks"];

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;

    } elseif (abs($reldays) < 365) {

        // months ago

        $relmonths = floor(abs($reldays/30));

        $return = $relmonths." ".$PHORUM["DATA"]["LANG"]["relative_months"];

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;

    } else {

        // years ago

        $relyears = floor(abs($reldays/365));

        if($relyears==1){
            $return = $PHORUM["DATA"]["LANG"]["relative_one_year"];
        } else {
            $return = $relyears." ".$PHORUM["DATA"]["LANG"]["relative_years"];
        }

        $return.= " ".$PHORUM["DATA"]["LANG"]["relative_ago"];

        return $return;
    }
}
// }}}

// {{{ Function: phorum_api_format_filesize()
/**
 * Formats a file size in bytes to a human readable format. Human
 * readable formats are MB (MegaByte), KB (KiloByte) and byte.
 *
 * @param integer bytes
 *     The number of bytes.
 *
 * @param string
 *     The formatted size.
 */
function phorum_api_format_filesize($bytes)
{
    if ($bytes >= 1024*1024) {
        return round($bytes/1024/1024, 2) . "&nbsp;MB";
    } elseif ($bytes >= 1024) {
        return round($bytes/1024, 1) . "&nbsp;KB";
    } else {
        return $bytes . ($bytes == 1 ? "&nbsp;byte" : "&nbsp;bytes");
    }
}
// }}}

// {{{ Function: phorum_api_format_strip()
/**
 * Strips HTML <tags> and BBcode [tags] from the body.
 *
 * @param string body
 *     The block of body text to strip.
 *
 * @return string
 *     The stripped body.
 */
function phorum_api_format_strip($body)
{
    // Strip HTML <tags>
    $stripped = preg_replace("|</*[a-z][^>]*>|i", "", $body);

    // Strip BB Code [tags]
    $stripped = preg_replace("|\[/*[a-z][^\]]*\]|i", "", $stripped);

    // Handle censoring.
    $stripped = phorum_api_format_censor($stripped);

    return $stripped;
}
// }}}

// {{{ Function: phorum_api_format_censor
/**
 * Handle replacing bad words with the string from the
 * PHORUM_BADWORD_REPLACE constant.
 *
 * @param string $str
 *     The string in which to replace the bad words.
 *
 * @param string $str
 *     The possibly modifed string.
 */
function phorum_api_format_censor($str)
{
    $phorum = Phorum::API();
    static $badwords = NULL;
    
    if (!is_array($badwords)) {
        $badwords = $phorum->ban->list(PHORUM_BAD_WORDS);
    }
    
    // Do string replacements if there are bad words configured.
    if (!empty($badwords))
    {
        $words = array();
        foreach ($badwords as $badword) {
            $words[] = "/\b".preg_quote($badword['string'],'/').
                       "(ing|ed|s|er|es)*\b/i";
        }
        $str = preg_replace($words, PHORUM_BADWORD_REPLACE, $str);
    }

    return $str;
}
// }}}

?>
