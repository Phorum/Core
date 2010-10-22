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
 * This script implements an overly careful file writing function, which
 * can be used to safely write files disk, without risking problems
 * due to crashed scripts or full disks.
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

/**
 * This function can be used to safely write a file to disk.
 *
 * For writing the file, a swap file is used during the write phase.
 * Only if writing the swap file fully succeeded without running
 * into errors, the swap file is moved to its final location.
 * This way, we cannot get broken data because of scripts that crash
 * during writing or because of disks that are full.
 *
 * @param string $file
 *     The name of the file to write to.
 *
 * @param string $data
 *     The data to put in the file.
 *
 * @return boolean
 *     TRUE in case the file was written successfully to disk.
 *     FALSE if writing the file failed. The function
 *     {@link phorum_api_strerror()} can be used to retrieve
 *     information about the error which occurred.
 */
function phorum_api_write_file($file, $data)
{
    // Reset error storage.
    $GLOBALS['PHORUM']['API']['errno'] = NULL;
    $GLOBALS['PHORUM']['API']['error'] = NULL;
    ini_set('track_errors', 1);

    // Generate the swap file name.
    $stamp   = preg_replace('/\D/', '', microtime());
    $swpfile = $file . '.swp' . $stamp;

    // Open the swap file.
    $fp = @fopen($swpfile, 'w');
    if (!$fp) {
        @unlink($swpfile);
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            "Cannot create swap file \"$swpfile\": $php_errormsg"
        );
    }

    // Write file data to disk.
    @fputs($fp, $data);

    // Close the swap file.
    if (!@fclose($fp)) {
        @unlink($swpfile);
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            "Error on closing swap file \"$swpfile\": disk full?"
        );
    }

    // A special check on the created outputfile. We have seen strange
    // things happen on Windows2000 where the webserver could not read
    // the file it just had written :-/
    if (! $fp = @fopen($swpfile, 'r')) {
        @unlink($swpfile);
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            "Cannot read swap file \"$swpfile\", although it was just " .
            "written to disk. This is probably due to a problem with " .
            "the file permissions for the storage directory."
        );
    }
    @fclose($fp);

    // Move the swap file to its final location.
    if (!@rename($swpfile, $file)) {
        @unlink($swpfile);
        return phorum_api_error_set(
            PHORUM_ERRNO_ERROR,
            "Cannot move swap file \"$swpfile\": $php_errormsg"
        );
    }

    return TRUE;
}

?>
