<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

if(!defined("PHORUM")) return;

$forums = phorum_db_get_forums( 0, $parent_id );

$PHORUM["DATA"]["FORUMS"] = array();

$forums_shown=false;

$new_checks = array();

if($PHORUM["DATA"]["LOGGEDIN"] && !empty($forums)){
    if($PHORUM["show_new_on_index"]==2){
        $new_checks = phorum_db_newflag_check(array_keys($forums));
    } elseif($PHORUM["show_new_on_index"]==1){
        $new_counts = phorum_db_newflag_count(array_keys($forums));
    }
}

foreach( $forums as $forum ) {

    if ( $forum["folder_flag"] ) {

        // Do not include vroot folders in the list.
        if ($forum['vroot'] == $forum['forum_id']) {
            continue;
        }

        $forum["URL"]["LIST"] = phorum_get_url( PHORUM_INDEX_URL, $forum["forum_id"] );

    } else {

        if($PHORUM["hide_forums"] && !phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, $forum["forum_id"])){
            continue;
        }

        $forum["url"] = phorum_get_url( PHORUM_LIST_URL, $forum["forum_id"] );

        // if there is only one forum in Phorum, redirect to it.
        if ( $parent_id==0 && count( $forums ) < 2 ) {
            phorum_redirect_by_url($forum['url']);
            exit();
        }

        if ( $forum["message_count"] > 0 ) {
            $forum["raw_last_post"] = $forum["last_post_time"];
            $forum["last_post"] = phorum_date( $PHORUM["long_date_time"], $forum["last_post_time"] );
        } else {
            $forum["last_post"] = "&nbsp;";
        }

        $forum["URL"]["LIST"] = phorum_get_url( PHORUM_LIST_URL, $forum["forum_id"] );
        if ($PHORUM["DATA"]["LOGGEDIN"]) {
            $forum["URL"]["MARK_READ"] = phorum_get_url( PHORUM_INDEX_URL, $forum["forum_id"], "markread", $PHORUM['forum_id']);
        }
        if(isset($PHORUM['use_rss']) && $PHORUM['use_rss']) {
            $forum["URL"]["FEED"] = phorum_get_url( PHORUM_FEED_URL, $forum["forum_id"], "type=".$PHORUM["default_feed"] );
        }


        $forum["raw_message_count"] = $forum["message_count"];
        $forum["message_count"] = number_format($forum["message_count"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);
        $forum["raw_thread_count"] = $forum["thread_count"];
        $forum["thread_count"] = number_format($forum["thread_count"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);

        if($PHORUM["DATA"]["LOGGEDIN"]){
            if($PHORUM["show_new_on_index"]==1){

                $forum["new_messages"] = number_format($new_counts[$forum["forum_id"]]["messages"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);
                $forum["new_threads"] = number_format($new_counts[$forum["forum_id"]]["threads"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);

            } elseif($PHORUM["show_new_on_index"]==2){

                $forum["new_message_check"] = $new_checks[$forum["forum_id"]];
            }
        }
    }

    $forums_shown=true;

    if($forum["folder_flag"]){
        $PHORUM["DATA"]["FOLDERS"][] = $forum;
    } else {
        $PHORUM["DATA"]["FORUMS"][] = $forum;
    }
}

if(!$forums_shown){
    // we did not show any forums here, show an error-message
    // set all our URL's
    phorum_build_common_urls();
    unset($PHORUM["DATA"]["URL"]["TOP"]);
    $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["NoForums"];

    phorum_output("message");

} else {

    if (isset($PHORUM["hooks"]["index"]))
        $PHORUM["DATA"]["FORUMS"]=phorum_hook("index", $PHORUM["DATA"]["FORUMS"]);

    // set all our URL's
    phorum_build_common_urls();

    // should we show the top-link?
    if($PHORUM['forum_id'] == 0 || $PHORUM['vroot'] == $PHORUM['forum_id']) {
        unset($PHORUM["DATA"]["URL"]["INDEX"]);
    }

    phorum_output("index_classic");
}

?>
