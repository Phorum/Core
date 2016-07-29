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
 * This script implements tools for encoding JSON data.
 *
 * Phorum uses PHP's json_encode() and json_decode() functions for working
 * with JSON data. This API layer is needed however, because those functions
 * are designed to only work with UTF-8 data. Since Phorum can work with
 * other charsets as well, charset conversion is needed in some cases.
 *
 * @package    PhorumAPI
 * @subpackage JSON
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/charset.php';

// {{{ Function: phorum_api_json_encode()
/**
 * Encode a PHP variable into a JSON structure.
 *
 * JSON data is always UTF-8. In case Phorum is not using UTF-8 as the
 * default charset, the data is converted to UTF-8 automatically.
 *
 * @param mixed $var
 *     The PHP variable to encode.
 *
 * @return string
 *     The JSON encoded representation of the PHP variable.
 */
function phorum_api_json_encode($var)
{
    global $PHORUM;

    if (strtoupper($PHORUM['DATA']['CHARSET']) != 'UTF-8') {
        $var = phorum_api_charset_convert_to_utf8($var);
    }

    return json_encode($var);
}
// }}}

// {{{ Function phorum_api_json_decode()
/**
 * Decode a JSON encoded structure into a PHP variable.
 *
 * JSON data is always UTF-8. In case Phorum is not using UTF-8 as the
 * default charset, the data is converted from UTF-8 back to Phorum's
 * charset automatically.
 *
 * @param string $json
 *     The JSON structure to decode.
 *
 * @return string
 *     The PHP variable representation of the JSON structure.
 */
function phorum_api_json_decode($json)
{
    global $PHORUM;

    $var = json_decode($json, TRUE);

    if (strtoupper($PHORUM['DATA']['CHARSET']) != 'UTF-8') {
        $var = phorum_api_charset_convert_from_utf8($var);
    }

    return $var;
}
// }}}

?>
