<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2006  Phorum Development Team                              //
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
define('phorum_page','list');

include_once("./common.php");
include_once("./include/format_functions.php");
//include_once('./include/timing.php');

//timing_start();

// set all our common URL's
phorum_build_common_urls();

if(!phorum_check_read_common()) {
  return;
}


if(empty($PHORUM["forum_id"])){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL);
    phorum_redirect_by_url($dest_url);
    exit();
}

// somehow we got to a folder in list.php
if($PHORUM["folder_flag"]){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
    phorum_redirect_by_url($dest_url);
    exit();
}

$newflagkey = $PHORUM["forum_id"]."-".$PHORUM['user']['user_id'];

// check for markread
if (!empty($PHORUM["args"][1]) && $PHORUM["args"][1] == 'markread' && $PHORUM["DATA"]["LOGGEDIN"]){
    // setting all posts read
    unset($PHORUM['user']['newinfo']);
    phorum_db_newflag_allread();
    if($PHORUM['cache_newflags']) {
        phorum_cache_remove('newflags',$newflagkey);
        phorum_cache_remove('newflags_index',$newflagkey);
    }


    // redirect to a fresh list without markread in url
    $dest_url = phorum_get_url(PHORUM_LIST_URL);
    phorum_redirect_by_url($dest_url);
    exit();

}

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

// figure out what page we are on
if (empty($PHORUM["args"]["page"]) || !is_numeric($PHORUM["args"]["page"]) || $PHORUM["args"]["page"] < 0){
    $page=1;
} else {
    $page=intval($PHORUM["args"]["page"]);
}
$offset=$page-1;

// check the moderation-settings
$PHORUM["DATA"]["MODERATOR"] = phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

$build_move_url=false;
if($PHORUM["DATA"]["MODERATOR"]) {
    // find out how many forums this user can moderate
    $forums=phorum_db_get_forums(0,-1,$PHORUM['vroot']);

    $modforums=0;
    foreach($forums as $id=>$forum){
        if($forum["folder_flag"]==0 && phorum_user_moderate_allowed($id)){
            $modforums++;
        }
        if($modforums > 1) {
            $build_move_url=true;
            break;
        }
    }
}


if($PHORUM['threaded_list']) { // make it simpler :)
    $PHORUM["list_length"] = $PHORUM['list_length_threaded'];
} else {
    $PHORUM["list_length"] = $PHORUM['list_length_flat'];
}

// Figure out paging for threaded and flat mode. Sticky messages
// are in the thread_count, but because these are handled as a separate
// list (together with the announcements), they should not be included
// in the pages computation.
$pages=ceil(($PHORUM["thread_count"] - $PHORUM['sticky_count']) / $PHORUM["list_length"]);

// If we only have stickies and/of announcements, the number of pages
// will be zero. In that case, simply use one page.
if ($pages == 0) $pages = 1;

$pages_shown = (isset($PHORUM["TMP"]["list_pages_shown"])) ? $PHORUM["TMP"]["list_pages_shown"] : 11;

// first $pages_shown pages
if($page - floor($pages_shown/2) <= 0  || $page <= $pages_shown){
    $page_start=1;

// last $pages_shown pages
} elseif($page > $pages - floor($pages_shown/2)) {
    $page_start = $pages - $pages_shown + 1;

// all others
} else {
    $page_start = $page - floor($pages_shown/2);
}

$pageno=1;

for($x=0;$x<$pages_shown && $x<$pages;$x++){
    $pageno=$x+$page_start;
    $PHORUM["DATA"]["PAGES"][] = array(
    "pageno"=>$pageno,
    "url"=>phorum_get_url(PHORUM_LIST_URL, $PHORUM["forum_id"], "page=$pageno")
    );
}


$PHORUM["DATA"]["CURRENTPAGE"]=$page;
$PHORUM["DATA"]["TOTALPAGES"]=$pages;

if($page_start>1){
    $PHORUM["DATA"]["URL"]["FIRSTPAGE"]=phorum_get_url(PHORUM_LIST_URL, $PHORUM["forum_id"], "page=1");
}

if($pageno<$pages){
    $PHORUM["DATA"]["URL"]["LASTPAGE"]=phorum_get_url(PHORUM_LIST_URL, $PHORUM["forum_id"], "page=$pages");
}

