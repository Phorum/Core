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

// Show message if set in the URL.
if (isset($_GET['okmsg'])) {
    phorum_admin_okmsg(htmlspecialchars($_GET['okmsg']));
}

$folder_id = (int)((isset($_GET["parent_id"])) ? $_GET["parent_id"] : 0);
$parent_parent_id = (int)((isset($_GET["pparent"])) ? $_GET["pparent"] : 0);

// Load the info for the current folder.
$folder = phorum_api_forums_get($folder_id);

// Load the list of forums and folders that are in the current folder.
$forums = phorum_api_forums_by_folder_id(
    $folder_id, PHORUM_FLAG_INCLUDE_INACTIVE
);

// Change the display order of the items in the list.
if (isset($_GET['display_up']) || isset($_GET['display_down']))
{
    if (isset($_GET['display_up'])) {
        $forum_id = (int)$_GET['display_up'];
        $movement = 'up';
    } else {
        $forum_id = (int)$_GET['display_down'];
        $movement = 'down';
    }

    phorum_api_forums_change_order($folder_id, $forum_id, $movement, 1);

    // Get a fresh forum list with updated order.
    $forums = phorum_api_forums_by_folder_id($folder_id);
}

$rows = '';
foreach($forums as $forum_id => $forum)
{
    if ($forum["folder_flag"])
    {
        $visit_url = phorum_api_url(PHORUM_INDEX_URL, $forum['forum_id']);
        $type="folder";
        $folder_edit_url = phorum_admin_build_url(array('module=editfolder',"forum_id=$forum_id"));
        $folder_delete_url = phorum_admin_build_url(array('module=deletefolder',"forum_id=$forum_id"));
        $actions="<a href=\"$folder_edit_url\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$folder_delete_url\">Delete</a>&nbsp;&#149;&nbsp;<a href=\"$visit_url\">Visit</a>";
        $mainurl=phorum_admin_build_url(array('module=default',"parent_id=$forum_id"));
    } else {
        $visit_url = phorum_api_url(PHORUM_LIST_URL, $forum['forum_id']);
        $type="forum";
        $forum_edit_url = phorum_admin_build_url(array('module=editforum',"forum_id=$forum_id"));
        $forum_delete_url = phorum_admin_build_url(array('module=deleteforum',"forum_id=$forum_id"));
        $actions="<a href=\"$forum_edit_url\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$forum_delete_url\">Delete</a>&nbsp;&#149;&nbsp;<a href=\"$visit_url\">Visit</a>";
        $mainurl=NULL;
    }

    $rows.="<tr><th align=\"left\" valign=\"top\" class=\"PhorumAdminTableRow forum-title\">";
    if ($mainurl) $rows .= "<a href=\"$mainurl\">";
    $rows .= "<span class=\"icon-$type\"></span>";
    $rows .= '<strong>' . ($forum['vroot'] == $forum['forum_id'] ? 'Virtual root: ' : '') . $forum['name'] . '</strong>';
    if ($mainurl) $rows .= "</a>";
    $mv_up_url = phorum_admin_build_url(array('module=default',"display_up=$forum_id","parent_id=$folder_id"));
    $mv_down_url = phorum_admin_build_url(array('module=default',"display_down=$forum_id","parent_id=$folder_id"));
    $rows .= "<p class=\"forum-description\">$forum[description]</p></th><td class=\"PhorumAdminTableRow\"><a href=\"$mv_up_url\"><img border=\"0\" src=\"{$PHORUM["http_path"]}/images/arrow_up.png\" alt=\"Up\" title=\"Up\"/></a>&nbsp;<a href=\"$mv_down_url\"><img border=\"0\" src=\"{$PHORUM["http_path"]}/images/arrow_down.png\" alt=\"Down\" title=\"Down\"/></a></td><td class=\"PhorumAdminTableRow\">$actions</td></tr>\n";
}

if (empty($rows)) {
    $rows="<tr><td colspan=\"3\" class=\"PhorumAdminTableRow\" style=\"padding:15px\">There are no forums or folders in this folder.</td></tr>\n";
}

if ($folder_id > 0)
{
    $elts = array();
    foreach ($folder['forum_path'] as $id => $name)
    {
        if (empty($elts)) {
            if (empty($folder['vroot'])) {
                $name = 'Root folder';
            } else {
                $elturl = phorum_admin_build_url(array('module=default','parent_id='.$folder['parent_id']));
                $elts[] = "<a href=\"$elturl\">Back to parent folder</a>";
                $name = 'Virtual Root "'.$name.'"';
            }
        }
        if ($folder_id == $id) {
            $elts[] = '<strong>' . $name . '</strong>';
        } else {
            $elturl = phorum_admin_build_url(array('module=default',"parent_id=$id"));
            $elts[] = "<a href=\"$elturl\">$name</a>";
        }
    }

    $path = implode(' / ', $elts);
}
else {
    $path='<strong>Root folder</strong>';
}

?>

<div class="PhorumAdminTitle">
  Forum and folder settings
</div>

<div class="PhorumAdminBreadcrumbs">
  <?php
  if (empty($folder['forum_id'])) {
      print "<span class=\"icon-folder\"></span>";
  } else {
      $upurl = phorum_admin_build_url(array('module=default',"parent_id=$parent_parent_id"));
      print "<a href=\"$upurl\"><span class=\"icon-folder-up\"></span></a>";
  }
  echo "$path";
  ?>
</div>

<table border="0" cellspacing="2" cellpadding="3" width="100%">
<?php echo $rows; ?>
</table>
