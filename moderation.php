<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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
define('phorum_page','moderation');

include_once("./common.php");
include_once("./include/moderation_functions.php");
include_once("./include/thread_info.php");
include_once("./include/email_functions.php");

if(!phorum_check_read_common()) {
  return;
}

$PHORUM["DATA"]["MODERATOR"] = phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

$msgthd_id = (isset($_POST["thread"])) ? (int)$_POST["thread"] : (int)$PHORUM['args'][2];

$mod_step = (isset($_POST["mod_step"])) ? (int)$_POST["mod_step"] : (int)$PHORUM['args'][1];

if(empty($msgthd_id) || !$PHORUM["DATA"]["MODERATOR"]) {
   phorum_return_to_list();
}

// If the user is not fully logged in, send him to the login page.
// because moderation action can vary so much, the only safe bet is to send them
// to the referrer if they are not fully logged in
if(!$PHORUM["DATA"]["FULLY_LOGGEDIN"]){
    phorum_redirect_by_url(phorum_get_url(PHORUM_LOGIN_URL, "redir=".$_SERVER["HTTP_REFERER"]));
    exit();
}


$template="message";
// set all our URL's
phorum_build_common_urls();

// make it possible to override this var in a hook
$is_admin_user=$PHORUM["user"]["admin"];

// a hook for doing stuff in moderation, i.e. logging moderator-actions
if (isset($PHORUM["hooks"]["moderation"]))
    phorum_hook("moderation",$mod_step);


$invalidate_message_cache = array();

