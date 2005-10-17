<?php

if(!defined("PHORUM")) return;

include_once("./include/thread_info.php");

/**
 * just returns to the list and exits the program
 */
function phorum_return_to_list()
{
    $PHORUM=$GLOBALS["PHORUM"];
    if(!empty($PHORUM["forum_id"])){
        phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
    }else{
        phorum_redirect_by_url(phorum_get_url(PHORUM_INDEX_URL));
    }
    exit();
}

function phorum_handle_edit_message()
{
    GLOBAL $PHORUM;

    $old_message = phorum_db_get_message($_POST['message_id']);

    // building the array of the new message
    $new_message = array("subject" => $_POST['subject'], "body" => $_POST['body']);

    if (phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)){
        if (!empty($_POST["author"])){
            $new_message["author"] = $_POST["author"];
        }
        if (!empty($_POST["email"])){
            $new_message["email"] = $_POST["email"];
        } elseif($old_message["user_id"] == 0) {
            $new_message["email"] = "";
        }
    }

    if(isset($_POST["special"]) && phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)){
        if(empty($_POST["parent"]) && $_POST["special"] == "sticky"){
            $new_message["forum_id"] = $PHORUM["forum_id"];
            $new_message["status"] = $old_message['status'];
            $new_message["sort"]   = PHORUM_SORT_STICKY;
        } elseif(empty($_POST["parent"]) && $_POST["special"] == "announcement" && $PHORUM["user"]["admin"]) {
            $new_message["forum_id"] = 0;
            $new_message["sort"]     = PHORUM_SORT_ANNOUNCEMENT;
            $new_message["closed"]   = 1;
            $new_message["status"] = $old_message['status'];
        }else{
            $new_message["forum_id"] = $PHORUM["forum_id"];
            $new_message["status"] = $old_message['status'];
            $new_message["sort"]   = PHORUM_SORT_DEFAULT;
        }
    }
    else{
        $new_message["forum_id"] = $PHORUM["forum_id"];
        $new_message["status"] = $old_message['status'];
        $new_message["sort"]   = $old_message['sort'];
    }
    
    if(empty($_POST["subject"])){
        $error = $PHORUM["DATA"]["LANG"]["ErrSubject"];
    }elseif (empty($_POST["body"])){
        $error = $PHORUM["DATA"]["LANG"]["ErrBody"];
    }

    if(isset($error)){
        $PHORUM['DATA']['ERROR'] = $error;
        $PHORUM['DATA']['FRM'] = 2;
        $PHORUM['DATA']['EDIT'] = $old_message;
        $PHORUM['DATA']['EDIT'] = $_POST;
        if(isset($PHORUM["DATA"]["EDIT"]["sort"])) { 
    		if($PHORUM["DATA"]["EDIT"]["sort"] == "sticky"){
    			$PHORUM["DATA"]["EDIT"]["special"] = PHORUM_SORT_STICKY;
    		}elseif($PHORUM["DATA"]["EDIT"]["sort"] == "announcement"){
    			$PHORUM["DATA"]["EDIT"]["special"] = PHORUM_SORT_ANNOUNCEMENT;
    		}
        }
        $PHORUM['DATA']['EDIT']['emailreply'] = phorum_db_get_if_subscribed($PHORUM['DATA']['EDIT']['forum_id'], $PHORUM['DATA']['EDIT']['thread'], $PHORUM['DATA']['EDIT']['user_id']);
        $PHORUM['DATA']["EDIT"]["mod_step"] = PHORUM_SAVE_EDIT_POST;
        $PHORUM['DATA']["URL"]["ACTION"] = phorum_get_url(PHORUM_MODERATION_ACTION_URL);
    }else{
        // copy the old meta data
        $new_message["meta"] = $old_message["meta"];

        if(isset($_POST['show_signature']) && $_POST['show_signature'])
            $new_message['meta']['show_signature']=1;
        else
            $new_message['meta']['show_signature']=0;

        $new_message["meta"]["edit_count"] = (empty($old_message["meta"]["edit_count"])) ? 1 : $old_message["meta"]["edit_count"] + 1;
        $new_message["meta"]["edit_date"] = time();
        $new_message["meta"]["edit_username"] = $PHORUM["user"]["username"];

        $new_message = phorum_hook("pre_edit", $new_message);

        // delete attachments
        if(isset($_POST["attachments"])){
            foreach($_POST["attachments"] as $file_id){
                phorum_db_file_delete($file_id);
                foreach($new_message["meta"]["attachments"] as $key=>$file_data){
                    if($file_data["file_id"]==$file_id){
                        unset($new_message["meta"]["attachments"][$key]);
                        break;
                    }
                }
            }
        }

        phorum_db_update_message($_POST['message_id'], $new_message);

        phorum_hook("post_edit", $_POST["message_id"]);
        
        // update children to the same sort setting
        if($old_message["parent_id"]==0 && $new_message["sort"]!=$old_message["sort"]){
            $messages=phorum_db_get_messages($old_message["thread"]);
            unset($messages["users"]);
            foreach($messages as $message_id=>$message){
                if($message["sort"]!=$new_message["sort"]){
                    $message["sort"]=$new_message["sort"];
                    phorum_db_update_message($message_id, $message);
                }
            }

            // The forum stats have to be updated. Announcements aren't
            // counted in the thread_count, so if switching to or
            // from announcement, the thread_count will change.
            phorum_db_update_forum_stats(true);
        }

        phorum_update_thread_info($old_message['thread']);

        if(isset($_POST['email_reply']) && $_POST['email_reply'] && isset($old_message['user_id'])){
            phorum_user_subscribe($old_message['user_id'], $old_message['forum_id'], $old_message['thread'], 0);
        }elseif(isset($old_message['user_id'])){
            phorum_user_unsubscribe($old_message['user_id'], $old_message['thread'], $old_message['forum_id']);
        }    
        $PHORUM['DATA']['MESSAGE'] = $PHORUM["DATA"]["LANG"]["MsgModEdited"];
    }
}

?>
