<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;

// if private messages are disabled, just show a simple error message
if (!$PHORUM["enable_pm"]){
    $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMDisabled"];
    $template = "stdblock";
    return;
}

include_once("./include/format_functions.php");

// Check for bans for the PM posting interface.
if( (isset($_POST['action']) && $_POST['action'] == 'post') ||
    (isset($PHORUM["args"]["page"]) && $PHORUM["args"]["page"] == 'post') ){
        
    include_once("./include/profile_functions.php");
    $PHORUM['banlists'] = phorum_db_get_banlists();

    $error = '';
    if (!phorum_check_ban_lists($PHORUM["user"]["username"], PHORUM_BAD_NAMES)) {
        $error = $PHORUM["DATA"]["LANG"]["ErrBannedName"];
    } elseif (!phorum_check_ban_lists($PHORUM["user"]["email"], PHORUM_BAD_EMAILS)) {
        $error = $PHORUM["DATA"]["LANG"]["ErrBannedEmail"];
    } elseif (!phorum_check_ban_lists($PHORUM["user"]["user_id"], PHORUM_BAD_USERID)) {
        $error = $PHORUM["DATA"]["LANG"]["ErrBannedUser"];
    } 
    
    // Show an error in case we encountered a ban. 
    if (! empty($error)) {
        $PHORUM["DATA"]["BLOCK_CONTENT"] = $error;
        $template = "stdblock";
        return;
    }
}

if(isset($_POST["action"])) {

    switch($_POST["action"]){

        case "delete":
            
            if(isset($_POST["to_delete"])){

                foreach($_POST["to_delete"] as $pm_id){
                    $loc_message=phorum_db_pm_get($pm_id);
                    // check that its for the right user
                    if($loc_message['to_user_id'] == $PHORUM["user"]["user_id"]) {                 
                        phorum_db_pm_delete(PHORUM_PM_INBOX, $pm_id);
                    }
                }
            }
                        
            elseif(isset($_POST["from_delete"])){

                foreach($_POST["from_delete"] as $pm_id){
                    $loc_message=phorum_db_pm_get($pm_id);
                    if($loc_message['from_user_id'] == $PHORUM["user"]["user_id"]) {
                         phorum_db_pm_delete(PHORUM_PM_OUTBOX, $pm_id);
                    }
                }
                $PHORUM["args"]["page"]="sent";
            }
            
            
            // If no checkboxes are checked, then we can't decide on which
            // page we are by using these. In that case, we use the box
            // parameter to jump to the right page afterwards.
            else {
                if ($_POST["box"] == "sent") {
                    $PHORUM["args"]["page"]="sent";
                }
            }

            break;

        case "post":

            if(isset($_POST["preview"])){

                $PHORUM["args"]["page"]="post";

            } else {

                // post a new message

                $to_user_id=phorum_db_user_check_field("username", $_POST["to"]);

                $error="";

                if($to_user_id){

                    if(empty($_POST["subject"]) || empty($_POST["message"])){

                        $error=$PHORUM["DATA"]["LANG"]["PMRequiredFields"];

                    } else {                    

                        if(empty($_POST["keep"])) $_POST["keep"]=0;

                        // Check if sender and recipient have not yet reached the 
                        // maximum number of messages that may be stored on the server.
                        if (!$PHORUM['user']['admin'] && $PHORUM['max_pm_messagecount']) 
                        {
                            $checkusers = array(phorum_db_user_get($to_user_id,false));
                            if ($_POST['keep']) $checkusers[] = $PHORUM['user'];
                            foreach ($checkusers as $user)
                            {   
                                if ($user['admin']) continue; // No limits for admins
                                $current_count = phorum_db_pm_messagecount(PHORUM_PM_ALLFOLDERS);
                                if ($current_count['total'] >= $PHORUM['max_pm_messagecount']) {
                                    if ($user['user_id'] == $to_user_id) {
                                        $error=$PHORUM["DATA"]["LANG"]["PMToMailboxFull"];
                                    } else {
                                        $error=$PHORUM["DATA"]["LANG"]["PMFromMailboxFull"];
                                    }
                                }  
                            }
                        }
                        
                        // Send the private message if no errors occurred.
                        if (empty($error)) {
                            if(!phorum_db_pm_send($_POST["subject"], $_POST["message"], $to_user_id, NULL, $_POST["keep"])){
                                $error=$PHORUM["DATA"]["LANG"]["PMNotSent"];
                            } else {
                                phorum_hook("pm_sent","");
                            }
                        }
                    }
                    
                } else {
                    $error=$PHORUM["DATA"]["LANG"]["UserNotFound"];
                }

                if($error){
                    $PHORUM["args"]["page"]="post";
                }

            }

            break;
    }

}

