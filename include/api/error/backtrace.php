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
 * This script implements the Error API function phorum_api_error_backtrace().
 *
 * @package    PhorumAPI
 * @subpackage ErrorHandling
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_error_backtrace()
/**
 * Generate a debug backtrace.
 *
 * @param integer $skip
 *     The amount of backtrace levels to skip. The call to this function
 *     is skipped by default, so you don't have to count that in.
 *
 * @param string|NULL $hidepath
 *     NULL to not hide paths or a string to replace the Phorum path with.
 *
 * @return string
 *     The backtrace in text format.
 */
function phorum_api_error_backtrace($skip = 0, $hidepath = "{path to Phorum}")
{
    // Allthough Phorum 4.3.0 is the required PHP version
    // for Phorum at the time of writing, people might still be running
    // Phorum on older PHP versions. For those people, we'll skip
    // creation of a backtrace.

    $backtrace = NULL;
    $first = TRUE;
    if (function_exists("debug_backtrace"))
    {
        $bt = debug_backtrace();
        $backtrace = '';

        $step_count = 0;
        foreach ($bt as $id => $step)
        {
            // Don't include the call to this function. The $id 1, 2 and 3 are
            // checked for cases where this API call was called through the
            // Phorum::API()->error->backtrace() construction.
            if ($id == 0) continue;

            // Skip some entries that are not too useful for our backtrace.
            if ($step['function'] == '__call' ||
                $step['function'] == 'call_user_func_array' ||
                empty($step['line'])) {
                continue;
            }

            // Skip the required number of steps.
            $step_count ++;
            if ($step_count <= $skip) continue;

            if ($hidepath !== NULL && isset($step["file"])) {
                $file = str_replace(PHORUM_PATH, $hidepath, $step["file"]);
            }

            if (!$first) $backtrace .= "\n----\n";
            $first = FALSE;

            if (isset($step['object']) && is_a($step['object'], 'Phorum')) {
                $step['function'] =
                    $step['object']->getFunctionName($step['function']);
            }
            $backtrace .= "Function " . $step["function"] . "() called" .
                          (!empty($step["line"])
                           ? " at\n" .  $file . ":" . $step["line"]
                           : "");
        }
    }
    else
    {
        $backtrace = 'PHP function "debug_backtrace" is not available ' .
                     'on this system. No backtrace could be generated.';
    }

    return $backtrace;
}

// }}}

?>
