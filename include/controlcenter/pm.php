<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;
// if private messages are disabled, just show a simple error message
if (!$PHORUM["enable_pm"]){
    $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMDisabled"];
    $template = "stdblock";
    return;
}

if(!empty($_POST)){

    switch($_POST["action"]){

        case "delete":

            if(isset($_POST["to_delete"])){

                foreach($_POST["to_delete"] as $pm_id){
                    $loc_message=phorum_db_get_private_message($pm_id);
                    // check that its for the right user
                    if($loc_message['to_user_id'] == $PHORUM["user"]["user_id"]) {
                        phorum_db_update_private_message($pm_id, "to_del_flag", 1);
                    }
                }
            }

            if(isset($_POST["from_delete"])){

                foreach($_POST["from_delete"] as $pm_id){
                    $loc_message=phorum_db_get_private_message($pm_id);
                    if($loc_message['from_user_id'] == $PHORUM["user"]["user_id"]) {
                        phorum_db_update_private_message($pm_id, "from_del_flag", 1);
                    }
                }
                $PHORUM["args"]["page"]="sent";
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

                        if(!phorum_db_put_private_messages($_POST["to"], $to_user_id, $_POST["subject"], $_POST["message"], $_POST["keep"])){
                            $error=$PHORUM["DATA"]["LANG"]["PMNotSent"];
                        } else {
                            phorum_hook("pm_sent","");
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
    $loc_message=phorum_db_get_private_message($pm_id);
    if(empty($loc_message)){
        $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMNotAvailable"];
        $template = "stdblock";
        return;
    }

    switch($PHORUM["args"]["action"]){

        case "to_delete":
            // check that its for the right user
            if($loc_message['to_user_id'] == $PHORUM["user"]["user_id"]) {
              phorum_db_update_private_message($pm_id, "to_del_flag", 1);
            }
            $PHORUM["args"]["page"]="inbox";
            break;

        case "from_delete":
            // check that its for the right user
            if($loc_message['from_user_id'] == $PHORUM["user"]["user_id"]) {
                phorum_db_update_private_message($pm_id, "from_del_flag", 1);
            }
            $PHORUM["args"]["page"]="sent";
            break;

    }
}


if(empty($PHORUM["args"]["page"])) $PHORUM["args"]["page"]="inbox";

switch ($PHORUM["args"]["page"]) {

    case "inbox":
        // show message lists of incoming eamils

        $to_messages=phorum_db_get_private_messages($PHORUM["user"]["user_id"], "to");

        foreach($to_messages as $message){

            $msg=array();
            $msg["message_id"]=$message["private_message_id"];
            $msg["from"]=htmlspecialchars($message["from_username"]);
            $msg["subject"]=htmlspecialchars($message["subject"]);
            $msg["date"]=phorum_date($PHORUM["short_date"], $message["datestamp"]);
            $msg["read"]=$message["read_flag"];
            $msg["profile_url"]=phorum_get_url(PHORUM_PROFILE_URL, $message["from_user_id"]);
            $msg["read_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=read", "pm_id=".$message["private_message_id"]);

            $PHORUM["DATA"]["INBOX"][]=$msg;
        }

        $PHORUM["DATA"]["ACTION"]=phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );
        $PHORUM["DATA"]["FORUM_ID"]=$PHORUM["forum_id"];

        $template = "cc_pm_inbox";
        break;

    case "sent":
        // show message lists of outgoing eamils

        $from_messages=phorum_db_get_private_messages($PHORUM["user"]["user_id"], "from");

        foreach($from_messages as $message){

            $msg=array();
            $msg["message_id"]=$message["private_message_id"];
            $msg["to"]=htmlspecialchars($message["to_username"]);
            $msg["read"]=$message["read_flag"];
            $msg["date"]=phorum_date($PHORUM["short_date"], $message["datestamp"]);
            $msg["subject"]=htmlspecialchars($message["subject"]);
            $msg["profile_url"]=phorum_get_url(PHORUM_PROFILE_URL, $message["to_user_id"]);
            $msg["read_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=read", "pm_id=".$message["private_message_id"]);

            $PHORUM["DATA"]["SENT"][]=$msg;
        }

        $PHORUM["DATA"]["ACTION"]=phorum_get_url( PHORUM_CONTROLCENTER_ACTION_URL );
        $PHORUM["DATA"]["FORUM_ID"]=$PHORUM["forum_id"];

        $template = "cc_pm_sent";
        break;

    case "read":

        // show a single message

        $message=phorum_db_get_private_message($PHORUM["args"]["pm_id"]);
        $msg=array();

        // check that its for the right user
        if($message['to_user_id'] == $PHORUM["user"]["user_id"] || $message['from_user_id'] == $PHORUM["user"]["user_id"]) {

          if($message['to_user_id'] == $PHORUM["user"]["user_id"]) { // only read if not in sent
              phorum_db_update_private_message($PHORUM["args"]["pm_id"], "read_flag", 1);
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

            if($message["from_user_id"]==$PHORUM["user"]["user_id"]){
                $msg["delete_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "action=from_delete", "pm_id=".$message["private_message_id"]);
            } else {
                $msg["reply_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "page=post", "pm_id=".$message["private_message_id"]);
                $msg["delete_url"]=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PM, "action=to_delete", "pm_id=".$message["private_message_id"]);
            }
        }

        $PHORUM["DATA"]["MESSAGE"]=$msg;

        $template = "cc_pm_read";
        break;

    case "post":

        // post a private message

        if(isset($PHORUM["args"]["pm_id"])){

            // reply
            $message=phorum_db_get_private_message($PHORUM["args"]["pm_id"]);

            $msg["from"]=$PHORUM["user"]["username"];
            if($message["to_user_id"] == $PHORUM["user"]["user_id"]) {
              $msg["message_id"]=$message["private_message_id"];
              $msg["to"]=htmlspecialchars($message["from_username"]);
              $msg["to_id"]=$message["from_user_id"];
              $msg["keep"] = "0";

              if(substr($message["subject"], 0, 3)!="Re:"){
                  $message["subject"]="Re: ".$message["subject"];
              }
              $msg["subject"]=htmlspecialchars($message["subject"]);

              $msg["message"] = strip_body($message["message"]);
              $msg["message"] = str_replace("\n", "\n> ", $msg["message"]);
              $msg["message"] = wordwrap(trim($msg["message"]), 50, "\n> ", true);
              $msg["message"] = "{$msg['to']} {$PHORUM['DATA']['LANG']['Wrote']}:\n".str_repeat("-", 55)."\n> {$msg['message']}\n\n\n";
            }

        } else {

            // new message or error

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

            // reset them htmlencoded
            $msg["subject"] = htmlspecialchars($msg["subject"]);
            $msg["message"] = htmlspecialchars($msg["message"]);


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
                        $userinfo["username"] = htmlspecialchars($userinfo["username"]);
                        $userinfo["displayname"] = htmlspecialchars($userinfo["displayname"]);
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

?>
