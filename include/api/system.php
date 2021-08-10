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
 * This script implements the Phorum system API.
 *
 * The system API is used for finding information about the system on which
 * Phorum is running.
 *
 * @package    PhorumAPI
 * @subpackage System
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ phorum_api_system_get_max_upload()
/**
 * Retrieve the maximum possible file upload size.
 *
 * This function determines the system's upload limit. This limit is
 * defined by the maximum upload filesize (PHP), the maximum POST request
 * size (PHP) and the maximum database packet size.
 *
 * @return array
 *     An array containing three elements:
 *     - The overall system's maximum upload size
 *     - The maximum as imposed by PHP
 *     - The maximum as imposed by the database
 */
function phorum_api_system_get_max_upload()
{
    global $PHORUM;

    // Determine limit as imposed by PHP.
    $pms = phorum_api_system_phpsize2bytes(ini_get('post_max_size'));
    $umf = phorum_api_system_phpsize2bytes(ini_get('upload_max_filesize'));
    $php_limit = ($umf > $pms ? $pms : $umf);

    // Determines the database server's limit for file uploads. This limit
    // is determined by the maximum packet size that the database can handle.
    // We asume that there's a 40% overhead in the packet, so that 60% of
    // the packet can be used for sending an uploaded file to the database.
    $db_limit = $PHORUM['DB']->maxpacketsize();
    if ($db_limit != NULL) {
        $db_limit = $db_limit * 0.6;
    }

    $limit = $php_limit;
    if ($db_limit && $db_limit < $php_limit) {
        $limit = $db_limit;
    }

    $data = array($limit, $php_limit, $db_limit);

    /*
     * [hook]
     *     system_max_upload
     *
     * [description]
     *     This hook allows a module to control the maximum file size for
     *     a file upload.  Most notable would be file system storage.  It
     *     could ignore the db_limit.
     *
     * [category]
     *     File storage
     *
     * [when]
     *     In <filename>include/api/system.php</filename>,
     *     in the function phorum_api_system_get_max_upload().
     *
     * [input]
     *     An array containing the default limit, the data layer limit and
     *     the PHP limit
     *
     * [output]
     *     A 3 part array with the limits adjusted as you wish.  The first
     *     element in the array would be the most important.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_system_max_upload($data)
     *     {
     *         // ignore the db_limit
     *         $data[0] = $data[2];
     *         return $data;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["system_max_upload"])) {
        $data = phorum_api_hook("system_max_upload", $data);
    }

    return $data;
}
// }}}

// {{{ phorum_api_system_phpsize2bytes()
/**
 * Converts a size parameter as used in the PHP ini-file
 * (e.g. 1024, 10k, 8M) to a number of bytes.
 *
 * @param string $size
 *     The PHP size parameter to convert.
 * @return integer
 *     The size parameter, converted to a number of bytes.
 */
function phorum_api_system_phpsize2bytes($size)
{
    $size = trim($size);
    $last = strtolower($size[strlen($size)-1]);
    switch($last) {
       // The 'G' modifier is available since PHP 5.1.0
       case 'g':
           $size *= 1024;
       case 'm':
           $size *= 1024;
       case 'k':
           $size *= 1024;
    }
    return $size;
}
// }}}

?>
