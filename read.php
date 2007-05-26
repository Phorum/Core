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
define('phorum_page','read');

include_once("./common.php");
include_once("./include/email_functions.php");
include_once("./include/format_functions.php");

// for dev-purposes ..
//include_once('./include/timing.php');

//timing_start();

// set all our URL's ... we need these earlier
phorum_build_common_urls();

// checking read-permissions
if(!phorum_check_read_common()) {
  return;
}

// somehow we got to a folder
if($PHORUM["folder_flag"]){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
    phorum_redirect_by_url($dest_url);
    exit();
}

$newflagkey = $PHORUM["forum_id"]."-".$PHORUM['user']['user_id'];

if ($PHORUM["DATA"]["LOGGEDIN"]) { // reading newflags in

    $PHORUM['user']['newinfo'] = null;

    if($PHORUM['cache_newflags']) {
        $PHORUM['user']['newinfo']=phorum_cache_get('newflags',$newflagkey,$PHORUM['cache_version']);
    }

    if($PHORUM['user']['newinfo'] == null) {
        $PHORUM['user']['newinfo']=phorum_db_newflag_get_flags();
        if($PHORUM['cache_newflags']) {
            phorum_cache_put('newflags',$newflagkey,$PHORUM['user']['newinfo'],86400,$PHORUM['cache_version']);
        }
    }
}

$PHORUM["DATA"]["MODERATOR"] = phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

// Find out how many forums this user can moderate.
// If the user can moderate more than one forum, then
// present the move message moderation link.
if ($PHORUM["DATA"]["MODERATOR"]) {
    $build_move_url=false;
    $forums=phorum_db_get_forums(0, NULL, $PHORUM['vroot']);
    $modforums=0;
    foreach ($forums as $id=>$forum) {
        if ($forum["folder_flag"]==0 && phorum_user_moderate_allowed($id)) {
            $modforums++;
            if ($modforums > 1) {
                $build_move_url = true;
                break;
            }
        }
    }
}

