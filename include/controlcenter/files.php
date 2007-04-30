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

if(!defined("PHORUM_CONTROL_CENTER")) return;

require_once("./include/api/base.php");
require_once("./include/api/file_storage.php");

// First a basic write access check for user files in general.
// Specific checks for uploaded files is done below here.
$allow_upload = phorum_api_file_check_write_access(array(
    "link" => PHORUM_LINK_USER)
);

// No error message means that write access is granted.
if ($allow_upload["error"] === NULL)
{
    // Handle new uploaded file.
    if (!empty($_FILES) && is_uploaded_file($_FILES["newfile"]["tmp_name"]))
    {
        // Create a file array that we can use for checking write access
        // more detailed than using the basic check above.
        $file = array(
            "user_id"   => $PHORUM["user"]["user_id"],
            "filename"  => $_FILES["newfile"]["name"],
            "filesize"  => $_FILES["newfile"]["size"],
            "file_data" => '',
            "link"      => PHORUM_LINK_USER
        );

        // Check write access and enforce quota limits.
        $file = phorum_api_file_check_write_access($file);
        print "TODO add_datetime in PM<br>";
        print "<br>End of todo!";
        exit;

        if ($PHORUM["max_file_size"]>0 && 
            $_FILES["newfile"]["size"]>$PHORUM["max_file_size"]*1024) {
            $error_msg = true;
            $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["FileTooLarge"];
        }

        if (!empty($PHORUM["file_types"]))
        {
            $ext = strtolower(substr($_FILES["newfile"]["name"], strrpos($_FILES["newfile"]["name"], ".")+1));
            $allowed_exts=explode(";", $PHORUM["file_types"]);                
            if(!in_array($ext, $allowed_exts)){
                $error_msg = true;
                $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["FileWrongType"];
            }
        }

        if($PHORUM["file_space_quota"]>0 && phorum_db_get_user_filesize_total($PHORUM["user"]["user_id"])+$_FILES["newfile"]["size"]>=$PHORUM["file_space_quota"]*1024){
            $error_msg = true;
            $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["FileOverQuota"];
        }

        if(empty($error_msg)){

            // Read in the uploaded file.
            $fp = fopen($_FILES["newfile"]["tmp_name"], "r");
            $file_data = fread($fp, $_FILES["newfile"]["size"]);
            fclose($fp);

            // Store the file.
            $file = phorum_api_file_store(array(
                "user_id"   => $PHORUM["user"]["user_id"],
                "filename"  => $_FILES["newfile"]["name"],
                "filesize"  => $_FILES["newfile"]["size"],
                "file_data" => $file_data,
                "link"      => PHORUM_LINK_USER
            ));

            // Display error if an error was returned.
            if ($file["error"] !== NULL) {
                $PHORUM["DATA"]["ERROR"] = $file["error"];
                include phorum_get_template("header");
                phorum_hook("after_header");
                include phorum_get_template("message");
                phorum_hook("before_footer");
                include phorum_get_template("footer");
                exit();
            }
        }
    }

    // Handle deleting selected messages.
    elseif(!empty($_POST["delete"])) {

        foreach($_POST["delete"] as $file_id){

            phorum_api_file_delete($file_id);

        }                

    }

    $files = phorum_db_get_user_file_list($PHORUM["user"]["user_id"]);

    $total_size=0;

    foreach($files as $key => $file) {
        $files[$key]["filesize"] = phorum_filesize($file["filesize"]);
        $files[$key]["raw_dateadded"]=$file["add_datetime"];
        $files[$key]["dateadded"]=phorum_date($PHORUM["short_date_time"], $file["add_datetime"]);

        $files[$key]["url"]=phorum_get_url(PHORUM_FILE_URL, "file=$key");

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

    $PHORUM["DATA"]["FILES"] = $files;

    $PHORUM["DATA"]["TOTAL_FILES"] = count($files);
    $PHORUM["DATA"]["TOTAL_FILE_SIZE"] = phorum_filesize($total_size);

} else {
    $template = "message";

    $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["UploadNotAllowed"];
} 

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["EditMyFiles"];


?>
