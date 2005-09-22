<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;

// If private messages are disabled, just show a simple error message.
if (!$PHORUM["enable_pm"]) {
    $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMDisabled"];
    $template = "stdblock";
    return;
}

// ------------------------------------------------------------------------
// Parameter handling
// ------------------------------------------------------------------------

// Retrieve a parameter from either the args-list or $_POST.
function phorum_getparam($name)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $ret = NULL;
    if (isset($PHORUM["args"][$name])) {
        $ret = $PHORUM["args"][$name];
    }elseif (isset($_POST[$name])) {
        $ret = $_POST[$name];
    }

    return $ret;
}

// Get basic parameters.
$action    = phorum_getparam('action');
$page      = phorum_getparam('page');
$folder_id = phorum_getparam('folder_id');
$pm_id     = phorum_getparam('pm_id');
$forum_id  = $PHORUM["forum_id"];

// Use the inbox as the default folder_id.
if (!$folder_id) $folder_id = PHORUM_PM_INBOX;

// Set some default template data.
$PHORUM["DATA"]["ACTION"]=phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );
$PHORUM["DATA"]["FOLDER_ID"] = $folder_id;
$PHORUM["DATA"]["FOLDER_IS_INCOMING"] = $folder_id == PHORUM_PM_OUTBOX ? 0 : 1;
$PHORUM["DATA"]["FORUM_ID"] = $PHORUM["forum_id"];

// ------------------------------------------------------------------------
// Banlist checking
// ------------------------------------------------------------------------