switch ($mod_step) {

   case PHORUM_DELETE_MESSAGE: // this is a message delete

        $message = phorum_db_get_message($msgthd_id);

        // A hook to allow modules to implement extra or different
        // delete functionality.
        $delete_handled = 0;
        if (isset($PHORUM["hooks"]["before_delete"]))
            list($delete_handled,$msg_ids,$msgthd_id,$message,$delete_mode) = phorum_hook("before_delete", array(0,0,$msgthd_id,$message,PHORUM_DELETE_MESSAGE));

        // Handle the delete action, unless a module already handled it.
        if (!$delete_handled) {

            // Delete the message from the database.
            phorum_db_delete_message($msgthd_id, PHORUM_DELETE_MESSAGE);

            // Delete the message attachments from the database.
            require_once('./include/api/file_storage.php');
            $files=phorum_db_get_message_file_list($msgthd_id);
            foreach($files as $file_id=>$data) {
                phorum_api_file_delete($file_id);
            }
        }

        // Run a hook for performing custom actions after cleanup.
        if (isset($PHORUM["hooks"]["delete"]))
            phorum_hook("delete", array($msgthd_id));

        $PHORUM['DATA']['OKMSG']="1 ".$PHORUM["DATA"]['LANG']['MsgDeletedOk'];
        if(isset($PHORUM['args']['old_forum']) && !empty($PHORUM['args']['old_forum'])) {
            $PHORUM['forum_id']=(int)$PHORUM['args']['old_forum'];
        }
        if(isset($PHORUM['args']["prepost"])) {
            $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_get_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED);
        } else {
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        }
        break;

   case PHORUM_DELETE_TREE: // this is a message delete

        $message = phorum_db_get_message($msgthd_id);

        $nummsgs = 0;

        // A hook to allow modules to implement extra or different
        // delete functionality.
        $delete_handled = 0;
        if (isset($PHORUM["hooks"]["before_delete"]))
            list($delete_handled,$msg_ids,$msgthd_id,$message,$delete_mode) = phorum_hook("before_delete", array(0,array(),$msgthd_id,$message,PHORUM_DELETE_TREE));

        if(!$delete_handled) {

            // Delete the message and all its replies.
            $msg_ids = phorum_db_delete_message($msgthd_id, PHORUM_DELETE_TREE);

            // Cleanup the attachments for all deleted messages.
            require_once('./include/api/file_storage.php');
            foreach($msg_ids as $id){
                $files=phorum_db_get_message_file_list($id);
                foreach($files as $file_id=>$data){
                    phorum_api_file_delete($file_id);
                }
            }

            // Check if we have moved threads to delete.
            // We unset the forum id, so phorum_db_get_messages()
            // will return messages with the same thread id in
            // other forums as well (those are the move notifications).
            $forum_id = $PHORUM["forum_id"];
            $PHORUM["forum_id"] = 0;
            $moved = phorum_db_get_messages($msgthd_id);
            $PHORUM["forum_id"] = $forum_id;
            foreach ($moved as $id => $data) {
                if (!empty($data["moved"])) {
                    phorum_db_delete_message($id, PHORUM_DELETE_MESSAGE);
                }
            }

        }

        $nummsgs=count($msg_ids);

        // Run a hook for performing custom actions after cleanup.
        if (isset($PHORUM["hooks"]["delete"]))
            phorum_hook("delete", $msg_ids);

        $PHORUM['DATA']['OKMSG']=$nummsgs." ".$PHORUM["DATA"]["LANG"]['MsgDeletedOk'];
        if(isset($PHORUM['args']['old_forum']) && !empty($PHORUM['args']['old_forum'])) {
            $PHORUM['forum_id']=(int)$PHORUM['args']['old_forum'];
        }
        if(isset($PHORUM['args']["prepost"])) {
            // add some additional args
            $addcode = "";
            if(isset($PHORUM['args']['moddays']) && is_numeric($PHORUM['args']['moddays'])) {
                $addcode.="moddays=".$PHORUM['args']['moddays'];
            }
            if(isset($PHORUM['args']['onlyunapproved']) && is_numeric($PHORUM['args']['onlyunapproved'])) {
                if(!empty($addcode))
                    $addcode.=",";

                $addcode.="onlyunapproved=".$PHORUM['args']['onlyunapproved'];
            }
            $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_get_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED,$addcode);
        } else {
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        }
        break;

   case PHORUM_MOVE_THREAD: // this is the first step of a message move

        $message = phorum_db_get_message($msgthd_id);

        $PHORUM['DATA']['URL']["ACTION"]=phorum_get_url(PHORUM_MODERATION_ACTION_URL);
        $PHORUM['DATA']["FORM"]["forum_id"]=$PHORUM["forum_id"];
        $PHORUM['DATA']["FORM"]["thread_id"]=$msgthd_id;
        $PHORUM['DATA']["FORM"]["mod_step"]=PHORUM_DO_THREAD_MOVE;
        $PHORUM['DATA']["FORM"]["subject"] =htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

        // get all the forums the moderator may move to
        $PHORUM['DATA']["MoveForumsOption"]="";

        $forums=phorum_db_get_forums(0, NULL, $PHORUM['vroot']);
        asort($forums);

        foreach($forums as $id=>$forum){
            if ($id == $PHORUM["forum_id"]) continue;
            // TODO: this does not match the check at the start of the read
            // TODO: and list scripts, where we check if this user has perms
            // TODO: for moderation of two or more forums, before we
            // TODO: enable the move feature. We should either check
            // TODO: for 2 or more moderated forums and check that moving
            // TODO: is only done between moderated forums or check for
            // TODO: 1 or more moderated forums and allow moving between
            // TODO: any two forums. Now we have a mix of those two.
            // add  && phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES, $id) if the
            // mod should only be able to move to forums he also moderates
            if($forum["folder_flag"]==0){
                 // it makes no sense to move to the forum we are in already
                 if($forum['forum_id'] != $PHORUM['forum_id']) {
                    $forum_data[strtolower($forum["name"])]=array("forum_id"=>$id, "name"=>$forum["name"]);
                 }
            }
        }

        $PHORUM['DATA']['FRM']=1;
        $PHORUM['DATA']['FORUMS']=$forum_data;
        $output=true;

        $template="move_form";

        break;

   case PHORUM_DO_THREAD_MOVE: // this is the last step of a message move

        $movetoid=(int)$_POST['moveto'];

        // only do something if a forum was selected
        if(empty($movetoid)) {
            $PHORUM['DATA']['MESSAGE']=$PHORUM["DATA"]['LANG']['MsgMoveSelectForum'];
        } else {
            $PHORUM['DATA']['OKMSG']=$PHORUM["DATA"]['LANG']['MsgMoveOk'];
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
            $message = phorum_db_get_message($msgthd_id);

            // find out if we have a notification-message already in this
            // target-forum for this thread ... it doesn't make sense to keep this
            // message any longer as the thread has reappeared on its original location
            $temp_forum_id=$PHORUM['forum_id'];
            $PHORUM['forum_id']=$movetoid;
            $check_messages=phorum_db_get_messages($msgthd_id);

            unset($check_messages['users']);

            // ok, we found exactly one message of this thread in the target forum
            if(is_array($check_messages) && count($check_messages) == 1) {
                // ... going to delete it
                $tmp_message=array_shift($check_messages);
                $retval=phorum_db_delete_message($tmp_message['message_id']);
            }

            $PHORUM['forum_id']=$temp_forum_id;

            // Move the thread to another forum.
            phorum_db_move_thread($msgthd_id, $movetoid);

            // Create a new message in place of the old one to notify
            // visitors that the thread was moved.
            if(isset($_POST['create_notification']) && $_POST['create_notification']) {
                $newmessage = $message;
                $newmessage['body']=" -- moved topic -- ";
                $newmessage['moved']=1;
                $newmessage['sort']=PHORUM_SORT_DEFAULT;
                unset($newmessage['message_id']);

                phorum_db_post_message($newmessage);
            }
            if (isset($PHORUM["hooks"]["move_thread"]))
                phorum_hook("move_thread", $msgthd_id);

            foreach ($message['meta']['message_ids'] as $message_id) {
                $invalidate_message_cache[] = array(
                    "message_id" => $message_id,
                    "forum_id"   => $message['forum_id']
                );
            }
        }
        break;

   case PHORUM_CLOSE_THREAD: // we have to close a thread

        $PHORUM['DATA']['OKMSG']=$PHORUM["DATA"]['LANG']['ThreadClosedOk'];
        $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        phorum_db_close_thread($msgthd_id);
        if (isset($PHORUM["hooks"]["close_thread"]))
            phorum_hook("close_thread", $msgthd_id);

        $invalidate_message_cache[] = array(
            "message_id" => $msgthd_id,
            "forum_id"   => $PHORUM["forum_id"]
        );

        break;

    case PHORUM_REOPEN_THREAD: // we have to reopen a thread

        $PHORUM['DATA']['OKMSG']=$PHORUM["DATA"]['LANG']['ThreadReopenedOk'];
        $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        phorum_db_reopen_thread($msgthd_id);
        if (isset($PHORUM["hooks"]["reopen_thread"]))
            phorum_hook("reopen_thread", $msgthd_id);

        $invalidate_message_cache[] = array(
            "message_id" => $msgthd_id,
            "forum_id"   => $PHORUM["forum_id"]
        );

        break;

    case PHORUM_APPROVE_MESSAGE: // approving a message

        $PHORUM['DATA']['OKMSG']="1 ".$PHORUM["DATA"]['LANG']['MsgApprovedOk'];

        $old_message = phorum_db_get_message($msgthd_id);
        $newpost=array("status"=>PHORUM_STATUS_APPROVED);

        // setting the new status
        phorum_db_update_message($msgthd_id, $newpost);

        // updating the thread-info
        phorum_update_thread_info($old_message['thread']);

        // updating the forum-stats
        phorum_db_update_forum_stats(false, 1, $old_message["datestamp"]);

        if (isset($PHORUM["hooks"]["after_approve"]))
            phorum_hook("after_approve", array($old_message, PHORUM_APPROVE_MESSAGE));

        if($old_message['status'] != PHORUM_STATUS_HIDDEN ) {
          phorum_email_notice($old_message);
        }

        if(isset($PHORUM['args']['old_forum']) && is_numeric($PHORUM['args']['old_forum'])) {
            $PHORUM['forum_id']=(int)$PHORUM['args']['old_forum'];
        }


        if(isset($PHORUM['args']["prepost"])) {
            // add some additional args
            $addcode = "";
            if(isset($PHORUM['args']['moddays']) && is_numeric($PHORUM['args']['moddays'])) {
                $addcode.="moddays=".$PHORUM['args']['moddays'];
            }
            if(isset($PHORUM['args']['onlyunapproved']) && is_numeric($PHORUM['args']['onlyunapproved'])) {
                if(!empty($addcode))
                    $addcode.=",";
                $addcode.="onlyunapproved=".$PHORUM['args']['onlyunapproved'];
            }
            $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_get_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED,$addcode);
        } else {
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        }
        break;

    case PHORUM_APPROVE_MESSAGE_TREE: // approve a message and all answers to it

        $old_message = phorum_db_get_message($msgthd_id);
        $newpost=array("status"=>PHORUM_STATUS_APPROVED);

        $mids = phorum_db_get_messagetree($msgthd_id, $old_message["forum_id"]);
        // make an array from the string
        $mids_arr=explode(",",$mids);

        // count the entries for later use
        $num_approved=count($mids_arr);
        foreach($mids_arr as $key => $mid) {
            // setting the new status
            phorum_db_update_message($mid, $newpost);

        }

        // updating the thread-info
        phorum_update_thread_info($old_message['thread']);

        // updating the forum-stats
        phorum_db_update_forum_stats(false, "+$num_approved", $old_message["datestamp"]);

        if (isset($PHORUM["hooks"]["after_approve"]))
            phorum_hook("after_approve", array($old_message, PHORUM_APPROVE_MESSAGE_TREE));

        $PHORUM['DATA']['OKMSG']="$num_approved ".$PHORUM['DATA']['LANG']['MsgApprovedOk'];
        if(isset($PHORUM['args']["prepost"])) {
            // add some additional args
            $addcode = "";
            if(isset($PHORUM['args']['moddays']) && is_numeric($PHORUM['args']['moddays'])) {
                $addcode.="moddays=".$PHORUM['args']['moddays'];
            }
            if(isset($PHORUM['args']['onlyunapproved']) && is_numeric($PHORUM['args']['onlyunapproved'])) {
                if(!empty($addcode))
                    $addcode.=",";

                $addcode.="onlyunapproved=".$PHORUM['args']['onlyunapproved'];
            }
            $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_get_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED,$addcode);
        } else {
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        }
        break;

    case PHORUM_HIDE_POST: // hiding a message (and its replies)

        $old_message = phorum_db_get_message($msgthd_id);
        $newpost=array("status"=>PHORUM_STATUS_HIDDEN);

        $mids = phorum_db_get_messagetree($msgthd_id, $old_message["forum_id"]);
        // make an array from the string
        $mids_arr=explode(",",$mids);

        // count the entries for later use
        $num_hidden=count($mids_arr);
        foreach($mids_arr as $key => $mid) {
            // setting the new status
            phorum_db_update_message($mid, $newpost);

        }

        if (isset($PHORUM["hooks"]["hide"]))
            phorum_hook("hide", $msgthd_id);

        // updating the thread-info
        phorum_update_thread_info($old_message['thread']);

        // updating the forum-stats
        phorum_db_update_forum_stats(false, "-$num_hidden", $old_message["datestamp"]);

        $PHORUM['DATA']['OKMSG']="$num_hidden ".$PHORUM['DATA']['LANG']['MsgHiddenOk'];
        if(isset($PHORUM['args']["prepost"])) {
            $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_get_url(PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED);
        } else {
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        }
        break;

   case PHORUM_MERGE_THREAD: // this is the first step of a thread merge

        $template="merge_form";
        $PHORUM['DATA']['URL']["ACTION"]     = phorum_get_url(PHORUM_MODERATION_ACTION_URL);
        $PHORUM['DATA']["FORM"]["forum_id"]  = $PHORUM["forum_id"];
        $PHORUM['DATA']["FORM"]["thread_id"] = $msgthd_id;
        $PHORUM['DATA']["FORM"]["mod_step"]  = PHORUM_DO_THREAD_MERGE;

        // the moderator selects the target thread to merge to
        $merge_t1 = phorum_moderator_data_get('merge_t1');
        if( !$merge_t1 || $merge_t1==$msgthd_id ) {
            phorum_moderator_data_put('merge_t1', $msgthd_id);
            $PHORUM['DATA']["FORM"]["merge_none"] =true;
            $message = phorum_db_get_message($merge_t1, "message_id", true);
            $PHORUM['DATA']["FORM"]["merge_subject1"] =htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        }
        // the moderator selects the source thread to merge from
        else {
            $PHORUM['DATA']["FORM"]["merge_t1"] =$merge_t1;
            $message = phorum_db_get_message($merge_t1, "message_id", true);
            $PHORUM['DATA']["FORM"]["merge_subject1"] =htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            $message = phorum_db_get_message($msgthd_id);
            $PHORUM['DATA']["FORM"]["thread_subject"] =htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
        }
        break;

   case PHORUM_DO_THREAD_MERGE: // this is the last step of a thread merge

        if( isset($_POST['thread1']) && $_POST['thread1']) {
            // Commit Thread Merge
            settype($_POST['thread1'], "int");
            settype($_POST['thread'], "int"); // Thread 2
            $PHORUM['DATA']['OKMSG'] = $PHORUM["DATA"]['LANG']['MsgMergeOk'];
            $PHORUM['DATA']["URL"]["REDIRECT"] = $PHORUM["DATA"]["URL"]["LIST"];
            $PHORUM["reverse_threading"] = 0;

            // Get the target thread.
            $target =phorum_db_get_message($_POST['thread1'], "message_id", true);
            if (!$target) trigger_error(
                "Can't retrieve target thread " . $_POST['thread1'],
                E_USER_ERROR
            );

            // Get all messages from the thread that we have to merge.
            $merge_messages=phorum_db_get_messages($_POST['thread']);
            unset($merge_messages['users']);

            // Create new messages in the target thread for
            // all messages that have to be merged.
            $msgid_translation=array();
            foreach($merge_messages as $msg)
            {
                $oldid=$msg['message_id'];

                $msg['thread']   = $target['thread'];   // the thread we merge with
                $msg['forum_id'] = $target['forum_id']; // the forum_id of the new thread
                $msg['sort']     = $target['sort'];     // the sort type of the new thread

                if($msg['message_id'] == $msg['thread']) {
                    $msg['parent_id']=$target['thread'];
                } elseif(isset($msgid_translation[$msg['parent_id']])) {
                    $msg['parent_id']=$msgid_translation[$msg['parent_id']];
                } else {
                    $msg['parent_id']=$msg['thread'];
                }

                unset($msg['message_id']);
                unset($msg['modifystamp']);

                phorum_db_post_message($msg,true);

                // Link attached files to the new message id.
                $linked_files = phorum_db_get_message_file_list($oldid);
                foreach ($linked_files as $linked_file) {
                    phorum_db_file_link($linked_file["file_id"], $msg["message_id"], PHORUM_LINK_MESSAGE);
                }

                // save the new message-id for later use
                $msgid_translation[$oldid]=$msg['message_id'];
            }

            // deleting messages which are now doubled
            phorum_db_delete_message($_POST['thread'], PHORUM_DELETE_TREE);

            // update message count / stats
            phorum_db_update_forum_stats(true);
            // change forum_id for the following calls to update the right forum
            $PHORUM["forum_id"] =$target['forum_id'];
            // update message count / stats
            phorum_update_thread_info($target['thread']);
            phorum_db_update_forum_stats(true);
        } else {
            // Cancel Thread Merge
            $PHORUM['DATA']['OKMSG']=$PHORUM["DATA"]['LANG']['MsgMergeCancel'];
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
        }

        // unset temporary moderator_data
        phorum_moderator_data_remove('merge_t1');

        break;

   case PHORUM_SPLIT_THREAD: // this is the first step of a thread split

           $PHORUM['DATA']['URL']["ACTION"]=phorum_get_url(PHORUM_MODERATION_ACTION_URL);
           $PHORUM['DATA']["FORM"]["forum_id"]=$PHORUM["forum_id"];
           $message =phorum_db_get_message($msgthd_id);
           $PHORUM['DATA']["FORM"]["thread_id"]=$message["thread"];
           $PHORUM['DATA']["FORM"]["message_id"]=$msgthd_id;
           $PHORUM['DATA']["FORM"]["message_subject"]=htmlspecialchars($message["subject"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
           $PHORUM['DATA']["FORM"]["mod_step"]=PHORUM_DO_THREAD_SPLIT;
           $template="split_form";
           break;

   case PHORUM_DO_THREAD_SPLIT: // this is the last step of a thread split

           $PHORUM['DATA']['OKMSG']=$PHORUM["DATA"]['LANG']['MsgSplitOk'];
           $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
           settype($_POST['forum_id'], "int");
           settype($_POST['message'], "int");
           settype($_POST['thread'], "int");
           phorum_db_split_thread($_POST['message'],$_POST['forum_id']);
           // update message count / stats
           phorum_update_thread_info($_POST['thread']);
           phorum_update_thread_info($_POST['message']);
           phorum_db_update_forum_stats(true);
           break;

    default:

        if(!isset($PHORUM['DATA']['OKMSG'])) $PHORUM['DATA']['OKMSG']="";
        $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
}

// remove the affected messages from the cache if caching is enabled.
if ($PHORUM['cache_messages']) {
    foreach($invalidate_message_cache as $message) {
        phorum_cache_remove('message', $message["message_id"]);
        phorum_db_update_forum(array('forum_id'=>$PHORUM['forum_id'],'cache_version'=>($PHORUM['cache_version']+1)));
    }
}


if(!isset($PHORUM['DATA']['BACKMSG'])) {
    $PHORUM['DATA']["BACKMSG"]=$PHORUM['DATA']["LANG"]["BackToList"];
}

phorum_output($template);

?>
