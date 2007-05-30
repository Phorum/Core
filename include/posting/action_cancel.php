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
?>