// setup some stuff based on the url passed
if(empty($PHORUM["args"][1])) {
    // we have no forum-id given, redirect to the index
    phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
    exit();
} elseif(empty($PHORUM["args"][2]) || $PHORUM["args"][2]=="printview") {
    $thread = (int)$PHORUM["args"][1];
    $message_id = (int)$PHORUM["args"][1];

    // printview is requested
    if(isset($PHORUM["args"][2]) && $PHORUM["args"][2]=="printview") {
      $PHORUM["DATA"]["PRINTVIEW"]=1;
    } else {
      $PHORUM["DATA"]["PRINTVIEW"]=0;
    }
} else{
    if(!is_numeric($PHORUM["args"][2])) {
        $dest_url="";
        $newervar=(int)$PHORUM["args"][1];

        switch($PHORUM["args"][2]) {
            case "newer":
                $thread = phorum_db_get_neighbour_thread($newervar, "newer");
                break;
            case "older":
                $thread = phorum_db_get_neighbour_thread($newervar, "older");
                break;
            case "markthreadread":

                if($PHORUM["DATA"]["LOGGEDIN"]) {
                    // thread needs to be in $thread for the redirection
                    $thread = (int)$PHORUM["args"][1];
                    $thread_message=phorum_db_get_message($thread,'message_id');

                    $mids=array();
                    foreach($thread_message['meta']['message_ids'] as $mid) {
                        if(!isset($PHORUM['user']['newinfo'][$mid]) && $mid > $PHORUM['user']['newinfo']['min_id']) {
                            $mids[]=$mid;
                        }
                    }

                    $msg_count=count($mids);

                    // any messages left to update newinfo with?
                    if($msg_count > 0){
                        phorum_db_newflag_add_read($mids);
                        if($PHORUM['cache_newflags']) {
                            phorum_cache_remove('newflags',$newflagkey);
                            phorum_cache_remove('newflags_index',$newflagkey);
                        }
                        unset($mids);
                    }
                }
                break;
            case "gotonewpost":
                // thread needs to be in $thread for the redirection
                $thread = (int)$PHORUM["args"][1];
                $thread_message=phorum_db_get_message($thread,'message_id');
                $message_ids=$thread_message['meta']['message_ids'];

                foreach($message_ids as $mkey => $mid) {
                    // if already read, remove it from message-array
                    if(isset($PHORUM['user']['newinfo'][$mid]) || $mid <= $PHORUM['user']['newinfo']['min_id']) {
                        unset($message_ids[$mkey]);
                    }

                }

                // it could happen that they are all read
                if(count($message_ids)) {
                    asort($message_ids,SORT_NUMERIC); // make sure they are sorted


                    $new_message=array_shift($message_ids); // get the first element

                    if($PHORUM['threaded_read'] == 0) { // get new page
                        $new_page=ceil(phorum_db_get_message_index($thread,$new_message)/$PHORUM['read_length']);
                        $dest_url=phorum_get_url(PHORUM_READ_URL,$thread,$new_message,"page=$new_page");
                    } else { // for threaded
                        $dest_url=phorum_get_url(PHORUM_READ_URL,$thread,$new_message);
                    }
                } else {
                    // lets go back to the index if they are all read
                    $dest_url=phorum_get_url(PHORUM_LIST_URL);
                }

                break;


        }

        if(empty($dest_url)) {
            if($thread > 0) {
                $dest_url = phorum_get_url(PHORUM_READ_URL, $thread);
            } else{
                // we are either at the top or the bottom, go back to the list.
                $dest_url = phorum_get_url(PHORUM_LIST_URL);
            }
        }

        phorum_redirect_by_url($dest_url);
        exit();
    }

    $thread = (int)$PHORUM["args"][1];
    $message_id = (int)$PHORUM["args"][2];
    if(isset($PHORUM["args"][3]) && $PHORUM["args"][3]=="printview") {
      $PHORUM["DATA"]["PRINTVIEW"]=1;
    } else {
      $PHORUM["DATA"]["PRINTVIEW"]=0;
    }
}

//timing_mark("before database");

// determining the page if page isn't given and message_id != thread
$page=0;
if(!$PHORUM["threaded_read"]) {
    if(isset($PHORUM['args']['page']) && is_numeric($PHORUM["args"]["page"]) && $PHORUM["args"]["page"] > 0) {
                $page=(int)$PHORUM["args"]["page"];
    } elseif($message_id != $thread) {
                $page=ceil(phorum_db_get_message_index($thread,$message_id)/$PHORUM['read_length']);
    } else {
                $page=1;
    }
    if(empty($page)) {
        $page=1;
    }
}
/*
 thats the caching part
 */

