<?php

if(!defined("PHORUM")) return;

/**
 * This is the callback-function for removing hidden messages from an array of messages
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
    $messages=phorum_db_get_messages($thread);
    //these are not needed here
    unset($messages['users']);
    
    // remove hidden/unapproved messages from the array
    $filtered_messages=array_filter($messages, "phorum_remove_hidden");    
    
    $thread_count=count($filtered_messages);

    if($thread_count>0){

        $message_ids=array_keys($filtered_messages);
    
        $parent_message=$filtered_messages[$thread];
    
        $recent_message=end($filtered_messages);
        
        // prep the message to save
        $message["thread_count"]=$thread_count;
        $message["modifystamp"]=$recent_message["datestamp"];
        $message["meta"]=$parent_message["meta"];
        $message["meta"]["recent_post"]["user_id"]=$recent_message["user_id"];
        $message["meta"]["recent_post"]["author"]=$recent_message["author"];
        $message["meta"]["recent_post"]["message_id"]=$recent_message["message_id"];
        $message["meta"]["message_ids"]=$message_ids;

        phorum_db_update_message($thread, $message);
    }

}


?>
