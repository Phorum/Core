#!/usr/bin/php
<?php
if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}
$opts = getopt("hf:");

if(isset($opts["h"])){
    help();
}

if(empty($opts["f"])){
    help("Filename is required");
}

if(!file_exists($opts["f"])){ help($opts["f"]." not found");
}

$fp = fopen($opts["f"], "r");
if($fp===false){
    help("Could not open ".$opts["f"]);
}
fclose($fp);

define("phorum_page", "xml_import");

define("PHORUM_ADMIN", true);

$pwd = getcwd();
chdir(dirname(dirname(__FILE__)));
require_once "./common.php";

include_once "./include/api/forums.php";
require_once PHORUM_PATH.'/include/api/thread.php';

chdir($pwd);

$xml = simplexml_load_file($opts["f"], null, LIBXML_NOCDATA | LIBXML_NOENT);

$group_count = 0;
$user_count = 0;
$forum_count = 0;
$message_count = 0;

// array to store a map of various things
// that we will need to track later on
$map = array(
    "folders" => array(),
    "forums"  => array()
);

$errors = array();

foreach($xml as $type => $data) {

    switch($type) {

        case "groups":
            echo "Importing Groups...\n";
            $done = 0;
            foreach($data->group as $group){
                $group = (array)$group;
                $new_groups[$group["@attributes"]["id"]] = $PHORUM['DB']->add_group($group["name"]);
                $group_count++;
                $done++;
                progress($done, count($data->group));
            }
            echo "\n\n";
            break;

        case "users":

            echo "Importing Users...\n";
            $done = 0;
            foreach($data->user as $user){

                $user = makeArray($user);

                foreach($user as &$val){
                    if(is_object($val)){
                        $val = (array)$val;
                    } elseif(!is_array($val)){
                        if(is_numeric($val)){
                            $val = (int)$val;
                        } else {
                            $val = (string)$val;
                        }
                    }
                }

                if(empty($user["id"]) || empty($user["username"]) || empty($user["email"])){
                    $errors[] = "Users must have a user id, user name and email address\n  -  id: $user[id]  username: $user[username]  email: $user[email]";
                    continue;
                }

                $new_user = array(
                    "user_id"  => $user["id"],
                    "username" => (string)$user["username"],
                    "email"    => (string)$user["email"]
                );

                unset($user["username"]);
                unset($user["email"]);

                if(isset($user["groups"])){
                    $groups = $user["groups"];
                    unset($user["groups"]);
                }

                if(isset($user["forumpermissions"])){
                    $permissions = $user["forumpermissions"];
                    unset($user["forumpermissions"]);
                }

                foreach($user as $field=>$value){

                    switch($field){
                        case "real_name":
                        case "display_name":
                        case "password":
                        case "signature":
                        case "user_language":
                            $new_user[$field] = (string)$value;
                            break;

                        case "threaded_list":
                        case "threaded_read":
                        case "hide_email":
                        case "active":
                        case "posts":
                        case "admin":
                        case "date_added":
                        case "date_last_active":
                        case "last_active_forum":
                        case "hide_activity":
                        case "show_signature":
                        case "email_notify":
                        case "pm_email_notify":
                        case "tz_offset":
                        case "is_dst":
                        case "moderation_email":
                            $new_user[$field] = (int)$value;
                            break;
                    }
                }

                if(isset($user->password)){
                    phorum_api_user_save($new_user, PHORUM_FLAG_RAW_PASSWORD);
                } else {
                    phorum_api_user_save($new_user);
                }


                if(!empty($permissions)){

                    $perm = 0;

                    if(isset($permissions->read)){
                        $perm = $perm | PHORUM_USER_ALLOW_READ;
                    }

                    if(isset($permissions->reply)){
                        $perm = $perm | PHORUM_USER_ALLOW_REPLY;
                    }

                    if(isset($permissions->edit)){
                        $perm = $perm | PHORUM_USER_ALLOW_EDIT;
                    }

                    if(isset($permissions->new)){
                        $perm = $perm | PHORUM_USER_ALLOW_NEW_TOPIC;
                    }

                    if(isset($permissions->new)){
                        $perm = $perm | PHORUM_USER_ALLOW_NEW_TOPIC;
                    }

                    if(isset($permissions->attach)){
                        $perm = $perm | PHORUM_USER_ALLOW_ATTACH;
                    }

                    if(isset($permissions->moderate_messages)){
                        $perm = $perm | PHORUM_USER_ALLOW_MODERATE_MESSAGES;
                    }

                    if(isset($permissions->moderate_messages)){
                        $perm = $perm | PHORUM_USER_ALLOW_MODERATE_USERS;
                    }

                }

                $permissions = $user->forumpermissions;


                if(!empty($groups)){
                    $user_groups = array();
                    foreach($groups as $group){
                        if(isset($new_groups[$group["@attributes"]["id"]])){
                            $group_id = $new_groups[$group["@attributes"]["id"]];
                            $user_groups[$group_id] = array();
                            if(isset($group->status) && $group->status > 0){
                                $user_groups[$group_id]["user_status"] = PHORUM_USER_GROUP_APPROVED;
                            } else {
                                $user_groups[$group_id]["user_status"] = PHORUM_USER_GROUP_UNAPPROVED;
                            }
                        }
                    }
                    if(count($user_groups)){
                        phorum_api_user_save_groups($user_id, $user_groups);
                    }
                }

                $done++;
                progress($done, count($data->user));

            }
            echo "\n\n";

            break;

        case "folders":

            echo "Importing Folders...\n";
            $done = 0;
            foreach($data->folder as $folder){
                $done++;
                progress($done, count($data->folder));
                $folder = makeArray($folder);
                if($folder["id"]==0) continue;
                $new_folder = array(
                    "forum_id" => null,
                    "name" => $folder["name"],
                    "description" => $folder["description"],
                    "active" => !$folder["hidden"],
                    "folder_flag" => 1
                );

                $new_folder = phorum_api_forums_save($new_folder);

                $map["folders"][$folder["id"]] = $new_folder["folder_id"];

            }

            echo "\n\n";
            break;


        case "forums":

            echo "Importing Forums...\n";
            $done = 0;
            foreach($data->forum as $forum){
                $forum = makeArray($forum);

                $new_forum = array(
                    "forum_id" => null,
                    "parent_id" => $map["folders"][$forum["folder_id"]],
                    "name" => $forum["name"],
                    "description" => $forum["description"],
                    "active" => !$forum["hidden"],
                    "read_length" => $forum["readlength"],
                    "list_length_flat" => $forum["flatlistlength"],
                    "list_length_threaded" => $forum["threadedlistlength"],
                    "threaded_list" => ($forum["listview"]=="flat") ? 0 : 1,
                    "threaded_read" => ($forum["readview"]=="flat") ? 0 : 1,
                    "folder_flag" => 0
                );

                if(isset($forum["permissions"])){

                    $new_forum["pub_perms"] = 0;
                    $new_forum["reg_perms"] = 0;

                    foreach($forum["permissions"] as $type=>$perms){
                        $field = ($type=="registeredUsers") ? "reg_perms" : "pub_perms";
                        foreach($perms as $perm=>$val){
                            if($val){
                                switch($perm){
                                    case "read":
                                        $new_forum[$field] = $new_forum[$field] | PHORUM_USER_ALLOW_READ;
                                        break;
                                    case "post":
                                        $new_forum[$field] = $new_forum[$field] | PHORUM_USER_ALLOW_NEW_TOPIC;
                                        break;
                                    case "reply":
                                        $new_forum[$field] = $new_forum[$field] | PHORUM_USER_ALLOW_REPLY;
                                        break;
                                    case "edit":
                                        $new_forum[$field] = $new_forum[$field] | PHORUM_USER_ALLOW_EDIT;
                                        break;
                                    case "attach":
                                        $new_forum[$field] = $new_forum[$field] | PHORUM_USER_ALLOW_ATTACH;
                                        break;
                                    case "mod":
                                        $new_forum[$field] = $new_forum[$field] | PHORUM_USER_ALLOW_MODERATE_MESSAGES;
                                        break;
                                }
                            }
                        }
                    }



                } else {
                    $new_forum["pub_perms"] = PHORUM_USER_ALLOW_READ;
                    $new_forum["reg_perms"] = PHORUM_USER_ALLOW_READ  |
                                              PHORUM_USER_ALLOW_REPLY |
                                              PHORUM_USER_ALLOW_EDIT  |
                                              PHORUM_USER_ALLOW_NEW_TOPIC;
                }


                $new_forum = phorum_api_forums_save($new_forum);

                $map["forums"][$forum["id"]] = $new_forum["forum_id"];

                $done++;
                progress($done, count($data->forum));

            }


            echo "\n\n";
            break;

        case "topics":

            echo "Importing Topics...\n";
            $done = 0;
            foreach($data->topic as $topic){
                $topic = makeArray($topic);

                $forum_id = $map["forums"][$topic["forum_id"]];

                $thread = 0;
                $parent = 0;

                // good ol simplexml and its one element sh-t
                if(isset($topic["message"]["id"])) $topic["message"] = array($topic["message"]);

                foreach($topic["message"] as $message){

                    $new_message = array(
                        "forum_id" => $forum_id,
                        "thread" => $thread,
                        "parent_id" => $parent,
                        "user_id" => $message["author_id"],
                        "author" => $message["author"],
                        "subject" => $message["subject"],
                        "body" => $message["body"],
                        "status" => PHORUM_STATUS_APPROVED,
                        "datestamp" => $message["timestamp"],
                        "moderator_post" => 0,
                        "sort" => PHORUM_SORT_DEFAULT,
                        "closed" => 0,
                    );

                    $message_id = $PHORUM['DB']->post_message($new_message, true);

                    if(empty($thread)){
                        $thread = $message_id;
                        $parent = $message_id;
                    }

                }

                phorum_api_thread_update_metadata($thread);

                $done++;
                progress($done, count($data->topic));
            }

            echo "\n\n";
            break;

        case "privatemessages":

            echo "Importing Private Messages...\n";
            $done = 0;
            foreach($data->privatemessage as $pm){
                $pm = makeArray($pm);

                $pm_id = $PHORUM['DB']->pm_send($pm["subject"], $pm["body"], $pm["to_user_id"], $pm["from_user_id"], false);

                if($pm_id){

                    // HAX!!
                    $PHORUM['DB']->interact(
                        DB_RETURN_RES,
                        "UPDATE {$PHORUM['pm_messages_table']}
                         SET    datestamp = $pm[timestamp]
                         WHERE  pm_message_id = $pm_id",
                        NULL,
                        DB_MASTERQUERY
                    );
                }

                $done++;
                progress($done, count($data->privatemessage));
            }

            echo "\n\n";
            break;

    }


}

