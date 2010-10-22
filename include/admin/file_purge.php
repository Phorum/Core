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

    if(!defined("PHORUM_ADMIN")) return;

    include_once("./include/format_functions.php");
    include_once("./include/api/file_storage.php");

    // Execute file purging for real?
    if (count($_POST)) {
        $deleted = phorum_api_file_purge_stale(TRUE);
        phorum_admin_okmsg("Purged " . count($deleted) . " files");
    }

    // Retrieve a list of stale files.
    $purge_files = phorum_api_file_purge_stale(FALSE);

    include_once "./include/admin/PhorumInputForm.php";
    $frm = new PhorumInputForm ("", "post", count($purge_files) ? "Purge stale files now" : "Refresh screen");

    $frm->hidden("module", "file_purge");

    $frm->addbreak("Purging stale files...");
    $frm->addmessage(
        "It's possible that there are files stored in the Phorum system,
         which no longer are linked to anything. For example, if users
         write messages with attachments, but do not post them in the end,
         the attachment files will be left behind in the database.
         Using this maintenance tool, you can purge those stale files
         from the system.");

    $prev_reason = '';
    if (count($purge_files))
    {
        $frm->addbreak("There are currently " . count($purge_files) . 
                       " stale files in the database");

        foreach($purge_files as $id => $file)
        {
            if ($file['reason'] != $prev_reason) {
                $prev_reason = $file['reason'];
                $frm->addsubbreak("Reason: " . $file['reason']);
            }

            $frm->addrow(htmlspecialchars($file["filename"]), phorum_filesize($file["filesize"]));
        }
    } else {
        $frm->addmessage("There are currently no stale files in the database");
    }

    $frm->show();


?>
