<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team
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

define('phorum_page','list');

require_once './common.php';

require_once PHORUM_PATH.'/include/api/thread.php';
require_once PHORUM_PATH.'/include/api/format/messages.php';

// set all our common URL's
phorum_build_common_urls();

if (!phorum_check_read_common()) {
  return;
}

// No forum_id in the request.
if (empty($PHORUM["forum_id"])){
    phorum_api_redirect(PHORUM_INDEX_URL);
}

// Somehow we got to a folder in list.php.
if ($PHORUM["folder_flag"]){
    phorum_api_redirect(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
}

// Handle "mark read" clicks.
if (!empty($PHORUM["args"][1]) && $PHORUM["args"][1] == 'markread' &&
    $PHORUM['user']['user_id']) {

    // Mark all posts in the current forum as read.
    phorum_api_newflags_markread($PHORUM['forum_id'], PHORUM_MARKREAD_FORUMS);

    // Redirect to a fresh list of the current folder without the mark read
    // parameters in the URL. This way we prevent users from bookmarking
    // the mark read URL.
    phorum_api_redirect(PHORUM_LIST_URL);
}

// figure out what page we are on
if (empty($PHORUM["args"]["page"]) || !is_numeric($PHORUM["args"]["page"]) || $PHORUM["args"]["page"] < 0){
    $page=1;
} else {
    $page=intval($PHORUM["args"]["page"]);
}
$offset=$page-1;

// Check if the current user is allowed to moderate messages.
$PHORUM["DATA"]["MODERATOR"] = phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

// Find out how many forums this user can moderate. If the user can moderate
// more than one forum, then we will present the move message moderation link.
if ($PHORUM["DATA"]["MODERATOR"]) {
    $modforums = phorum_api_user_check_access(
        PHORUM_USER_ALLOW_MODERATE_MESSAGES,
        PHORUM_ACCESS_LIST
    );
    $build_move_url = count($modforums) >= 2;
}

// Determine what list length to use.
if($PHORUM['threaded_list']) {
    $PHORUM["list_length"] = $PHORUM['list_length_threaded'];
} else {
    $PHORUM["list_length"] = $PHORUM['list_length_flat'];
}

// Figure out paging for threaded and flat mode. Sticky messages
// are in the thread_count, but because these are handled as a separate
// list, they should not be included in the pages computation.
$pages=ceil(($PHORUM["thread_count"] - $PHORUM['sticky_count']) / $PHORUM["list_length"]);

// If we only have stickies, the number of pages will be zero.
// In that case, simply use one page.
if ($pages == 0) $pages = 1;

$pages_shown = (isset($PHORUM["TMP"]["list_pages_shown"]))
             ? $PHORUM["TMP"]["list_pages_shown"] : 11;

// first $pages_shown pages
if ($page - floor($pages_shown/2) <= 0  || $page < $pages_shown) {
    $page_start=1;
// last $pages_shown pages
} elseif($page > $pages - floor($pages_shown/2)) {
    $page_start = $pages - $pages_shown + 1;
// all others
} else {
    $page_start = $page - floor($pages_shown/2);
}

$pageno = 1;

$list_page_url_template = phorum_api_url(PHORUM_LIST_URL, '%forum_id%', 'page=%page_num%');

for ($x=0;$x<$pages_shown && $x<$pages;$x++) {
    $pageno=$x+$page_start;
    $PHORUM["DATA"]["PAGES"][] = array(
    "pageno" => $pageno,
    "url"    => str_replace(
                    array('%forum_id%', '%page_num%'),
                    array($PHORUM["forum_id"], $pageno),
                    $list_page_url_template
                )
    );
}


$PHORUM["DATA"]["CURRENTPAGE"]=$page;
$PHORUM["DATA"]["TOTALPAGES"]=$pages;
$PHORUM["DATA"]["URL"]["PAGING_TEMPLATE"]=str_replace(
    '%forum_id%',$PHORUM["forum_id"],$list_page_url_template
);


if($page_start>1){
    $PHORUM["DATA"]["URL"]["FIRSTPAGE"]=str_replace(
        array('%forum_id%','%page_num%'),
        array($PHORUM["forum_id"],'1'),
        $list_page_url_template
    );
}

if($pageno<$pages){
    $PHORUM["DATA"]["URL"]["LASTPAGE"]=str_replace(
        array('%forum_id%','%page_num%'),
        array($PHORUM["forum_id"],$pages),
        $list_page_url_template
    );
}

if($pages>$page){
    $nextpage=$page+1;
    $PHORUM["DATA"]["URL"]["NEXTPAGE"]=str_replace(
        array('%forum_id%','%page_num%'),
        array($PHORUM["forum_id"],$nextpage),
        $list_page_url_template
    );
    $PHORUM["DATA"]["NEXTPAGE"] = $nextpage;
}

if ($page>1)
{
    $prevpage=$page-1;
    $PHORUM["DATA"]["URL"]["PREVPAGE"]=str_replace(
        array('%forum_id%','%page_num%'),
        array($PHORUM["forum_id"],$prevpage),
        $list_page_url_template
    );
    $PHORUM["DATA"]["PREVPAGE"] = $prevpage;
}

$min_id=0;

$rows = NULL;
$bodies_in_list = isset($PHORUM['TMP']['bodies_in_list']) && $PHORUM['TMP']['bodies_in_list'];

// always init cache key
$cache_key = $PHORUM['forum_id']."-".$PHORUM['cache_version']."-".$page."-";
$cache_key.= $PHORUM['threaded_list']."-".$PHORUM['threaded_read']."-".$PHORUM["language"];
$cache_key.= "-".$PHORUM["count_views"]."-".($bodies_in_list?"1":"0")."-".$PHORUM['float_to_top'];
$cache_key.= "-".$PHORUM['user']['tz_offset'];

if($PHORUM['cache_messages'] &&
   (!$PHORUM['DATA']['LOGGEDIN'] || $PHORUM['use_cookies']) &&
   !$PHORUM['count_views']) {
    $rows = phorum_api_cache_get('message_list',$cache_key);
}

if($rows == null)
{
    // Get the threads
    $rows = array();

    // get the thread set started
    $rows = $PHORUM['DB']->get_thread_list($offset, $bodies_in_list);

    // redirect if invalid page
    if(count($rows) < 1 && $offset > 0){
        phorum_api_redirect(PHORUM_LIST_URL);
    }

    if ($PHORUM["threaded_list"]){

        // prepare needed url-templates
        $read_url_template = phorum_api_url(PHORUM_READ_URL, '%thread_id%','%message_id%');

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
            $rows[$key]["datestamp"] = phorum_api_format_date($PHORUM["short_date_time"], $row["datestamp"]);

            $rows[$key]["URL"]["READ"] = str_replace(array('%thread_id%','%message_id%'),
                                                     array($row['thread'],$row['message_id']),
                                                     $read_url_template);

            if($row["message_id"] == $row["thread"]){
                $rows[$key]["threadstart"] = true;
            }else{
                $rows[$key]["threadstart"] = false;
            }

            $rows[$key]["new"] = "";

        }

        $rows = phorum_api_thread_sort($rows);

    } else {

        $read_url_template        = phorum_api_url(PHORUM_READ_URL, '%thread_id%');
        $newpost_url_template     = phorum_api_url(PHORUM_READ_URL, '%thread_id%','gotonewpost');
        $read_page_url_template   = phorum_api_url(PHORUM_READ_URL, '%thread_id%','page=%page_num%');
        $recent_page_url_template = phorum_api_url(PHORUM_READ_URL, '%thread_id%','%message_id%','page=%page_num%');
        $recent_url_template      = phorum_api_url(PHORUM_READ_URL, '%thread_id%','%message_id%');

        // loop through and read all the data in.
        foreach($rows as $key => $row){

            $rows[$key]["raw_lastpost"]   = $row["modifystamp"];
            $rows[$key]["raw_datestamp"]  = $row["datestamp"];

            $rows[$key]["lastpost"]       = phorum_api_format_date($PHORUM["short_date_time"], $row["modifystamp"]);
            $rows[$key]["datestamp"]      = phorum_api_format_date($PHORUM["short_date_time"], $row["datestamp"]);

            $rows[$key]["URL"]["READ"]    = str_replace('%thread_id%',$row['thread'],$read_url_template);
            $rows[$key]["URL"]["NEWPOST"] = str_replace('%thread_id%',$row['thread'],$newpost_url_template);
            $rows[$key]["threadstart"] = true;

            $rows[$key]["new"] = "";

            if($PHORUM["count_views"]) {  // show viewcount if enabled

                $viewcount = $PHORUM["count_views_per_thread"]
                           ? $row['threadviewcount']
                           : $row['viewcount'];

                if($PHORUM["count_views"] == 2) { // viewcount as column
                    $PHORUM["DATA"]["VIEWCOUNT_COLUMN"]=true;
                    $rows[$key]["viewcount"] = number_format($viewcount, 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);
                } else { // viewcount added to the subject
                    $rows[$key]["subject"] = $row["subject"]." ($viewcount " . $PHORUM['DATA']['LANG']['Views_Subject'] . ")";
                }
            }

            // default thread-count
            $thread_count=$row["thread_count"];

            $rows[$key]["reply_count"] = number_format($row['thread_count']-1, 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);
            $rows[$key]["thread_count"] = number_format($row['thread_count'], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);

            $pages=1;

            if(!$PHORUM["threaded_read"]) {

                // pages for regular users
                if($thread_count>$PHORUM["read_length"]){


                    $pages=ceil($thread_count/$PHORUM["read_length"]);

                    if($pages<=5){
                        $page_links=array();
                        for($x=1;$x<=$pages;$x++){
                            $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$x),$read_page_url_template);
                            $page_links[]="<a href=\"$url\">$x</a>";
                        }
                        $rows[$key]["pages"]=implode("&nbsp;", $page_links);
                    } else {
                        $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],'1'),$read_page_url_template);
                        $rows[$key]["pages"]="<a href=\"$url\">1</a>&nbsp;";
                        $rows[$key]["pages"].="...&nbsp;";
                        $pageno=$pages-2;
                        $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$pageno),$read_page_url_template);
                        $rows[$key]["pages"].="<a href=\"$url\">$pageno</a>&nbsp;";
                        $pageno=$pages-1;
                        $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$pageno),$read_page_url_template);
                        $rows[$key]["pages"].="<a href=\"$url\">$pageno</a>&nbsp;";
                        $pageno=$pages;
                        $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$pageno),$read_page_url_template);
                        $rows[$key]["pages"].="<a href=\"$url\">$pageno</a>";
                    }

                }

                // pages for moderators
                if(isset($row["meta"]["message_ids_moderator"])) {
                    $thread_count_mods=count($row["meta"]["message_ids_moderator"]);

                    if($thread_count_mods != $thread_count && $thread_count_mods > $PHORUM["read_length"]){

                        $pages_mods=ceil($thread_count_mods/$PHORUM["read_length"]);

                        $rows[$key]["pages_moderators_count"] = $pages_mods;

                        if($pages_mods<=5){
                            $page_links=array();
                            for($x=1;$x<=$pages_mods;$x++){
                                $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$x),$read_page_url_template);
                                $page_links[]="<a href=\"$url\">$x</a>";
                            }
                            $rows[$key]["pages_moderators"]=implode("&nbsp;", $page_links);
                        } else {
                            $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],'1'),$read_page_url_template);
                            $rows[$key]["pages_moderators"]="<a href=\"$url\">1</a>&nbsp;";
                            $rows[$key]["pages_moderators"].="...&nbsp;";
                            $pageno=$pages_mods-2;
                            $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$pageno),$read_page_url_template);
                            $rows[$key]["pages_moderators"].="<a href=\"$url\">$pageno</a>&nbsp;";
                            $pageno=$pages_mods-1;
                            $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$pageno),$read_page_url_template);
                            $rows[$key]["pages_moderators"].="<a href=\"$url\">$pageno</a>&nbsp;";
                            $pageno=$pages_mods;
                            $url = str_replace(array('%thread_id%','%page_num%'),array($row['thread'],$pageno),$read_page_url_template);
                            $rows[$key]["pages_moderators"].="<a href=\"$url\">$pageno</a>";
                        }
                    }
                }
            }


            if(isset($row['recent_message_id'])) { // should always be true
                // building the recent message link
                if($pages>1){
                    $rows[$key]["URL"]["LAST_POST"] = str_replace(
                       array('%thread_id%','%message_id%','%page_num%'),
                       array($row['thread'],$row['recent_message_id'],$pages),
                       $recent_page_url_template
                    );
                } else {
                    $rows[$key]["URL"]["LAST_POST"] = str_replace(
                        array('%thread_id%','%message_id%'),
                        array($row['thread'],$row['recent_message_id']),
                        $recent_url_template
                    );
                }
            }


        }
    }

    if($PHORUM['cache_messages'] &&
       (!$PHORUM['DATA']['LOGGEDIN'] || $PHORUM['use_cookies']) &&
       !$PHORUM['count_views']) {
        phorum_api_cache_put('message_list',$cache_key,$rows);
    }
}

