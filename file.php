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

define('phorum_page','file');
require_once './common.php';

require_once PHORUM_PATH.'/include/api/file.php';

// We start a buffer here, so we can catch any (warning) output
// from being prepended to file data that we return. The file
// API layer will handle cleaning up of the buffered data.
ob_start();

// The "file" argument contains the ID of the requested file.
// If this argument is missing, we redirect the user back to
// the message list for the forum.
if (empty($PHORUM["args"]["file"])) {
    phorum_api_redirect(PHORUM_LIST_URL);
}
$file_id = (int) $PHORUM["args"]["file"];

// Check if the file is available and if the user is allowed to read it.
$file = phorum_api_file_check_read_access($file_id);

// Handle file access errors.
if ($file === FALSE)
{
    $PHORUM["DATA"]["ERROR"] = phorum_api_error_message();

    phorum_build_common_urls();

    phorum_api_output("message");
    return;
}


// Access is allowed. Send the file to the browser.
$flags = empty($PHORUM['args']['download'])
       ? 0 : PHORUM_FLAG_FORCE_DOWNLOAD;
phorum_api_file_send($file, $flags);

// Exit here explicitly for not giving back control to portable and
// embedded Phorum setups.
exit(0);

?>
