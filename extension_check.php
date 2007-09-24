<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2007  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

/**
 * This script is used to check if the Phorum PHP extension is installed
 * and if it is usable by this Phorum install. This script is called by
 * the admin interface to provide feedback to the administrator about the.
 * extension being usable or not.
 *
 * If the extension is usable, the script will print a green gif image. If
 * it is not usable, it will print a red gif image.
 *
 * @package    PhorumAPI
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// This will prevent common.php from loading the extension library.
define('PHORUM_ADMIN', 1);

// Try to load the Phorum PHP extension. Wrap this in output buffering,
// so problems here won't wreck the outputted image code.
ob_start();
if (! extension_loaded('phorum')) {
    @dl('phorum.so');
}

// Load the common Phorum code.
define('phorum_page','extension_check');
include_once( "./common.php" );

// Flush any output so far.
phorum_ob_clean();

// Check if the extension version matches this Phorum version.
if (function_exists('phorum_ext_version')) {
    if (phorum_ext_version() == PHORUM_EXTENSION_VERSION) {
        // All looks okay. Print a green block.
        header("Content-Type: image/gif");
        foreach (array(
            0x47,0x49,0x46,0x38,0x39,0x61,0x0f,0x00,
            0x0b,0x00,0x80,0x02,0x00,0x00,0x00,0x00,
            0x35,0xf4,0x21,0x21,0xfe,0x0a,0x50,0x68,
            0x6f,0x72,0x75,0x6d,0x2e,0x6f,0x72,0x67,
            0x00,0x2c,0x00,0x00,0x00,0x00,0x0f,0x00,
            0x0b,0x00,0x00,0x02,0x14,0x8c,0x8f,0xa9,
            0xcb,0x9d,0x00,0x02,0x74,0x73,0xba,0x2b,
            0x57,0x54,0xfb,0x74,0x0e,0x59,0xd8,0xc8,
            0x14,0x00,0x3b
        ) as $byte) print chr($byte);
        exit;
    }
}

// Something went wrong. Print a red block.
header("Content-Type: image/gif");
foreach (array(
    0x47,0x49,0x46,0x38,0x39,0x61,0x0f,0x00,
    0x0b,0x00,0xa1,0x02,0x00,0xbc,0x12,0x12,
    0xff,0xff,0xff,0x00,0x00,0x00,0x00,0x00,
    0x00,0x21,0xfe,0x0a,0x50,0x68,0x6f,0x72,
    0x75,0x6d,0x2e,0x6f,0x72,0x67,0x00,0x21,
    0xf9,0x04,0x01,0x0a,0x00,0x02,0x00,0x2c,
    0x00,0x00,0x00,0x00,0x0f,0x00,0x0b,0x00,
    0x00,0x02,0x14,0x84,0x8f,0xa9,0xcb,0x9d,
    0x11,0x00,0x74,0x73,0xba,0x7b,0xa0,0x6e,
    0x11,0xf5,0xb7,0x7c,0xd8,0x98,0x14,0x00,
    0x3b
) as $byte) print chr($byte);
exit;

?>
