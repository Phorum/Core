<?php 
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
// Copyright (C) 2003  Phorum Development Team                                //
// http://www.phorum.org                                                      //
//                                                                            //
// This program is free software. You can redistribute it and/or modify       //
// it under the terms of either the current Phorum License (viewable at       //
// phorum.org) or the Phorum License that was distributed with this file      //
//                                                                            //
// This program is distributed in the hope that it will be useful,            //
// but WITHOUT ANY WARRANTY, without even the implied warranty of             //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                       //
//                                                                            //
// You should have received a copy of the Phorum License                      //
// along with this program.                                                   //
////////////////////////////////////////////////////////////////////////////////
define('phorum_page','control');

include_once("./common.php");

phorum_require_login();

include_once("./include/email_functions.php");
include_once("./include/format_functions.php");

define("PHORUM_CONTROL_CENTER", 1);

// a user has to be logged in to use his control-center
if (!$PHORUM["DATA"]["LOGGEDIN"]) {
    phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
    exit();
} 

$error_msg = false;

// generating the id of the page to use
$panel = (!isset($PHORUM['args']['panel']) || empty($PHORUM["args"]['panel'])) ? PHORUM_CC_SUMMARY : $PHORUM["args"]['panel'];

// sometimes we set it from a post-form
if (isset($_POST['panel'])) {
    $panel = $_POST['panel'];
} 

phorum_build_common_urls();

// generating the cc-urls
$PHORUM['DATA']['URL']['CC0'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUMMARY);
$PHORUM['DATA']['URL']['CC1'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_THREADS);
$PHORUM['DATA']['URL']['CC2'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_FORUMS);
$PHORUM['DATA']['URL']['CC3'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERINFO);
$PHORUM['DATA']['URL']['CC4'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SIGNATURE);
$PHORUM['DATA']['URL']['CC5'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_MAIL);
$PHORUM['DATA']['URL']['CC6'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_BOARD);
$PHORUM['DATA']['URL']['CC7'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PASSWORD);
$PHORUM['DATA']['URL']['CC8'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED);
$PHORUM['DATA']['URL']['CC9'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_FILES);
$PHORUM['DATA']['URL']['CC10'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS);
$PHORUM['DATA']['URL']['CC11'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM);
$PHORUM['DATA']['URL']['CC12'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=sent");
$PHORUM['DATA']['URL']['CC13'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=post");
$PHORUM['DATA']['URL']['CC14'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PRIVACY);
$PHORUM['DATA']['URL']['CC15'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MODERATION);
$PHORUM['DATA']['URL']['CC16'] = phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MEMBERSHIP);

if ($PHORUM["file_uploads"] || $PHORUM["user"]["admin"]) {
    $PHORUM["DATA"]["MYFILES"] = true;
} else {
    $PHORUM["DATA"]["MYFILES"] = false;
} 

// determine if the user is a moderator
$PHORUM["DATA"]["MESSAGE_MODERATOR"] = (count(phorum_user_access_list(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) > 0);
$PHORUM["DATA"]["USER_MODERATOR"] = phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_USERS);
$PHORUM["DATA"]["GROUP_MODERATOR"] = phorum_user_allow_moderate_group();
$PHORUM["DATA"]["MODERATOR"] = ($PHORUM["DATA"]["USER_MODERATOR"] + $PHORUM["DATA"]["MESSAGE_MODERATOR"] + $PHORUM["DATA"]["GROUP_MODERATOR"]) > 0;

// settings for the common form, doesn't need to be unique for each case
$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );

$user = $PHORUM['user'];

// security messures
unset($user["password"]);
unset($user["permissions"]);

// set any custom profile fields that are not present.
if (!empty($PHORUM["PROFILE_FIELDS"])) {
    foreach($PHORUM["PROFILE_FIELDS"] as $field) {
        if (!isset($user[$field])) $user[$field] = "";
    } 
} 
$PHORUM["DATA"]["PROFILE"] = $user;

$PHORUM["DATA"]["PROFILE"]["forum_id"] = isset($PHORUM["forum_id"])?$PHORUM['forum_id']:0;
if ($PHORUM['forum_id'] > 0 && $PHORUM['folder_flag']==0) {
    $PHORUM['DATA']['URL']['BACK'] = phorum_get_url(PHORUM_LIST_URL);
    $PHORUM['DATA']['URL']['BACKTITLE'] = $PHORUM['DATA']['LANG']['BacktoForum'];
} else {
    if(isset($PHORUM['forum_id'])) {
        $PHORUM['DATA']['URL']['BACK'] = phorum_get_url(PHORUM_INDEX_URL,$PHORUM['forum_id']);
    } else {
        $PHORUM['DATA']['URL']['BACK'] = phorum_get_url(PHORUM_INDEX_URL);
    }
    $PHORUM['DATA']['URL']['BACKTITLE'] = $PHORUM['DATA']['LANG']['BackToForumList'];
} 
$PHORUM["DATA"]["PROFILE"]["PANEL"] = $panel;

// load the file for that panel - main-part
$panel = basename($panel);
if (file_exists("./include/controlcenter/$panel.php")) {
    include "./include/controlcenter/$panel.php";
} else {
    include "./include/controlcenter/summary.php";
} 

if (isset($template))
    $PHORUM['DATA']['content_template'] = $template;

if (isset($error) && !empty($error)) // transferring messages
    $PHORUM['DATA']['ERROR'] = $error;

include phorum_get_template("header");
phorum_hook("after_header");
if ($error_msg) {
    include phorum_get_template("message");
} else {
    include phorum_get_template("cc_index");
} 
phorum_hook("before_footer");
include phorum_get_template("footer");


////////////////////////////////////////////////////////////////////////


/**
 * common function which is used to save the userdata from the post-data
 */
function phorum_controlcenter_user_save($panel)
{
    $PHORUM = $GLOBALS['PHORUM'];
    $userdata = $_POST;
    $error = "";

    if (!isset($userdata['hide_email']) && $panel == PHORUM_CC_MAIL)
        $userdata['hide_email'] = 0; 

    // set the user id to the logged in user.
    $userdata["user_id"] = $PHORUM["user"]["user_id"];
    
    $userdata=phorum_hook("cc_save_user", $userdata);
    
    // remove anything that is not actual user data
    unset($userdata["forum_id"]);
    unset($userdata["panel"]);
    unset($userdata["password2"]);     
    
    if(isset($userdata['error'])) {
    	$error=$userdata['error'];
    	unset($userdata['error']);
    } elseif (!phorum_user_save($userdata)) {
    	$error = $PHORUM["DATA"]["LANG"]["ErrUserAddUpdate"];
    } else {
    	$error = $PHORUM["DATA"]["LANG"]["ProfileUpdatedOk"];

    	// if they set a new password, lets create a new session
    	if (isset($userdata["password"]) && !empty($userdata["password"])) {
    		phorum_user_set_current_user($userdata["user_id"]);
    		phorum_user_create_session();
    	}

    	// reset the profile
    	foreach($GLOBALS["PHORUM"]["DATA"]["PROFILE"] as $key=>$value){
    		if(isset($GLOBALS["PHORUM"]["user"][$key])){
    			$GLOBALS["PHORUM"]["DATA"]["PROFILE"][$key]=$GLOBALS["PHORUM"]["user"][$key];
    		} elseif($key!="PANEL" && $key!="forum_id") { // these two go into the form from this var
    		$GLOBALS["PHORUM"]["DATA"]["PROFILE"][$key]="";
    		}
    	}
    }

    return $error;
} 

?>
