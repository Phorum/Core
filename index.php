<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2010  Phorum Development Team                               //
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
        $dest_url = phorum_get_url(PHORUM_INDEX_URL, $folder_id);
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

// add feed urls
if (isset($PHORUM['use_rss']) && $PHORUM['use_rss'])
{
    $PHORUM['DATA']['FEEDS'] = array(
        array(
            'URL' => phorum_get_url(PHORUM_FEED_URL, $PHORUM['vroot'], 'type='.$PHORUM['default_feed']),
            'TITLE' => $PHORUM['DATA']['FEED'] . ' ('. strtolower($PHORUM['DATA']['LANG']['Threads']) . ')'
        ),
        array(
            "URL" => phorum_get_url(PHORUM_FEED_URL, $PHORUM['vroot'], 'replies=1', 'type='.$PHORUM['default_feed']),
            "TITLE" => $PHORUM['DATA']['FEED'] . ' (' . strtolower($PHORUM['DATA']['LANG']['Threads'].' + '.$PHORUM['DATA']['LANG']['replies']) . ')'
        )
    );
}


if ( isset( $PHORUM["forum_id"] ) ) {
    $parent_id = (int)$PHORUM["forum_id"];
} else {
    $parent_id = 0;
}

if(!isset($PHORUM["use_new_folder_style"]) || $PHORUM["use_new_folder_style"]){
    include_once "./include/index_new.php";
} else {
    include_once "./include/index_classic.php";
}

?>
