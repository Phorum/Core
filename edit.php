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
define('phorum_page','edit');

include_once("./common.php");
include_once("./include/moderation_functions.php");
include_once("./include/thread_info.php");

if(!phorum_check_read_common()) {
  return;
}

$PHORUM["DATA"]["REDIRECT"] = false;
$PHORUM["DATA"]["MODERATOR"] = phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

$message_id = (isset($_POST["message_id"])) ? (int)$_POST["message_id"] : (int)$PHORUM["args"][2];

$mod_step = (isset($_POST["mod_step"])) ? (int)$_POST["mod_step"] : (int)$PHORUM["args"][1];

if(empty($message_id) || empty($mod_step)){
	phorum_return_to_list();
}

$getmsg = phorum_db_get_message($message_id);

// we need to check if the thread is closed
if ($message_id == $getmsg["thread"]){
    $thread_is_closed = $getmsg["closed"];
}
else{
    $parent = phorum_db_get_message($getmsg["thread"]);
    $thread_is_closed = $parent["closed"];
}

// security checks, see if they're allowed to edit this post
$useredit = (($getmsg["user_id"] == $PHORUM["user"]["user_id"]) && phorum_user_access_allowed(PHORUM_USER_ALLOW_EDIT) &&
              !$thread_is_closed && ($PHORUM["user_edit_timelimit"] == 0 || $getmsg["datestamp"] + ($PHORUM["user_edit_timelimit"] * 60) >= time()));

if(!($useredit || $PHORUM["DATA"]["MODERATOR"])){
    $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["EditPostForbidden"];
    $PHORUM["DATA"]["EDIT"]["edit_allowed"] = 0;
    $template = "edit";
}
else{
    $PHORUM["DATA"]["EDIT"]["edit_allowed"] = 1;
    switch ($mod_step){
        case PHORUM_MOD_EDIT_POST: // user wants to edit a post (moderators use moderation.php)
            $PHORUM["DATA"]["EDIT"]["useredit"] = 1;
            $PHORUM["DATA"]["FRM"] = 1;

            // expose the actual message fields
            foreach($getmsg as $key=>$value){
                if(!is_array($value)){
                    $PHORUM["DATA"]["EDIT"][$key]=htmlspecialchars($value);
                }    
            }

            // expose the meta data that are scalar values
            foreach($getmsg["meta"] as $key=>$value){
                if(!is_array($value)){
                    $PHORUM["DATA"]["EDIT"]["meta"][$key]=htmlspecialchars($value);
                }    
            }

            if(isset($getmsg["meta"]["attachments"])){
                foreach($getmsg["meta"]["attachments"] as $file_data){
                    $PHORUM["DATA"]["EDIT"]["attachments"][]=array("file_id"=>$file_data["file_id"], "file_name"=>htmlspecialchars($file_data["name"]));
                }
            }

            // set this to help deal with announcements
            $PHORUM["DATA"]["EDIT"]["forum_id"]=$PHORUM["forum_id"];

            $PHORUM["DATA"]["EDIT"]["emailreply"] = phorum_db_get_if_subscribed($PHORUM["DATA"]["EDIT"]["forum_id"], $PHORUM["DATA"]["EDIT"]["thread"], $PHORUM["DATA"]["EDIT"]["user_id"]);
            $PHORUM["DATA"]["EDIT"]["mod_step"] = PHORUM_SAVE_EDIT_POST;
            $PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url(PHORUM_EDIT_ACTION_URL);
            $template="edit";
            break;

        case PHORUM_SAVE_EDIT_POST: // saving the edited post-data
            phorum_handle_edit_message();
            $PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_get_url(PHORUM_READ_URL, $_POST['thread'], $_POST["message_id"]);
            $template="message";
            $PHORUM['DATA']["BACKMSG"]=$PHORUM['DATA']["LANG"]["BackToThread"];
            break;

        default:
            phorum_return_to_list($PHORUM["args"][0]);
    }
}

// set all our URL's
phorum_build_common_urls();

include phorum_get_template("header");
phorum_hook("after_header");

include phorum_get_template($template);

phorum_hook("before_footer");
include phorum_get_template("footer");

?>
