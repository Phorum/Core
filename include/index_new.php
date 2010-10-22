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

// get vroot or create a fake one for forum_id=0
if($PHORUM["forum_id"]==0){

    $forums[0] = array(
                    "forum_id" => 0,
                    "folder_flag" => 1,
                    "vroot" => 0
                 );
} else {

    $forums = phorum_db_get_forums( $PHORUM["forum_id"] );
}

// init some data
$PHORUM["DATA"]["FORUMS"] = array();
$forums_shown=false;
$forums_to_check = array();

// get all the children forums/folders for the current forum_id
if($PHORUM["vroot"]==$PHORUM["forum_id"]){
    $more_forums = phorum_db_get_forums( 0, $PHORUM["forum_id"] );
    foreach($more_forums as $forum_id => $forum){
        if(empty($forums[$forum_id])){
            $forums[$forum_id]=$forum;
            if($PHORUM["show_new_on_index"]!=0 && $forum["folder_flag"]==0){
                $forums_to_check[] = $forum_id;
            }
        }
    }
    $folders[$PHORUM["forum_id"]]=$PHORUM["forum_id"];
}



// loop the children and get their children.
foreach( $forums as $key=>$forum ) {
    if($forum["folder_flag"] && $forum["vroot"]==$PHORUM["vroot"]){
        $folders[$key]=$forum["forum_id"];
        $forums[$key]["URL"]["LIST"] = phorum_get_url( PHORUM_INDEX_URL, $forum["forum_id"] );
        $forums[$key]["level"] = 0;

        if(isset($more_forums) && $forum["forum_id"] == $PHORUM["forum_id"]) {
            $sub_forums = $more_forums;
        } else {
            $sub_forums = phorum_db_get_forums( 0, $forum["forum_id"] );
        }

        foreach($sub_forums as $sub_forum){
            if(!$sub_forum["folder_flag"] || ($sub_forum["folder_flag"] && $sub_forum["parent_id"]!=$PHORUM['vroot'])){
                $folder_forums[$sub_forum["parent_id"]][]=$sub_forum;
                if($PHORUM["show_new_on_index"]!=0 && $sub_forum["folder_flag"]==0){
                    $forums_to_check[] = $sub_forum["forum_id"];
                }
            }
        }
    }
}

if($PHORUM["DATA"]["LOGGEDIN"] && !empty($forums_to_check)){
    if($PHORUM["show_new_on_index"]==2){
        $new_checks = phorum_db_newflag_check($forums_to_check);
    } elseif($PHORUM["show_new_on_index"]==1){
        $new_counts = phorum_db_newflag_count($forums_to_check);
    }
}

foreach( $folders as $folder_key=>$folder_id ) {

    if(!isset($folder_forums[$folder_id])) continue;

    $shown_sub_forums=array();

    foreach($folder_forums[$folder_id] as $key=>$forum){

        if($forum["folder_flag"]) {
            $forum["URL"]["INDEX"] = phorum_get_url( PHORUM_INDEX_URL, $forum["forum_id"] );
        } else {
            if($PHORUM["hide_forums"] && !phorum_api_user_check_access(PHORUM_USER_ALLOW_READ, $forum["forum_id"])){
                unset($folder_forums[$folder_id][$key]);
                continue;
            }

        $forum["URL"]["LIST"] = phorum_get_url( PHORUM_LIST_URL, $forum["forum_id"] );
        if ($PHORUM['DATA']['LOGGEDIN']) {
            $forum["URL"]["MARK_READ"] = phorum_get_url( PHORUM_INDEX_URL, $forum["forum_id"], "markread", $PHORUM['forum_id'] );
        }
        if(isset($PHORUM['use_rss']) && $PHORUM['use_rss']) {
            $forum["URL"]["FEED"] = phorum_get_url( PHORUM_FEED_URL, $forum["forum_id"], "type=".$PHORUM["default_feed"] );
        }


            if ( $forum["message_count"] > 0 ) {
                $forum["last_post"] = phorum_date( $PHORUM["long_date_time"], $forum["last_post_time"] );
                $forum["raw_last_post"] = $forum["last_post_time"];
            } else {
                $forum["last_post"] = "&nbsp;";
            }

            $forum["raw_message_count"] = $forum["message_count"];
            $forum["message_count"] = number_format($forum["message_count"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);
            $forum["raw_thread_count"] = $forum["thread_count"];
            $forum["thread_count"] = number_format($forum["thread_count"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);

            if($PHORUM["DATA"]["LOGGEDIN"]) {

                if($PHORUM["show_new_on_index"]==1){

                    $forum["new_messages"] = number_format($new_counts[$forum["forum_id"]]["messages"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);
                    $forum["new_threads"] = number_format($new_counts[$forum["forum_id"]]["threads"], 0, $PHORUM["dec_sep"], $PHORUM["thous_sep"]);

                } elseif($PHORUM["show_new_on_index"]==2){

                    if(!empty($new_checks[$forum["forum_id"]])){
                        $forum["new_message_check"] = true;
                    } else {
                        $forum["new_message_check"] = false;
                    }

                }

            }
        }

        $forum["level"] = 1;

        $shown_sub_forums[] = $forum;

    }

    if(count($shown_sub_forums)){
        $PHORUM["DATA"]["FORUMS"][]=$forums[$folder_key];
        $PHORUM["DATA"]["FORUMS"]=array_merge($PHORUM["DATA"]["FORUMS"], $shown_sub_forums);
    }

}


// set all our URL's
phorum_build_common_urls();

if(!count($PHORUM["DATA"]["FORUMS"])){
    $PHORUM["DATA"]["OKMSG"]=$PHORUM["DATA"]["LANG"]["NoForums"];
    phorum_output("message");
    return;
}

// should we show the top-link?
if($PHORUM['forum_id'] == 0 || $PHORUM['vroot'] == $PHORUM['forum_id']) {
    unset($PHORUM["DATA"]["URL"]["INDEX"]);
}

/**
 * [hook]
 *     index
 *
 * [description]
 *     This hook can be used to modify the data for folders and forums
 *     that are shown on the index page.
 *
 * [category]
 *     Page data handling
 *
 * [when]
 *     Just before the index page is shown.
 *
 * [input]
 *     An array containing all the forums and folders that will be shown
 *     on the index page.
 *
 * [output]
 *     The same array as the one that was used for the hook call
 *     argument, possibly with some updated fields in it.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_index($data)
 *     {
 *         global $PHORUM;
 *
 *         foreach ($data as $id => $item)
 *         {
 *             if (!$item['folder_flag'])
 *             {
 *                 $data[$id]['description'] .= '<br/>Blah foo bar baz';
 *             }
 *         }
 *
 *         return $data;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["index"]))
    $PHORUM["DATA"]["FORUMS"]=phorum_hook("index", $PHORUM["DATA"]["FORUMS"]);

phorum_output("index_new");

?>
