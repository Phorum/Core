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

if ( !defined( "PHORUM" ) ) return;

/**
 * These functions are Phorum's interface to the user data.  If you want
 * to use your own user data, just replace these functions.
 *
 * The functions do use Phorum's database layer.  Of course, it is not
 * required.
 */

// if you write your own user layer, set this to false
define( "PHORUM_ORIGINAL_USER_CODE", true );

/**
 * phorum_user_allow_moderate_group()
 *
 * Return true if the current user is allowed to moderate
 * a given group, or any group if no group is given.
 *
 * @param int - a group id to check (default, all)
 * @return bool
 */
function phorum_user_allow_moderate_group($group_id = 0)
{
    $groups = phorum_user_get_moderator_groups();
    if ($group_id == 0 && count($groups) > 0){
        return true;
    }
    elseif (isset($groups[$group_id])){
        return true;
    }
    else{
        return false;
    }
}

/**
 * phorum_user_get_moderator_groups()
 *
 * This function will return a list of the groups the current user
 * is allowed to moderate. For admins, this will return all the groups.
 *
 * The array is of the form array[group_id] = groupname.
 * @return array
 */
function phorum_user_get_moderator_groups()
{
    $PHORUM=$GLOBALS["PHORUM"];
    $groups = array();

    // if its an admin, return all groups as a moderator
    if ($PHORUM["user"]["admin"]){
        $fullgrouplist = phorum_db_get_groups();
        // the permission here is for a forum, we don't care about that
        foreach ($fullgrouplist as $groupid => $groupperm){
            $groups[$groupid] = $fullgrouplist[$groupid]["name"];
        }
    } else {
        $grouplist = phorum_user_get_groups($PHORUM["user"]["user_id"]);

        if(count($grouplist)) {
            $fullgrouplist = phorum_db_get_groups(array_keys($grouplist));

            foreach ($grouplist as $groupid => $perm){
                if ($perm == PHORUM_USER_GROUP_MODERATOR){
                    $groups[$groupid] = $fullgrouplist[$groupid]["name"];
                }
            }
        }
    }
    return $groups;
}

/**
 * phorum_user_get_groups()
 *
 * This function will return a list of groups the user
 * is a member of, as well as the users permissions.
 *
 * The returned list has the group id as the key, and
 * the permission as the value. Permissions are the
 * PHORUM_USER_GROUP constants.
 * @param int - the users user_id
 * @return array
 */
function phorum_user_get_groups($user_id)
{
    return phorum_db_user_get_groups($user_id);
}

/**
 * phorum_user_save_groups()
 *
 * This function saves a users group permissions. The data
 * to save should be an array of the form array[group_id] = permission
 * @param int - the users user_id
 * @param array - group permissions to save
 * @return bool - true if successful
 */
function phorum_user_save_groups($user_id, $groups)
{
    if(isset($GLOBALS["PHORUM"]['cache_users']) && $GLOBALS["PHORUM"]['cache_users']) {
        phorum_cache_remove('user',$user_id);
    }
    return phorum_db_user_save_groups($user_id, $groups);
}

function phorum_user_addpost($user_id)
{
    return phorum_db_user_addpost($user_id);
}

function phorum_user_delete($user_id)
{
    if(isset($GLOBALS["PHORUM"]['cache_users']) && $GLOBALS["PHORUM"]['cache_users']) {
        phorum_cache_remove('user',$user_id);
    }
    return phorum_db_user_delete($user_id);
}

/**
 * phorum_user_check_custom_field()
 *
 * This function takes a custom-fields name and content
 * as arguments and returns an array of the user_ids found
 * or NULL if no users are found
 *
 * optional match-parameter
 * 0 - exact match
 * 1 - like-clause
 */

function phorum_user_check_custom_field($field_name,$field_content,$match=0) {

    $type=-1;
    foreach($GLOBALS['PHORUM']['PROFILE_FIELDS'] as $ctype => $cdata) {
        if($ctype !== 'num_fields' && empty($cdata['deleted']) && $cdata['name'] == $field_name) {
            $type=$ctype;
            break;
        }
    }
    if($type > -1) {
        $retval=phorum_db_get_custom_field_users($type,$field_content,$match);
    } else {
        $retval=NULL;
    }

    return $retval;
}

?>