if($pages>$page){
    $nextpage=$page+1;
    $PHORUM["DATA"]["URL"]["NEXTPAGE"]=phorum_get_url(PHORUM_LIST_URL, $PHORUM["forum_id"], "page=$nextpage");
}
if($page>1){
    $prevpage=$page-1;
    $PHORUM["DATA"]["URL"]["PREVPAGE"]=phorum_get_url(PHORUM_LIST_URL, $PHORUM["forum_id"], "page=$prevpage");
}

$min_id=0;

$rows = NULL;
if($PHORUM['cache_messages'] && (!$PHORUM['DATA']['LOGGEDIN'] || $PHORUM['use_cookies'])) {
    $cache_key=$PHORUM['forum_id']."-".$PHORUM['cache_version']."-".$page."-".$PHORUM['threaded_list']."-".$PHORUM['threaded_read']."-".$PHORUM["language"]."-".$PHORUM["count_views"];
    $rows = phorum_cache_get('message_list',$cache_key);
}

if($rows == null) {


    //timing_mark('before db');
    // Get the threads
    $rows = array();

    // get the thread set started
    $rows = phorum_db_get_thread_list($offset);

    //timing_mark('after db');

    // redirect if invalid page
    if(count($rows) < 1 && $offset > 0){
        $dest_url = phorum_get_url(PHORUM_LIST_URL);
        phorum_redirect_by_url($dest_url);
        exit();
    }

    if ($PHORUM["threaded_list"]){

        // loop through and read all the data in.
        foreach($rows as $key => $row){

            if($PHORUM["count_views"]) {  // show viewcount if enabled
                if($PHORUM["count_views"] == 2) { // viewcount as column
                    $rows[$key]["viewcount"] = number_format($row['viewcount'], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);
                } else { // viewcount added to the subject
                    $rows[$key]["subject"]=$row["subject"]." ({$row['viewcount']} " . $PHORUM['DATA']['LANG']['Views_Subject'] . ")";
                }
            }

            $rows[$key]["raw_datestamp"] = $row["datestamp"];
            $rows[$key]["datestamp"] = phorum_date($PHORUM["short_date_time"], $row["datestamp"]);
            $rows[$key]["raw_lastpost"] = $row["modifystamp"];
            $rows[$key]["lastpost"] = phorum_date($PHORUM["short_date_time"], $row["modifystamp"]);

            $rows[$key]["URL"]["READ"] = phorum_get_url(PHORUM_READ_URL, $row["thread"], $row["message_id"]);

            if($row["message_id"] == $row["thread"]){
                $rows[$key]["threadstart"] = true;
            }else{
                $rows[$key]["threadstart"] = false;
            }

            $rows[$key]["new"] = "";
            // recognizing moved threads
            if(isset($row['meta']['moved']) && $row['meta']['moved'] == 1) {
                $rows[$key]['moved']=1;
            }

            if ($row["user_id"]){
                $url = phorum_get_url(PHORUM_PROFILE_URL, $row["user_id"]);
                $rows[$key]["URL"]["PROFILE"] = $url;
                $rows[$key]["linked_author"] = "<a href=\"$url\">".htmlspecialchars($row['author'])."</a>";
            } else {
                $rows[$key]["URL"]["PROFILE"] = "";
                if(!empty($row['email'])) {
                    $email_url = phorum_html_encode("mailto:$row[email]");
                    // we don't normally put HTML in this code, but this makes it easier on template builders
                    $rows[$key]["linked_author"] = "<a href=\"".$email_url."\">".htmlspecialchars($row["author"])."</a>";
                } else {
                    $rows[$key]["linked_author"] = htmlspecialchars($row["author"]);
                }
            }
            if($min_id == 0 || $min_id > $row['message_id'])
            $min_id = $row['message_id'];
        }
        // don't move this up.  We want it to be conditional.
        include_once("./include/thread_sort.php");

        $rows = phorum_sort_threads($rows);

    }else{

        // loop through and read all the data in.
        foreach($rows as $key => $row){

            $rows[$key]["raw_lastpost"] = $row["modifystamp"];
            $rows[$key]["lastpost"] = phorum_date($PHORUM["short_date_time"], $row["modifystamp"]);
            $rows[$key]["raw_datestamp"] = $row["datestamp"];
            $rows[$key]["datestamp"] = phorum_date($PHORUM["short_date_time"], $row["datestamp"]);
            $rows[$key]["URL"]["READ"] = phorum_get_url(PHORUM_READ_URL, $row["thread"]);
            $rows[$key]["URL"]["NEWPOST"] = phorum_get_url(PHORUM_READ_URL, $row["thread"],"gotonewpost");

            $rows[$key]["new"] = "";

            if($PHORUM["count_views"]) {  // show viewcount if enabled
                if($PHORUM["count_views"] == 2) { // viewcount as column
                    $PHORUM["DATA"]["VIEWCOUNT_COLUMN"]=true;
                    $rows[$key]["viewcount"]=$row['viewcount'];
                } else { // viewcount added to the subject
                    $rows[$key]["subject"]=$row["subject"]." ({$row['viewcount']} " . $PHORUM['DATA']['LANG']['Views_Subject'] . ")";
                }
            }

            // recognizing moved threads
            if(isset($row['meta']['moved']) && $row['meta']['moved'] == 1) {
                $rows[$key]['moved']=1;
            } else {
                $rows[$key]['moved']=0;
            }

            // default thread-count
            $thread_count=$row["thread_count"];

            $rows[$key]["thread_count"] = number_format($row['thread_count'], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);

            if ($PHORUM["DATA"]["LOGGEDIN"]){

                if($PHORUM["DATA"]["MODERATOR"]){
                    $rows[$key]["URL"]["DELETE_MESSAGE"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_DELETE_MESSAGE, $row["message_id"]);
                    $rows[$key]["URL"]["DELETE_THREAD"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_DELETE_TREE, $row["message_id"]);
                    if($build_move_url) {
                        $rows[$key]["URL"]["MOVE"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_MOVE_THREAD, $row["message_id"]);
                    }
                    $rows[$key]["URL"]["MERGE"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_MERGE_THREAD, $row["message_id"]);
                    // count could be different with hidden or unapproved posts
                    if(!$PHORUM["threaded_read"] && isset($row["meta"]["message_ids_moderator"])) {
                        $thread_count=count($row["meta"]["message_ids_moderator"]);
                    }
                }

                if(!$rows[$key]['moved'] && isset($row['meta']['message_ids']) && is_array($row['meta']['message_ids'])) {
                    foreach ($row['meta']['message_ids'] as $cur_id) {
                        if(!isset($PHORUM['user']['newinfo'][$cur_id]) && $cur_id > $PHORUM['user']['newinfo']['min_id'][$rows[$key]["forum_id"]])
                        $rows[$key]["new"] = $PHORUM["DATA"]["LANG"]["newflag"];

                        if($min_id == 0 || $min_id > $cur_id)
                        $min_id = $cur_id;
                    }
                }
            }

            if ($row["user_id"]){
                $url = phorum_get_url(PHORUM_PROFILE_URL, $row["user_id"]);
                $rows[$key]["URL"]["PROFILE"] = $url;
                $rows[$key]["linked_author"] = "<a href=\"$url\">".htmlspecialchars($row["author"])."</a>";
            }else{
                $rows[$key]["URL"]["PROFILE"] = "";
                if(!empty($row['email'])) {
                    $email_url = phorum_html_encode("mailto:$row[email]");
                    // we don't normally put HTML in this code, but this makes it easier on template builders
                    $rows[$key]["linked_author"] = "<a href=\"".$email_url."\">".htmlspecialchars($row["author"])."</a>";
                } else {
                    $rows[$key]["linked_author"] = $row["author"];
                }
            }

            $pages=1;
            // thread_count computed above in moderators-section
            if(!$PHORUM["threaded_read"] && $thread_count>$PHORUM["read_length"]){

                $pages=ceil($thread_count/$PHORUM["read_length"]);

                if($pages<=5){
                    $page_links="";
                    for($x=1;$x<=$pages;$x++){
                        $url=phorum_get_url(PHORUM_READ_URL, $row["thread"], "page=$x");
                        $page_links[]="<a href=\"$url\">$x</a>";
                    }
                    $rows[$key]["pages"]=implode("&nbsp;", $page_links);
                } else {
                    $url=phorum_get_url(PHORUM_READ_URL, $row["thread"], "page=1");
                    $rows[$key]["pages"]="<a href=\"$url\">1</a>&nbsp;";
                    $rows[$key]["pages"].="...&nbsp;";
                    $pageno=$pages-2;
                    $url=phorum_get_url(PHORUM_READ_URL, $row["thread"], "page=$pageno");
                    $rows[$key]["pages"].="<a href=\"$url\">$pageno</a>&nbsp;";
                    $pageno=$pages-1;
                    $url=phorum_get_url(PHORUM_READ_URL, $row["thread"], "page=$pageno");
                    $rows[$key]["pages"].="<a href=\"$url\">$pageno</a>&nbsp;";
                    $pageno=$pages;
                    $url=phorum_get_url(PHORUM_READ_URL, $row["thread"], "page=$pageno");
                    $rows[$key]["pages"].="<a href=\"$url\">$pageno</a>";
                }
            }

            if(isset($row['meta']['recent_post'])) {
                if($pages>1){
                    $rows[$key]["URL"]["LAST_POST"]=phorum_get_url(PHORUM_READ_URL, $row["thread"], $row["meta"]["recent_post"]["message_id"], "page=$pages");
                } else {
                    $rows[$key]["URL"]["LAST_POST"]=phorum_get_url(PHORUM_READ_URL, $row["thread"], $row["meta"]["recent_post"]["message_id"]);
                }

                $row['meta']['recent_post']['author'] = htmlspecialchars($row['meta']['recent_post']['author']);
                if ($row["meta"]["recent_post"]["user_id"]){
                    $url = phorum_get_url(PHORUM_PROFILE_URL, $row["meta"]["recent_post"]["user_id"]);
                    $rows[$key]["URL"]["PROFILE_LAST_POST"] = $url;
                    $rows[$key]["last_post_by"] = "<a href=\"$url\">{$row['meta']['recent_post']['author']}</a>";
                }else{
                    $rows[$key]["URL"]["PROFILE_LAST_POST"] = "";
                    $rows[$key]["last_post_by"] = $row["meta"]["recent_post"]["author"];
                }
            } else {
                $rows[$key]["last_post_by"] = "";
            }
        }
    }


    if($PHORUM['cache_messages'] && (!$PHORUM['DATA']['LOGGEDIN'] || $PHORUM['use_cookies'])) {
        phorum_cache_put('message_list',$cache_key,$rows);
    }
}

