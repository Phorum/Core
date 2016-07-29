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
 * This script implements censor (bad words) formatting.
 *
 * @package    PhorumAPI
 * @subpackage Formatting
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/ban.php';

// {{{ Function: phorum_api_format_censor_compile()
/**
 * Compile the search and replace arguments that have to be used
 * to handle censor word replacements.
 *
 * This is implemented as a separate call, so formatting code
 * can load the compiled arguments, to call preg_replace() on
 * data on its own. This saves a lot of function calls, which
 * improves the overall speed.
 *
 * @return array
 *     An array containing two elements:
 *     - an array of regular expressions that is used for searching for
 *       bad words. If no bad words have been configured, then NULL is returned.
 *     - The string to replace bad words with.
 *       This is the PHORUM_BAD_WORDS constant. We pushed it in here, in
 *       case we want to make this variable in the future.
 */
function phorum_api_format_censor_compile()
{
    static $search = '';

    // Load the badwords and compile the replacement regexp.
    if ($search === '') {
        $words = phorum_api_ban_list(PHORUM_BAD_WORDS);
        if (!empty($words)) {
            $search = array();
            foreach ($words as $word) {
                $search[] = "/\b".preg_quote($word['string'],'/').
                            "(ing|ed|s|er|es)*\b/i";
            }
        } else {
            $search = NULL;
        }
    }

    return array($search, PHORUM_BADWORD_REPLACE);
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
    list ($search, $replace) = phorum_api_format_censor_compile();
    if ($search !== NULL) {
        $str = preg_replace($search, $replace, $str);
    }

    return $str;
}
// }}}

?>
