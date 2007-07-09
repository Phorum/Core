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

function phorum_user_verify( $user_id, $tmp_pass )
{
    $user_id = phorum_db_user_check_field( array( "user_id", "password_temp" ), array( $user_id, md5( $tmp_pass ) ), array( "=", "=" ) );
    return $user_id;
}

function phorum_user_check_username( $username )
{
    return phorum_db_user_check_field( "username", $username );
}

function phorum_user_check_email( $email )
{
    return phorum_db_user_check_field( "email", $email );
}

/**
* (generic) function for checking a user-field in the database
*/
function phorum_user_check_field( $field_name, $field_value)
{
    return phorum_db_user_check_field( $field_name , $field_value );
}

/**
 * Retrieve the display name for a user.
 *
 * The name to use depends on the "display_name_source" setting. This
 * one points to either the username or the real_name field of the
 * user. If the display_name is requested for an unknown user, then
 * a fallback name will be used.
 *
 * Note: this function does not take the custom_display_name functionality
 *       into account. In its final state it should. For now, this function
 *       was written to provide a safe name to display in PM notifies and
 *       message quotes.
 *
 * @param mixed $user
 *     Either a full user record, the user_id for a user or NULL to
 *     use the user_id of the active Phorum user.
 *
 * @param mixed $fallback
 *     The fallback name to use in case the user is unknown or NULL
 *     to use the "AnonymousUser" language string.
 *
 * @return string $display_name
 *     The display name to use for the user.
 */
function phorum_user_get_display_name( $user = NULL, $fallback = NULL )
{
    if ($fallback === NULL) {
        $fallback = $GLOBALS['PHORUM']['DATA']['LANG']['AnonymousUser'];
    }

    // Use the user_id for the active user.
    if ($user === NULL) {
        $user = $GLOBALS['PHORUM']['user']['user_id'];
    }

    // Lookup the user for a given user_id.
    if (!is_array($user)) {
        settype($user, "int");
        $user = phorum_db_user_get($user);
    }

    // Determine the display name.
    if (empty($user)) {
        $display_name = $fallback;
    } else {
        $display_name = $user['username'];
        if ($GLOBALS["PHORUM"]['display_name_source'] == 'real_name' &&
            trim($user['real_name']) != '') {
            $display_name = $user['real_name'];
        }
    }

    return $display_name;
}

function phorum_user_subscribe( $user_id, $forum_id, $thread, $type )
{
    $list=phorum_user_access_list( PHORUM_USER_ALLOW_READ );
    if(!in_array($forum_id, $list)) return;
    return phorum_db_user_subscribe( $user_id, $forum_id, $thread, $type );
}

function phorum_user_unsubscribe( $user_id, $thread, $forum_id=0 )
{
    if($forum_id){
        return phorum_db_user_unsubscribe( $user_id, $thread, $forum_id );
    } else {
        return phorum_db_user_unsubscribe( $user_id, $thread );
    }
}

/**
 * This function returns true if the current user is allowed to moderate $forum_id or the user given through user_data
 */

function phorum_user_moderate_allowed( $forum_id = 0, $user_data = 0 )
{
    $PHORUM = $GLOBALS["PHORUM"];

    if ( $user_data == 0 ) {
        $user_data = $PHORUM["user"];
    }
    // if this is an admin, stop now
    if ( $user_data["admin"] ) return true;

    // they have no special permissions, return
    if(!isset($user_data["permissions"])){
        return false;
    }

    // this sets up a check for moderation at any level
    if ( $forum_id==PHORUM_MODERATE_ALLOWED_ANYWHERE ){
        $perms = $user_data["permissions"];
    } else {
        // else we check only one forum
        // if no forum_id passed, check current forum
        if ( $forum_id==0 ){
            $forum_id = $PHORUM["forum_id"];
        }
        if(isset($user_data["permissions"][$forum_id])){
            $perms[$forum_id] = $user_data["permissions"][$forum_id];
        } else {
            return false;
        }
    }

    // check the users permission array
    foreach($perms as $forum_id => $perm) {
        if ( $perm & PHORUM_USER_ALLOW_MODERATE_MESSAGES ) {
            return true;
        }
    }

    return false;
}