if ($PHORUM["count_views"] == 2) { // viewcount as column
    $PHORUM["DATA"]["VIEWCOUNT_COLUMN"] = true;
}

if ($PHORUM['DATA']['LOGGEDIN'])
{
    // used later if user is moderator
    if ($PHORUM["DATA"]["MODERATOR"])
    {
        $delete_url_template        = phorum_api_url(PHORUM_MODERATION_URL, PHORUM_DELETE_MESSAGE, '%message_id%', 'ref_message_id=%message_id%');
        $delete_thread_url_template = phorum_api_url(PHORUM_MODERATION_URL, PHORUM_DELETE_TREE, '%message_id%', 'ref_message_id=%message_id%');
        $move_thread_url_template   = phorum_api_url(PHORUM_MODERATION_URL, PHORUM_MOVE_THREAD, '%message_id%', 'ref_message_id=%message_id%');
        $merge_thread_url_template  = phorum_api_url(PHORUM_MODERATION_URL, PHORUM_MERGE_THREAD, '%message_id%', 'ref_message_id=%message_id%');
        if(isset($row['pages_moderators'])) {
            $recent_page_url_template = phorum_api_url(PHORUM_READ_URL, '%thread_id%','%message_id%', 'ref_message_id=%message_id%', 'page=%page_num%');
            $recent_url_template      = phorum_api_url(PHORUM_READ_URL, '%thread_id%','%message_id%', 'ref_message_id=%message_id%');
        }
    }

    $mark_thread_read_url_template = phorum_api_url(PHORUM_READ_URL, '%thread_id%', "markthreadread", "list");

    // Add newflags to the messages for authenticated users.
    if ($PHORUM['user']['user_id'])
    {
        $message_type = $PHORUM['threaded_list']
              ? PHORUM_NEWFLAGS_BY_MESSAGE_EXSTICKY
              : PHORUM_NEWFLAGS_BY_THREAD;
        $rows = phorum_api_newflags_apply_to_messages($rows, $message_type);
    }

    foreach ($rows as $key => $row)
    {
        if (isset($row['new'])) {
            $rows[$key]["URL"]["MARKTHREADREAD"] = str_replace(
                '%thread_id%', $row['thread'], $mark_thread_read_url_template
            );
        }

        if($PHORUM["DATA"]["MODERATOR"]){

            if($rows[$key]["threadstart"]) {
                $rows[$key]["URL"]["DELETE_THREAD"]  = str_replace('%message_id%',$row['message_id'],$delete_thread_url_template);
                if($build_move_url) {
                    $rows[$key]["URL"]["MOVE"]       = str_replace('%message_id%',$row['message_id'],$move_thread_url_template);
                }
                $rows[$key]["URL"]["MERGE"]          = str_replace('%message_id%',$row['message_id'],$merge_thread_url_template);;
            } else {
                $rows[$key]["URL"]["DELETE_MESSAGE"] = str_replace('%message_id%',$row['message_id'],$delete_url_template);
            }

            // pagelinks for moderators
            if(isset($row['pages_moderators'])) {
                $rows[$key]['pages'] = $row['pages_moderators'];


                // building their last message link too
                if(isset($row['recent_message_id']) && isset($row['pages_moderators_count'])) { // should always be true
                    // building the recent message link
                    $pages = $row['pages_moderators_count'];
                    if($pages>1){
                        $rows[$key]["URL"]["LAST_POST"] = str_replace(array('%thread_id%','%message_id%','%page_num%'),
                                                                      array($row['thread'],$row['recent_message_id'],$pages),
                                                                      $recent_page_url_template);
                    } else {
                        $rows[$key]["URL"]["LAST_POST"] = str_replace(array('%thread_id%','%message_id%'),
                                                                      array($row['thread'],$row['recent_message_id']),
                                                                      $recent_url_template);
                    }
                }
            }
        }
    }
}

