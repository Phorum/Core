<?php

if(!defined("PHORUM")) return;

// check that the user can post.
if( (empty($PHORUM["DATA"]["POST"]["parentid"]) && !phorum_user_access_allowed(PHORUM_USER_ALLOW_NEW_TOPIC)) ||
    (!empty($PHORUM["DATA"]["POST"]["parentid"]) && !phorum_user_access_allowed(PHORUM_USER_ALLOW_REPLY)) ){

    if($PHORUM["DATA"]["LOGGEDIN"]){

        // if they are logged in and can't post, they don't have rights
    	$PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["NoPost"];
        
    } else {

        // check if they could post if logged in.
        // if so, let them know to log in.
        if( (!empty($PHORUM["DATA"]["POST"]["parentid"]) && $PHORUM["reg_perms"] & PHORUM_USER_ALLOW_REPLY)  || 
            (empty($PHORUM["DATA"]["POST"]["parentid"]) && $PHORUM["reg_perms"] & PHORUM_USER_ALLOW_NEW_TOPIC) ){

        	$PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["PleaseLoginPost"];

        } else {

        	$PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["NoPost"];

        }
    }

	include phorum_get_template("message");

} else {

    if(empty($PHORUM["DATA"]["POST"]["author"])) $PHORUM["DATA"]["POST"]["author"] = "";
    if(empty($PHORUM["DATA"]["POST"]["email"])) $PHORUM["DATA"]["POST"]["email"] = "";
    if(empty($PHORUM["DATA"]["POST"]["subject"])) $PHORUM["DATA"]["POST"]["subject"] = "";
    if(empty($PHORUM["DATA"]["POST"]["body"])) $PHORUM["DATA"]["POST"]["body"] = "";
    if(empty($PHORUM["DATA"]["POST"]["thread"])) $PHORUM["DATA"]["POST"]["thread"] = 0;
    if(empty($PHORUM["DATA"]["POST"]["parentid"])) $PHORUM["DATA"]["POST"]["parentid"] = 0;
    if(empty($PHORUM["DATA"]["POST"]["forumid"])) $PHORUM["DATA"]["POST"]["forumid"] = $PHORUM["forum_id"];
    if(empty($PHORUM["DATA"]["ERROR"])) $PHORUM["DATA"]["ERROR"] = "";

    $PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url(PHORUM_POST_ACTION_URL);

    // moderated state?
    $PHORUM["DATA"]["MODERATED"] = ($PHORUM["moderation"] && !phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) ? true : false;

    // allow attachments?
    $PHORUM["DATA"]["ATTACHMENTS"] = ($PHORUM["max_attachments"]>0 && phorum_user_access_allowed(PHORUM_USER_ALLOW_ATTACH)) ? true : false;

    if($PHORUM["DATA"]["LOGGEDIN"]){
    	$PHORUM["DATA"]["POST"]["username"] = $PHORUM["user"]["username"];
    	$PHORUM["DATA"]["POST"]["show_special"] = phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES);
    	$PHORUM["DATA"]["POST"]["show_announcement"] = $PHORUM["user"]["admin"];
        if(empty($_POST["preview"])) {
            if(isset($PHORUM['user']['show_signature']) && $PHORUM['user']['show_signature']) $PHORUM['DATA']['POST']['show_signature']=1;
            if(isset($PHORUM['user']['email_notify']) && $PHORUM['user']['email_notify']) $PHORUM['DATA']['POST']['email_reply']=1;        
        }
    }
    
    include phorum_get_template("post_form");
}
    
?>
