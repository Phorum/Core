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
 * This script implements a file reading function, that will not only
 * read the contents of a file, but that will also strip UTF-8 Byte Order
 * Marker (BOM) characters from the file.
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_read_file()
/**
 * Reads a file from disk and returns the contents of that file.
 *
 * @param string $file
 *     The filename of the file to read.
 *
 * @return string
 *     The contents of the file.
 */
function phorum_api_read_file($file)
{
    // Check if the file exists.
    if (! file_exists($file)) trigger_error(
        "phorum_api_read_file(): file \"" . htmlspecialchars($file) . "\" " .
        "does not exist",
        E_USER_ERROR
    );

    // In case we're handling a zero byte large file, we don't read it in.
    // Running fread($fp, 0) later on would result in a PHP warning.
    $size = filesize($file);
    if ($size == 0) return "";

    // Read in the file contents.
    if (! $fp = fopen($file, "r")) trigger_error(
        "phorum_get_file_contents: failed to read file " .
        "\"" . htmlspecialchars($file) . "\"",
        E_USER_ERROR
    );

    // Strip UTF-8 byte order markers from the files. These only mean
    // harm for PHP scripts.
    $data = '';
    if ($size >= 3) {
        $data = fread($fp, 3);
        if ($data == "\xef\xbb\xbf") $data = '';
        $size -= 3;
    }

    // Read the rest of the file.
    if ($size > 0) {
        $data .= fread($fp, $size);
    }

    fclose($fp);

    return $data;
}
// }}}

?>
