<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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
 * This script implements multi bytes safe word wrapping.
 *
 * @package    PhorumAPI
 * @subpackage Formatting
 * @copyright  2011, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */


// {{{ Function: phorum_api_format_wordwrap()
/**
 * Unicode and newline aware version of wordwrap.
 *
 * @param string $text
 *     The text to format.
 * @param integer $width
 *     The width to wrap to. Defaults to 72.
 * @param string $break
 *     The line is broken using the optional break parameter. Defaults to '\n'.
 * @param boolean $cut
 *     If the cut is set to true, the string is always wrapped at the specified width.
 * @return string
 *     Formatted text.
 *
 * The related variable from the language file is:
 * - $PHORUM['DATA']['HCHARSET']: the charset to use
 *
 * @link http://cakephp.org CakePHP(tm) Project
 * @see Cake\Utility\Text::wordWrap
 */
function phorum_api_format_wordwrap( $text, $width = 72, $break = "\n", $cut = false )
{
    // Unfortunately, mbstring is a non-default extension and we can therefore
    // not be sure that it is available in the PHP installation.
    static $mbstring_available;
    if ($mbstring_available === NULL) {
        $mbstring_available = (boolean) function_exists('mb_internal_encoding')
                              && function_exists('mb_strlen')
                              && function_exists('mb_strpos')
                              && function_exists('mb_strrpos')
                              && function_exists('mb_substr');
    }
    if ($mbstring_available) {
        $paragraphs = explode($break, $text);
        foreach ($paragraphs as &$paragraph) {
            $paragraph = _phorum_api_format_wordwrap($paragraph, $width, $break, $cut);
        }
        return implode($break, $paragraphs);
    } else {
        return wordwrap($text, $width, $break, $cut);
    }
}
// }}}

// {{{ Function: _phorum_api_format_wordwrap()
/**
 * Unicode aware version of wordwrap as helper method.
 *
 * @param string text
 *     The text to format.
 * @param integer width
 *     The width to wrap to. Defaults to 72.
 * @param string break
 *     The line is broken using the optional break parameter. Defaults to '\n'.
 * @param boolean cut
 *     If the cut is set to true, the string is always wrapped at the specified width.
 * @return string
 *     Formatted text.
 *
 * The related variable from the language file is:
 * - $PHORUM['DATA']['HCHARSET']: the charset to use
 *
 * @link http://cakephp.org CakePHP(tm) Project
 * @see Cake\Utility\Text::_wordWrap
 */
function _phorum_api_format_wordwrap( $text, $width = 72, $break = "\n", $cut = false )
{
    global $PHORUM;

    if (isset($PHORUM['DATA']['HCHARSET']) && $PHORUM['DATA']['HCHARSET']) {
        $encoding = $PHORUM['DATA']['HCHARSET'];
    } else {
        $encoding = mb_internal_encoding();
    }
    if ($cut) {
        $parts = [];
        while (mb_strlen($text, $encoding) > 0) {
            $part = mb_substr($text, 0, $width, $encoding);
            $parts[] = trim($part);
            $text = trim(mb_substr($text, mb_strlen($part, $encoding), NULL, $encoding));
        }
        return implode($break, $parts);
    }
    $parts = [];
    while (mb_strlen($text, $encoding) > 0) {
        if ($width >= mb_strlen($text, $encoding)) {
            $parts[] = trim($text);
            break;
        }
        $part = mb_substr($text, 0, $width, $encoding);
        $nextchar = mb_substr($text, $width, 1, $encoding);
        if ($nextchar !== ' ') {
            $breakat = mb_strrpos($part, ' ', 0, $encoding);
            if ($breakat === false) {
                $breakat = mb_strpos($text, ' ', $width, $encoding);
            }
            if ($breakat === false) {
                $parts[] = trim($text);
                break;
            }
            $part = mb_substr($text, 0, $breakat, $encoding);
        }
        $part = trim($part);
        $parts[] = $part;
        $text = trim(mb_substr($text, mb_strlen($part, $encoding), NULL, $encoding));
    }
    return implode($break, $parts);
}
// }}}

?>
