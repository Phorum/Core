<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

// Initialize the Phorum API space.
$GLOBALS["PHORUM"]["API"] = array(
    "errno" => NULL,
    "error" => NULL
);

// ----------------------------------------------------------------------
// Definitions
// ----------------------------------------------------------------------

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

/**
 * Retrieve the error data for the last Phorum API function that was called.
 *
 * @return mixed
 *     If no error is set, then this function will return NULL. 
 *     Else, an array containing two elements is returned. The first
 *     element will be the errno and the second one the error message.
 */
function phorum_api_error()
{
    if ($GLOBALS["PHORUM"]["API"]["errno"] === NULL) {
        return NULL;
    } else {
        return array(
            $GLOBALS["PHORUM"]["API"]["errno"],
            $GLOBALS["PHORUM"]["API"]["error"]
        );
    }
}

?>
