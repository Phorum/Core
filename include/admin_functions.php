<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2011  Phorum Development Team                              //
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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if (!defined("PHORUM_ADMIN")) return;

require_once './include/api/forums.php';

function phorum_admin_error($error)
{
    echo "<div class=\"PhorumAdminError\">$error</div>\n";
}

function phorum_admin_okmsg($error)
{
    echo "<div class=\"PhorumAdminOkMsg\">$error</div>\n";
}

function phorum_get_folder_info()
{
    $folders=array();
    $folder_data=array();

    $forums = phorum_api_forums_get(
        NULL, NULL, NULL, NULL,
        PHORUM_FLAG_INCLUDE_INACTIVE | PHORUM_FLAG_FOLDERS
    );

    foreach($forums as $forum){
        $path = $forum["name"];
        $parent_id=$forum["parent_id"];
        while($parent_id!=0  && $parent_id!=$forum["forum_id"]){
            $path=$forums[$parent_id]["name"]."::$path";
            $parent_id=$forums[$parent_id]["parent_id"];
        }
        $folders[$forum["forum_id"]]=$path;
    }

    asort($folders);

    $tmp=array("--None--");

    foreach($folders as $id => $folder){
        $tmp[$id]=$folder;
    }

    $folders=$tmp;

    return $folders;

}

/*
*
* $forums_only can be 0,1,2,3
* 0 = all forums / folders
* 1 = all forums
* 2 = only forums + vroot-folders (used in banlists)
* 3 = only vroot-folders
*
* $vroot can be -1,0 or > 0
* -1 works as told above
* 0 returns only forums / folders with vroot = 0
* > 0 returns only forums / folders with the given vroot
*
*/

function phorum_get_forum_info($forums_only=0,$vroot = -1)
{
    $folders=array();
    $folder_data=array();

    $forums = phorum_api_forums_get(
        NULL, NULL, NULL, NULL,
        PHORUM_FLAG_INCLUDE_INACTIVE
    );

    foreach($forums as $forum){

        if( (
        $forums_only == 0 ||
        ($forum['folder_flag'] == 0 && $forums_only != 3) ||
        ($forums_only==2 && $forum['vroot'] > 0 && $forum['vroot'] == $forum['forum_id']) ||
        ($forums_only==3 && $forum['vroot'] == $forum['forum_id'] )
        ) && ($vroot == -1 || $vroot == $forum['vroot']) )  {


            $path = $forum["name"];
            $parent_id=$forum["parent_id"];

            while ($parent_id != 0)
            {
                $path=$forums[$parent_id]["name"]."::$path";
                $parent_id=$forums[$parent_id]["parent_id"];
            }

            if($forums_only!=3 && $forum['vroot'] && $forum['vroot']==$forum['forum_id']) {
                $path.=" (Virtual Root)";
            }
            $folders[$forum["forum_id"]]=$path;
        }
    }

    asort($folders,SORT_STRING);

    return $folders;

}

function phorum_admin_build_url($input_args) {
    global $PHORUM;
    
    $url = $PHORUM["admin_http_path"];
    
    if($input_args == 'base') {
        return $url;
    }
    
    if(is_array($input_args) && count($input_args)) {
        $url .="?".implode("&",$input_args);
        $url = preg_replace("!&{0,1}phorum_admin_token=([A-Za-z0-9]*)!", "", $url);
        if(!empty($PHORUM['admin_token'])) {
            $url .="&phorum_admin_token=".$PHORUM['admin_token'];
        }
    } elseif(!is_array($input_args) && !empty($input_args)) {
        $url .="?".$input_args;
        $url = preg_replace("!&{0,1}phorum_admin_token=([A-Za-z0-9]*)!", "", $url);
        if(!empty($PHORUM['admin_token'])) {
            $url .="&phorum_admin_token=".$PHORUM['admin_token'];
        }
    } else {
        if(!empty($PHORUM['admin_token'])) {
            $url = preg_replace("!\?{0,1}phorum_admin_token=([A-Za-z0-9]*)!", "", $url);
            $url .="?phorum_admin_token=".$PHORUM['admin_token'];
        }
    }
    
    
    
    return $url;
}
?>
