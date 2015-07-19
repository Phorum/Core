<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

if(!defined("PHORUM_ADMIN")) return;

function phorum_admin_error($error)
{
    echo "<div class=\"PhorumAdminError\">$error</div>\n";
}

function phorum_admin_okmsg($error)
{
    echo "<div class=\"PhorumAdminOkMsg\">$error</div>\n";
}
// phorum_get_language_info and phorum_get_template_info moved to common.php (used in the cc too)

function phorum_get_folder_info()
{
    $folders=array();
    $folder_data=array();

    $forums = phorum_db_get_forums();

    foreach($forums as $forum){
        if($forum["folder_flag"]){
            $path = $forum["name"];
            $parent_id=$forum["parent_id"];
            while($parent_id!=0  && $parent_id!=$forum["forum_id"]){
                $path=$forums[$parent_id]["name"]."::$path";
                $parent_id=$forums[$parent_id]["parent_id"];
            }
            $folders[$forum["forum_id"]]=$path;
        }
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

    $forums = phorum_db_get_forums();

    foreach($forums as $forum){

        if( (
        $forums_only == 0 ||
        ($forum['folder_flag'] == 0 && $forums_only != 3) ||
        ($forums_only==2 && $forum['vroot'] > 0 && $forum['vroot'] == $forum['forum_id']) ||
        ($forums_only==3 && $forum['vroot'] == $forum['forum_id'] )
        ) && ($vroot == -1 || $vroot == $forum['vroot']) )  {


            $path = $forum["name"];
            $parent_id=$forum["parent_id"];

            while( $parent_id!=0 ){

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


/*
* Sets the given vroot for the descending forums / folders
* which are not yet in another descending vroot
*
* $folder = folder from which we should go down
* $vroot  = virtual root we set the folders/forums to
* $old_vroot = virtual root which should be overrideen with the new value
*
*/
function phorum_admin_set_vroot($folder,$vroot=-1,$old_vroot=0) {
    // which vroot
    if($vroot == -1) {
        $vroot=$folder;
    }

    // get the desc forums/folders
    $descending=phorum_admin_get_descending($folder);
    $valid=array();

    // collecting vroots
    $vroots=array();
    foreach($descending as $id => $data) {
        if($data['folder_flag'] == 1 && $data['vroot'] != 0 && $data['forum_id'] == $data['vroot']) {
            $vroots[$data['vroot']]=true;
        }
    }

    // getting forums which are not in a vroot or not in *this* vroot
    foreach($descending as $id => $data) {
        if($data['vroot'] == $old_vroot || !isset($vroots[$data['vroot']])) {
            $valid[$id]=$data;
        }
    }

    // $valid = forums/folders which are not in another vroot
    $set_ids=array_keys($valid);
    $set_ids[]=$folder;

    $new_forum_data=array('forum_id'=>$set_ids,'vroot'=>$vroot);
    $returnval=phorum_db_update_forum($new_forum_data);

    return $returnval;
}

function phorum_admin_get_descending($parent) {

    $ret_data=array();
    $arr_data=phorum_db_get_forums(0,$parent);
    foreach($arr_data as $key => $val) {
        $ret_data[$key]=$val;
        if($val['folder_flag'] == 1) {
            $more_data=phorum_db_get_forums(0,$val['forum_id']);
            $ret_data=$ret_data + $more_data; // array_merge reindexes the array
        }
    }
    return $ret_data;
}

function phorum_admin_build_path_array($only_forum = NULL)
{
    $paths = array();

    // The forum_id = 0 root node is not in the database.
    // Here, we create a representation for that node that will work.
    $root = array(
        'vroot'    => 0,
        'forum_id' => 0,
        'name'     => $GLOBALS['PHORUM']['title']
    );

    // If we are going to update the paths for all nodes, then we pull
    // in our full list of forums and folders from the database. If we only
    // need the path for a single node, then the node and all its parent
    // nodes are retrieved using single calls to the database.
    if ($only_forum === NULL) {
        $nodes = phorum_db_get_forums();
        $nodes[0] = $root;
    } else {
        if ($only_forum == 0) {
            $nodes = array(0 => $root);
        } else {
            $nodes = phorum_db_get_forums($only_forum);
        }
    }

    // Build the paths for the retrieved node(s).
    foreach($nodes as $id => $node)
    {
        $path = array();

        while (TRUE)
        {
            // Add the node to the path.
            $path[$node['forum_id']] = $node['name'];

            // Stop building when we hit a (v)root.
            if ($node['forum_id'] == 0 ||
                $node['vroot'] == $node['forum_id']) break;

            // Find the parent node. The root node (forum_id = 0) is special,
            // since that one is not in the database. We create an entry on
            // the fly for that one here.
            if ($node['parent_id'] == 0) {
                $node = $root;
            } elseif ($only_forum !== NULL) {
                $tmp = phorum_db_get_forums($node['parent_id']);
                $node = $tmp[$node['parent_id']];
            } else {
                $node = $nodes[$node['parent_id']];
            }
        }

        // Reverse the path, since we have been walking up the path here.
        // For the parts of the application that use this data, it's more
        // logical if the root nodes come first in the path arrays.
        $paths[$id] = array_reverse($path, TRUE);
    }

    // We cannot remember what this was needed for. For now, we leave it out.
    // $paths = array_reverse($folders, true);

    return $paths;
}

function phorum_admin_build_url($input_args, $return_raw = FALSE) {
    global $PHORUM;
    
    $url = $PHORUM["admin_http_path"];
 
    if($input_args == 'base') {
        return $return_raw ? $url : htmlspecialchars($url);
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

    return $return_raw ? $url : htmlspecialchars($url);
}
?>
