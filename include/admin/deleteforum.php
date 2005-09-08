<?php

    if(!defined("PHORUM_ADMIN")) return;

    if($_GET["confirm"]=="Yes"){

        if($_GET["folder_flag"]){
            phorum_db_drop_folder($_GET["forum_id"]);
            $msg="The folder was deleted.  All forums and folders in this folder have been moved to this folder's parent.";
        } else {
            phorum_db_drop_forum($_GET["forum_id"]);
            $msg="The forum was deleted.  All messages in that forum were deleted.";
        }

    } elseif($_GET["confirm"]=="No"){

        $msg="No action was taken.";

    } else {

	$forums = phorum_db_get_forums((int)$_GET["forum_id"]);
        $forum=array_shift($forums);

        if($forum["folder_flag"]){
            $msg="Are you sure you want to delete $forum[name]?  All forums and folders in this folder will be moved to this folder's parent.";
        } else {
            $msg="Are you sure you want to delete $forum[name]?  All messages in this forum will be deleted";
        }
        $msg.="<form action=\"$_SERVER[PHP_SELF]\" method=\"get\"><input type=\"hidden\" name=\"module\" value=\"$module\" /><input type=\"hidden\" name=\"forum_id\" value=\"$_GET[forum_id]\" /><input type=\"hidden\" name=\"folder_flag\" value=\"$forum[folder_flag]\" /><input type=\"submit\" name=\"confirm\" value=\"Yes\" />&nbsp;<input type=\"submit\" name=\"confirm\" value=\"No\" /></form>";

    }

?>
<div class="PhorumInfoMessage"><?php echo $msg; ?></div>