// run list mods
if (isset($PHORUM["hooks"]["list"]))
    $rows = phorum_api_hook("list", $rows);

// if we retrieve the body too we need to setup some more variables for
// the messages to make it a little more similar to the view in read.php
if ($bodies_in_list)
{
    if($PHORUM["max_attachments"]>0) {
        $attachment_url_template = phorum_api_url(PHORUM_FILE_URL, 'file=%file_id%', 'filename=%file_name%');
    }
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
            $editmessage = str_replace ("%lastedit%", phorum_api_format_date($PHORUM["short_date_time"],$row['meta']['edit_date']),  $editmessage);
            $editmessage = str_replace ("%lastuser%", $row['meta']['edit_username'],  $editmessage);
            $row["body"].="\n\n\n\n$editmessage";
        }


        if($PHORUM["max_attachments"]>0 && isset($row["meta"]["attachments"])){
            $PHORUM["DATA"]["ATTACHMENTS"]=true;
            $row["attachments"]=$row["meta"]["attachments"];
            // unset($row["meta"]["attachments"]);
            foreach($row["attachments"] as $key=>$file){
                $row["attachments"][$key]["size"]=phorum_api_format_filesize($file["size"]);
                $row["attachments"][$key]["name"]=phorum_api_format_htmlspecialchars($file['name']);
                $row["attachments"][$key]["url"] =str_replace(array('%file_id%','%file_name%'),array($file['file_id'],urlencode($file['name'])),$attachment_url_template);
            }
        }
        $rows[$id] = $row;
    }
}

