<?php 
///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2003  Phorum Development Team                               //
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

$forums = phorum_db_get_forums( 0, $parent_id );

$PHORUM["DATA"]["FORUMS"] = array();

$forums_shown=false;

foreach( $forums as $forum ) {
    
    if ( $forum["folder_flag"] ) {

        $forum["url"] = phorum_get_url( PHORUM_INDEX_URL, $forum["forum_id"] );

    } else {

        if($PHORUM["hide_forums"] && !phorum_user_access_allowed(PHORUM_USER_ALLOW_READ, $forum["forum_id"])){
            continue;
        }

        $forum["url"] = phorum_get_url( PHORUM_LIST_URL, $forum["forum_id"] );

        // if there is only one forum in Phorum, redirect to it.
        if ( $parent_id==0 && count( $forums ) < 2 ) {
            phorum_redirect_by_url($forum['url']);
            exit();
        } 

        if ( $forum["message_count"] > 0 ) {
            $forum["last_post"] = phorum_date( $PHORUM["long_date"], $forum["last_post_time"] );
        } else {
            $forum["last_post"] = "";
        } 

        if($PHORUM["DATA"]["LOGGEDIN"] && $PHORUM["show_new_on_index"]){
            list($forum["new_messages"], $forum["new_threads"]) = phorum_db_newflag_get_unread_count($forum["forum_id"]);
        }
    } 

    $forums_shown=true;

    $PHORUM["DATA"]["FORUMS"][] = $forum;
} 

if(!$forums_shown){
    // we did not show any forums here, redirect to login page.
    $login_url=phorum_get_url(PHORUM_LOGIN_URL);
    phorum_redirect_by_url($login_url);
    exit();
}

$PHORUM["DATA"]["FORUMS"]=phorum_hook("index", $PHORUM["DATA"]["FORUMS"]);

// set all our URL's
phorum_build_common_urls();

include phorum_get_template( "header" );
phorum_hook("after_header");
include phorum_get_template( "index" );
phorum_hook("before_footer");
include phorum_get_template( "footer" );

?>