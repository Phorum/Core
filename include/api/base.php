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
 * @package   Phorum base API, implementing basic shared API functionality
 * @author    Maurice Makaay <maurice@phorum.org>
 * @copyright 2007, Phorum Development Team
 */

if (!defined("PHORUM")) return;

/**
 * Phorum errno values for specifying errors.
 */
define("PHORUM_ERRNO_NOACCESS",        1);
define("PHORUM_ERRNO_INTEGRITY",       2);
define("PHORUM_ERRNO_FILENOTFOUND",    3);
define("PHORUM_ERRNO_FILEEXTLINK",     4);

/**
 * Phorum flags for steering function behaviour.
 */
define("PHORUM_FLAG_GET",          1);
define("PHORUM_FLAG_SEND",         2);
define("PHORUM_FLAG_IGNORE_PERMS", 8);

/**
 * A mapping of errno values to their readable error message.
 * TODO: Maybe move this to the language files, so the error message can
 * TODO: be internationalized?
 */
$GLOBALS["PHORUM"]["phorum_api_errors"] = array(
    PHORUM_ERRNO_NOACCESS       => "Permisison denied.",
    PHORUM_ERRNO_INTEGRITY      => "Integrity problem in the database.", PHORUM_ERRNO_FILENOTFOUND   => "File not found.",
    PHORUM_ERRNO_FILEEXTLINK    => "External link to file denied.",
);

/**
 * Lookup the textual error message for a Phorum errno.
 *
 * @param $errno - The errno to lookup.
 *
 * @param $error - The textual error message.
 */
function phorum_api_strerror($errno)
{
    settype($errno, "int");

    if (isset($GLOBALS["PHORUM"]["phorum_api_errors"][$errno])) {
        return $GLOBALS["PHORUM"]["phorum_api_errors"][$errno];
    } else {
        "Unknown errno value ($errno).";
    }
}

?>
