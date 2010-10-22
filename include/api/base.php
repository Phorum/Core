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
 * This script implements basic API functionality.
 *
 * The functionality of this script is shared between all other API scripts.
 * If you include any of the other API scripts in your code, then this script
 * should be included as well.
 *
 * @package    PhorumAPI
 * @subpackage BaseAPI
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

// {{{ Constant and variable definitions

// Initialize the Phorum API space.
$GLOBALS["PHORUM"]["API"] = array(
    "errno" => NULL,
    "error" => NULL
);

/**
 * A general purpose errno value, mostly used for returning a generic
 * errno with a specific error message.
 */
define("PHORUM_ERRNO_ERROR",           1);

/**
 * An errno value, which indicates a permission problem.
 */
define("PHORUM_ERRNO_NOACCESS",        2);

/**
 * An errno value, which indicates that something was not found.
 */
define("PHORUM_ERRNO_NOTFOUND",        3);

/**
 * An errno value, which indicates a database integrity problem.
 */
define("PHORUM_ERRNO_INTEGRITY",       4);

/**
 * An errno value, which indicates invalid input data.
 */
define("PHORUM_ERRNO_INVALIDINPUT",    5);

// A mapping of Phorum errno values to a human readable counter part.
$GLOBALS["PHORUM"]["API"]["errormessages"] = array(
    PHORUM_ERRNO_ERROR        => "An error occurred.",
    PHORUM_ERRNO_NOACCESS     => "Permisison denied.",
    PHORUM_ERRNO_NOTFOUND     => "Not found.",
    PHORUM_ERRNO_INTEGRITY    => "Database integrity problem detected.",
    PHORUM_ERRNO_INVALIDINPUT => "Invalid input.",
);

// }}}

// {{{ Function: phorum_api_error_set
/**
 * Set a Phorum API error.
 *
 * @param integer $errno
 *     The errno value for the error that occurred. There are several
 *     specific errno values available, but for a generic error message
 *     that does not need a specific errno, {@link PHORUM_ERRNO_ERROR} can be
 *     used.
 *
 * @param string $error
 *     This is the error message, describing the error that occurred.
 *     if this parameter is omitted or NULL, then the message will be
 *     set to a generic message for the {@link $errno} that was used.
 *
 * @return bool
 *     This function will always return FALSE as its return value,
 *     so a construction like "return phorum_api_error_set(...)" can
 *     be used for setting an error and returning FALSE at the same time.
 */
function phorum_api_error_set($errno, $error = NULL)
{
    if ($error === NULL) {
        if (isset($GLOBALS["PHORUM"]["API"]["errormessages"][$errno])) {
            $error = $GLOBALS["PHORUM"]["API"]["errormessages"][$errno];
        } else {
            $error = "Unknown errno value ($errno).";
        }
    }

    $GLOBALS["PHORUM"]["API"]["errno"] = $errno;
    $GLOBALS["PHORUM"]["API"]["error"] = $error;

    return FALSE;
}
// }}}

// {{{ Function: phorum_api_errno
/**
 * Retrieve the error code for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error code or NULL if no error was set.
 */
function phorum_api_errno()
{
    if ($GLOBALS["PHORUM"]["API"]["errno"] === NULL) {
        return NULL;
    } else {
        return $GLOBALS["PHORUM"]["API"]["errno"];
    }
}
// }}}

// {{{ Function: phorum_api_strerror
/**
 * Retrieve the error message for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error message or NULL if no error was set.
 */
function phorum_api_strerror()
{
    if ($GLOBALS["PHORUM"]["API"]["error"] === NULL) {
        return NULL;
    } else {
        return $GLOBALS["PHORUM"]["API"]["error"];
    }
}

?>
