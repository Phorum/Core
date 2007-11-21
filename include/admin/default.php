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

if (!defined("PHORUM_ADMIN")) return;

include_once('./include/api/forums.php');

$folder_id = (int)((isset($_GET["parent_id"])) ? $_GET["parent_id"] : 0);
$parent_parent_id = (int)((isset($_GET["pparent"])) ? $_GET["pparent"] : 0);

$forums = phorum_api_admin_forums_by_folder($folder_id);

// change the display-order
if (isset($_GET['display_up']) || isset($_GET['display_down']))
{
    if (isset($_GET['display_up'])) {
        $forum_id = (int)$_GET['display_up'];
        $movement = 'up';
    } else {
        $forum_id = (int)$_GET['display_down'];
        $movement = 'down';
    }

    phorum_api_admin_forums_change_order($folder_id, $forum_id, $movement, 1);

    // Get a fresh forum list with updated order.
    $forums = phorum_api_admin_forums_by_folder($folder_id);
}

$rows = '';
foreach($forums as $forum_id => $forum)
{
    if($forum["folder_flag"]){
        $type="Folder";
        $actions="<a href=\"{$PHORUM["admin_http_path"]}?module=default&parent_id=$forum_id&pparent=$folder_id\">Browse</a>&nbsp;&#149;&nbsp;<a href=\"{$PHORUM["admin_http_path"]}?module=editfolder&forum_id=$forum_id\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"{$PHORUM["admin_http_path"]}?module=deletefolder&forum_id=$forum_id\">Delete</a>";
        $editurl="{$PHORUM["admin_http_path"]}?module=editfolder&forum_id=$forum_id";
    } else {
        $type="Forum";
        $actions="<a href=\"{$PHORUM["admin_http_path"]}?module=editforum&forum_id=$forum_id\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"{$PHORUM["admin_http_path"]}?module=deleteforum&forum_id=$forum_id\">Delete</a>";
        $editurl="{$PHORUM["admin_http_path"]}?module=editforum&forum_id=$forum_id";
    }

    $rows.="<tr><td class=\"PhorumAdminTableRow\"><a href=\"$editurl\">$forum[name]</a><br />$forum[description]</td><td class=\"PhorumAdminTableRow\">$type</td><td class=\"PhorumAdminTableRow\"><a href=\"{$PHORUM["admin_http_path"]}?module=default&display_up=$forum_id&parent_id=$folder_id\">Up</a>&nbsp;&#149;&nbsp;<a href=\"{$PHORUM["admin_http_path"]}?module=default&display_down=$forum_id&parent_id=$folder_id\">Down</a></td><td class=\"PhorumAdminTableRow\">$actions</td></tr>\n";
}

if(empty($rows)){
    $rows="<tr><td colspan=\"4\" class=\"PhorumAdminTableRow\">There are no forums or folders in this folder.</td></tr>\n";
}

if($folder_id>0){
    $folder_data=phorum_get_folder_info();

    $path=$folder_data[$folder_id];
} else {
    $path="Choose a forum or folder.";
}



?>

<div class="PhorumAdminTitle"><?php echo "$path &nbsp;&nbsp; <a href=\"{$PHORUM["admin_http_path"]}?module=default&parent_id={$parent_parent_id}\"><span class=\"PhorumAdminTitle\">Go Up</span></a>";?></div>
<table border="0" cellspacing="2" cellpadding="3" width="100%">
<tr>
    <td class="PhorumAdminTableHead">Name</td>
    <td class="PhorumAdminTableHead">Type</td>
    <td class="PhorumAdminTableHead">Move</td>
    <td class="PhorumAdminTableHead">Actions</td>
</tr>
<?php echo $rows; ?>
</table>
