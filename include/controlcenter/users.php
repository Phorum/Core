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

if(!defined("PHORUM_CONTROL_CENTER")) return;

if (!$PHORUM["DATA"]["USER_MODERATOR"]) {
    phorum_redirect_by_url(phorum_get_url(PHORUM_CONTROLCENTER_URL));
    exit();
} 

$users=phorum_db_user_get_unapproved();

if(!empty($_POST["user_ids"])){

    foreach($_POST["user_ids"] as $user_id){
    	
    	// initialize it
    	$userdata=array();
    	
    	$user=phorum_api_user_get($user_id, TRUE);

        if(!isset($_POST["approve"]) && $user['active'] != PHORUM_USER_ACTIVE){
        	
            $userdata["active"]=PHORUM_USER_INACTIVE;
            
        } else {
            
            if($user["active"]==PHORUM_USER_PENDING_BOTH){
            	
                $userdata["active"]=PHORUM_USER_PENDING_EMAIL;
                
            } elseif($user["active"]==PHORUM_USER_PENDING_MOD) {
            	
                $userdata["active"]=PHORUM_USER_ACTIVE;
                // send reg approved message
                $maildata["mailsubject"]=$PHORUM["DATA"]["LANG"]["RegApprovedSubject"];
                $maildata["mailmessage"]=wordwrap($PHORUM["DATA"]["LANG"]["RegApprovedEmailBody"], 72);
                phorum_email_user(array($user["email"]), $maildata);
                
            }
        }

        $userdata["user_id"]=$user_id;

        // only save it if something was changed 
        if(isset($userdata['active'])) {
            phorum_api_user_save($userdata);
        }
    }
}
if(empty($users)){
    $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["NoUnapprovedUsers"];
} else {

    // get a fresh list to update any changes
    $users=phorum_db_user_get_unapproved();

    // XSS prevention.
    foreach ($users as $id => $user) {
        $users[$id]["username"] = htmlspecialchars($user["username"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        $users[$id]["email"] = htmlspecialchars($user["email"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    }

    $PHORUM["DATA"]["USERS"]=$users;

    $PHORUM["DATA"]["ACTION"]=phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );
    $PHORUM["DATA"]["FORUM_ID"]=$PHORUM["forum_id"];

    $template = "cc_users";
}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["UnapprovedUsers"];

?>
