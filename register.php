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
define('phorum_page','register');

include_once("./common.php");
include_once("./include/users.php");
include_once("./include/profile_functions.php");
include_once("./include/email_functions.php");

// set all our URL's
phorum_build_common_urls();

if(isset($PHORUM["args"]["approve"])){

    $tmp_pass=substr($PHORUM["args"]["approve"], 0, 8);
    $user_id=(int)substr($PHORUM["args"]["approve"], 8);

    $user_id=phorum_user_verify($user_id, $tmp_pass);
    
    if($user_id){

        $user=phorum_user_get($user_id);
        
        if($user["active"] == PHORUM_USER_INACTIVE) { // user has been denied!
             $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegVerifyFailed"];
             
        } else { // user pending somehow
            if($user["active"]==PHORUM_USER_PENDING_BOTH){
                $moduser["active"]=PHORUM_USER_PENDING_MOD;
                $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegVerifyMod"];
            } else {
                $moduser["active"]=PHORUM_USER_ACTIVE;
                $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegAcctActive"];
            }
    
            $moduser["user_id"]=$user_id;
            phorum_user_save($moduser);
        }

    } else {
        $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegVerifyFailed"];
    }

    include phorum_get_template("header");
    phorum_hook("after_header");
    include phorum_get_template("message");
    phorum_hook("before_footer");
    include phorum_get_template("footer");
    exit();

}

if(count($_POST)){
	$error=""; // init it as empty
	
    if(!isset($_POST["username"]) || empty($_POST['username']) ){
        $error = $PHORUM["DATA"]["LANG"]["ErrUsername"];
    }elseif(!isset($_POST["email"]) || !phorum_valid_email($_POST["email"])){
        $error = $PHORUM["DATA"]["LANG"]["ErrEmail"];
    }elseif(empty($_POST["password"]) || $_POST["password"] != $_POST["password2"]){
        $error = $PHORUM["DATA"]["LANG"]["ErrPassword"];
    }else{

        if(phorum_user_check_username($_POST["username"])){
        
            $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdName"];

        } elseif (phorum_user_check_email($_POST["email"])){
        
            $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdEmail"];

        } elseif (!phorum_check_ban_lists($_POST["username"], PHORUM_BAD_NAMES)) {

            $error = $PHORUM["DATA"]["LANG"]["ErrBannedName"];

        } elseif (!phorum_check_ban_lists($_POST["email"], PHORUM_BAD_EMAILS)) {

            $error = $PHORUM["DATA"]["LANG"]["ErrBannedEmail"];

        } else {
        
            $userdata = $_POST;
            $userdata["hide_email"]=true;
            $userdata["date_added"]=time();
            $userdata["date_last_active"]=time();
            
            // remove anything that is not actual user data
            unset($userdata["forum_id"]);
            unset($userdata["password2"]);

            // email confirmation for registration
            if($PHORUM["registration_control"]==PHORUM_REGISTER_INSTANT_ACCESS){
            
                $userdata["active"] = PHORUM_USER_ACTIVE;
            } elseif($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_EMAIL){
            
                $userdata["active"] = PHORUM_USER_PENDING_EMAIL;
                $userdata["password_temp"]=substr(md5(microtime()), 0, 8);
            } elseif($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_MODERATOR){
            
                $userdata["active"] = PHORUM_USER_PENDING_MOD;
            } elseif($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_BOTH) {
            
                $userdata["password_temp"]=substr(md5(microtime()), 0, 8);            
                $userdata["active"] = PHORUM_USER_PENDING_BOTH;
            }
            
            $userdata=phorum_hook("before_register", $userdata);
            if(isset($userdata['error'])) {
            	$error=$userdata['error'];
            	unset($userdata['error']);	
            } elseif ($user_id=phorum_user_add($userdata)){

                if($PHORUM["registration_control"]==PHORUM_REGISTER_INSTANT_ACCESS){

                    $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegThanks"];

                } elseif($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_EMAIL || $PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_BOTH){
                    $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegVerifyEmail"];
                } elseif($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_MODERATOR){
                    $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegVerifyMod"];
                }

                if($PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_BOTH || $PHORUM["registration_control"]==PHORUM_REGISTER_VERIFY_EMAIL) {
                        $verify_url=phorum_get_url(PHORUM_REGISTER_URL, "approve=".$userdata["password_temp"]."$user_id");
                        $maildata["mailsubject"]=$PHORUM["DATA"]["LANG"]["VerifyRegEmailSubject"];
                        $maildata["mailmessage"]=wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody1"], 72)."\n\n$verify_url\n\n".wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody2"], 72);
                        phorum_email_user(array($userdata["email"]), $maildata);
                }

                
                $PHORUM["DATA"]["BACKMSG"] = $PHORUM["DATA"]["LANG"]["RegBack"];
                $PHORUM["DATA"]["URL"]["REDIRECT"] = phorum_get_url(PHORUM_LOGIN_URL);

                phorum_hook("after_register",$userdata);
                
                include phorum_get_template("header");
                phorum_hook("after_header");
                include phorum_get_template("message");
                phorum_hook("before_footer");
                include phorum_get_template("footer");

                exit();
            } else {
                $error = $PHORUM["DATA"]["LANG"]["ErrUserAddUpdate"];
            }
        }
    }

    if(!empty($error)){
        foreach($_POST as $key=>$value){
            $PHORUM["DATA"]["REGISTER"][$key] = htmlspecialchars($value);
        }
        $PHORUM["DATA"]["REGISTER"]["noshowemail"] = (empty($_POST["noshowemail"])) ? "" : "checked";
        $PHORUM["DATA"]["ERROR"] = htmlspecialchars($error);
    }
}else{
    $PHORUM["DATA"]["REGISTER"]["username"] = "";
    $PHORUM["DATA"]["REGISTER"]["email"] = "";
    $PHORUM["DATA"]["ERROR"] = "";
}

if(is_array($PHORUM["PROFILE_FIELDS"])) {
    foreach($PHORUM["PROFILE_FIELDS"] as $field){

        if(isset($_POST[$field])){
            $PHORUM["DATA"]["REGISTER"][$field] = $_POST[$field];
        } else {
            $PHORUM["DATA"]["REGISTER"][$field] = "";
        }
    }
}

$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url( PHORUM_REGISTER_ACTION_URL );
$PHORUM["DATA"]["REGISTER"]["forum_id"] = $PHORUM["forum_id"];

$PHORUM["DATA"]["REGISTER"]["block_title"] = $PHORUM["DATA"]["LANG"]["Register"];

include phorum_get_template("header");
phorum_hook("after_header");
include phorum_get_template("register");
phorum_hook("before_footer");
include phorum_get_template("footer");

?>
