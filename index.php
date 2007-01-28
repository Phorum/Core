<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2006  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
///////////////////////////////////////////////////////////////////////////////
define('phorum_page','index');

include_once( "./common.php" );

include_once( "./include/format_functions.php" );

if(!phorum_check_read_common()) {
  return;
}

// check for markread
if (!empty($PHORUM["args"][1]) && $PHORUM["args"][1] == 'markread' && $PHORUM["DATA"]["LOGGEDIN"]){
    // setting all posts read
    if(isset($PHORUM["forum_id"])){
        unset($PHORUM['user']['newinfo']);
        phorum_db_newflag_allread($PHORUM["forum_id"]);
        if($PHORUM['cache_newflags']) {
            $newflagkey = $PHORUM["forum_id"]."-".$PHORUM['user']['user_id'];
            phorum_cache_remove('newflags', $newflagkey);
            phorum_cache_remove('newflags_index', $newflagkey);
        }
    }

    // redirect to a fresh list of the current folder without markread in url
    if(isset($PHORUM["args"][2]) && !empty($PHORUM["args"][2])) {
        $folder_id = (int)$PHORUM["args"][2];
        $dest_url = phorum_get_url(PHORUM_INDEX_URL,$folder_id);
    } else {
        $dest_url = phorum_get_url(PHORUM_INDEX_URL);
    }
    phorum_redirect_by_url($dest_url);
    exit();

}

// somehow we got to a forum in index.php
if(!empty($PHORUM["forum_id"]) && $PHORUM["folder_flag"]==0){
    $dest_url = phorum_get_url(PHORUM_LIST_URL);
    phorum_redirect_by_url($dest_url);
    exit();
}

if ( isset( $PHORUM["forum_id"] ) ) {
    $parent_id = (int)$PHORUM["forum_id"];
} else {
    $parent_id = 0;
}


$announcements = phorum_db_get_announcements();
if($announcements){

    foreach($announcements as $key => $announcement){
        $announcements[$key]["raw_lastpost"] = $announcement["modifystamp"];
        $announcements[$key]["lastpost"] = phorum_date($PHORUM["short_date_time"], $announcement["modifystamp"]);
        $announcements[$key]["raw_datestamp"] = $announcement["datestamp"];
        $announcements[$key]["datestamp"] = phorum_date($PHORUM["short_date_time"], $announcement["datestamp"]);
        $announcements[$key]["URL"]["READ"] = phorum_get_url(PHORUM_READ_URL, $announcement["thread"]);
        $announcements[$key]["URL"]["NEWPOST"] = phorum_get_url(PHORUM_READ_URL, $announcement["thread"],"gotonewpost");

        // save raw thread count
        $thread_count=$announcement["thread_count"];

        $announcements[$key]["thread_count"] = number_format($announcement['thread_count'], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);

        if($PHORUM["count_views"]) {  // show viewcount if enabled
            if($PHORUM["count_views"] == 2) { // viewcount as column
                $PHORUM["DATA"]["VIEWCOUNT_COLUMN"]=true;
                $announcements[$key]["viewcount"]=$announcement['viewcount'];
            } else { // viewcount added to the subject
                $announcements[$key]["subject"]=$announcement["subject"]." ({$announcement['viewcount']} " . $PHORUM['DATA']['LANG']['Views_Subject'] . ")";
            }
        }


        if ($announcement["user_id"]){
            $url = phorum_get_url(PHORUM_PROFILE_URL, $announcement["user_id"]);
            $announcements[$key]["URL"]["PROFILE"] = $url;
            $announcements[$key]["linked_author"] = "<a href=\"$url\">".htmlspecialchars($announcement["author"])."</a>";
        } else {
            $announcements[$key]["URL"]["PROFILE"] = "";
            if(!empty($announcement['email'])) {
                $email_url = phorum_html_encode("mailto:$announcement[email]");
                // we don't normally put HTML in this code, but this makes it easier on template builders
                $announcements[$key]["linked_author"] = "<a href=\"".$email_url."\">".htmlspecialchars($announcement["author"])."</a>";
            } else {
                $announcements[$key]["linked_author"] = $announcement["author"];
            }
        }

        $pages=1;
        // thread_count computed above in moderators-section
        if(!$PHORUM["threaded_read"] && $thread_count>$PHORUM["read_length"]){

            $pages=ceil($thread_count/$PHORUM["read_length"]);

            if($pages<=5){
                $page_links="";
                for($x=1;$x<=$pages;$x++){
                    $url=phorum_get_url(PHORUM_READ_URL, $announcement["thread"], "page=$x");
                    $page_links[]="<a href=\"$url\">$x</a>";
                }
                $announcements[$key]["pages"]=implode("&nbsp;", $page_links);
            } else {
                $url=phorum_get_url(PHORUM_READ_URL, $announcement["thread"], "page=1");
                $announcements[$key]["pages"]="<a href=\"$url\">1</a>&nbsp;";
                $announcements[$key]["pages"].="...&nbsp;";
                $pageno=$pages-2;
                $url=phorum_get_url(PHORUM_READ_URL, $announcement["thread"], "page=$pageno");
                $announcements[$key]["pages"].="<a href=\"$url\">$pageno</a>&nbsp;";
                $pageno=$pages-1;
                $url=phorum_get_url(PHORUM_READ_URL, $announcement["thread"], "page=$pageno");
                $announcements[$key]["pages"].="<a href=\"$url\">$pageno</a>&nbsp;";
                $pageno=$pages;
                $url=phorum_get_url(PHORUM_READ_URL, $announcement["thread"], "page=$pageno");
                $announcements[$key]["pages"].="<a href=\"$url\">$pageno</a>";
            }
        }

        if(isset($announcement['meta']['recent_post'])) {
            if($pages>1){
                $announcements[$key]["URL"]["LAST_POST"]=phorum_get_url(PHORUM_READ_URL, $announcement["thread"], $announcement["meta"]["recent_post"]["message_id"], "page=$pages");
            } else {
                $announcements[$key]["URL"]["LAST_POST"]=phorum_get_url(PHORUM_READ_URL, $announcement["thread"], $announcement["meta"]["recent_post"]["message_id"]);
            }

            $announcement['meta']['recent_post']['author'] = htmlspecialchars($announcement['meta']['recent_post']['author']);
            if ($announcement["meta"]["recent_post"]["user_id"]){
                $url = phorum_get_url(PHORUM_PROFILE_URL, $announcement["meta"]["recent_post"]["user_id"]);
                $announcements[$key]["URL"]["PROFILE_LAST_POST"] = $url;
                $announcements[$key]["last_post_by"] = "<a href=\"$url\">{$announcement['meta']['recent_post']['author']}</a>";
            }else{
                $announcements[$key]["URL"]["PROFILE_LAST_POST"] = "";
                $announcements[$key]["last_post_by"] = $announcement["meta"]["recent_post"]["author"];
            }
        } else {
            $announcements[$key]["last_post_by"] = "";
        }

    }

    $PHORUM["DATA"]["MESSAGES"] = $announcements;

}

if($PHORUM["use_new_folder_style"]){
    include_once "./include/index_new.php";
} else {
    include_once "./include/index_classic.php";
}

?>
