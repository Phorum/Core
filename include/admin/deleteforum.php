<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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

if ($_GET["confirm"] == "Yes")
{
    $res = phorum_api_forums_delete((int)$_GET['forum_id']);

    if ($res === NULL) {
        $msg = "No action was taken.";
    } else {
        $msg = $res['folder_flag']
             ? "The folder has been deleted. All forums and folders from " .
               "this folder have been moved to the folder's parent."
             : "The forum and all messages it contained have been deleted.";
    }
}
elseif ($_GET["confirm"] == "No")
{
    $msg = "No action was taken.";
}
else
{
    $forum = phorum_api_forums_get((int)$_GET['forum_id'],NULL,NULL,NULL,PHORUM_FLAG_INCLUDE_INACTIVE);

    if($forum["folder_flag"]){
        $msg="Are you sure you want to delete $forum[name]?  All forums and folders in this folder will be moved to this folder's parent.";
    } else {
        $msg="Are you sure you want to delete $forum[name]?  All messages in this forum will be deleted";
    }
    $frm_url = phorum_admin_build_url();
    $msg.="<form action=\"$frm_url\" method=\"get\"><input type=\"hidden\" name=\"phorum_admin_token\" value=\"{$PHORUM['admin_token']}\"><input type=\"hidden\" name=\"module\" value=\"$module\" /><input type=\"hidden\" name=\"forum_id\" value=\"{$forum['forum_id']}\" /><input type=\"hidden\" name=\"folder_flag\" value=\"$forum[folder_flag]\" /><input type=\"submit\" name=\"confirm\" value=\"Yes\" />&nbsp;<input type=\"submit\" name=\"confirm\" value=\"No\" /></form>";

}

?>
<div class="PhorumInfoMessage"><?php echo $msg; ?></div>