if ($page == 'post' || $action == 'post')
{
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

// ------------------------------------------------------------------------
// Perform actions
// ------------------------------------------------------------------------

// Initialize error and ok message.
$error = '';
$okmsg = '';

// Translate button clicks from the read page to appropriate actions.
if (isset($_POST['close_message'])) {
    $page = 'list';
} elseif (isset($_POST['delete_message'])) {
    $page = 'list';
    $_POST['delete'] = 1;
    $_POST['checked'] = array($pm_id);
    $action = 'list';
} elseif (isset($_POST['move_message'])) {
    $page = 'list';
    $_POST['move'] = 1;
    $_POST['checked'] = array($pm_id);
    $action = 'list';
} elseif (isset($_POST['reply_message'])) {
    $page = 'post';
    $action = '';
}

if (!empty($action)) {

    // Utility function to check if a foldername already exists.
    // No extreme checking with locking here. Technically
    // speaking duplicate foldernames will work. It's just 
    // confusing for the user.
    function phorum_pm_folder_exists($foldername)
    { 
        $PHORUM = $GLOBALS["PHORUM"];
        foreach ($PHORUM["DATA"]["PM_FOLDERS"] as $id => $data) {
            if (strcasecmp($foldername, $data["name"]) == 0) {
                return true;
            }
        }      
        return false;
    }
    
    // Redirect will be set to a true value if after performing
    // the action we want to use a redirect to get to the
    // result page. This is done for two reasons:
    // 1) Let the result page use refreshed PM data;
    // 2) Prevent reloading of the action page (which could for
    //    example result in duplicate message sending).
    // The variable $redirect_message can be set to a language
    // key string to have a message displayed after redirection.
    $redirect = false;
    $redirect_message = '';
        
    switch($action) {

        // Actions which are triggered from the folder management interface.
        case "folders": {
            
            $redirect = false;
            $page = "folders";
            
            // Create folder.
            if (!empty($_POST['create_folder'])) 
            {
                $foldername = trim($_POST["create_folder_name"]);
                
                if ($foldername != '') 
                {
                    if (phorum_pm_folder_exists($foldername)) {
                        $error = $PHORUM["DATA"]["LANG"]["PMFolderExistsError"];
                    } else {
                        phorum_db_pm_create_folder($foldername);
                        $redirect_message = "PMFolderCreateSuccess";
                        $redirect = true;   
                    }
                    
                }    
            }
            
            // Rename a folder.
            elseif (!empty($_POST['rename_folder']))
            {
                $from = $_POST['rename_folder_from'];
                $to = trim($_POST['rename_folder_to']);
                
                if (!empty($from) && $to != '') {
                    if (phorum_pm_folder_exists($to)) {
                        $error = $PHORUM["DATA"]["LANG"]["PMFolderExistsError"];
                    } else {
                        phorum_db_pm_rename_folder($from, $to);
                        $redirect_message = "PMFolderRenameSuccess";
                        $redirect = true;                           
                    }
                }
            }
            
            // Delete a folder.
            elseif (!empty($_POST['delete_folder'])) 
            {
                $folder_id = $_POST["delete_folder_target"];
                if (!empty($folder_id)) {
                    phorum_db_pm_delete_folder($folder_id);    
                    $redirect_message = "PMFolderDeleteSuccess";
                    $redirect = true;
                }
            }
           
            break;
        }
        
        // Actions which are triggered from the list interface.
        case "list": {

            // Delete all checked messages.
            if (isset($_POST["delete"]) && isset($_POST["checked"])) {
                foreach($_POST["checked"] as $pm_id) {
                    if (phorum_db_pm_get($pm_id, $folder_id)) {
                        phorum_db_pm_delete($pm_id, $folder_id);
                    }
                }
            }
            
            // Move checked messages to another folder.
            elseif (isset($_POST["move"]) && isset($_POST["checked"])) {
                $to = $_POST['target_folder'];
                if (! empty($to)) {
                    foreach($_POST["checked"] as $pm_id) {
                        if (phorum_db_pm_get($pm_id, $folder_id)) {
                            phorum_db_pm_move($pm_id, $folder_id, $to);
                        }
                    }
                }
            }

            $page = "list";
            $redirect = true;

            break;
        }

        // Finish posting a message.
        case "post": {

            // Previewing the message.
            if(isset($_POST["preview"])){

                $page = "post";

                // Posting the message.
            } else {

                // Get the user id for the recipient username.
                // PMTODO wen supporting multiple recipients, this will be different.
                // Also mind $recipients and the upcoming check on the maximum 
                // number of messages.
                $to_user_id = phorum_db_user_check_field("username", $_POST["to"]);
                
                
                if($to_user_id){
                   
                    $recipients = array(phorum_db_user_get($to_user_id, false));
                    
                    // Check if all required message data is filled in.
                    if(empty($_POST["subject"]) || empty($_POST["message"])){

                        $error = $PHORUM["DATA"]["LANG"]["PMRequiredFields"];

                    // Message data is okay. Post the message.
                    } else {

                        if(empty($_POST["keep"])) $_POST["keep"] = 0;

                        // Check if sender and recipient have not yet reached the
                        // maximum number of messages that may be stored on the server.
                        if (!$PHORUM['user']['admin'] && $PHORUM['max_pm_messagecount'])
                        {
                            // Build a list of users to check.
                            $checkusers = $recipients;
                            if ($_POST['keep']) $checkusers[] = $PHORUM['user'];
                            
                            // Check all users.
                            foreach ($checkusers as $user)
                            {
                                if ($user['admin']) continue; // No limits for admins
                                $current_count = phorum_db_pm_messagecount(PHORUM_PM_ALLFOLDERS);
                                if ($current_count['total'] >= $PHORUM['max_pm_messagecount']) {
                                    if ($user['user_id'] == $to_user_id) {
                                        $error = $PHORUM["DATA"]["LANG"]["PMToMailboxFull"];
                                    } else {
                                        $error = $PHORUM["DATA"]["LANG"]["PMFromMailboxFull"];
                                    }
                                }
                            }
                        }

                        // Send the private message if no errors occurred.
                        if (empty($error)) {
                            
                            $pm_message_id = phorum_db_pm_send($_POST["subject"], $_POST["message"], $to_user_id, NULL, $_POST["keep"]);
                            
                            // Show an error in case of problems.
                            if(! $pm_message_id){

                                $error = $PHORUM["DATA"]["LANG"]["PMNotSent"];

                            // Do e-mail notifications on successfull sending.
                            } else {

                                include_once("./include/email_functions.php");

                                $pm_message = array(
                                    'pm_message_id' => $pm_message_id,
                                    'subject'       => $_POST['subject'],
                                    'message'       => $_POST['message'],
                                    'from_username' => $PHORUM['user']['username'],
                                    'from_user_id'  => $PHORUM['user']['user_id'],
                                );
                                
                                $langrcpts = array();
                                
                                // Sort all recipients that want a notify by language.
                                foreach ($recipients as $rcpt) {
                                    if ($rcpt["pm_email_notify"]) {
                                        if (!isset($langrcpts[$rcpt["user_language"]])) {
                                            $langrcpts[$rcpt["user_language"]] = array($rcpt);
                                        } else {
                                            $langrcpts[$rcpt["user_language"]][] = $rcpt;
                                        }
                                    }
                                }
                                
                                phorum_email_pm_notice($pm_message, $langrcpts);
   
                                phorum_hook("pm_sent", $pm_message);
                            }
                        }
                    }
                    
                } else {
                    $error = $PHORUM["DATA"]["LANG"]["UserNotFound"];
                }

                // Stay on the post page in case of errors. Redirect on success.
                if($error){
                    $page = "post";
                } else {
                    $redirect = true;
                }

            }

            break;
        }
    }
    
    // The action has been completed successfully. 
    // Redirect the user to the result page.
    if ($redirect) 
    {
        $redir_url = phorum_get_url(
            PHORUM_CONTROLCENTER_URL, 
            "panel=" . PHORUM_CC_PM,
            "page=" . $page,
            "folder_id=" . $folder_id, 
            "pm_id=" . $pm_id,
            "okmsg=" . $redirect_message
        );
        
        ob_end_clean();
        phorum_redirect_by_url($redir_url);
        exit;
    }
}

// ------------------------------------------------------------------------
// Display a PM page
// ------------------------------------------------------------------------

// A utility function to apply the default forum message formatting
// to a private message.
function phorum_pm_format($message)
{
    include_once("./include/format_functions.php");
    
    // Reformat message so it looks like a forum message.
    $message["author"] = $message["from_username"];
    $message["body"] = $message["message"];
    $message["email"] = "";
    
    // Run the message through the formatting code.
    list($message) = phorum_format_messages(array($message));
    
    // Reformat message back to a private message.
    $message["message"] = $message["body"];
    $message["from_username"] = $message["author"];
    unset($message["body"]);
    unset($message["author"]);
    
    return $message;
}

// Use the message list as the default page.
if (!$page) $page = "list";

// Show an OK message for a redirected page?
$okmsg_id = phorum_getparam('okmsg');
if ($okmsg_id && isset($PHORUM["DATA"]["LANG"][$okmsg_id])) {
    $okmsg = $PHORUM["DATA"]["LANG"][$okmsg_id];
}

// Make error and OK messages available in the template.
$PHORUM["DATA"]["ERROR"] = (empty($error)) ? "" : $error;
$PHORUM["DATA"]["OKMSG"] = (empty($okmsg)) ? "" : $okmsg;

switch ($page) {

    // Manage the PM folders.
    case "folders": {
        
        $PHORUM["DATA"]["CREATE_FOLDER_NAME"] = isset($_POST["create_folder_name"]) ? htmlspecialchars($_POST["create_folder_name"]) : '';
        $PHORUM["DATA"]["RENAME_FOLDER_NAME"] = isset($_POST["rename_folder_name"]) ? htmlspecialchars($_POST["rename_folder_name"]) : '';

        // PMTODO implement folder management
        $template = "cc_pm_folders";
        break;
    }

    // Manage the buddies.
    case "buddies": {

        // PMTODO implement buddy management
        $template = "cc_pm_buddies";
        break;
    }
    
    // Show a listing of messages in a folder.
    case "list": {

        // Check if the folder exists for the user.
        if (! isset($PHORUM["DATA"]["PM_FOLDERS"][$folder_id])) {
            $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMFolderNotAvailable"];
            $template = "stdblock";
            return;
        }

        $list = phorum_db_pm_list($folder_id);

        // Prepare data for the templates (formatting and XSS prevention).
        foreach ($list as $message_id => $message)
        {
            $list[$message_id]["subject"] = htmlspecialchars($message["subject"]);
            $list[$message_id]["message"] = htmlspecialchars($message["message"]);
            $list[$message_id]["from_username"] = htmlspecialchars($message["from_username"]);
            $list[$message_id]["from_profile_url"] = phorum_get_url(PHORUM_PROFILE_URL, $message["from_user_id"]);
            $list[$message_id]["read_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=read", "folder_id=$folder_id", "pm_id=$message_id");
            $list[$message_id]["date"] = phorum_date($PHORUM["short_date"], $message["datestamp"]);
            foreach ($message["recipients"] as $rcpt_id => $rcpt) {
                $list[$message_id]["recipients"][$rcpt_id]["username"] = htmlspecialchars($rcpt["username"]);
            }

            // PMTODO backward compatibility with 1 recipient in all messages.
            // Once the interface is up to multiple recipients, this part can be
            // removed.
            reset($message['recipients']);
            list($rcpt_id, $rcpt) = each($message['recipients']);
            $list[$message_id]["to_profile_url"] = phorum_get_url(PHORUM_PROFILE_URL, $rcpt_id);
            $list[$message_id]["to_username"] = htmlspecialchars($rcpt["username"]);
            $list[$message_id]["read_flag"] = $rcpt["read_flag"];
        }

        // Setup template variables.
        $PHORUM["DATA"]["MESSAGECOUNT"] = count($list);
        $PHORUM["DATA"]["MESSAGES"] = $list;
        $PHORUM["DATA"]["FOLDERNAME"] = $PHORUM["DATA"]["PM_FOLDERS"][$folder_id]["name"];

        $template = "cc_pm_list";
        break;
    }
    
    // Read a single private message.
    case "read": {

        if(($message=phorum_db_pm_get($pm_id, $folder_id))) {

            // Mark the message read.
            if (! $message['read_flag']) {
                phorum_db_pm_setflag($message["pm_message_id"], PHORUM_PM_READ_FLAG, true);
            }
            
            // Run the message through the default message formatting.
            $message = phorum_pm_format($message);

            // Setup URL's and format date.
            $message["from_profile_url"]=phorum_get_url(PHORUM_PROFILE_URL, $message["from_user_id"]);
            $message["date"]=phorum_date($PHORUM["short_date"], $message["datestamp"]);
            
            // PMTODO backward compatibility with 1 recipient in all messages.
            // Once the interface is up to multiple recipients, this part can be
            // removed.
            reset($message['recipients']);
            list($rcpt_id, $rcpt) = each($message['recipients']);
            $message["to_profile_url"] = phorum_get_url(PHORUM_PROFILE_URL, $rcpt_id);
            $message["to_username"] = htmlspecialchars($rcpt["username"]);
            $message["read_flag"] = $rcpt["read_flag"];
            
            $PHORUM["DATA"]["MESSAGE"] = $message;
            $template = "cc_pm_read";
        
        } else {    

            // The message was not found. Show an error.
            $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMNotAvailable"];
            $template = "stdblock";
            return;
        }

        break;
    }
    
    // Post a new private message.
    case "post": {

        // Reply to a private message.
        if(isset($pm_id)) {

            $message=phorum_db_pm_get($pm_id);

            // Setup variables for the template.
            $msg["from_username"] = htmlspecialchars($PHORUM["user"]["username"]);
            $msg["keep"] = 0;
            if(substr($message["subject"], 0, 3) != "Re:"){
                $message["subject"] = "Re: ".$message["subject"];
            }
            $msg["subject"]=htmlspecialchars($message["subject"]);
            
            // PMTODO backward compatibility. This can be changed once we have
            // a new recipient selection system. But mind the $msg['to'] in
            // the quoted body code.
            $msg["to"]=htmlspecialchars($message["from_username"]);
            $msg["to_id"]=$message["from_user_id"];
                               
            // Build a quoted version of the message body.     
            $msg["message"] = phorum_strip_body($message["message"]);
            $msg["message"] = str_replace("\n", "\n> ", $msg["message"]);
            $msg["message"] = wordwrap(trim($msg["message"]), 50, "\n> ", true);
            $msg["message"] = "{$msg['to']} {$PHORUM['DATA']['LANG']['Wrote']}:\n".str_repeat("-", 55)."\n> {$msg['message']}\n\n\n";

            $PHORUM["DATA"]["MESSAGE"] = $msg;
            
        // Reply privately to a forum post.
        } elseif (isset($PHORUM["args"]["message_id"])) {

            $message = phorum_db_get_message($PHORUM["args"]["message_id"], "message_id", true);

            if (phorum_user_access_allowed(PHORUM_USER_ALLOW_READ) && ($PHORUM["forum_id"]==$message["forum_id"] || $message["forum_id"] == 0)) {
  
                // get url to the message board thread
                // TODO: would be nicer to get the url to the post within the thread
                $origurl = phorum_get_url(PHORUM_READ_URL, $message["thread"]);

                // Find the real username, because some mods rewrite the
                // username in the message table. There will be a better solution
                // for selecting recipients, but for now this will fix some
                // of the problems.
                $user = phorum_db_user_get($message["user_id"], false);

                $msg["from_username"] = htmlspecialchars($PHORUM["user"]["username"]);
                $msg["to"] = htmlspecialchars($user["username"]);
                $msg["to_id"] = $message["user_id"];
                $msg["keep"] = "0";
                $msg["subject"] = htmlspecialchars($message["subject"]);
                $msg["message"] = phorum_strip_body($message["body"]);
                $msg["message"] = str_replace("\n", "\n> ", $msg["message"]);
                $msg["message"] = wordwrap(trim($msg["message"]), 50, "\n> ", true);
                $msg["message"] = "{$PHORUM['DATA']['LANG']['InReplyTo']} {$origurl}\n{$msg['to']} {$PHORUM['DATA']['LANG']['Wrote']}:\n".str_repeat("-", 55)."\n> {$msg['message']}\n\n\n";
            
                $PHORUM["DATA"]["MESSAGE"] = $msg;
            }

        // Write a new private message. This part of the code will
        // also be run in case of errors.
        } else {

            $msg["preview"] = (empty($_POST["preview"])) ? 0 : 1;
            $msg["from_username"] = htmlspecialchars($PHORUM["user"]["username"]);
            $msg["to_id"] = (empty($_POST["to_id"])) ? "" : $_POST["to_id"];
            $msg["to"] = (empty($_POST["to"])) ? "" : htmlspecialchars($_POST["to"]);
            $msg["subject"] = (empty($_POST["subject"])) ? "" : $_POST["subject"];
            $msg["message"] = (empty($_POST["message"])) ? "" : $_POST["message"];
            $msg["keep"] = (empty($_POST["keep"])) ? 0 : 1;
            
            // formatting for preview
            if ($msg["preview"]) 
            {
                $preview = phorum_pm_format($msg);
                $preview["from_profile_url"] = '#';
                $preview["to_profile_url"] = '#';
                
                $PHORUM["DATA"]["PREVIEW"]   = $preview;
            }

            // Escape subject and message only now, because phorum_pm_format()
            // needs them unmodified.
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

            $PHORUM["DATA"]["MESSAGE"] = $msg;
        }
        
        $template = "cc_pm_post";
        break;
    }
}


      
// Make message count and quota information available in the templates.
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