echo "Updating Forum Stats...\n";
$done = 0;
foreach($map["forums"] as $fid){
    $PHORUM["forum_id"] = $fid;
    $PHORUM['DB']->update_forum_stats(true);
    $done++;
    progress($done, count($map["forums"]));
}

echo "\n\n";

if(!empty($errors)){
    echo "There were some errors.\n";
    echo implode("\n", $errors);
    echo "\n\n";
}


/*************************************/

function makeArray($obj) {
    $arr = (array)$obj;
    if(empty($arr)){
         $arr = "";
    } else {
        foreach($arr as $key=>$value){
            if(!is_scalar($value)){
                $arr[$key] = makeArray($value);
            }
        }
    }
    return $arr;
}

function progress($done, $total, $size=30) {

    if($done > $total) return;

    $perc=(double)($done/$total);

    $bar=floor($perc*$size);

    $status_bar="\r[";
    $status_bar.=str_repeat("=", $bar);
    if($bar<$size){
        $status_bar.=">";
        $status_bar.=str_repeat(" ", $size-$bar);
    } else {
        $status_bar.="=";
    }

    $disp=number_format($perc*100, 1);

    $status_bar.="] $disp%  $done/$total";

    echo "$status_bar  ";

    flush();

}

function help($message="") {
    $exit = 0;
    if($message){
        echo $message."\n";
        $exit = 1;
    }
    echo __FILE__." -f [-h]\n";
    echo " -f XML Filename to import\n";
    echo " -h Show this help\n";
    exit($exit);
}

?>