if($PHORUM["count_views"] == 2) { // viewcount as column
    $PHORUM["DATA"]["VIEWCOUNT_COLUMN"]=true;
}
//timing_mark('after preparation');

if($PHORUM['DATA']['LOGGEDIN']) {
    // the stuff needed by user
    foreach($rows as $key => $row){
        // newflag for collapsed flat view or special threads (sticky and announcement)
        if ((!$PHORUM['threaded_list'] ||
            $rows[$key]['sort'] == PHORUM_SORT_STICKY || $rows[$key]['sort'] == PHORUM_SORT_ANNOUNCEMENT) &&
            isset($row['meta']['message_ids']) && is_array($row['meta']['message_ids'])) {
            foreach ($row['meta']['message_ids'] as $cur_id) {
                if(!isset($PHORUM['user']['newinfo'][$cur_id]) && $cur_id > $PHORUM['user']['newinfo']['min_id'][$row['forum_id']])
                $rows[$key]["new"] = $PHORUM["DATA"]["LANG"]["newflag"];
            }
        }
        // newflag for regular messages
        else {
            if (!isset($PHORUM['user']['newinfo'][$row['message_id']]) && $row['message_id'] > $PHORUM['user']['newinfo']['min_id'][$row['forum_id']]) {
                $rows[$key]["new"]=$PHORUM["DATA"]["LANG"]["newflag"];
            }
        }

        if($PHORUM["DATA"]["MODERATOR"]){
            $rows[$key]["URL"]["DELETE_MESSAGE"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_DELETE_MESSAGE, $row["message_id"]);
            $rows[$key]["URL"]["DELETE_THREAD"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_DELETE_TREE, $row["message_id"]);
            if($build_move_url) {
                $rows[$key]["URL"]["MOVE"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_MOVE_THREAD, $row["message_id"]);
            }
            $rows[$key]["URL"]["MERGE"] = phorum_get_url(PHORUM_MODERATION_URL, PHORUM_MERGE_THREAD, $row["message_id"]);
            // count could be different with hidden or unapproved posts
            if(!$PHORUM["threaded_read"] && isset($row["meta"]["message_ids_moderator"])) {
                $thread_count=count($row["meta"]["message_ids_moderator"]);
            }
        }

    }

}

