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
