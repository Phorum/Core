<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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

if(!defined("PHORUM_CONTROL_CENTER")) return;

if(isset($PHORUM['args']['group'])){
    $group_id = $PHORUM['args']['group'];

} elseif(isset($_POST["group"])){
    $group_id = $_POST["group"];

} else {
    $group_id = "";
}

if(isset($PHORUM['args']['filter'])){
    $filter = $PHORUM['args']['filter'];

} elseif(isset($_POST["filter"])){
    $filter = $_POST["filter"];

} else {
    $filter = "";
}



if (!empty($group_id)){
    $perm = phorum_user_allow_moderate_group($group_id);
}
else{
    $perm = $PHORUM["DATA"]["GROUP_MODERATOR"];
}

if (!$perm) {
    phorum_redirect_by_url(phorum_get_url(PHORUM_CONTROLCENTER_URL));
    exit();
}

// figure out what the user is trying to do, in this case we have a group to list (and maybe some commands)
if (!empty($group_id)){
    // if adding a new user to the group
    if (isset($_REQUEST["adduser"])){
        $userid = phorum_db_user_check_field("username", $_REQUEST["adduser"]);
        // load the users groups, add the new group, then save again
        $groups = phorum_user_get_groups($userid);
        // make sure the user isn't already a member of the group
        if (!isset($groups[$group_id])){
            $groups[$group_id] = PHORUM_USER_GROUP_APPROVED;
            phorum_user_save_groups($userid, $groups);
            $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["UserAddedToGroup"];
        }
    }

    // if changing the existing members of the group
    if (isset($_REQUEST["status"])){
        foreach ($_REQUEST["status"] as $userid => $status){
            // load the users groups, make the change, then save again
            $groups = phorum_user_get_groups($userid);
            // we can't set someone to be a moderator from here
            if ($status != PHORUM_USER_GROUP_MODERATOR){
                $groups[$group_id] = $status;
            }
            if ($status == PHORUM_USER_GROUP_REMOVE){
                unset($groups[$group_id]);
            }
            phorum_user_save_groups($userid, $groups);
        }
        $PHORUM["DATA"]["OKMSG"] = $PHORUM["DATA"]["LANG"]["ChangesSaved"];
    }

    $group = phorum_db_get_groups($group_id);
    $PHORUM["DATA"]["GROUP"]["id"] = $group_id;
    $PHORUM["DATA"]["GROUP"]["name"] = $group[$group_id]["name"];
    $PHORUM["DATA"]["USERS"] = array();
    $PHORUM["DATA"]["GROUP"]["URL"]["VIEW"] = phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $group_id);

    $PHORUM["DATA"]["FILTER"] = array();
    $PHORUM["DATA"]["FILTER"][] = array("name" => $PHORUM["DATA"]["LANG"]["ShowAll"],
        "enable" => (empty($filter)),
        "url" => phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $group_id),
        "id" => 0);
    $PHORUM["DATA"]["FILTER"][] = array("name" => $PHORUM["DATA"]["LANG"]["ShowApproved"],
        "enable" => (!empty($filter) && $filter == PHORUM_USER_GROUP_APPROVED),
        "url" => phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $group_id, "filter=" . PHORUM_USER_GROUP_APPROVED),
        "id" => PHORUM_USER_GROUP_APPROVED);
    $PHORUM["DATA"]["FILTER"][] = array("name" => $PHORUM["DATA"]["LANG"]["ShowGroupModerator"],
        "enable" => (!empty($filter) && $filter == PHORUM_USER_GROUP_MODERATOR),
        "url" => phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $group_id, "filter=" . PHORUM_USER_GROUP_MODERATOR),
        "id" => PHORUM_USER_GROUP_MODERATOR);
    $PHORUM["DATA"]["FILTER"][] = array("name" => $PHORUM["DATA"]["LANG"]["ShowSuspended"],
        "enable" => (!empty($filter) && $filter == PHORUM_USER_GROUP_SUSPENDED),
        "url" => phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $group_id, "filter=" . PHORUM_USER_GROUP_SUSPENDED),
        "id" => PHORUM_USER_GROUP_SUSPENDED);
    $PHORUM["DATA"]["FILTER"][] = array("name" => $PHORUM["DATA"]["LANG"]["ShowUnapproved"],
        "enable" => (!empty($filter) && $filter == PHORUM_USER_GROUP_UNAPPROVED),
        "url" => phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $group_id, "filter=" . PHORUM_USER_GROUP_UNAPPROVED),
        "id" => PHORUM_USER_GROUP_UNAPPROVED);

    $PHORUM["DATA"]["STATUS_OPTIONS"] = array();
    $PHORUM["DATA"]["STATUS_OPTIONS"][] = array("value" => PHORUM_USER_GROUP_REMOVE, "name" => "&lt; " . $PHORUM["DATA"]["LANG"]["RemoveFromGroup"] . " &gt;");
    $PHORUM["DATA"]["STATUS_OPTIONS"][] = array("value" => PHORUM_USER_GROUP_APPROVED, "name" => $PHORUM["DATA"]["LANG"]["Approved"]);
    $PHORUM["DATA"]["STATUS_OPTIONS"][] = array("value" => PHORUM_USER_GROUP_UNAPPROVED, "name" => $PHORUM["DATA"]["LANG"]["Unapproved"]);
    $PHORUM["DATA"]["STATUS_OPTIONS"][] = array("value" => PHORUM_USER_GROUP_SUSPENDED, "name" => $PHORUM["DATA"]["LANG"]["Suspended"]);

    $groupmembers = phorum_db_get_group_members($group_id);
    $usersingroup = array_keys($groupmembers);
    $users = phorum_api_user_get($usersingroup);
    $memberlist = array();
    foreach ($groupmembers as $userid => $status){
        // if we have a filter, check that the user is in it
        if (!empty($filter)){
            if ($filter != $status){
                continue;
            }
        }

        $disabled = false;
        $statustext = "";
        // moderators can't edit other moderators
        if ($status == PHORUM_USER_GROUP_MODERATOR){
            $disabled = true;
            $statustext = $PHORUM["DATA"]["LANG"]["PermGroupModerator"];
        }

        $PHORUM["DATA"]["USERS"][$userid] = array("userid" => $userid,
            "name" => htmlspecialchars($users[$userid]["username"]),
            "display_name" => htmlspecialchars($users[$userid]["display_name"]),
            "status" => $status,
            "statustext" => $statustext,
            "disabled" => $disabled,
            "flag" => ($status < PHORUM_USER_GROUP_APPROVED),
            "url" => phorum_get_url(PHORUM_PROFILE_URL, $userid)
            );
    }

    if (isset($PHORUM["hooks"]["user_list"]))
        $PHORUM["DATA"]["USERS"] = phorum_hook("user_list", $PHORUM["DATA"]["USERS"]);

    // if the option to build a dropdown list is enabled, build the list of members that could be added
    if ($PHORUM["enable_dropdown_userlist"]){
        $userlist = phorum_user_get_list(1);
        $PHORUM["DATA"]["NEWMEMBERS"] = array();

        foreach ($userlist as $userid => $userinfo){
            if (!in_array($userid, $usersingroup)){
                $userinfo["username"] = htmlspecialchars($userinfo["username"]);
                $userinfo["display_name"] = htmlspecialchars($userinfo["display_name"]);
                $PHORUM["DATA"]["NEWMEMBERS"][] = $userinfo;
            }
        }
    }
}


// if they aren't doing anything, show them a list of groups they can moderate
else{
    $PHORUM["DATA"]["GROUPS"] = array();
    $groups = phorum_user_get_moderator_groups();
    // put these things in order so the user can read them
    asort($groups);
    foreach ($groups as $groupid => $groupname){
        // get the group members who are unapproved, so we can count them
        $members = phorum_db_get_group_members($groupid, PHORUM_USER_GROUP_UNAPPROVED);
        $PHORUM["DATA"]["GROUPS"][] = array("id" => $groupid,
            "name" => $groupname,
            "unapproved" => count($members),
            "URL" => array(
                "VIEW" => phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $groupid),
                "UNAPPROVED" => phorum_get_url(PHORUM_CONTROLCENTER_ACTION_URL, "panel=" . PHORUM_CC_GROUP_MODERATION,  "group=" . $groupid, "filter=" . PHORUM_USER_GROUP_UNAPPROVED)
                )
            );
    }
}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["GroupMembership"];
$PHORUM["DATA"]['POST_VARS'].="<input type=\"hidden\" name=\"group\" value=\"$group_id\" />\n";

$template = "cc_groupmod";
?>