// The list page needs additional formatting for the recent author data
$recent_author_spec = array(
    "recent_user_id",        // user_id
    "recent_author",         // author
    NULL,                    // email (we won't link to email for recent)
    "recent_author",         // target author field
    "RECENT_AUTHOR_PROFILE"  // target author profile URL field
);

// format messages
$rows = phorum_api_format_messages($rows, array($recent_author_spec));

// set up the data
$PHORUM["DATA"]["MESSAGES"] = $rows;

if ($PHORUM["DATA"]["LOGGEDIN"]) {
    $PHORUM["DATA"]["URL"]["MARK_READ"] = phorum_api_url(PHORUM_LIST_URL, $PHORUM["forum_id"], "markread");
}
if($PHORUM["DATA"]["MODERATOR"]) {
   $PHORUM["DATA"]["URL"]["UNAPPROVED"] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=messages");
}

// add feed url
if (isset($PHORUM['use_rss']) && $PHORUM['use_rss'])
{
    $PHORUM['DATA']['FEEDS'] = array(
        array(
            'URL' => phorum_api_url(PHORUM_FEED_URL, $PHORUM['forum_id'], 'type='.$PHORUM['default_feed']),
            'TITLE' => $PHORUM['DATA']['FEED'] . ' ('. strtolower($PHORUM['DATA']['LANG']['Threads']) . ')'
        ),
        array(
            "URL" => phorum_api_url(PHORUM_FEED_URL, $PHORUM['forum_id'], 'replies=1', 'type='.$PHORUM['default_feed']),
            "TITLE" => $PHORUM['DATA']['FEED'] . ' (' . strtolower($PHORUM['DATA']['LANG']['Threads'].' + '.$PHORUM['DATA']['LANG']['replies']) . ')'
        )
    );

    $PHORUM["DATA"]["URL"]["FEED"] = phorum_api_url(PHORUM_FEED_URL, $PHORUM['forum_id'], 'replies=1', 'type='.$PHORUM['default_feed']);

}

// include the correct template
if ($PHORUM["threaded_list"]){
    $template = "list_threads";
}else{
    $template = "list";
}

phorum_api_output($template);

?>
