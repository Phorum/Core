<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;
// we need that for signature formatting
include_once('./include/format_functions.php');

$template = "cc_start";
$PHORUM['DATA']['UserPerms'] = phorum_readable_permissions();
$PHORUM['DATA']['PROFILE']['date_added'] = phorum_date( $PHORUM['short_date'], $PHORUM['DATA']['PROFILE']['date_added']);
if( !empty($PHORUM["user"]["admin"]) ||
    (phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) ||
    (phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_USERS)) ||
    !$user["hide_activity"]){

    $PHORUM["DATA"]["PROFILE"]["date_last_active"]=phorum_date( $PHORUM['short_date'], $PHORUM["DATA"]["PROFILE"]["date_last_active"]);
} else {
    unset($PHORUM["DATA"]["PROFILE"]["date_last_active"]);
}

$PHORUM["DATA"]["PROFILE"]["username"] = htmlspecialchars($PHORUM["DATA"]["PROFILE"]["username"]);


$foo_message=array('author'=>'','email'=>'','subject'=>'','linked_author'=>'','body'=>$PHORUM["DATA"]["PROFILE"]['signature']);

list($foo_message)=phorum_format_messages(array($foo_message));

$PHORUM["DATA"]["PROFILE"]['signature']=$foo_message['body'];

$PHORUM["DATA"]["PROFILE"] = phorum_hook("profile", $PHORUM["DATA"]["PROFILE"]);
/* --------------------------------------------------------------- */

function phorum_readable_permissions()
{
    $PHORUM = $GLOBALS['PHORUM'];
    $newperms = array();

    if (isset($PHORUM["user"]["permissions"])) {
        $forums = phorum_db_get_forums(array_keys($PHORUM["user"]["permissions"]));

        foreach($PHORUM["user"]["permissions"] as $forum => $perms) {
            if(isset($forums[$forum])) {
                if($perms & PHORUM_USER_ALLOW_MODERATE_MESSAGES){
                    $newperms[] = array('forum' => $forums[$forum]["name"], 'perm' => $PHORUM['DATA']['LANG']['PermModerator']);
                }

                if($perms & PHORUM_USER_ALLOW_READ){
                    $newperms[] = array('forum' => $forums[$forum]["name"], 'perm' => $PHORUM['DATA']['LANG']['PermAllowRead']);
                }

                if($perms & PHORUM_USER_ALLOW_REPLY){
                    $newperms[] = array('forum' => $forums[$forum]["name"], 'perm' => $PHORUM['DATA']['LANG']['PermAllowReply']);
                }

                if($perms & PHORUM_USER_ALLOW_NEW_TOPIC){
                    $newperms[] = array('forum' => $forums[$forum]["name"], 'perm' => $PHORUM['DATA']['LANG']['PermAllowPost']);
                }
            }
        }
    }

    return $newperms;
}
?>
