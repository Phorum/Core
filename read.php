<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

define('phorum_page','read');
require_once './common.php';

require_once './include/api/newflags.php';
require_once './include/api/format/messages.php';
require_once './include/api/thread.php';

// set all our URL's ... we need these earlier
phorum_build_common_urls();

// checking read-permissions
if (!phorum_check_read_common()) {
    return;
}

// somehow we got to a folder
if ($PHORUM["folder_flag"]) {
    phorum_api_redirect(PHORUM_INDEX_URL, $PHORUM['forum_id']);
}

$PHORUM["DATA"]["MODERATOR"] = phorum_api_user_check_access(
    PHORUM_USER_ALLOW_MODERATE_MESSAGES
);

// Find out how many forums this user can moderate.
// If the user can moderate more than one forum, then
// present the move message moderation link.
if ($PHORUM["DATA"]["MODERATOR"]) {
    $modforums = phorum_api_user_check_access(
        PHORUM_USER_ALLOW_MODERATE_MESSAGES,
        PHORUM_ACCESS_LIST
    );
    $build_move_url = count($modforums) >= 2;
}

// setup some stuff based on the url passed
if(empty($PHORUM["args"][1])) {
    // we have no thread-id given, redirect to the index
    phorum_api_redirect(PHORUM_LIST_URL);
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
}
else
{
    if(!is_numeric($PHORUM["args"][2])) {
        $dest_url="";
        $newervar=(int)$PHORUM["args"][1];
        $thread = 0;

        switch($PHORUM["args"][2])
        {
            case "newer":
                $thread = $PHORUM['DB']->get_neighbour_thread($newervar, "newer");
                break;

            case "older":
                $thread = $PHORUM['DB']->get_neighbour_thread($newervar, "older");
                break;

            case "markthreadread":
                // thread needs to be in $thread for the redirection
                $thread = (int) $PHORUM['args'][1];

                if ($PHORUM['user']['user_id']) {
                    phorum_api_newflags_markread(
                        $thread, PHORUM_MARKREAD_THREADS
                    );
                }

                // Could be called from list too, in which case we need
                // to redirect back to the list.
                if(isset($PHORUM["args"][3]) && $PHORUM["args"][3] == "list") {
                    phorum_api_redirect(PHORUM_LIST_URL);
                }
                break;

            case "gotonewpost":
                // thread needs to be in $thread for the redirection
                $thread = (int)$PHORUM["args"][1];

                // Find the first unread message_id.
                $new_message = phorum_api_newflags_firstunread($thread);

                if ($new_message) {
                    if ($PHORUM['threaded_read'] == 0) { // get new page
                        $new_page=ceil($PHORUM['DB']->get_message_index($thread,$new_message)/$PHORUM['read_length']);
                        $dest_url=phorum_api_url(PHORUM_READ_URL,$thread,$new_message,"page=$new_page");
                    } else { // for threaded
                        $dest_url=phorum_api_url(PHORUM_READ_URL,$thread,$new_message);
                    }
                }
                break;

        }

        if(empty($dest_url)) {
            if($thread > 0) {
                $dest_url = phorum_api_url(PHORUM_READ_URL, $thread);
            } else{
                // we are either at the top or the bottom, go back to the list.
                $dest_url = phorum_api_url(PHORUM_LIST_URL);
            }
        }

        phorum_api_redirect($dest_url);
    }

    $thread = (int)$PHORUM["args"][1];
    $message_id = (int)$PHORUM["args"][2];
    if(isset($PHORUM["args"][3]) && $PHORUM["args"][3]=="printview") {
      $PHORUM["DATA"]["PRINTVIEW"]=1;
    } else {
      $PHORUM["DATA"]["PRINTVIEW"]=0;
    }
}

// determining the page if page isn't given and message_id != thread
$page=0;
if(!$PHORUM["threaded_read"]) {
    if (isset($PHORUM['args']['page']) &&
        is_numeric($PHORUM["args"]["page"]) && $PHORUM["args"]["page"] > 0) {
        $page=(int)$PHORUM["args"]["page"];
    } elseif($message_id != $thread) {
        $page=ceil($PHORUM['DB']->get_message_index($thread,$message_id)/$PHORUM['read_length']);
    }
    if (empty($page)) {
        $page=1;
    }
}

/*
 thats the caching part
 */

