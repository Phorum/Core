#!/usr/bin/php
<?php

define('PHORUM_ADMIN', 1);

define('phorum_page', 'convert_announcements');

if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}
chdir(dirname(__FILE__) . "/..");
require_once './common.php';

// Make sure that the output is not buffered.
phorum_ob_clean();

// init module
$PHORUM["mod_announcements"] = array(
    "pages" => array(
        "index" => 1,
        "list" => 1
    ),
    "only_show_unread" => 0,
    "number_to_show" => 3,
    "days_to_show" => 0
);


// A template for the forums to be created for announcement forums
$template=array(
    "name"=>'Announcements',
    "active"=>1,
    "description"=>'Read this forum first to find out the latest information.',
    "template"=>'emerald',
    "folder_flag"=>0,
    "parent_id"=>0,
    "list_length_flat"=>30,
    "list_length_threaded"=>15,
    "read_length"=>20,
    "moderation"=>0,
    "threaded_list"=>0,
    "threaded_read"=>0,
    "float_to_top"=>0,
    "display_ip_address"=>0,
    "allow_email_notify"=>1,
    "language"=>$$PHORUM["default_forum_options"]["language"],
    "email_moderators"=>0,
    "display_order"=>99,
    "edit_post"=>1,
    "pub_perms" =>  1,
    "reg_perms" =>  3
);

$vroots[] = 0;

// get all current forums
$forums = phorum_db_get_forums();

// find the vroots
foreach($forums as $forum){
    if($forum["vroot"]==$forum["forum_id"] && $forum["folder_flag"]){
        $vroots[] = $forum["forum_id"];
    }
}

foreach($vroots as $vroot){

    // alter the template to work for this vroot
    $template["vroot"] = $vroot;
    $template["parent_id"] = $vroot;

    // add the new announcement forum for this vroot
    $forum_id = phorum_db_add_forum($template);

    // activate the forum in the announcements module
    $PHORUM["mod_announcements"]["vroot"][$vroot] = $forum_id;

    // update messages to the new forum_id
    $sql = "update {$PHORUM['message_table']} set forum_id=$forum_id, sort=2 where forum_id=$vroot";
    phorum_db_interact(DB_RETURN_RES, $sql);

    // update the new forums stats
    $PHORUM["forum_id"] = $forum_id;
    phorum_db_update_forum_stats(true);

}

// add the hooks and functions to the module
if(!in_array("announcements", $PHORUM["hooks"]["common"]["mods"])){
    $PHORUM["hooks"]["common"]["mods"][] = "announcements";
}
if(!in_array("phorum_setup_announcements", $PHORUM["hooks"]["common"]["funcs"])){
    $PHORUM["hooks"]["common"]["funcs"][] = "phorum_setup_announcements";
}
if(!in_array("announcements", $PHORUM["hooks"]["after_header"]["mods"])){
    $PHORUM["hooks"]["after_header"]["mods"][] = "announcements";
}
if(!in_array("phorum_show_announcements", $PHORUM["hooks"]["after_header"]["funcs"])){
    $PHORUM["hooks"]["after_header"]["funcs"][] = "phorum_show_announcements";
}
$PHORUM["mods"]["announcements"] = 1;

// update module in phorum settings
phorum_db_update_settings(
    array(
        "mods" => $PHORUM["mods"],
        "hooks" => $PHORUM["hooks"],
        "mod_announcements" => $PHORUM["mod_announcements"],
    )
);


?>