/**
 * calls the db-function for listing all the moderators for a forum
 * This returns an array of moderators, key as their userid, value as their email address.
 */
function phorum_user_get_moderators( $forum_id , $ignore_user_perms = false, $for_email = false)
{
    $gotmods=false;
    if(isset($GLOBALS["PHORUM"]['cache_users']) && $GLOBALS["PHORUM"]['cache_users']) {
        $mods=phorum_cache_get('user','moderators-'.$forum_id.'-'.$ignore_user_perms);
        if($mods != null) {
            $gotmods=true;
        }
    }
    if(!$gotmods) {
        $mods=phorum_db_user_get_moderators( $forum_id , $ignore_user_perms, $for_email);
    }
    return $mods;
}

/**
 * phorum_user_access_allowed()
 *
 * @param  int $permission Use the PHORUM_ALLOW_* constants
 * @return bool
 */
function phorum_user_access_allowed( $permission, $forum_id = 0 )
{
    $PHORUM = $GLOBALS["PHORUM"];

    if ( empty( $forum_id ) ) $forum_id = $PHORUM["forum_id"];

    $ret = false;
    // user is an admin, he gets it all
    if ( !empty( $PHORUM["user"]["admin"] ) ) {
        $ret = true;
    } else {
        // user is logged in.
        if ( $PHORUM["user"]["user_id"] > 0 ) {
            // if the user has perms for this forum, use them.
            if ( isset( $PHORUM["user"]["permissions"][$forum_id] ) ) {
                $perms = $PHORUM["user"]["permissions"][$forum_id];
            // else we use the forum's default perms
            // for registered users
            } elseif ( $forum_id ) {
                if ( $forum_id != $PHORUM["forum_id"] ) {
                    $forums = phorum_db_get_forums( $forum_id );
                    $forum = array_shift( $forums );
                } else {
                    $forum = $PHORUM;
                }
                $perms = $forum["reg_perms"];
            }
        // user is not logged in
        // use the forum default perms for public users
        } elseif ( $forum_id ) {
            if ( $forum_id != $PHORUM["forum_id"] ) {
                $forums = phorum_db_get_forums( $forum_id );
                $forum = array_shift( $forums );
            } else {
                $forum = $PHORUM;
            }
            if(isset($forum['pub_perms']))
                $perms = $forum["pub_perms"];
        }

        if ( !empty( $perms ) && ( $ret || ( $perms &$permission ) ) ) {
            $ret = true;
        } else {
            $ret = false;
        }
    }

    return $ret;
}

/**
 * phorum_user_access_list()
 *
 * This function will return a list of forum ids in which
 * the current user has $permission
 *
 * @param  $permission Use the PHORUM_ALLOW_* constants
 * @return bool
 */

function phorum_user_access_list( $permission )
{
    $PHORUM = $GLOBALS["PHORUM"];

    $forums = phorum_db_get_forums(0, NULL, $PHORUM['vroot']);
    $forum_list = array();

    $field = ( $PHORUM["user"]["user_id"] > 0 ) ? "reg_perms" : "pub_perms";

    foreach( $forums as $forum_id => $forum ) {
        if ( $PHORUM["user"]["admin"] || $forum[$field] &$permission ) {
            $forum_list[$forum_id] = $forum_id;
        }
        // if its a folder, they have read permission but nothing else
        elseif ($forum["folder_flag"] && $permission == PHORUM_USER_ALLOW_READ){
            $forum_list[$forum_id] = $forum_id;
        }
    }

    if ( !$PHORUM["user"]["admin"] && !empty( $PHORUM["user"]["permissions"] ) ) {
        foreach( $PHORUM["user"]["permissions"] as $forum_id => $perms ) {
            if ( isset( $forum_list[$forum_id] ) ) unset( $forum_list[$forum_id] );
            if ( $perms & $permission ) {
                $forum_list[$forum_id] = $forum_id;
            }
        }
    }

    return $forum_list;
}

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