// run list mods
$rows = phorum_hook("list", $rows);

// if we retrieve the body too we need to setup some more variables for the messages
// to make it a little more similar to the view in read.php
if(isset($PHORUM['TMP']['bodies_in_list']) && $PHORUM['TMP']['bodies_in_list'] == 1) {

    foreach ($rows as $id => $row) {

        // is the message unapproved?
        $row["is_unapproved"] = ($row['status'] < 0) ? 1 : 0;

        // check if its the first message in the thread
        if($row["message_id"] == $row["thread"]) {
            $row["threadstart"] = true;
        } else{
            $row["threadstart"] = false;
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
                $row["ip"]=$PHORUM["DATA"]["LANG"]["IPLogged"];
            }
        }

        // add the edited-message to a post if its edited
        if(isset($row['meta']['edit_count']) && $row['meta']['edit_count'] > 0) {
            $editmessage = str_replace ("%count%", $row['meta']['edit_count'], $PHORUM["DATA"]["LANG"]["EditedMessage"]);
            $editmessage = str_replace ("%lastedit%", phorum_date($PHORUM["short_date_time"],$row['meta']['edit_date']),  $editmessage);
            $editmessage = str_replace ("%lastuser%", $row['meta']['edit_username'],  $editmessage);
            $row["body"].="\n\n\n\n$editmessage";
        }


        if($PHORUM["max_attachments"]>0 && isset($row["meta"]["attachments"])){
            $PHORUM["DATA"]["ATTACHMENTS"]=true;
            $row["attachments"]=$row["meta"]["attachments"];
            // unset($row["meta"]["attachments"]);
            foreach($row["attachments"] as $key=>$file){
                $row["attachments"][$key]["size"]=phorum_filesize($file["size"]);
                $row["attachments"][$key]["name"]=htmlentities($file['name'], ENT_COMPAT, $PHORUM["DATA"]["CHARSET"]);
                $row["attachments"][$key]["url"] = phorum_get_url(PHORUM_FILE_URL, "file={$file['file_id']}");
            }
        }
        $rows[$id] = $row;
    }
}

