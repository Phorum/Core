<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2003  Phorum Development Team                              //
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
define('phorum_page','attach');

include_once("./common.php");
include_once("./include/email_functions.php");
include_once("./include/thread_info.php");
include_once("./include/format_functions.php");

if(!empty($PHORUM["args"][1]) && is_numeric($PHORUM["args"][1])){
    $message_id = (int)$PHORUM["args"][1];
} elseif(!empty($_POST["message_id"]) && is_numeric($_POST['message_id'])){
    $message_id = (int)$_POST["message_id"];
}


// set all our URL's
phorum_build_common_urls();
// check read-permissions
if(!phorum_check_read_common()) {
  return;
}

// make sure the user can attach messages and that we have a message_id
if(!phorum_user_access_allowed(PHORUM_USER_ALLOW_ATTACH) || empty($message_id)) {
    $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["AttachNotAllowed"];
	include phorum_get_template("header");
    phorum_hook("after_header");
	include phorum_get_template("message");
    phorum_hook("before_footer");
	include phorum_get_template("footer");
    return;
}

$message = phorum_db_get_message($message_id);

// make sure we got a message back and that this is the same user, 
// not too old, and does not already have attachments.
if(empty($message) || 
   $message["user_id"]!=$PHORUM["user"]["user_id"] || 
   $message["datestamp"]<time()-PHORUM_MAX_TIME_TO_ATTACH ||
   !empty($message["meta"]["attachments"])){

    $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["AttachNotAllowed"];
	include phorum_get_template("header");
    phorum_hook("after_header");
	include phorum_get_template("message");
    phorum_hook("before_footer");
	include phorum_get_template("footer");
    return;
}

if(!empty($_POST)){

    if(isset($_POST["cancel"])){

        phorum_db_delete_message($_POST["message_id"]);
    
        $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["AttachCancel"];
        $PHORUM['DATA']["BACKMSG"]=$PHORUM['DATA']["LANG"]["BackToList"];
        $PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_get_url(PHORUM_LIST_URL);

    	include phorum_get_template("header");
        phorum_hook("after_header");
    	include phorum_get_template("message");
        phorum_hook("before_footer");
    	include phorum_get_template("footer");
        return;

    } elseif(!empty($_FILES)){

        $uploaded_files=array();
        foreach($_FILES as $file){

            if(count($uploaded_files)>=$PHORUM["max_attachments"]) break;

            if(!is_uploaded_file($file["tmp_name"])) continue;

            if($PHORUM["max_attachment_size"]>0 && $file["size"]>$PHORUM["max_attachment_size"]*1024){
                $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["AttachFileSize"]." ".$PHORUM["max_attachment_size"]."kB";
                break;
            }

            if(!empty($PHORUM["allow_attachment_types"])){
                $ext=strtolower(substr($file["name"], strrpos($file["name"], ".")+1));
                $allowed_exts=explode(";", $PHORUM["allow_attachment_types"]);                
                if(!in_array($ext, $allowed_exts)){
                    $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["AttachFileTypes"]." ".$PHORUM["allow_attachment_types"];
                    break;
                }
            }
    
            // read in the file
            $fp=fopen($file["tmp_name"], "r");
            $buffer=base64_encode(fread($fp, $file["size"]));
            fclose($fp);

            $file_id=phorum_db_file_save($PHORUM["user"]["user_id"], $file["name"], $file["size"], $buffer, $message_id);
            $uploaded_files[]=array("file_id"=>$file_id, "name"=>$file["name"], "size"=>$file["size"]);
            
        }        

    }
    
    if(empty($uploaded_files)){
        // they did not attach any files
        
        if(empty($PHORUM["DATA"]["ERROR"])){
            $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["AttachmentsMissing"];
        }

    } else {

        if($PHORUM["moderation"] == PHORUM_MODERATE_ON && !phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
            $save_message["status"]=PHORUM_STATUS_HOLD;
        } else {
            $save_message["status"]=PHORUM_STATUS_APPROVED;
        }
        $save_message["meta"]=$message["meta"];
        $save_message["meta"]["attachments"]=$uploaded_files;
        phorum_db_update_message($message_id, $save_message);
        
        if($save_message["status"] > 0){
            phorum_update_thread_info($message["thread"]);
            phorum_db_update_forum_stats(false, 1, $message["datestamp"]);
            // mailing subscribed users
            phorum_email_notice($message);
        }        

        $PHORUM["DATA"]["MESSAGE"] = count($uploaded_files)." ".$PHORUM["DATA"]["LANG"]["AttachDone"];
        $PHORUM['DATA']["BACKMSG"]=$PHORUM['DATA']["LANG"]["BackToList"];
        $PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_get_url(PHORUM_LIST_URL);
        
    	include phorum_get_template("header");
        phorum_hook("after_header");
    	include phorum_get_template("message");
        phorum_hook("before_footer");
    	include phorum_get_template("footer");
        return;
    }

}

$PHORUM["DATA"]["MESSAGE"]["author"] = htmlspecialchars($message["author"]);
$PHORUM["DATA"]["MESSAGE"]["subject"] = htmlspecialchars($message["subject"]);
$PHORUM["DATA"]["MESSAGE"]["body"] = htmlspecialchars($message["body"]);
$PHORUM["DATA"]["MESSAGE"]["message_id"] = (int)$message["message_id"];
$PHORUM["DATA"]["MESSAGE"]["forum_id"] = (int)$message["forum_id"];

$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url( PHORUM_ATTACH_ACTION_URL );

$PHORUM["DATA"]["ATTACH_FILE_TYPES"]=$PHORUM["allow_attachment_types"];
$PHORUM["DATA"]["ATTACH_FILE_SIZE"]=$PHORUM["max_attachment_size"]."kB";

for($x=1;$x<=$PHORUM["max_attachments"];$x++){
    $PHORUM["DATA"]["INPUTS"][]["number"]=$x;
}

include phorum_get_template("header");
phorum_hook("after_header");
include phorum_get_template("attach");
phorum_hook("before_footer");
include phorum_get_template("footer");


?>
