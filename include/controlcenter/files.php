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

if(!defined("PHORUM_CONTROL_CENTER")) return;

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["EditMyFiles"];

// First a basic write access check for user files in general, so we
// can tell the user that personal files are not allowed. Specific checks
// for newly uploaded files are done below here.
if (!phorum_api_file_check_write_access(array("link" => PHORUM_LINK_USER))) {
    $template = "message";
    $PHORUM["DATA"]["ERROR"] = phorum_api_strerror();
    return;
}

// ----------------------------------------------------------------------
// Handle storing a newly uploaded file.
// ----------------------------------------------------------------------

if (!empty($_FILES) && is_uploaded_file($_FILES["newfile"]["tmp_name"]))
{
    // Read in the uploaded file.
    if(!empty($_FILES["newfile"]["size"])) {
        $fp = fopen($_FILES["newfile"]["tmp_name"], "r");
        $file_data = fread($fp, $_FILES["newfile"]["size"]);
        fclose($fp);
    } else {
        $file_data = "";
    }

    // Create the file array for the file storage API.
    $file = array(
        "user_id"   => $PHORUM["user"]["user_id"],
        "filename"  => $_FILES["newfile"]["name"],
        "filesize"  => $_FILES["newfile"]["size"],
        "file_data" => $file_data,
        "link"      => PHORUM_LINK_USER
    );

    // Store the file.
    if (!phorum_api_file_check_write_access($file) ||
        !phorum_api_file_store($file)) {
        $PHORUM["DATA"]["ERROR"] = phorum_api_strerror();
    } else {
        $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["FileAdded"];
    }
}

// ----------------------------------------------------------------------
// Handle deleting selected messages.
// ----------------------------------------------------------------------

elseif (!empty($_POST["delete"]))
{
    foreach($_POST["delete"] as $file_id){
        if (phorum_api_file_check_delete_access($file_id)) {
            phorum_api_file_delete($file_id);
        }
    }                
}

// ----------------------------------------------------------------------
// Display the files for the current user.
// ----------------------------------------------------------------------

$files = phorum_db_get_user_file_list($PHORUM["user"]["user_id"]);

$total_size=0;

foreach($files as $key => $file) {
    $files[$key]["filesize"] = phorum_filesize($file["filesize"]);
    $files[$key]["raw_dateadded"]=$file["add_datetime"];
    $files[$key]["dateadded"]=phorum_date($PHORUM["short_date_time"], $file["add_datetime"]);

    $files[$key]["url"]=phorum_get_url(PHORUM_FILE_URL, "file=$key", "filename=".urlencode($file['filename']));

    $total_size+=$file["filesize"];
} 

$template = "cc_files";

if($PHORUM["max_file_size"]){
    $PHORUM["DATA"]["FILE_SIZE_LIMIT"]=$PHORUM["DATA"]["LANG"]["FileSizeLimits"] . ' ' . phorum_filesize($PHORUM["max_file_size"]*1024);
}

if($PHORUM["file_types"]){
    $PHORUM["DATA"]["FILE_TYPE_LIMIT"]=$PHORUM["DATA"]["LANG"]["FileTypeLimits"];
}

if($PHORUM["file_space_quota"]){
    $PHORUM["DATA"]["FILE_QUOTA_LIMIT"]=$PHORUM["DATA"]["LANG"]["FileQuotaLimits"] . ' ' . phorum_filesize($PHORUM["file_space_quota"]*1024);;
}

foreach ($files as $id => $file) {
  $files[$id]['filename'] = htmlspecialchars(
    $file['filename'], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
}

$PHORUM["DATA"]["FILES"] = $files;

$PHORUM["DATA"]["TOTAL_FILES"] = count($files);
$PHORUM["DATA"]["TOTAL_FILE_SIZE"] = phorum_filesize($total_size);

?>
