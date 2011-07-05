#!/usr/bin/php
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

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define("phorum_page", "stresstest");
define("PHORUM_ADMIN", 1);

// I guess the phorum-directory is one level up. if you move the script to
// somewhere else you'll need to change that.
$PHORUM_DIRECTORY = dirname(__FILE__) . "/../";

// change directory to the main-dir so we can use common.php
if(file_exists($PHORUM_DIRECTORY."/common.php")) {
    chdir($PHORUM_DIRECTORY);
    if (!is_readable("./common.php")) {
        fprintf(STDERR,
            "Unable to read common.php from directory $PHORUM_DIRECTORY\n");
        exit(1);
    }
} else {
    fprintf(STDERR, 
        "Unable to find Phorum file \"common.php\".\n" .
        "Please check the \$PHORUM_DIRECTORY in " . basename(__FILE__) ."\n");
    exit(1);
}

// include required files
include_once './common.php';
include_once ("./include/thread_info.php");

// Make sure that the output is not buffered.
phorum_ob_clean();

print "\n";
print "Phorum stress testing tool\n";
print "--------------------------\n";
print "\n";
print "This tool can be used for easily creating lots of users and messages.\n";
print "It is only meant for developers to do large data volume stress tests.\n";
print "Do not run this on a live production Phorum installation, or you\n";
print "will end up with a lot of bogus data in your forums.\n";
print "\n";
print "Are you sure you want to continue? (type \"yes\")\n";
print "> ";

$yes = trim(fgets(STDIN));
if ($yes != 'yes') {
    print "\nAborting ...\n\n";
    exit();
}

print "\nHow many users do you want to create?\n";
print "> "; $ucount = (int)trim(fgets(STDIN));

print "\nHow many threads do you want to create?\n";
print "> "; $tcount = (int)trim(fgets(STDIN));

print "\nHow many messages do you want to create per thread?\n";
print "> "; $mcount = (int)trim(fgets(STDIN));

print "\nHow many newflags do you want to set per user?\n";
print "> "; $ncount = (int)trim(fgets(STDIN));

print "\n";

// Create random users.
$randomuserprefixes = array(
    "CBiLL", "Gummi", "tomaz", "Oliver",
    "Bastian", "rheo", "iamback"
);

if ($ucount > 0) {
    print "Creating $ucount random user(s):\n\n";
    for ($i = 0; $i < $ucount; $i++) {
        $name = $randomuserprefixes[array_rand($randomuserprefixes)];
        $name .= rand(1, 9999999); 
        $email = $name . '@example.com'; 
        $pass = "xxxxxxxx";

        $user = array(
            "user_id"  => NULL,
            "username" => $name,
            "password" => $pass,
            "email"    => $email,
            "active"   => PHORUM_USER_ACTIVE
        );

        phorum_api_user_save($user);
        print ".";
    }
    print "\n";
}

// Retrieve users which we can use to post with.
$users = phorum_api_user_list(PHORUM_GET_ACTIVE);
$user_ids = array_keys($users);
if (!count($user_ids)) 
    die ("No users found that can be used for posting.\n");

// Retrieve forums to post in.
$forums = phorum_db_get_forums(0, NULL, 0);
$forum_ids = array();
foreach ($forums as $id => $forum) {
    if ($forum["folder_flag"]) continue;
    $forum_ids[] = $id;
}
if (!count($forum_ids)) 
    die ("No users found that can be used for posting.\n");


if ($tcount)
{
    $batch = time();

    print "\nPosting $tcount threads to the database:\n\n";

    $count = 0;
    while ($tcount)
    {
        $f = $forum_ids[$count % count($forum_ids)];

        print ".";

        $count ++;

        $treemsgs = array();
        $parent = 0;
        $thread = 0;


        for ($i=0; $i<$mcount; $i++)
        {
            $u = $user_ids[$i % count($user_ids)];

            $msg = array(
                "parent_id" => $parent,
                "thread"    => $thread,
                "forum_id"  => $f,
                "subject"   => "Message $i of stress batch thread $batch / $count",
                "body"      => "I am just a test message, created by the Phorum\n" .
                               "stress testing software. I have no value at all.\n",
                "user_id"   => $u,
                "author"    => $users[$u]["username"],
                "email"     => '',
                "ip"        => "127.0.0.1",
                "status"    => PHORUM_STATUS_APPROVED,
                "msgid"     => "<stressbatch_{$batch}_{$count}_{$i}@localhost>",
                "moderator_post" => 0,
                "sort"      => PHORUM_SORT_DEFAULT,
                "closed"    => 0,
            );
             
            phorum_db_post_message($msg);

            $thread = $msg["thread"];
            $treemsgs[] = $msg["message_id"];

            $parent = $treemsgs[array_rand($treemsgs)];
        }

        phorum_update_thread_info($thread);

        $tcount --;
    }

    print "\n";

    foreach ($forum_ids as $id) {
        $PHORUM["forum_id"] = $id;
        phorum_db_update_forum_stats(true);
    }
}

if ($ncount) 
{
    print "\nSetting $ncount newflags for " . count($users) . " users:\n\n";

    $recent = phorum_db_get_recent_messages($ncount);

    $markread = array();
    foreach ($recent as $id => $msg)
    {
    	if($id == 'users') {
    		continue;
    	}
        $markread[] = array(
            "id"    => $id,
            "forum" => $msg["forum_id"]
        );
    }    

    foreach ($users as $user_id => $stuff) 
    {
        print ".";
        $PHORUM["user"]["user_id"] = $user_id;
        phorum_db_newflag_add_read($markread);
    }
    print "\n";
}

print "\nDone!\n\n";

exit(0);
?>
