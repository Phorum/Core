<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
 * This script implements error handling functionality for the Phorum API.
 *
 * @package    PhorumAPI
 * @subpackage ErrorHandling
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Variable definitions

global $PHORUM;

/**
 * A mapping of Phorum errno values to a human readable counter part.
 * These descriptions are used if no speicific error message was provided
 * to phorum_api_error().
 */
$PHORUM["API"]["errormessages"] = array(
    PHORUM_ERRNO_ERROR        => "An error occurred.",
    PHORUM_ERRNO_NOACCESS     => "Permisison denied.",
    PHORUM_ERRNO_NOTFOUND     => "Not found.",
    PHORUM_ERRNO_INTEGRITY    => "Database integrity problem detected.",
    PHORUM_ERRNO_INVALIDINPUT => "Invalid input.",
);

// }}}

// {{{ Function: phorum_api_error()
/**
 * Set a Phorum API error.
 *
 * @param integer $errno
 *     The error code for the error that occurred. There are several
 *     errno constants available to indicate the type of error that occurred:
 *
 *     - {@link PHORUM_ERRNO_ERROR}: A generic all-purpose error code
 *     - {@link PHORUM_ERRNO_NOACCESS}: Permission denied
 *     - {@link PHORUM_ERRNO_NOTFOUND}: Resource not found
 *     - {@link PHORUM_ERRNO_INTEGRITY}: Database integrity problem
 *     - {@link PHORUM_ERRNO_INVALIDINPUT}: Data input error
 *
 * @param string $error
 *     This is the error message, describing the error that occurred.
 *     if this parameter is omitted or NULL, then the message will be
 *     set to a generic message, based on the {@link $errno} that was used.
 *
 * @return bool
 *     This function will always use FALSE as its return value,
 *     so a construction like "return phorum_api_error(...)" can
 *     be used for setting an error and returning FALSE at the same time.
 */ 
function phorum_api_error($errno, $error = NULL)
{
    global $PHORUM;

    if ($error === NULL) {
        if (isset($PHORUM["API"]["errormessages"][$errno])) {
            $error = $PHORUM["API"]["errormessages"][$errno];
        } else {
            $error = "Unknown errno value ($errno).";
        }
    }

    $PHORUM["API"]["errno"] = $errno;
    $PHORUM["API"]["error"] = $error;

    return FALSE;
}
// }}}

// {{{ Function: phorum_api_error_code()
/**
 * Retrieve the error code for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error code or NULL if no error was set.
 */
function phorum_api_error_code()
{
    global $PHORUM;
    return $PHORUM["API"]["errno"];
}
// }}}

// {{{ Function: phorum_api_error_message()
/**
 * Retrieve the error message for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error message or NULL if no error was set.
 */
function phorum_api_error_message()
{
    global $PHORUM;
    return $PHORUM["API"]["error"];
}
// }}}

?>
