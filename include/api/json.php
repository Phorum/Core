<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
 * This script implements tools for encoding JSON data.
 *
 * Phorum uses PHP's json_encode() and json_decode() functions for working
 * with JSON data. This API layer is needed however, because those functions
 * are designed to only work with UTF-8 data. Since Phorum can work with
 * other charsets as well, charset conversion is needed in some cases.
 *
 * @package    PhorumAPI
 * @subpackage JSONAPI
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

if (!function_exists('json_decode'))
{
    function json_decode($content, $assoc=false)
    {
        require_once('./include/api/json-pear.php');

        static $json_a;
        static $json_b;

        if ($assoc) {
            if (!$json_a) {
                $json_a = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
            }
            $json = $json_a;
        } else {
            if (!$json_b) {
                $json_b = new Services_JSON;
            }
            $json = $json_b;
        }

        return $json->decode($content);
    }
}
 
if (!function_exists('json_encode'))
{
    function json_encode($content)
    {
        require_once('./include/api/json-pear.php');

        static $json;
        if (!$json) {
            $json = new Services_JSON;
        }
        return $json->encode($content);
    }
}

// {{{ Function: phorum_api_json_encode()
function phorum_api_json_encode($var)
{
    global $PHORUM;

    if (strtoupper($PHORUM['DATA']['CHARSET']) != 'UTF-8') {
        $var = phorum_api_json_convert_to_utf8($var);
    }

    return json_encode($var);
}
// }}}

// {{{ Function phorum_api_json_decode()
function phorum_api_json_decode($var)
{
    global $PHORUM;
    return json_decode($var, TRUE);
}
// }}}

// {{{ Function: phorum_api_json_convert_to_utf8()
function phorum_api_json_convert_to_utf8($var)
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
       foreach ($var as $k => $v) {
           $new[phorum_api_json_convert_to_utf8($k)] =
               phorum_api_json_convert_to_utf8($v);
       }
       $var = $new;
   }
   elseif (is_object($var))
   {
       $var = clone($var);
       $vars = get_object_vars($var);
       foreach ($vars as $property => $value) {
           $var->$property = phorum_api_json_convert_to_utf8($value);
       }
   }
   elseif (is_string($var))
   {
       // Fix for characters that do not survive the UTF-8 conversion somehow.
       $var = strtr($var, $cp1252_map);

       // Convert to UTF-8.
       $var = iconv($PHORUM['DATA']['CHARSET'], 'UTF-8', $var);
   }
   return $var;
}
//}}}

?>
