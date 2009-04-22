<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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

if ($_GET["confirm"]=="Yes")
{
    if ($_GET["folder_flag"])
    {
        $cur_folder_id=(int)$_GET['forum_id'];
        // handling vroots
        $oldfolder = phorum_api_forums_get($cur_folder_id);

        if($oldfolder['parent_id'] > 0) { // is it a real folder?
            $parent_folder = phorum_api_forums_get($oldfolder['parent_id']);
            if($parent_folder['vroot'] > 0) { // is a vroot set?
                // then set the vroot to the vroot of the parent-folder
                phorum_admin_set_vroot($cur_folder_id,$parent_folder['vroot'],$cur_folder_id);
            }
        } else { // just default root ...
            phorum_admin_set_vroot($cur_folder_id,0,$cur_folder_id);
        }
        // done with vroots

        phorum_db_drop_folder($cur_folder_id);
        $msg="The folder was deleted.  All forums and folders in this folder have been moved to this folder's parent.";
    } else {
        phorum_db_drop_forum($_GET["forum_id"]);
        $msg="The forum was deleted.  All messages in that forum were deleted.";
    }

} elseif($_GET["confirm"]=="No"){

    $msg="No action was taken.";

} else {

    $forum = phorum_api_forums_get((int)$_GET['forum_id']);

    if($forum["folder_flag"]){
        $msg="Are you sure you want to delete $forum[name]?  All forums and folders in this folder will be moved to this folder's parent.";
    } else {
        $msg="Are you sure you want to delete $forum[name]?  All messages in this forum will be deleted";
    }
    $frm_url = phorum_admin_build_url('base');
    $msg.="<form action=\"$frm_url\" method=\"get\"><input type=\"hidden\" name=\"phorum_admin_token\" value=\"{$PHORUM['admin_token']}\"><input type=\"hidden\" name=\"module\" value=\"$module\" /><input type=\"hidden\" name=\"forum_id\" value=\"{$forum['forum_id']}\" /><input type=\"hidden\" name=\"folder_flag\" value=\"$forum[folder_flag]\" /><input type=\"submit\" name=\"confirm\" value=\"Yes\" />&nbsp;<input type=\"submit\" name=\"confirm\" value=\"No\" /></form>";
    
}

?>
<div class="PhorumInfoMessage"><?php echo $msg; ?></div>
