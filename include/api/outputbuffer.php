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
 * This script implements utility functions for working with the
 * PHP output buffer.
 *
 * @package    PhorumAPI
 * @subpackage Tools
 * @copyright  2007, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

/**
 * Start a new output buffer.
 */
function phorum_api_outputbuffer_start()
{
    ob_start();
}

/**
 * Stop the output buffer and return the buffered contents.
 *
 * @return string
 */
function phorum_api_outputbuffer_stop()
{
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

/**
 * Clear out all output that PHP buffered up to now.
 */
function phorum_api_outputbuffer_clean()
{
    // Clear out all output that PHP buffered up to now.
    for(;;) {
        $status = ob_get_status();
        if (!$status ||
            $status['name'] == 'ob_gzhandler' ||
            !$status['del']) break;
        ob_end_clean();
    }
}
?>
