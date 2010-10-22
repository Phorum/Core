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
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM")) return;

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

// Clean up unlinked attachments from the database.
foreach ($message["attachments"] as $info) {
    if (! $info["linked"]) {
        if (phorum_api_file_check_delete_access($info["file_id"])) {
            phorum_api_file_delete($info["file_id"]);
        }
    }
}

$PHORUM["posting_template"] = "message";
$PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["AttachCancel"];
$PHORUM["DATA"]["BACKMSG"] = $PHORUM["DATA"]["LANG"]["BackToList"];
$PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_get_url(PHORUM_LIST_URL);

$error_flag = true;

/*
 * [hook]
 *     posting_action_cancel_post
 *
 * [description]
 *     Allow modules to perform custom action whenever the user cancels editing
 *     of his post. This can be used to e.g. redirect the user immediately back
 *     to the edited post where he came from.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In <filename>action_cancel.php</filename> at the end of the file when 
 *     everything has been done.
 *
 * [input]
 *     Array containing message data.
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_posting_action_cancel_post ($message)
 *     {
 *         global $PHORUM;
 *
 *         // perform a custom redirect
 *         phorum_redirect_by_url($PHORUM["DATA"]["URL"]["REDIRECT"]);
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["posting_action_cancel_post"]))
    phorum_hook("posting_action_cancel_post", $message);
?>
