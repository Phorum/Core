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
 * This script implements utility functions for handling output buffering.
 *
 * @package    PhorumAPI
 * @subpackage Output
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_buffer_start()
/**
 * Start a new output buffer.
 */
function phorum_api_buffer_start()
{
    ob_start();
}
// }}}

// {{{ Function: phorum_api_buffer_flush()
/**
 * Stop the output buffer and flush the buffered contents to the browser.
 */
function phorum_api_buffer_flush()
{
    ob_end_flush();
}
// }}}

// {{{ Function: phorum_api_buffer_get()
/**
 * Stop the output buffer and return the buffered contents.
 *
 * @return string
 */
function phorum_api_buffer_get()
{
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}
// }}}

// {{{ Function: phorum_api_buffer_clear()
/**
 * Clear out all output that PHP buffered up to now.
 */
function phorum_api_buffer_clear()
{
    // Clear out all output that PHP buffered up to now.
    for(;;) {
        $status = ob_get_status();
        if (empty($status) || $status === FALSE || $status['name'] == 'ob_gzhandler') break;
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
          if (!($status['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) break;
        } else {
          if (!$status['del']) break;
        }
        ob_end_clean();
    }
}
// }}}

?>
