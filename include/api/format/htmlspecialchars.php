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
 * This script implements our default htmlspecialchars function.
 *
 * @package    PhorumAPI
 * @subpackage Formatting
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */


// {{{ Function: phorum_api_format_htmlspecialchars()
/**
 * htmlspecialchars with default arguments.
 *
 * @param string $string
 *     The string being converted.
 * @return string
 *     The converted string.
 *
 * The related variable from the language file is:
 * - $PHORUM['DATA']['HCHARSET']: the charset to use
 */
function phorum_api_format_htmlspecialchars( $string )
{
    global $PHORUM;
    return htmlspecialchars($string, ENT_QUOTES, $PHORUM['DATA']['HCHARSET']);
}
// }}}

?>
