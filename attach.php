<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2005  Phorum Development Team                              //
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
   $message["datestamp"]<time()-PHORUM_MAX_TIME_TO_ATTACH){

    $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["AttachNotAllowed"];
    include phorum_get_template("header");
    phorum_hook("after_header");
    include phorum_get_template("message");
    phorum_hook("before_footer");
    include phorum_get_template("footer");
    return;
}

$attachments = $message["meta"]["attachments"];
$existing_files = count($attachments);
$uploaded_files = 0;
$total_size = 0;
foreach ($attachments as $data) $total_size += $data['size'];

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

    } elseif(isset($_POST["finalize"])){

        if($PHORUM["moderation"] == PHORUM_MODERATE_ON && !phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
            $save_message["status"]=PHORUM_STATUS_HOLD;
        } else {
            $save_message["status"]=PHORUM_STATUS_APPROVED;
        }

        phorum_db_update_message($message_id, $save_message);

        if($save_message["status"] > 0){
            phorum_update_thread_info($message["thread"]);
            phorum_db_update_forum_stats(false, 1, $message["datestamp"]);
            // mailing subscribed users
            phorum_email_notice($message);
        }

        if($PHORUM["redirect_after_post"]=="read"){

            if($message["thread"]!=0){
                $top_parent = phorum_db_get_message($message["thread"]);
                $pages=ceil(($top_parent["thread_count"]+1)/$PHORUM["read_length"]);
            } else {
                $pages=1;
            }

            if($pages>1){
                $redir_url = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message["message_id"], "page=$pages");
            } else {
                $redir_url = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message["message_id"]);
            }

        } else {

            $redir_url = phorum_get_url(PHORUM_LIST_URL);

        }

        phorum_redirect_by_url($redir_url);

    } elseif(!empty($_FILES)){

        foreach($_FILES as $file){

            if(!is_uploaded_file($file["tmp_name"])) continue;

            if(($existing_files + $uploaded_files)>=$PHORUM["max_attachments"]) break;

	    if(($total_size + $file["size"])>=$PHORUM["max_totalattachment_size"] * 1024){
                $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["AttachTotalFileSize"]." ".phorum_filesize($PHORUM["max_totalattachment_size"] * 1024);
                break;
	    }

            if($PHORUM["max_attachment_size"]>0 && $file["size"]>$PHORUM["max_attachment_size"]*1024){
                $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["AttachFileSize"]." ".phorum_filesize($PHORUM["max_attachment_size"] * 1024);
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
            $attachments[]=array("file_id"=>$file_id, "name"=>$file["name"], "size"=>$file["size"]);
	    $uploaded_files++;
            $total_size += $file["size"];

        }

    }

    if(empty($uploaded_files)){
        // they did not attach any files

        if(empty($PHORUM["DATA"]["ERROR"])){
            $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["AttachmentsMissing"];
        }

    } else {

        $save_message["meta"]=$message["meta"];
        $save_message["meta"]["attachments"]=$attachments;
        phorum_db_update_message($message_id, $save_message);

        $redir_url = phorum_get_url(PHORUM_ATTACH_URL, $_POST["message_id"]);
        phorum_redirect_by_url($redir_url);

    }

}

$PHORUM["DATA"]["PREVIEW"]["author"] = htmlspecialchars($message["author"]);
$PHORUM["DATA"]["PREVIEW"]["subject"] = htmlspecialchars($message["subject"]);
$PHORUM["DATA"]["PREVIEW"]["body"] = htmlspecialchars($message["body"]);
$PHORUM["DATA"]["PREVIEW"]["ip"] = htmlspecialchars($message["ip"]);
$PHORUM["DATA"]["PREVIEW"]["edit_url"] = phorum_get_url(PHORUM_EDIT_URL, PHORUM_MOD_EDIT_POST, $message_id);
$PHORUM["DATA"]["MESSAGE"]["message_id"] = (int)$message["message_id"];
$PHORUM["DATA"]["MESSAGE"]["forum_id"] = (int)$message["forum_id"];

if($PHORUM["max_attachments"]>0 && isset($message["meta"]["attachments"])){
    foreach($message["meta"]["attachments"] as $key=>$file){
        $PHORUM["DATA"]["PREVIEW"]["attachments"][$key]["size"]=phorum_filesize($file["size"]);
        $PHORUM["DATA"]["PREVIEW"]["attachments"][$key]["name"]=htmlentities($file['name'], ENT_COMPAT, $PHORUM["DATA"]["CHARSET"]); // clear all special chars from name to avoid XSS
        $PHORUM["DATA"]["PREVIEW"]["attachments"][$key]["url"]=phorum_get_url(PHORUM_FILE_URL, "file={$file['file_id']}");
    }
    $phorum_attach_inputs = $PHORUM["max_attachments"]-count($message["meta"]["attachments"]);
} else {
    $phorum_attach_inputs = $PHORUM["max_attachments"];
}


$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url( PHORUM_ATTACH_ACTION_URL );

$PHORUM["DATA"]["ATTACH_FILE_TYPES"]=$PHORUM["allow_attachment_types"];
$PHORUM["DATA"]["ATTACH_FILE_SIZE"]=phorum_filesize($PHORUM["max_attachment_size"] * 1024);
$PHORUM["DATA"]["ATTACH_TOTALFILE_SIZE"]=phorum_filesize($PHORUM["max_totalattachment_size"] * 1024);
$PHORUM["DATA"]["ATTACH_MAX_ATTACHMENTS"] = $PHORUM["max_attachments"];

$max_upload_size = -1;
if ($PHORUM["max_totalattachment_size"]) {
    $max_upload_size = $PHORUM["max_totalattachment_size"] * 1024 - $total_size;
    if ($max_upload_size < 0) $max_upload_size = 0;
} elseif ($PHORUM["max_attachment_size"]) {
    $max_upload_size = $PHORUM["max_attachment_size"] * 1024;
}
if ($PHORUM["max_attachment_size"]) {
    if ($max_upload_size > $PHORUM["max_attachment_size"] * 1024)
        $max_upload_size = $PHORUM["max_attachment_size"] * 1024;
}
if ($max_upload_size != -1) {
    $PHORUM["DATA"]["ATTACH_FILE_SIZE"] = phorum_filesize($max_upload_size);
}
if ($max_upload_size == 0) {
    $phorum_attach_inputs = 0;
}

for($x=1;$x<=$phorum_attach_inputs;$x++){
    $PHORUM["DATA"]["INPUTS"][]["number"]=$x;
}

include phorum_get_template("header");
phorum_hook("after_header");
include phorum_get_template("preview");
include phorum_get_template("attach");
phorum_hook("before_footer");
include phorum_get_template("footer");


?>