if(!empty($PHORUM["args"]["action"]) && isset($PHORUM["args"]["pm_id"]) && !empty($PHORUM["args"]["pm_id"])){

    $pm_id = $PHORUM["args"]["pm_id"];
    $loc_message=phorum_db_pm_get($pm_id);
    if(empty($loc_message)){
        $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMNotAvailable"];
        $template = "stdblock";
        return;
    }
    
    switch($PHORUM["args"]["action"]){

        case "to_delete":
            // check that its for the right user
            if($loc_message['to_user_id'] == $PHORUM["user"]["user_id"]) { 
              phorum_db_pm_delete(PHORUM_PM_INBOX, $pm_id);
            }
            $PHORUM["args"]["page"]="inbox";
            break;
                                
        case "from_delete":
            // check that its for the right user                    
            if($loc_message['from_user_id'] == $PHORUM["user"]["user_id"]) {        
                phorum_db_pm_delete(PHORUM_PM_OUTBOX, $pm_id);
            }
            $PHORUM["args"]["page"]="sent";
            break;

    }
}


if(empty($PHORUM["args"]["page"])) $PHORUM["args"]["page"]="inbox";

switch ($PHORUM["args"]["page"]) {

    case "inbox":
        // show message lists of incoming eamils
    
        $to_messages=phorum_db_pm_list(PHORUM_PM_INBOX);
    
        foreach($to_messages as $message){
    
            $msg=array();
            $msg["message_id"]=$message["private_message_id"];
            $msg["from"]=htmlspecialchars($message["from_username"]);
            $msg["subject"]=htmlspecialchars($message["subject"]);
            $msg["date"]=phorum_date($PHORUM["short_date"], $message["datestamp"]);
            $msg["read"]=$message["read_flag"];
            $msg["profile_url"]=phorum_get_url(PHORUM_PROFILE_URL, $message["from_user_id"]);
            $msg["read_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=read", "box=inbox", "pm_id=".$message["private_message_id"]);
                    
            $PHORUM["DATA"]["INBOX"][]=$msg;
        }
    
        $PHORUM["DATA"]["ACTION"]=phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );
        $PHORUM["DATA"]["FORUM_ID"]=$PHORUM["forum_id"];

        $template = "cc_pm_inbox";
        break;
        
    case "sent":
        // show message lists of outgoing eamils
        
        $from_messages=phorum_db_pm_list(PHORUM_PM_OUTBOX);

        foreach($from_messages as $message){
    
            $msg=array();
            $msg["message_id"]=$message["private_message_id"];
            $msg["to"]=htmlspecialchars($message["to_username"]);
            $msg["read"]=$message["read_flag"];
            $msg["date"]=phorum_date($PHORUM["short_date"], $message["datestamp"]);
            $msg["subject"]=htmlspecialchars($message["subject"]);
            $msg["profile_url"]=phorum_get_url(PHORUM_PROFILE_URL, $message["to_user_id"]);
            $msg["read_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=read", "box=sent", "pm_id=".$message["private_message_id"]);
                    
            $PHORUM["DATA"]["SENT"][]=$msg;
        }
    
        $PHORUM["DATA"]["ACTION"]=phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );
        $PHORUM["DATA"]["FORUM_ID"]=$PHORUM["forum_id"];

        $template = "cc_pm_sent";
        break;
        
    case "read":

        // show a single message
    
        $message=phorum_db_pm_get($PHORUM["args"]["pm_id"]);
        $msg=array();
        
        // check that its for the right user
        if($message['to_user_id'] == $PHORUM["user"]["user_id"] || $message['from_user_id'] == $PHORUM["user"]["user_id"]) { 

          if($message['to_user_id'] == $PHORUM["user"]["user_id"]) { // only read if not in sent
        	  phorum_db_pm_setflag($PHORUM["args"]["pm_id"], PHORUM_PM_READ_FLAG, true);
          }

            // have to make some tricks to use the format function
            $message["author"]=$message["from_username"];
            $message["body"]=$message["message"];
            $message["email"]="";

            // format message
            list($message) = phorum_format_messages(array($message));

        	$msg["message_id"]=$message["private_message_id"];
        	$msg["to"]=htmlspecialchars($message["to_username"]);
        	$msg["from"]=$message["author"];
        	$msg["date"]=phorum_date($PHORUM["short_date"], $message["datestamp"]);
        	$msg["subject"]=$message["subject"];
        	$msg["message"]=$message["body"];
        	$msg["from_profile_url"]=phorum_get_url(PHORUM_PROFILE_URL, $message["from_user_id"]);
        	$msg["to_profile_url"]=phorum_get_url(PHORUM_PROFILE_URL, $message["to_user_id"]);

        	if ($message["from_user_id"] != $PHORUM["user"]["user_id"]) {
        		$msg["reply_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=post", "pm_id=".$message["private_message_id"]);
        	}
        	
        	if($PHORUM["args"]["box"] == 'inbox') {
        		$msg["delete_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "action=to_delete", "pm_id=".$message["private_message_id"]);
        	} else {
        		$msg["delete_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "action=from_delete", "pm_id=".$message["private_message_id"]);
        	}
        }

        $PHORUM["DATA"]["MESSAGE"]=$msg;
        
        $template = "cc_pm_read";
        break;
        
    case "post":
    
        // Reply to a private message.
        if(isset($PHORUM["args"]["pm_id"])){
    
            $message=phorum_db_pm_get($PHORUM["args"]["pm_id"]);
        
            $msg["from"]=htmlspecialchars($PHORUM["user"]["username"]);
            if($message["to_user_id"] == $PHORUM["user"]["user_id"]) {            
              $msg["message_id"]=$message["private_message_id"];
              $msg["to"]=htmlspecialchars($message["from_username"]);
              $msg["to_id"]=$message["from_user_id"];
              $msg["keep"] = "0";

              if(substr($message["subject"], 0, 3)!="Re:"){
                  $message["subject"]="Re: ".$message["subject"];
              }
              $msg["subject"]=htmlspecialchars($message["subject"]);
              
              $msg["message"] = phorum_strip_body($message["message"]); 
              $msg["message"] = str_replace("\n", "\n> ", $msg["message"]);
              $msg["message"] = wordwrap(trim($msg["message"]), 50, "\n> ", true);
              $msg["message"] = "{$msg['to']} {$PHORUM['DATA']['LANG']['Wrote']}:\n".str_repeat("-", 55)."\n> {$msg['message']}\n\n\n";
            }
    
        // Reply privately to a forum post.
        } elseif (isset($PHORUM["args"]["message_id"])) {

            $message = phorum_db_get_message($PHORUM["args"]["message_id"]);
 
            if (phorum_user_access_allowed(PHORUM_USER_ALLOW_READ) && ($PHORUM["forum_id"]==$message["forum_id"])) {
                // get url to the message board thread
                // TODO: would be nicer to get the url to the post within the thread 
                $origurl = phorum_get_url(PHORUM_READ_URL, $message["thread"]);

                // Find the real username, because some mods rewrite the
                // username in the message table. There will be a better solution
                // for selecting recipients, but for now this will fix some
                // of the problems.
                $user = phorum_db_user_get($message["user_id"],false);
                
                $msg["from"]=htmlspecialchars($PHORUM["user"]["username"]);
                $msg["message_id"]=0;
                $msg["to"]=htmlspecialchars($user["username"]);
                $msg["to_id"]=$message["user_id"];
                $msg["keep"] = "0";
                $msg["subject"]=htmlspecialchars($message["subject"]);
                $msg["message"] = phorum_strip_body($message["body"]);
                $msg["message"] = str_replace("\n", "\n> ", $msg["message"]);
                $msg["message"] = wordwrap(trim($msg["message"]), 50, "\n> ", true);
                $msg["message"] = "{$PHORUM['DATA']['LANG']['InReplyTo']} {$origurl}\n{$msg['to']} {$PHORUM['DATA']['LANG']['Wrote']}:\n".str_repeat("-", 55)."\n> {$msg['message']}\n\n\n";
            }

        // Write a new private message.
        } else {
    
            $msg["message_id"]=0;
            $msg["from"]=htmlspecialchars($PHORUM["user"]["username"]);
            $msg["preview"] = (empty($_POST["preview"])) ? 0 : 1;
            $msg["to_id"] = (empty($_POST["to_id"])) ? "" : $_POST["to_id"];
            $msg["subject"] = (empty($_POST["subject"])) ? "" : $_POST["subject"];
            $msg["message"] = (empty($_POST["message"])) ? "" : $_POST["message"];
            $msg["keep"] = (empty($_POST["keep"])) ? "" : htmlspecialchars($_POST["keep"]);
            
            // formatting for preview
            $msg["body"]=$msg["message"];
            $msg["author"]=$msg["from"];
            $msg["email"]="";
            list($msg_preview) = phorum_format_messages(array($msg));
            $msg['pr_subject']=$msg_preview['subject'];
            $msg["pr_message"]=$msg_preview['body'];
            // PMTODO Quick hack to prevent undefined value warning.
            $msg["from_profile_url"]='#';
            $msg["to_profile_url"]='#';
            
            // reset them htmlencoded
            $msg["subject"] = htmlspecialchars($msg["subject"]);
            $msg["message"] = htmlspecialchars($msg["message"]);
            
            // PMTODO: This is a quick hack to send PM by user_id.
            // Simply translate the user_id to the username.     
            // This will probably all be changed in the new PM system.
            if(!empty($PHORUM['args']['to_id'])) {
                $user = phorum_db_user_get($PHORUM['args']["to_id"], false);
                if ($user) {
                    $_POST['to'] = $user['username'];
                }
            }
                
            if(!empty($_POST["to"])){
                $msg["to"] = htmlspecialchars($_POST["to"]);
            } elseif(!empty($PHORUM["args"]["to"])){
                $msg["to"] = htmlspecialchars($PHORUM["args"]["to"]);
            } else {
                $msg["to"] = "";
                if ($PHORUM["enable_dropdown_userlist"]){
                    $users = array();
                    $userlist = phorum_user_get_list();
                    foreach ($userlist as $userinfo){
                        $userinfo["displayname"] = htmlspecialchars($userinfo["displayname"]);
                        $userinfo["username"] = htmlspecialchars($userinfo["username"]);
                        $users[] = $userinfo;
                    }
                    $PHORUM["DATA"]["USERS"] = $users;
                }
            }

        }
                
        $PHORUM["DATA"]["ERROR"] = (empty($error)) ? "" : $error;
    
        $PHORUM["DATA"]["ACTION"]=phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );
        $PHORUM["DATA"]["FORUM_ID"]=$PHORUM["forum_id"];

        $PHORUM["DATA"]["MESSAGE"]=$msg;
    
        $template = "cc_pm_post";
        break;
}
    
// Make messagecount information available in the templates.
$PHORUM['DATA']['MAX_PM_MESSAGECOUNT'] = 0;
if (! $PHORUM['user']['admin']) {
    $PHORUM['DATA']['MAX_PM_MESSAGECOUNT'] = $PHORUM['SETTINGS']['max_pm_messagecount'];
    if ($PHORUM['SETTINGS']['max_pm_messagecount']) 
    {
        $current_count = phorum_db_pm_messagecount(PHORUM_PM_ALLFOLDERS);
        $PHORUM['DATA']['PM_MESSAGECOUNT'] = $current_count['total'];
        $space_left = $PHORUM['SETTINGS']['max_pm_messagecount'] - $current_count['total'];
        if ($space_left < 0) $space_left = 0;
        $PHORUM['DATA']['PM_SPACE_LEFT'] = $space_left;
        $PHORUM['DATA']['LANG']['PMSpaceLeft'] = str_replace('%pm_space_left%', $space_left, $PHORUM['DATA']['LANG']['PMSpaceLeft']);
    }
}

?>
