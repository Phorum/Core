<?php 

/*

***** IT IS HIGHLY RECCOMENDED THAT YOU RUN THIS SCRIPT ON A CONSOLE ****

*/

// this script will convert the data from a phorum3-database to a phorum5-database
// it doesn't change the old phorum3-tables, the data is only copied to the p5-tables
// works only from php-4.2.0 upwards!!!

/* Instructions:

1. copy/move this script up one directory to the main phorum5-dir

2. edit the $CONVERT variables below to match the settings of your phorum3 install.

3. install p5 as usual, preferably to the same database where phorum3 lives, it uses different tables names with the phorum_-prefix

4. empty (from the p5-version) the phorum_messages and phorum_forums tables (i.e. using phpmyadmin, I could do this with the script too
   but I would find it a little bit rude ;-))
   i.e. delete from phorum_messages;
        delete from phorum_forums;

5. if you have shell access, run this script via the shell:
      php phorum3to5convert.php
   if you DO NOT have shell access, call 
      <phorum5-url>/phorum3to5convert.php with your browser.

   *** THIS STEP MAY TAKE A WHILE ***
   
6. take a look at the p5-forums ... and if all seems correct

7. delete phorum3to5convert.php

*/

ini_set ( "zlib.output_compression", "0");
ini_set ( "output_handler", "");
@ob_end_flush();

define("PHORUM5_CONVERSION", 1);

// containing the data for accessing the database
$CONVERT['old_dbhost'] = "localhost";
$CONVERT['old_dbuser'] = "phorum5";
$CONVERT['old_dbpass'] = "phorum5";

// database of the phorum3-tables
$CONVERT['olddb'] = "phorum";

// main-table-name for phorum3 (default is "forums")
$CONVERT['forumstable'] = "forums";

// separator, if you run it from the web <br>\n ... if from the console use only \n
$CONVERT['lbr'] = "<br>\n";

// absolute path to 3.4.x attachments (like in old admin) 
$CONVERT['attachmentdir'] = "path/to/files";

// we try to disable the execution timeout
// that command doesn't work in safe_mode :(
set_time_limit(0);

require './common.php';
require './include/thread_info.php';
require './scripts/phorum3_in.php';

// no need to change anything below this line
// establishing the first link to the old database
$oldlink = mysql_connect($CONVERT['old_dbhost'], $CONVERT['old_dbuser'], $CONVERT['old_dbpass'], true); 
mysql_select_db($CONVERT['olddb'], $oldlink);

if (!$oldlink) {
    print "Couldn't connect to the old database.".$CONVERT['lbr'];
    exit();
} 

// checking attachment-dir
if (!file_exists($CONVERT['attachmentdir']) || empty($CONVERT['attachmentdir'])) {
    echo "Directory {$CONVERT['attachmentdir']} doesn't exist. Attachments won't be converted. (doesn't matter if you don't have message-attachments) {$CONVERT['lbr']}"; 
}

$CONVERT['groups']=array();
$CONVERT['do_groups']=false;

// checking if the groups-table exists
if(phorum_convert_check_groups($oldlink)) {
    // reading groups (should be not too much, therefore we keep the array for later use)
    $CONVERT['groups'] = phorum_convert_getGroups($oldlink);
    if(count($CONVERT['groups'])) {
        echo "Writing groups ... {$CONVERT['lbr']}";
        foreach($CONVERT['groups'] as $groupid => $groupdata) {
            phorum_db_add_group($groupdata['name'],$groupid);
            $CONVERT['groups'][$groupid]['group_id']=$groupid;        
        }
    }
    $CONVERT['do_groups']=true;
}

$CONVERT['do_users']=false;
// checking if the users-table exists
if(phorum_convert_check_users($oldlink)) {
    $CONVERT['do_users']=true;
}

// reading the forums
$forums = phorum_convert_getForums($oldlink);

// going through all the forums (and folders)
echo "Writing forumdata ... {$CONVERT['lbr']}";
flush();
$offsets=array();

foreach($forums as $forumid => $forumdata) {
    $newforum = phorum_convert_prepareForum($forumdata);

    phorum_db_add_forum($newforum);

    if (!$forumdata['folder']) {
        $PHORUM['forum_id'] = $forumid;
        $CONVERT['forum_id'] = $forumid;

        echo "Reading maximum message-id from messages-table... {$CONVERT['lbr']}";
        flush();
        $CONVERT['max_id'] = phorum_db_get_max_messageid();
        $offsets[$forumid]=$CONVERT['max_id'];
        
        if ($forumdata['allow_uploads']=='Y' && file_exists($CONVERT['attachmentdir']."/".$forumdata['table_name'])) {
            $CONVERT['attachments']=phorum_convert_getAttachments($forumdata['table_name']);
            echo "Reading attachments for forum " . $forumdata['name'] . "...{$CONVERT['lbr']}";
            flush();
        }

        echo "Writing postings for forum " . $forumdata['name'] . "...{$CONVERT['lbr']}";
        flush();

        $count = 1;
        $total = 0;

        $res = phorum_convert_selectMessages($forumdata, $oldlink);
        while ($newmessage = phorum_convert_getNextMessage($res,$forumdata['table_name'])) {
           
            if(phorum_db_post_message($newmessage, true)) {
              phorum_update_thread_info($newmessage['thread']);
              echo "+";
              flush();
              if ($count == 50) {
                  $total += $count;
                  echo " $total from \"{$forumdata['name']}\"";
                  if($CONVERT['lbr']=="\n"){
                      // lets just go back on this line if we are on the console
                      echo "\r";
                  } else {
                      echo $CONVERT['lbr'];
                  }
                  flush();
                  $count = 0;
              } 
              $count++;
            } else {
              print "Error in message: ".$CONVERT['lbr'];
              print_var($newmessage);
              print $CONVERT['lbr'];
            }
        } 
        
        echo "{$CONVERT['lbr']}Updating forum-statistics: {$CONVERT['lbr']}";
        flush();
        phorum_db_update_forum_stats(true);
        echo $CONVERT['lbr'];
        flush();
    } 
} 
unset($forums);

// storing the offsets of the forums
phorum_db_update_settings(array("conversion_offsets"=>$offsets));

if($CONVERT['do_groups'] && count($CONVERT['groups'])) { // here we set the group-permissions
    echo "Writing group-permissions ... {$CONVERT['lbr']}";
    foreach($CONVERT['groups'] as $groupid => $groupdata) {
        phorum_db_save_group($groupdata);
    }
}

if($CONVERT['do_users']) {
    echo "migrating users ...{$CONVERT['lbr']}";
    flush();
    $group_perms=phorum_convert_getUserGroups($oldlink);
    $res = phorum_convert_selectUsers($oldlink);

    if (!$res) {
        echo "No users found, All done now.{$CONVERT['lbr']}";
        flush();
        exit;
    }

    // there are users...
    $count = 0;
    $userdata["date_added"] = time();
    $cur_time = time();
    while ($cur_user = phorum_convert_getNextUser($res)) {
        if (isset($cur_user['user_id'])) {
            phorum_user_add($cur_user, -1);
            $user_groups=$group_perms[$cur_user['user_id']];
            if(count($user_groups)) { // setting the user's group-memberships
            phorum_db_user_save_groups($cur_user['user_id'],$user_groups);
            }
            $count++;
        }
    }
    unset($users);
    print "$count users converted{$CONVERT['lbr']}";
}
echo "{$CONVERT['lbr']}Done.{$CONVERT['lbr']}";
flush();

?>