if($PHORUM['cache_messages']) {

    $data=array();
    $data['users']=array();

    // is he a moderator and gets all messages?
    $approved=1;
    if($PHORUM["DATA"]["MODERATOR"]) {
        $approved = 0;
    }

    $message_index=phorum_cache_get('message_index',$PHORUM['forum_id']."-$thread-$approved");

    $skip_cache = 0;

    if($message_index == null) {
        // nothing in the cache, get it from the database and store it in the cache
        $data[$thread] = phorum_db_get_message($thread,"message_id");
        $data['users'][] = $data[$thread]['user_id'];

        if($PHORUM["DATA"]["MODERATOR"] && isset($data[$thread]["meta"]["message_ids_moderator"])) {
            $message_index=$data[$thread]['meta']['message_ids_moderator'];
        } else {
            $message_index=$data[$thread]['meta']['message_ids'];
        }

        if(is_array($data[$thread])) {

            // sort it as expected
            sort($message_index);

            // put it in the cache now
            phorum_cache_put('message_index',$PHORUM['forum_id']."-$thread-$approved",$message_index);

        } else {
            $skip_cache = 1;
        }

    }

    // if we errored out in the previous condition we need to skip this whole part!
    if(!$skip_cache) {


        // we expect this message_index to be ordered by message-id already!

        // in this case we need the reversed order
        if($PHORUM['threaded_read'] && isset($PHORUM["reverse_threading"]) && $PHORUM["reverse_threading"]) {
            $message_index=array_reverse($message_index);
        }

        $start=$PHORUM["read_length"]*($page-1);

        if(!$PHORUM['threaded_read']) {
            // get the message-ids from this page (only in flat mode)
            $message_ids_page = array_slice($message_index, $start,$PHORUM["read_length"]);
        } else {
            // we need all message in threaded read ...
            $message_ids_page = $message_index;
        }

        // we need the threadstarter too but its not available in the additional pages
        if($page > 1) {
            array_unshift($message_ids_page,$thread);
        }


        $cache_messages = phorum_cache_get('message',$message_ids_page);


        // check the returned messages if they were found in the cache
        $db_messages=array();

        $msg_not_in_cache=0;

        foreach($message_ids_page as $mid) {
            if(!isset($cache_messages[$mid])) {
                $db_messages[]=$mid;
                $msg_not_in_cache++;
            } else {
                $data[$mid]=$cache_messages[$mid];
                $data['users'][] = $data[$mid]['user_id'];
            }
        }

        if($msg_not_in_cache) {

            $db_messages = phorum_db_get_message($db_messages,'message_id');
            // store the found messages in the cache

            foreach($db_messages as $mid => $message) {
                phorum_cache_put('message',$mid,$message);
                $data[$mid]=$message;
                $data['users'][] = $data[$mid]['user_id'];
            }

            if($PHORUM['threaded_read'] && isset($PHORUM["reverse_threading"]) && $PHORUM["reverse_threading"]) {
                krsort($data);
            } else {
                ksort($data);
            }
        }

    } else {
        $data = array('users'=>array());
    }

} else {
    // Get the thread
    $data = phorum_db_get_messages($thread,$page);
}

if($page>1 && !isset($data[$thread])){
    $first_message = phorum_db_get_message($thread);
    $data["users"][]=$first_message["user_id"];
    $data[$first_message["message_id"]] = $first_message;
}

//timing_mark("after database");