if($PHORUM['cache_messages'] &&
   (!$PHORUM['count_views'] || !$PHORUM["threaded_read"])) {

    $data=array();
    $data['users']=array();

    // is he a moderator and gets all messages?
    $approved=1;
    if($PHORUM["DATA"]["MODERATOR"]) {
        $approved = 0;
    }

    $message_index=phorum_api_cache_get('message_index',$PHORUM['forum_id']."-$thread-$approved");

    $skip_cache = 0;

    if ($message_index == null) {
        // nothing in the cache, get it from the database and store it in the cache
        $data[$thread] = $PHORUM['DB']->get_message($thread,"message_id");

        // Check if we really have requested the thread.
        // If not, then we redirect back to the message list.
        if (!empty($data[$thread]['parent_id'])) {
            $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["MessageNotFound"];
            $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
            $PHORUM['DATA']["BACKMSG"]=$PHORUM["DATA"]["LANG"]["BackToList"];

            $PHORUM["DATA"]["HTML_TITLE"] = phorum_api_format_htmlspecialchars($PHORUM["DATA"]["HTML_TITLE"]);
            phorum_api_output("message");
            return;
        }

        if ($data[$thread]['user_id'] > 0) {
            $data['users'][] = $data[$thread]['user_id'];
        }

        if($PHORUM["DATA"]["MODERATOR"] && isset($data[$thread]["meta"]["message_ids_moderator"])) {
            $message_index=$data[$thread]['meta']['message_ids_moderator'];
        } else {
            $message_index=$data[$thread]['meta']['message_ids'];
        }

        if(is_array($data[$thread])) {

            // sort it as expected
            sort($message_index);

            // put it in the cache now
            phorum_api_cache_put('message_index',$PHORUM['forum_id']."-$thread-$approved",$message_index);

        } else {
            $skip_cache = 1;
        }

    }

    // if we errored out in the previous condition we need to skip this whole part!
    if (!$skip_cache)
    {
        // we expect this message_index to be ordered by message-id already!

        // in this case we need the reversed order
        if($PHORUM['threaded_read'] && !empty($PHORUM["reverse_threading"])) {
            $message_index=array_reverse($message_index);
        }

        $start=$PHORUM["read_length"]*($page-1);

        if (!$PHORUM['threaded_read']) {
            // get the message-ids from this page (only in flat mode)
            $message_ids_page = array_slice($message_index, $start,$PHORUM["read_length"]);
        } else {
            // we need all message in threaded read ...
            $message_ids_page = $message_index;
        }

        // we need the threadstarter too but its not available in the additional pages
        if ($page > 1) {
            array_unshift($message_ids_page,$thread);
        }

        // need to add the forum_id to the message-id keys to make it forum dependant
        foreach($message_ids_page as $key => $value) {
            $message_ids_page[$key] = $PHORUM["forum_id"]."-".$value;
        }


        $cache_messages = phorum_api_cache_get('message', $message_ids_page);

        // check the returned messages if they were found in the cache
        $db_messages=array();

        $msg_not_in_cache=0;

        foreach($message_ids_page as $cache_id) {
            list ($fid, $mid) = explode("-", $cache_id);
            if (!isset($cache_messages[$cache_id])) {
                $db_messages[]=$mid;
                $msg_not_in_cache++;
            } else {
                $data[$mid]=$cache_messages[$cache_id];
                if ($data[$mid]['user_id'] > 0) {
                    $data['users'][] = $data[$mid]['user_id'];
                }
            }
        }

        if($msg_not_in_cache) {

            $db_messages = $PHORUM['DB']->get_message($db_messages,'message_id');
            // store the found messages in the cache

            foreach($db_messages as $mid => $message) {
                phorum_api_cache_put('message',$PHORUM["forum_id"]."-".$mid,$message);
                $data[$mid]=$message;
                if ($data[$mid]['user_id'] > 0) {
                    $data['users'][] = $data[$mid]['user_id'];
                }
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
    $data = $PHORUM['DB']->get_messages($thread,$page);
}

if($page>1 && !isset($data[$thread])){
    $first_message = $PHORUM['DB']->get_message($thread);
    $data["users"][]=$first_message["user_id"];
    $data[$first_message["message_id"]] = $first_message;
}

if(!empty($data) && isset($data[$thread]) && isset($data[$message_id])) {

    // setup the url-templates needed later
    $read_url_template_thread = phorum_api_url(PHORUM_READ_URL, '%thread_id%');
    $read_url_template_both   = phorum_api_url(PHORUM_READ_URL, '%thread_id%','%message_id%');
    $read_page_url_template   = phorum_api_url(PHORUM_READ_URL, '%thread_id%','page=%page_num%');
    $edit_url_template        = phorum_api_url(PHORUM_POSTING_URL, '%action_id%', '%message_id%');
    $reply_url_template       = phorum_api_url(PHORUM_REPLY_URL, '%thread_id%', '%message_id%');
    $reply_url_template_quote = phorum_api_url(PHORUM_REPLY_URL, '%thread_id%', '%message_id%','quote=1');
    if($PHORUM["track_edits"]) {
        $changes_url_template = phorum_api_url(PHORUM_CHANGES_URL, '%message_id%');
    }
    if($PHORUM['DATA']['LOGGEDIN']) {
        $follow_url_template  = phorum_api_url(PHORUM_FOLLOW_URL, '%thread_id%');
        if ($PHORUM["enable_pm"]) {
            $pm_url_template = phorum_api_url(PHORUM_PM_URL, "page=send", "message_id=%message_id%");
        }
        $report_url_template = phorum_api_url(PHORUM_REPORT_URL, '%message_id%');
    }

    if($PHORUM["DATA"]["MODERATOR"]) {
            $edit_url_template          = phorum_api_url(PHORUM_POSTING_URL, "moderation", '%message_id%');
            $moderation_url_template    = phorum_api_url(PHORUM_MODERATION_URL, '%action_id%', '%message_id%', 'ref_message_id=%message_id%', 'ref_thread_id=%thread_id%');
    }
    if($PHORUM["max_attachments"]>0) {
        $attachment_url_template = phorum_api_url(PHORUM_FILE_URL, 'file=%file_id%', 'filename=%file_name%');
        $attachment_download_url_template = phorum_api_url(PHORUM_FILE_URL, 'file=%file_id%', 'filename=%file_name%', 'download=1');
    }

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
        $PHORUM["DATA"]["URL"]["OLDERTHREAD"] = phorum_api_url(PHORUM_READ_URL, $data[$thread]["modifystamp"], "older");
        $PHORUM["DATA"]["URL"]["NEWERTHREAD"] = phorum_api_url(PHORUM_READ_URL, $data[$thread]["modifystamp"], "newer");
    } else{
        $PHORUM["DATA"]["URL"]["OLDERTHREAD"] = phorum_api_url(PHORUM_READ_URL, $data[$thread]["datestamp"], "older");
        $PHORUM["DATA"]["URL"]["NEWERTHREAD"] = phorum_api_url(PHORUM_READ_URL, $data[$thread]["datestamp"], "newer");
    }

    if ($PHORUM["DATA"]["LOGGEDIN"]) {
        $PHORUM["DATA"]["URL"]["MARKTHREADREAD"] = phorum_api_url(PHORUM_READ_URL, $thread, "markthreadread");
    }
    if($PHORUM["threaded_read"]) {
        $PHORUM["DATA"]["URL"]["PRINTVIEW"] = phorum_api_url(PHORUM_READ_URL, $thread, $message_id, "printview");
    } else {
        $PHORUM["DATA"]["URL"]["PRINTVIEW"] = phorum_api_url(PHORUM_READ_URL, $thread, "printview",'page='.$page);
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
            'url'=>str_replace(array('%thread_id%','%page_num%'),array($thread,$pageno),$read_page_url_template),
            );
        }

        $PHORUM["DATA"]["CURRENTPAGE"]=$page;
        $PHORUM["DATA"]["TOTALPAGES"]=$pages;
        $PHORUM["DATA"]["URL"]["PAGING_TEMPLATE"]=str_replace('%thread_id%',$thread,$read_page_url_template);

        if($page_start>1){
            $PHORUM["DATA"]["URL"]["FIRSTPAGE"]=str_replace(array('%thread_id%','%page_num%'),array($thread,'1'),$read_page_url_template);
        }

        if($pageno<$pages){
            $PHORUM["DATA"]["URL"]["LASTPAGE"]=str_replace(array('%thread_id%','%page_num%'),array($thread,$pages),$read_page_url_template);
        }

        if($pages>$page){
            $nextpage=$page+1;
            $PHORUM["DATA"]["URL"]["NEXTPAGE"]=str_replace(array('%thread_id%','%page_num%'),array($thread,$nextpage),$read_page_url_template);
            $PHORUM["DATA"]["NEXTPAGE"] = $nextpage;
        }
        if($page>1){
            $prevpage=$page-1;
            $PHORUM["DATA"]["URL"]["PREVPAGE"]=str_replace(array('%thread_id%','%page_num%'),array($thread,$prevpage),$read_page_url_template);
            $PHORUM["DATA"]["PREVPAGE"] = $prevpage;
        }
    }

    // fetch_user_ids filled from $PHORUM['DB']->get_messages
    if(isset($fetch_user_ids) && count($fetch_user_ids)){
        $user_info=phorum_api_user_get($fetch_user_ids);
    }

    // URLS which are common for the thread
    if ($PHORUM['DATA']['MODERATOR'])
    {
        if ($build_move_url) {
            $URLS['move_url'] = str_replace(
                array('%action_id%', '%message_id%', '%thread_id%'),
                array(PHORUM_MOVE_THREAD, $thread, $thread),
                $moderation_url_template
            );
        }

        $URLS['merge_url']  = str_replace(
            array('%action_id%', '%message_id%', '%thread_id%'),
            array(PHORUM_MERGE_THREAD, $thread, $thread),
            $moderation_url_template
        );
        $URLS['close_url']  = str_replace(
            array('%action_id%', '%message_id%', '%thread_id%'),
            array(PHORUM_CLOSE_THREAD, $thread, $thread),
            $moderation_url_template
        );
        $URLS['reopen_url'] = str_replace(
            array('%action_id%', '%message_id%', '%thread_id%'),
            array(PHORUM_REOPEN_THREAD, $thread, $thread),
            $moderation_url_template
        );
        $URLS['sticky_url'] = str_replace(
            array('%action_id%', '%message_id%', '%thread_id%'),
            array(PHORUM_MAKE_STICKY, $thread, $thread),
            $moderation_url_template
        );
        $URLS['unsticky_url'] = str_replace(
            array('%action_id%', '%message_id%', '%thread_id%'),
            array(PHORUM_MAKE_UNSTICKY, $thread, $thread),
            $moderation_url_template
        );
    }

    // Add information about new messages to the thread.
    $data = phorum_api_newflags_apply_to_messages($data);

    // main loop for template setup
    $messages = array();
    $read_messages = array(); // needed for marking new messages as read later on
    foreach($data as $key => $row)
    {
        // should we remove the bodies in threaded view
        if($remove_threaded_bodies && $row["message_id"]!=$thread && $row["message_id"] != $message_id) {
            unset($row["body"]); // strip body
        }

        // assign user data to the row
        if ($row["user_id"] && isset($user_info[$row["user_id"]]))
        {
            if (is_numeric($user_info[$row["user_id"]]["date_added"]))
            {
                $user_info[$row["user_id"]]["raw_date_added"] =
                    $user_info[$row["user_id"]]["date_added"];

                $user_info[$row["user_id"]]["date_added"] =
                    phorum_api_format_relative_date(
                        $user_info[$row["user_id"]]["date_added"]);
            }

            if (strlen($user_info[$row["user_id"]]["posts"]) > 3 && !strstr($user_info[$row["user_id"]]["posts"], $PHORUM["thous_sep"])){
                $user_info[$row["user_id"]]["posts"] =
                    number_format(
                        $user_info[$row["user_id"]]["posts"], 0, "",
                        $PHORUM["thous_sep"]
                    );
            }

            $row["user"] = $user_info[$row["user_id"]];
            unset($row["user"]["password"]);
            unset($row["user"]["password_tmp"]);
        }

        // Keep track of unread messages that we have to mark as read.
        // When we are in threaded mode, then we only have to mark the
        // currently viewed message as read. While hybrid mode is a
        // form of threaded reading too, we have to mark all the messages
        // read since they are all visible.
        if (!empty($row['new']) &&
            ($PHORUM['threaded_read'] != PHORUM_THREADED_ON ||
             $row['message_id'] == $message_id)) {
            $read_messages[] = $row['message_id'];
        }

        // is the message unapproved?
        $row["is_unapproved"] = ($row['status'] < 0) ? 1 : 0;

        // all stuff that makes only sense for moderators or admin
        if ($PHORUM["DATA"]["MODERATOR"])
        {
            $m_id = $row['message_id'];
            $t_id  = $row['thread'];

            $row["URL"]["DELETE_MESSAGE"] = str_replace(
                array('%action_id%', '%message_id%', '%thread_id%'),
                array(PHORUM_DELETE_MESSAGE, $m_id, $t_id),
                $moderation_url_template
            );
            $row["URL"]["DELETE_THREAD"] = str_replace(
                array('%action_id%', '%message_id%', '%thread_id%'),
                array(PHORUM_DELETE_TREE, $m_id, $t_id),
                $moderation_url_template
            );
            $row["URL"]["EDIT"] = str_replace(
                array('%action_id%', '%message_id%', '%thread_id%'),
                array("moderation", $m_id, $t_id),
                $edit_url_template
            );
            $row["URL"]["SPLIT"] = str_replace(
                array('%action_id%', '%message_id%', '%thread_id%'),
                array(PHORUM_SPLIT_THREAD, $m_id, $t_id),
                $moderation_url_template
            );
            if ($row['is_unapproved']) {
                $row["URL"]["APPROVE"] = str_replace(
                    array('%action_id%', '%message_id%', '%thread_id%'),
                    array(PHORUM_APPROVE_MESSAGE, $m_id, $t_id),
                    $moderation_url_template
                );
            } else {
                $row["URL"]["HIDE"] = str_replace(
                    array('%action_id%', '%message_id%', '%thread_id%'),
                    array(PHORUM_HIDE_POST, $m_id, $t_id),
                    $moderation_url_template
                );
            }

            if ($build_move_url) {
                $row["URL"]["MOVE"] = $URLS["move_url"];
            }

            if ($row['sort'] == PHORUM_SORT_DEFAULT) {
                $row["URL"]["STICKY"] = $URLS["sticky_url"];
            } else {
                $row["URL"]["UNSTICKY"] = $URLS["unsticky_url"];
            }

            $row["URL"]["MERGE"]  = $URLS["merge_url"];
            $row["URL"]["CLOSE"]  = $URLS["close_url"];
            $row["URL"]["REOPEN"] = $URLS["reopen_url"];
        }

        // allow editing only if logged in, allowed for forum, the thread is open,
        // its the same user, and its within the time restriction
        if($PHORUM["user"]["user_id"]==$row["user_id"] &&
           phorum_api_user_check_access(PHORUM_USER_ALLOW_EDIT) &&
           !$thread_is_closed &&
           ($PHORUM["user_edit_timelimit"] == 0 ||
            $row["datestamp"] + ($PHORUM["user_edit_timelimit"] * 60) >= time())) {
            $row["edit"]=1;
            if(!$PHORUM["DATA"]["MODERATOR"]) {
                $row["URL"]["EDIT"] = str_replace(array('%action_id%','%message_id%'),array("edit", $row["message_id"]),$edit_url_template);
            }
        }

        // this stuff is used in threaded and non threaded.
        $row["raw_short_datestamp"] = $row["datestamp"];
        $row["short_datestamp"] = phorum_api_format_date($PHORUM["short_date_time"], $row["datestamp"]);
        $row["raw_datestamp"] = $row["datestamp"];
        $row["datestamp"] = phorum_api_format_date($PHORUM["long_date_time"], $row["datestamp"]);

        $row["URL"]["READ"]   = str_replace(
            array('%thread_id%', '%message_id%'),
            array($row["thread"], $row["message_id"]),
            $read_url_template_both
        );
        $row["URL"]["REPLY"] = str_replace(
            array('%thread_id%', '%message_id%'),
            array($row["thread"], $row["message_id"]),
            $reply_url_template
        );
        $row["URL"]["QUOTE"] = str_replace(
            array('%thread_id%', '%message_id%'),
            array($row["thread"], $row["message_id"]),
            $reply_url_template_quote
        );

        $row["URL"]["PM"] = false;
        if ($PHORUM["DATA"]["LOGGEDIN"]) {
            $row["URL"]["FOLLOW"] = str_replace('%thread_id%',$row['thread'],$follow_url_template);
            // can only send private replies if the author is a registered user
            if ($PHORUM["enable_pm"] && $row["user_id"]) {
                $row["URL"]["PM"] = str_replace(
                    '%message_id%', $row['message_id'], $pm_url_template);
            }
            $row["URL"]["REPORT"] = str_replace(
                '%message_id%', $row['message_id'], $report_url_template);
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

        if(isset($row['body']))
        {
            // Should we show the signature?
            if (isset($row['user']['signature']) &&
                !empty($row['meta']['show_signature'])) {

                   $phorum_sig = trim($row['user']['signature']);
                   if (!empty($phorum_sig)){
                       $row['body'] .= "\n\n$phorum_sig";
                   }
            }

            // Add the "edited X times by ..." message to a post
            // if it was edited.
            if (!empty($row['meta']['edit_count']))
            {
                $editmessage = str_replace ("%count%", $row['meta']['edit_count'], $PHORUM["DATA"]["LANG"]["EditedMessage"]);
                $editmessage = str_replace ("%lastedit%", phorum_api_format_date($PHORUM["short_date_time"],$row['meta']['edit_date']),  $editmessage);
                $editmessage = str_replace ("%lastuser%", $row['meta']['edit_username'], $editmessage);
                $row["body"].="\n\n\n\n$editmessage";
                if($row['meta']['edit_count'] > 0 && ($PHORUM["track_edits"] == PHORUM_EDIT_TRACK_ON || ($PHORUM["track_edits"] == PHORUM_EDIT_TRACK_MODERATOR && $PHORUM["DATA"]["MODERATOR"] ) ) ) {
                    $row["URL"]["CHANGES"] = str_replace('%message_id%',$row['message_id'],$changes_url_template);
                }
            }
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

        if ($PHORUM["max_attachments"]>0 && isset($row["meta"]["attachments"]))
        {
            $row["attachments"]=$row["meta"]["attachments"];
            // unset($row["meta"]["attachments"]);
            foreach($row["attachments"] as $key=>$file){
                $row["attachments"][$key]["size"] = phorum_api_format_filesize($file["size"]);
                $row["attachments"][$key]["name"] = phorum_api_format_htmlspecialchars($file['name']);
                $safe_file = preg_replace('/[^\w\_\-\.]/', '_', $file['name']);
                $safe_file = preg_replace('/_+/', '_', $safe_file);
                $row["attachments"][$key]["url"]  = str_replace(array('%file_id%','%file_name%'),array($file['file_id'],$safe_file),$attachment_url_template);
                $row["attachments"][$key]["download_url"]  = str_replace(array('%file_id%','%file_name%'),array($file['file_id'],$safe_file),$attachment_download_url_template);
            }
        }

        $messages[$row["message_id"]] = $row;
    }

    if($PHORUM["threaded_read"]) {

        // run read-threads mods
        if (isset($PHORUM["hooks"]["readthreads"])) {
            $messages = phorum_api_hook("readthreads", $messages);
        }

        $messages = phorum_api_thread_sort($messages);

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
                $messages[$key]["URL"]["PREV"]      = str_replace(array('%thread_id%','%message_id%'),array($row["thread"], $last_key),$read_url_template_both);
                $messages[$last_key]["URL"]["NEXT"] = str_replace(array('%thread_id%','%message_id%'),array($row["thread"], $row["message_id"]),$read_url_template_both);
            }

            $last_key = $key;
        }
    }

     /**
     * [hook]
     *     read
     *
     * [availability]
     *     Phorum 5
     *
     * [description]
     *     This hook can be used to pre-process all the messages.
     *
     * [category]
     *     Read messages
     *
     * [when]
     *     Right before the countview is incremented and before the messages
     *     have been formatted.
     *
     * [input]
     *     The array of messages to be shown and the currently shown message_id.
     *     NOTE: the read hook is also used in feed.php but <b>without</b>
     *     the $message_id parameter, thus be advised to make the second
     *     parameter optional (default to 0), message_id was added in 5.2.15
     *
     * [output]
     *     The array of messages. Data attached to messages can be added (e.g.
     *     for specific usage in custom templates). The current message_id
     *     cannot be changed that way.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_read($messages, $message_id = 0)
     *     {
     *         // extend all message with some data
     *         foreach ($messages as &$message) {
     *             $message['somedata'] = dummy();
     *         }
     *         // Do something special with the current message
     *         if ($message_id > 0) {
     *             $messages[$message_id]['somedata'] = 0;
     *          }
     *
     *         return $messages;
     *     }
     *     </hookcode>
     */
    // run read mods
    if (isset($PHORUM["hooks"]["read"]))
        $messages = phorum_api_hook("read", $messages, $message_id);

    // increment viewcount if enabled
    if($PHORUM['count_views'] &&
      (!isset($PHORUM['status']) || $PHORUM["status"]!=PHORUM_MASTER_STATUS_READ_ONLY)) {
        // increment viewcount per thread if enabled
        $inc_thread_id = NULL;
        if (!empty($PHORUM['count_views_per_thread'])) {
            $inc_thread_id = $thread;
        }

        $PHORUM['DB']->increment_viewcount($message_id, $inc_thread_id);
    }

    // format messages
    $messages = phorum_api_format_messages($messages);

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

    $PHORUM["DATA"]["DESCRIPTION"] = preg_replace('!\s+!s'," ", strip_tags($PHORUM["DATA"]["TOPIC"]["body"]));
    $PHORUM["DATA"]["DESCRIPTION"] = mb_substr($PHORUM["DATA"]["DESCRIPTION"], 0, 300, $PHORUM["DATA"]["HCHARSET"]);
    $PHORUM["DATA"]["DESCRIPTION"] = phorum_api_format_htmlspecialchars($PHORUM["DATA"]["DESCRIPTION"]);

    // add feed url
    if(isset($PHORUM['use_rss']) && $PHORUM['use_rss']){
        // one for the page-links
        $PHORUM["DATA"]["URL"]["FEED"] = phorum_api_url( PHORUM_FEED_URL, $PHORUM["forum_id"], $thread, "type=".$PHORUM["default_feed"] );

        // and again for the header-links
        $PHORUM['DATA']['FEEDS'] = array(array(
            'TITLE' => $PHORUM['DATA']['FEED'],
            'URL' => $PHORUM["DATA"]["URL"]["FEED"]
        ));
    }


    // include the correct template

    $templates = array();

    if ($PHORUM["threaded_read"] == 1) {
        $templates[] = "read_threads";
    } elseif ($PHORUM["threaded_read"] == 2) {
        $templates[] = "read_hybrid";
    } else {
        $templates[] = "read";
    }

    if($PHORUM["DATA"]["LOGGEDIN"] && count($read_messages)) {
        phorum_api_newflags_markread($read_messages, PHORUM_MARKREAD_MESSAGES);
    }

    // {REPLY_ON_READ} is set when message replies are done on
    // the read page. The template can use this to add the
    // #REPLY anchor to the page. This way, the browser can jump
    // to the editor when clicking a reply link.
    $PHORUM["DATA"]["REPLY_ON_READ"] = !empty($PHORUM["reply_on_read_page"]);


    if (isset($PHORUM["reply_on_read_page"]) && $PHORUM["reply_on_read_page"]) {

        // Never show the reply box if the message is closed.
        if($thread_is_closed) {

            $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["ThreadClosed"];
            $templates[] = "message";

        } else {
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

            include './posting.php';
        }
    }

    phorum_api_output($templates);

}
// Is it a moved thread?
elseif ($toforum=phorum_check_moved_message($thread))
{
    $PHORUM["DATA"]["OKMSG"]=$PHORUM["DATA"]["LANG"]["MovedMessage"];
    $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_api_url(PHORUM_FOREIGN_READ_URL, $toforum, $thread);
    $PHORUM['DATA']["BACKMSG"]=$PHORUM["DATA"]["LANG"]["MovedMessageTo"];

    $PHORUM["DATA"]["HTML_TITLE"] = phorum_api_format_htmlspecialchars($PHORUM["DATA"]["HTML_TITLE"]);
    // have to include the header here for the Redirect
    phorum_api_output("message");

}
// Message not found.
else
{
    $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["MessageNotFound"];
    $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
    $PHORUM['DATA']["BACKMSG"]=$PHORUM["DATA"]["LANG"]["BackToList"];

    $PHORUM["DATA"]["HTML_TITLE"] = phorum_api_format_htmlspecialchars($PHORUM["DATA"]["HTML_TITLE"]);
    // have to include the header here for the Redirect
    phorum_api_output("message");
}

// find out if the given thread has been moved to another forum
function phorum_check_moved_message($thread)
{
    global $PHORUM;
    $forum_id=$GLOBALS['PHORUM']['forum_id'];
    $message=$PHORUM['DB']->get_message($thread,'message_id',true);

    if(!empty($message) && $message['forum_id'] != $forum_id) {
        $ret=$message['forum_id'];
    } else {
        $ret=false;
    }
    return $ret;
}

?>