// format messages
$rows = phorum_format_messages($rows);

//timing_mark('after formatting');

// set up the data
$PHORUM["DATA"]["MESSAGES"] = $rows;

$PHORUM["DATA"]["URL"]["MARK_READ"] = phorum_get_url(PHORUM_LIST_URL, $PHORUM["forum_id"], "markread");
if($PHORUM["DATA"]["MODERATOR"]) {
   $PHORUM["DATA"]["URL"]["UNAPPROVED"] = phorum_get_url(PHORUM_PREPOST_URL);
}

// updating new-info for first visit (last message on first page is first new)
if ($PHORUM["DATA"]["LOGGEDIN"] && $PHORUM['user']['newinfo']['min_id'][$PHORUM["forum_id"]] == 0 && !isset($PHORUM['user']['newinfo'][$min_id]) && $min_id != 0){
    // setting it as min-id
    // set it -1 as the comparison is "post newer than min_id"
    $min_id--;
    phorum_db_newflag_add_read($min_id);
    if($PHORUM['cache_newflags']) {
        phorum_cache_remove('newflags',$newflagkey);
        phorum_cache_remove('newflags_index',$newflagkey);
    }
}

include phorum_get_template("header");
phorum_hook("after_header");

// include the correct template
if ($PHORUM["threaded_list"]){
    include phorum_get_template("list_threads");
}else{
    include phorum_get_template("list");
}

phorum_hook("before_footer");
include phorum_get_template("footer");

//timing_mark('end');
//timing_print();

?>