if(!empty($data) && isset($data[$thread]) && isset($data[$message_id])) {

    $fetch_user_ids = null;
    if (isset($data['users'])) {
        $fetch_user_ids = $data['users'];
        unset($data['users']);
    }

    // remove the unneeded message bodies in threaded view
    // to avoid unnecessary formatting of bodies
    if ($PHORUM["threaded_read"] == 1 &&
        !(isset($PHORUM['TMP']['all_bodies_in_threaded_read']) &&
         !empty($PHORUM['TMP']['all_bodies_in_threaded_read']) ) ) {

            $remove_threaded_bodies=1;
            // the flag is used in the foreach-loop later on
    } else {
            $remove_threaded_bodies=0;
    }

    // build URL's that apply only here.
    if($PHORUM["float_to_top"]) {
        $PHORUM["DATA"]["URL"]["OLDERTHREAD"] = phorum_get_url(PHORUM_READ_URL, $data[$thread]["modifystamp"], "older");
        $PHORUM["DATA"]["URL"]["NEWERTHREAD"] = phorum_get_url(PHORUM_READ_URL, $data[$thread]["modifystamp"], "newer");
    } else{
        $PHORUM["DATA"]["URL"]["OLDERTHREAD"] = phorum_get_url(PHORUM_READ_URL, $thread, "older");
        $PHORUM["DATA"]["URL"]["NEWERTHREAD"] = phorum_get_url(PHORUM_READ_URL, $thread, "newer");
    }

    $PHORUM["DATA"]["URL"]["MARKTHREADREAD"] = phorum_get_url(PHORUM_READ_URL, $thread, "markthreadread");
    if($PHORUM["threaded_read"]) {
        $PHORUM["DATA"]["URL"]["PRINTVIEW"] = phorum_get_url(PHORUM_READ_URL, $thread, $message_id, "printview");
    } else {
        $PHORUM["DATA"]["URL"]["PRINTVIEW"] = phorum_get_url(PHORUM_READ_URL, $thread, "printview");
    }
    $thread_is_closed = (bool)$data[$thread]["closed"];

    // we might have more messages for mods
    if($PHORUM["DATA"]["MODERATOR"] && isset($data[$thread]["meta"]["message_ids_moderator"])) {
        $threadnum=count($data[$thread]['meta']['message_ids_moderator']);
    } else {
        $threadnum=$data[$thread]['thread_count'];
    }

    if(!$PHORUM["threaded_read"] && $threadnum > $PHORUM["read_length"]){
        $pages=ceil($threadnum/$PHORUM["read_length"]);

        if($pages<=11){
            $page_start=1;
        } elseif($pages-$page<5) {
            $page_start=$pages-10;
        } elseif($pages>11 && $page>6){
            $page_start=$page-5;
        } else {
            $page_start=1;
        }

        for($x=0;$x<11 && $x<$pages;$x++){
            $pageno=$x+$page_start;
            $PHORUM["DATA"]["PAGES"][] = array(
            "pageno"=>$pageno,
            "url"=>phorum_get_url(PHORUM_READ_URL, $thread, "page=$pageno")
            );
        }

        $PHORUM["DATA"]["CURRENTPAGE"]=$page;
        $PHORUM["DATA"]["TOTALPAGES"]=$pages;

        if($page_start>1){
            $PHORUM["DATA"]["URL"]["FIRSTPAGE"]=phorum_get_url(PHORUM_READ_URL, $thread, "page=1");
        }

        if($pageno<$pages){
            $PHORUM["DATA"]["URL"]["LASTPAGE"]=phorum_get_url(PHORUM_READ_URL, $thread, "page=$pages");
        }

        if($pages>$page){
            $nextpage=$page+1;
            $PHORUM["DATA"]["URL"]["NEXTPAGE"]=phorum_get_url(PHORUM_READ_URL, $thread, "page=$nextpage");
        }
        if($page>1){
            $prevpage=$page-1;
            $PHORUM["DATA"]["URL"]["PREVPAGE"]=phorum_get_url(PHORUM_READ_URL, $thread, "page=$prevpage");
        }
    }

    // fetch_user_ids filled from phorum_db_get_messages
    if(isset($fetch_user_ids) && count($fetch_user_ids)){
        $user_info=phorum_user_get($fetch_user_ids, false);
        // hook to modify user info
        if (isset($PHORUM["hooks"]["read_user_info"]))
            $user_info = phorum_hook("read_user_info", $user_info);
    }

    // URLS which are common for the thread
    if($PHORUM["DATA"]["MODERATOR"]) {
        if($build_move_url) {
                $URLS["move_url"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_MOVE_THREAD, $thread);
        }
        $URLS["merge_url"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_MERGE_THREAD, $thread);
        $URLS["close_url"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_CLOSE_THREAD, $thread);
        $URLS["reopen_url"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_REOPEN_THREAD, $thread);
    }

    // main loop for template setup
    $messages=array();
    $read_messages=array(); // needed for newinfo
    foreach($data as $key => $row) {

        // should we remove the bodies in threaded view
        if($remove_threaded_bodies && $row["message_id"]!=$thread && $row["message_id"] != $message_id) {
            unset($row["body"]); // strip body
        }

        // assign user data to the row
        if($row["user_id"] && isset($user_info[$row["user_id"]])){
            if(is_numeric($user_info[$row["user_id"]]["date_added"])){
                $user_info[$row["user_id"]]["raw_date_added"] = $user_info[$row["user_id"]]["date_added"];
                $user_info[$row["user_id"]]["date_added"] = phorum_relative_date($user_info[$row["user_id"]]["date_added"]);
            }
            if(strlen($user_info[$row["user_id"]]["posts"])>3 && !strstr($user_info[$row["user_id"]]["posts"], $PHORUM["thous_sep"])){
                $user_info[$row["user_id"]]["posts"] = number_format($user_info[$row["user_id"]]["posts"], 0, "", $PHORUM["thous_sep"]);
            }

            $row["user"]=$user_info[$row["user_id"]];
            unset($row["user"]["password"]);
            unset($row["user"]["password_tmp"]);
        }
        if(!($PHORUM["threaded_read"]==1) && $PHORUM["DATA"]["LOGGEDIN"] && $row['message_id'] > $PHORUM['user']['newinfo']['min_id'] && !isset($PHORUM['user']['newinfo'][$row['message_id']])) { // set this message as read
            $read_messages[] = array("id"=>$row['message_id'],"forum"=>$row['forum_id']);
        }
        // is the message unapproved?
        $row["is_unapproved"] = ($row['status'] < 0) ? 1 : 0;

        // all stuff that makes only sense for moderators or admin
        if($PHORUM["DATA"]["MODERATOR"]) {

            $row["URL"]["DELETE_MESSAGE"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_DELETE_MESSAGE, $row["message_id"]);
            $row["URL"]["DELETE_THREAD"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_DELETE_TREE, $row["message_id"]);
            $row["URL"]["EDIT"]=phorum_get_url(PHORUM_POSTING_URL, "moderation", $row["message_id"]);
            $row["URL"]["SPLIT"]=phorum_get_url(PHORUM_MODERATION_URL, PHORUM_SPLIT_THREAD, $row["message_id"]);
            if($row['is_unapproved']) {
              $row["URL"]["APPROVE"]=phorum_get_url(PHORUM_MODERATION_URL, PHORUM_APPROVE_MESSAGE, $row["message_id"]);
            } else {
              $row["URL"]["HIDE"]=phorum_get_url(PHORUM_MODERATION_URL, PHORUM_HIDE_POST, $row["message_id"]);
            }
            if($build_move_url) {
                $row["URL"]["MOVE"] = $URLS["move_url"];
            }
            $row["URL"]["MERGE"] = $URLS["merge_url"];
            $row["URL"]["CLOSE"] = $URLS["close_url"];
            $row["URL"]["REOPEN"] = $URLS["reopen_url"];
        }

        // allow editing only if logged in, allowed for forum, the thread is open,
        // its the same user, and its within the time restriction
        if($PHORUM["user"]["user_id"]==$row["user_id"] && phorum_user_access_allowed(PHORUM_USER_ALLOW_EDIT) &&
            !$thread_is_closed &&($PHORUM["user_edit_timelimit"] == 0 || $row["datestamp"] + ($PHORUM["user_edit_timelimit"] * 60) >= time())) {
            $row["edit"]=1;
            if(!$PHORUM["DATA"]["MODERATOR"]) {
                $row["URL"]["EDIT"]=phorum_get_url(PHORUM_POSTING_URL, "edit", $row["message_id"]);
            }
        }

        // this stuff is used in threaded and non threaded.
        $row["raw_short_datestamp"] = $row["datestamp"];
        $row["short_datestamp"] = phorum_date($PHORUM["short_date_time"], $row["datestamp"]);
        $row["raw_datestamp"] = $row["datestamp"];
        $row["datestamp"] = phorum_date($PHORUM["long_date_time"], $row["datestamp"]);
        $row["URL"]["READ"] = phorum_get_url(PHORUM_READ_URL, $row["thread"], $row["message_id"]);
        $row["URL"]["REPLY"] = phorum_get_url(PHORUM_REPLY_URL, $row["thread"], $row["message_id"]);
        $row["URL"]["QUOTE"] = phorum_get_url(PHORUM_REPLY_URL, $row["thread"], $row["message_id"], "quote=1");
        $row["URL"]["REPORT"] = phorum_get_url(PHORUM_REPORT_URL, $row["message_id"]);
        $row["URL"]["FOLLOW"] = phorum_get_url(PHORUM_FOLLOW_URL, $row["thread"]);

        // can only send private replies if the author is a registered user
        if ($PHORUM["enable_pm"] && $row["user_id"]) {
            $row["URL"]["PM"] = phorum_get_url(PHORUM_PM_URL, "page=send", "message_id=".$row["message_id"]);
        } else {
            $row["URL"]["PM"] = false;
        }

        // check if its the first message in the thread
        if($row["message_id"] == $row["thread"]) {
            $row["threadstart"] = true;
        } else{
            $row["threadstart"] = false;
        }

        // check if the default reply subject was used
        if($row["subject"] == "Re: ".$data[$thread]["subject"]){
            $row["default_reply"] = true;
        } else {
            $row["default_reply"] = false;
        }

        // should we show the signature?
        if(isset($row['body'])) {
            if(isset($row["user"]["signature"])
               && isset($row['meta']['show_signature']) && $row['meta']['show_signature']==1){

                   $phorum_sig=trim($row["user"]["signature"]);
                   if(!empty($phorum_sig)){
                       $row["body"].="\n\n$phorum_sig";
                   }
            }

            // add the edited-message to a post if its edited
            if(isset($row['meta']['edit_count']) && $row['meta']['edit_count'] > 0) {
                $editmessage = str_replace ("%count%", $row['meta']['edit_count'], $PHORUM["DATA"]["LANG"]["EditedMessage"]);
                $editmessage = str_replace ("%lastedit%", phorum_date($PHORUM["short_date_time"],$row['meta']['edit_date']),  $editmessage);
                $editmessage = str_replace ("%lastuser%", $row['meta']['edit_username'],  $editmessage);
                $row["body"].="\n\n\n\n$editmessage";
                if($row['meta']['edit_count'] > 0 && ($PHORUM["track_edits"] == PHORUM_EDIT_TRACK_ON || ($PHORUM["track_edits"] == PHORUM_EDIT_TRACK_MODERATOR && $PHORUM["DATA"]["MODERATOR"] ) ) ) {
                    $row["URL"]["CHANGES"] = phorum_get_url(PHORUM_CHANGES_URL, $row["message_id"]);
                }
            }
        }


        if(!empty($row["user_id"])) {
            $row["URL"]["PROFILE"] = phorum_get_url(PHORUM_PROFILE_URL, $row["user_id"]);
            // we don't normally put HTML in this code, but this makes it easier on template builders
            $row["linked_author"] = "<a href=\"".$row["URL"]["PROFILE"]."\">".htmlspecialchars($row["author"])."</a>";
        } elseif(!empty($row["email"])) {
            $row["URL"]["EMAIL"] = phorum_html_encode("mailto:$row[email]");
            // we don't normally put HTML in this code, but this makes it easier on template builders
            $row["linked_author"] = "<a href=\"".$row["URL"]["EMAIL"]."\">".htmlspecialchars($row["author"])."</a>";
        } else {
            $row["linked_author"] = htmlspecialchars($row["author"]);
        }

        // mask host if not a moderator
        if(empty($PHORUM["user"]["admin"]) && (empty($PHORUM["DATA"]["MODERATOR"]) || !PHORUM_MOD_IP_VIEW)){
            if($PHORUM["display_ip_address"]){
                if($row["moderator_post"]){
                    $row["ip"]=$PHORUM["DATA"]["LANG"]["Moderator"];
                } elseif(is_numeric(str_replace(".", "", $row["ip"]))){
                    $row["ip"]=substr($row["ip"],0,strrpos($row["ip"],'.')).'.---';
                } else {
                    $row["ip"]="---".strstr($row["ip"], ".");
                }

            } else {
                $row["ip"]="";
            }
        }

        if($PHORUM["max_attachments"]>0 && isset($row["meta"]["attachments"])){
            $PHORUM["DATA"]["ATTACHMENTS"]=true;
            $row["attachments"]=$row["meta"]["attachments"];
            // unset($row["meta"]["attachments"]);
            foreach($row["attachments"] as $key=>$file){
                $row["attachments"][$key]["size"]=phorum_filesize($file["size"]);
                $row["attachments"][$key]["name"]=htmlspecialchars($file['name']);
                $row["attachments"][$key]["url"]=phorum_get_url(PHORUM_FILE_URL, "file={$file['file_id']}");
            }
        }

        // newflag, if its NOT in newinfo AND newer than min_id,
        // then its a new message
        $row["new"]="";
        if ($PHORUM["DATA"]["LOGGEDIN"]){
            if (!isset($PHORUM['user']['newinfo'][$row['message_id']]) && $row['message_id'] > $PHORUM['user']['newinfo']['min_id']) {
                $row["new"]= $PHORUM["DATA"]["LANG"]["newflag"];
            }
        }

        $messages[$row["message_id"]]=$row;
    }

    if($PHORUM["threaded_read"]) {

        // don't move this up.  We want it to be conditional.
        include_once("./include/thread_sort.php");

        // run read-threads mods
        if (isset($PHORUM["hooks"]["readthreads"]))
            $messages = phorum_hook("readthreads", $messages);

        $messages = phorum_sort_threads($messages);

        if($PHORUM["DATA"]["LOGGEDIN"] && !isset($PHORUM['user']['newinfo'][$message_id]) && $message_id > $PHORUM['user']['newinfo']['min_id']) {
            $read_messages[] = array("id"=>$message_id,"forum"=>$messages[$message_id]['forum_id']);
        }

        // we have to loop again and create the urls for the Next and Previous links.
        foreach($messages as $key => $row) {

            if($PHORUM["count_views"]) {  // show viewcount if enabled
                  if($PHORUM["count_views"] == 2) { // viewcount as column
                      $PHORUM["DATA"]["VIEWCOUNT_COLUMN"]=true;
                      $messages[$key]["viewcount"]=$row['viewcount'];
                  } else { // viewcount added to the subject
                      $messages[$key]["subject"]=$row["subject"]." ({$row['viewcount']} {$PHORUM['DATA']['LANG']['Views']})";
                  }
            }


            $messages[$key]["URL"]["NEXT"] = $PHORUM["DATA"]["URL"]["NEWERTHREAD"];
            if(empty($last_key)) {
                $messages[$key]["URL"]["PREV"] = $PHORUM["DATA"]["URL"]["OLDERTHREAD"];
            } else{
                $messages[$key]["URL"]["PREV"] = phorum_get_url(PHORUM_READ_URL, $row["thread"], $last_key);
                $messages[$last_key]["URL"]["NEXT"] = phorum_get_url(PHORUM_READ_URL, $row["thread"], $row["message_id"]);
            }

            $last_key = $key;
        }
    }

    // run read mods
    if (isset($PHORUM["hooks"]["read"]))
        $messages = phorum_hook("read", $messages);

    // increment viewcount if enabled
    if($PHORUM['count_views'] && $PHORUM["status"]==PHORUM_MASTER_STATUS_NORMAL) {
        phorum_db_viewcount_inc($message_id);
    }

    // format messages
    $messages = phorum_format_messages($messages);

    // set up the data

    // this is the message that is the first in the thread
    $PHORUM["DATA"]["TOPIC"] = $messages[$thread];
    if($page>1){
        unset($messages[$thread]);
    }

    // this is the message that we are viewing in the threaded view.
    if ($PHORUM["threaded_read"]) {
        $PHORUM["DATA"]["MESSAGE"] = $messages[$message_id];
    }

    // this is all messages on the page
    $PHORUM["DATA"]["MESSAGES"] = $messages;

    // No htmlspecialchars() needed. The subject is already escaped.
    // Strip HTML tags from the HTML title. There might be HTML in
    // here, because of modules adding images and formatting.
    $PHORUM["DATA"]["HTML_TITLE"] = trim(strip_tags($PHORUM["threaded_read"] ? $PHORUM["DATA"]["MESSAGE"]["subject"] : $PHORUM["DATA"]["TOPIC"]["subject"]));
    $PHORUM["DATA"]["DESCRIPTION"] = htmlspecialchars(preg_replace('!\s+!s'," ",strip_tags(substr($PHORUM["DATA"]["TOPIC"]["body"],0,300))));

    // include the correct template

    $templates = array();

    if($PHORUM["threaded_read"] == 1) {
        $templates[] = "read_threads";
    } elseif($PHORUM["threaded_read"] == 2) {

        $templates[] = "read_hybrid";

    } else {

        $templates[] = "read";
    }
    if($PHORUM["DATA"]["LOGGEDIN"]) { // setting read messages really read
        phorum_db_newflag_add_read($read_messages);
        if($PHORUM['cache_newflags']) {
            phorum_cache_remove('newflags',$newflagkey);
            phorum_cache_remove('newflags_index',$newflagkey);
        }
    }

    // An anchor so clicking on a reply button can let the browser
    // jump to the editor or the closed thread message.
    if(isset($PHORUM["reply_on_read_page"]) && $PHORUM["reply_on_read_page"]) {
        $PHORUM["DATA"]["REPLY_ON_READ"] = true;
    }

    // Never show the reply box if the message is closed.
    if($thread_is_closed) {

        $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["ThreadClosed"];
        $templates[] = "message";

    } elseif (isset($PHORUM["reply_on_read_page"]) && $PHORUM["reply_on_read_page"]) {
        // Prepare the arguments for the posting.php script.
        $goto_mode = "reply";
        if (isset($PHORUM["args"]["quote"]) && $PHORUM["args"]["quote"]) {
            $goto_mode = "quote";
        }

        $PHORUM["postingargs"] = array(
            1 => $goto_mode,
            2 => $message_id,
            "as_include" => true
        );

        include("./posting.php");
    }

    phorum_output($templates);


} elseif($toforum=phorum_check_moved_message($thread)) { // is it a moved thread?

    $PHORUM["DATA"]["OKMSG"]=$PHORUM["DATA"]["LANG"]["MovedMessage"];
    $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_get_url(PHORUM_FOREIGN_READ_URL, $toforum, $thread);
    $PHORUM['DATA']["BACKMSG"]=$PHORUM["DATA"]["LANG"]["MovedMessageTo"];

    $PHORUM["DATA"]["HTML_TITLE"] = htmlspecialchars($PHORUM["DATA"]["HTML_TITLE"]);
    // have to include the header here for the Redirect
    phorum_output("message");

} else { // message not found
    $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["MessageNotFound"];
    $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
    $PHORUM['DATA']["BACKMSG"]=$PHORUM["DATA"]["LANG"]["BackToList"];

    $PHORUM["DATA"]["HTML_TITLE"] = htmlspecialchars($PHORUM["DATA"]["HTML_TITLE"]);
    // have to include the header here for the Redirect
    phorum_output("message");
}

// find out if the given thread has been moved to another forum
function phorum_check_moved_message($thread) {
    $forum_id=$GLOBALS['PHORUM']['forum_id'];
    $message=phorum_db_get_message($thread,'message_id',true);

    if(!empty($message) && $message['forum_id'] != $forum_id) {
        $ret=$message['forum_id'];
    } else {
        $ret=false;
    }
    return $ret;
}

//timing_mark("end");
//timing_print();

?>
