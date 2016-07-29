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
 * This script implements utility functions for generating credentials
 * (passwords, keys, tokens).
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_generate_password()
/**
 * Generate a new random password.
 *
 * @param integer $charpart
 *     The number of letters in the password.
 *
 * @param integer $numpart
 *     The number of numbers in the password.
 *
 * @return string
 *     The generated password.
 */
function phorum_api_generate_password($charpart = 4, $numpart = 3)
{
    $vowels = array("a", "e", "i", "o", "u");
    $cons   = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n",
                    "p", "r", "s", "t", "u", "v", "w", "tr", "cr",
                    "br", "fr", "th", "dr", "ch", "ph", "wr", "st",
                    "sp", "sw", "pr", "sl", "cl");

    $num_vowels = count($vowels);
    $num_cons   = count($cons);

    $password = '';

    for ($i = 0; $i < $charpart; $i++) {
        $password .= $cons[random_int(0, $num_cons - 1)] .
                     $vowels[random_int(0, $num_vowels - 1)];
    }

    $password = substr($password, 0, $charpart);

    if ($numpart) {
        $max=(int)str_pad("", $numpart, "9");
        $min=(int)str_pad("1", $numpart, "0");

        $num = (string)random_int($min, $max);
    }

    return strtolower($password.$num);
}
// }}}

// {{{ Function: phorum_api_generate_key()
/**
 * Generate a key.
 *
 * @param integer $size
 *     The number of characters in the key.
 *
 * @return string
 *     The generated key.
 */
function phorum_api_generate_key($size = 40)
{
   $chars = '0123456789!@#$%&abcdefghijklmnopqr'.
            'stuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $key = '';
   for ($i = 0; $i < $size; $i++) {
       $key .= substr($chars, random_int(0, strlen($chars)-1), 1);
   }

   return $key;
}
// }}}

?>
