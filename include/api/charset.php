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
 * This script implements utility functions for handling charsets.
 *
 * @package    PhorumAPI
 * @subpackage Charset
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_charset_convert_to_utf8()
/**
 * A helper function that converts a PHP variable from the active
 * Phorum charset (stored in $PHORUM["DATA"]["CHARSET"]) to UTF-8.
 *
 * @param mixed $var
 *     The variable to convert to UTF-8.
 *
 * @return mixed
 *     The converted variable.
 */
function phorum_api_charset_convert_to_utf8($var)
{
    global $PHORUM;

    // Don't convert if Phorum is in UTF-8 mode already.
    if (strtoupper($PHORUM['DATA']['CHARSET']) == 'UTF-8') return $var;

    // This character map is used to fix differences between ISO-8859-1 and
    // Windows-1252. The 1252 characters sometimes get in messages when users
    // cut-and-paste from Word and for some reason these are not handled
    // by the iconv() conversion. Thanks to Aidan Kehoe for posting this
    // map in the PHP manual pages (http://www.php.net/utf8_encode).

    static $cp1252_map = array
    (
        "\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
        "\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
        "\xc2\x83" => "\xc6\x92",     /* LATIN SMALL LETTER F WITH HOOK */
        "\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
        "\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
        "\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
        "\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
        "\xc2\x88" => "\xcb\x86",     /* MODIFIER LETTER CIRCUMFLEX ACCENT */
        "\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
        "\xc2\x8a" => "\xc5\xa0",     /* LATIN CAPITAL LETTER S WITH CARON */
        "\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
        "\xc2\x8c" => "\xc5\x92",     /* LATIN CAPITAL LIGATURE OE */
        "\xc2\x8e" => "\xc5\xbd",     /* LATIN CAPITAL LETTER Z WITH CARON */
        "\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
        "\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
        "\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
        "\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
        "\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
        "\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
        "\xc2\x97" => "\xe2\x80\x94", /* EM DASH */
        "\xc2\x98" => "\xcb\x9c",     /* SMALL TILDE */
        "\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
        "\xc2\x9a" => "\xc5\xa1",     /* LATIN SMALL LETTER S WITH CARON */
        "\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
        "\xc2\x9c" => "\xc5\x93",     /* LATIN SMALL LIGATURE OE */
        "\xc2\x9e" => "\xc5\xbe",     /* LATIN SMALL LETTER Z WITH CARON */
        "\xc2\x9f" => "\xc5\xb8"      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
    );

    if (is_array($var))
    {
        $new = array();
        foreach ($var as $key => $value)
        {
            $key = iconv($PHORUM['DATA']['CHARSET'], 'UTF-8', $key);
            if (is_string($value)) {
                $value = strtr($value, $cp1252_map);
                $value = iconv($PHORUM['DATA']['CHARSET'], 'UTF-8', $value);
            } elseif (is_array($value) || is_object($value)) {
                $value = phorum_api_charset_convert_to_utf8($value);
            }
            $new[$key] = $value;
        }
        $var = $new;
    }
    elseif (is_object($var))
    {
        $var = clone($var);
        $vars = get_object_vars($var);
        foreach ($vars as $property => $value) {
            if (is_string($value)) {
                $value = strtr($value, $cp1252_map);
                $value = iconv($PHORUM['DATA']['CHARSET'], 'UTF-8', $value);
            } elseif (is_array($value) || is_object($value)) {
                $value = phorum_api_charset_convert_to_utf8($value);
            }
            $var->$property = $value;
        }
    }
    elseif (is_string($var))
    {
        $var = strtr($var, $cp1252_map);
        $var = iconv($PHORUM['DATA']['CHARSET'], 'UTF-8', $var);
    }

    return $var;
}
//}}}

// {{{ Function: phorum_api_charset_convert_from_utf8()
/**
 * A helper function that converts a PHP variable from UTF-8 to
 * the active Phorum charset (stored in $PHORUM["DATA"]["CHARSET"]).
 *
 * @param mixed $var
 *     The variable to convert from UTF-8.
 *
 * @return mixed
 *     The converted variable.
 */
function phorum_api_charset_convert_from_utf8($var)
{
    global $PHORUM;

    // Don't convert if Phorum is in UTF-8 mode already.
    if (strtoupper($PHORUM['DATA']['CHARSET']) == 'UTF-8') return $var;

    if (is_array($var))
    {
        $new = array();
        foreach ($var as $key => $value)
        {
            $key = iconv('UTF-8', $PHORUM['DATA']['CHARSET'], $key);
            if (is_string($value)) {
                $value = iconv('UTF-8', $PHORUM['DATA']['CHARSET'], $value);
            } elseif (is_array($value) || is_object($value)) {
                $value = phorum_api_charset_convert_from_utf8($value);
            }
            $new[$key] = $value;
        }
        $var = $new;
    }
    elseif (is_object($var))
    {
        $var = clone($var);
        $vars = get_object_vars($var);
        foreach ($vars as $property => $value) {
            if (is_string($value)) {
                $value = iconv('UTF-8', $PHORUM['DATA']['CHARSET'], $value);
            } elseif (is_array($value) || is_object($value)) {
                $value = phorum_api_charset_convert_from_utf8($value);
            }
            $var->$property = $value;
        }
    }
    elseif (is_string($var))
    {
        $var = iconv('UTF-8', $PHORUM['DATA']['CHARSET'], $var);
    }

    return $var;
}
//}}}

?>
