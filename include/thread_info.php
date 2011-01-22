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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM")) return;

/**
 * This is the callback-function for removing hidden messages from
 * an array of messages
 */

function phorum_remove_hidden($val)
{
    return ($val['status'] > 0);
}

/**
 * This function sets the stats for a thread like count, timestamp, etc.
 */
function phorum_update_thread_info($thread)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $messages=phorum_db_get_messages($thread,0,1,1);
    //these are not needed here
    unset($messages['users']);

    // Compute the threadviewcount, based on the individual message views.
    // This can be useful for updating the view counters after enabling
    // the view_count_per_thread option.
    $threadviewcount = 0;
    foreach ($messages as $id => $message) {
        $threadviewcount += $message['viewcount'];
    }

    // remove hidden/unapproved messages from the array
    $filtered_messages=array_filter($messages, "phorum_remove_hidden");

    $thread_count=count($filtered_messages);

    if($thread_count>0){

        $message_ids=array_keys($filtered_messages);

        sort($message_ids);

        $parent_message=$filtered_messages[$thread];

        // find the latest post in the thread (aka recent_message)
        $last_message_id_by_time = 0;
        $last_post_time = 0;
                
        foreach($filtered_messages as $message_id => $message_data) {
            if($message_data['datestamp'] > $last_post_time) {
                $last_post_time          = $message_data['datestamp'];
                $last_message_id_by_time = $message_id;
            } elseif($message_data['datestamp'] == $last_post_time 
                     && $message_id > $last_message_id_by_time) {
                $last_post_time          = $message_data['datestamp'];
                $last_message_id_by_time = $message_id;
            }
        }
        
        $recent_message = $filtered_messages[$last_message_id_by_time];

        // prep the message to save
        $message = array();
        $message["thread_count"]      = $thread_count;
        $message["threadviewcount"]   = $threadviewcount;
        $message["modifystamp"]       = $recent_message["datestamp"];
        $message["recent_message_id"] = $recent_message["message_id"];
        $message["recent_user_id"]    = $recent_message["user_id"];
        $message["recent_author"]     = $recent_message["author"];
        $message["meta"]              = $parent_message["meta"];

        // For cleaning up pre-5.2 recent post data.
        unset($message["meta"]["recent_post"]);

        $message["meta"]["message_ids"]=$message_ids;
        // used only for mods
        $message_ids_moderator = array_keys($messages);
        sort($message_ids_moderator);
        $message["meta"]["message_ids_moderator"]=$message_ids_moderator;

        if($PHORUM['cache_messages']) {
            // we can simply store them here again, no need to invalidate the cache
            // this function is called in any place where we change something to the thread
            phorum_cache_put('message_index',$PHORUM['forum_id']."-$thread-1",$message["meta"]["message_ids"]);
            phorum_cache_put('message_index',$PHORUM['forum_id']."-$thread-0",$message["meta"]["message_ids_moderator"]);

            // but we need to invalidate the main-message as its changed for the recent author/message
            phorum_cache_remove('message',$thread);
        }

        phorum_db_update_message($thread, $message);
    }

}


?>
