<?php

if(!defined("PHORUM_ADMIN")) return;

$error="";

if(count($_POST)){

    $_POST["number_to_show"] = (int)$_POST["number_to_show"];
    if((int)$_POST["number_to_show"]==0){
        // sanity check
        $_POST["number_to_show"] = 5;
    }
    
    if(isset($_POST['vroot_forum_id']) && is_array($_POST['vroot_forum_id'])) {
    	
    	foreach($_POST['vroot_forum_id'] as $vroot_id => $vroot_forum_id) {

    		$PHORUM["mod_announcements"]["vroot"][$vroot_id]=(int)$vroot_forum_id;
    		
    	}
    }

    $PHORUM["mod_announcements"]["forum_id"] = (int)$_POST["forum_id"];
    $PHORUM["mod_announcements"]["pages"] = $_POST["pages"];
    $PHORUM["mod_announcements"]["only_show_unread"] = $_POST["only_show_unread"];
    $PHORUM["mod_announcements"]["number_to_show"] = (int)$_POST["number_to_show"];
    $PHORUM["mod_announcements"]["days_to_show"] = (int)$_POST["days_to_show"];

    phorum_db_update_settings(array(
        "mod_announcements" => $PHORUM["mod_announcements"]
    ));
    phorum_admin_okmsg("Announcement settings updated");
}

include_once "./include/admin/PhorumInputForm.php";

$frm = new PhorumInputForm ("", "post", "Save");

$frm->hidden("module", "modsettings");

$frm->hidden("mod", "announcements");

$frm->addbreak("Announcement Settings");


$page_list = $frm->checkbox("pages[index]", 1, "Forum List (index.php)", $PHORUM["mod_announcements"]["pages"]["index"])."<br/>".
             $frm->checkbox("pages[list]", 1, "Message List (list.php)", $PHORUM["mod_announcements"]["pages"]["list"])."<br/>".
             $frm->checkbox("pages[read]", 1, "Read Message (read.php)", $PHORUM["mod_announcements"]["pages"]["read"]);

$frm->addrow("Announcements Appear On", $page_list);

$frm->addrow("Show Only Unread?", $frm->checkbox("only_show_unread", 1, "", $PHORUM["mod_announcements"]["only_show_unread"]));
$frm->addrow("Number To Show", $frm->text_box("number_to_show", $PHORUM["mod_announcements"]["number_to_show"], 10));
$frm->addrow("Maximum Days To Show", $frm->text_box("days_to_show", $PHORUM["mod_announcements"]["days_to_show"], 10) . " (0 = forever)");

$forum_list_global = phorum_get_forum_info(1,0);

$frm->addrow("Global Announcement Forum", $frm->select_tag("forum_id", $forum_list_global, $PHORUM["mod_announcements"]["forum_id"]));

//$vroot_folders = phorum_db_get_forums(0, NULL, '\'forum_id\'');
$vroot_folders = phorum_get_forum_info(3,-1);

if(count($vroot_folders)) {
	$frm->addbreak("Announcement Forums for Virtual Root Folders");
	
	foreach($vroot_folders as $vroot_folder_id => $vroot_path) {
		
		$forum_list_vroot = phorum_get_forum_info(1,$vroot_folder_id);
		
		$forum_list_vroot[0]='No Announcements for this Virtual Root';
		
		asort($forum_list_vroot);
		
		$frm->addrow("Ann. Forum for ".$vroot_path, $frm->select_tag("vroot_forum_id[$vroot_folder_id]", $forum_list_vroot, (isset($PHORUM["mod_announcements"]["vroot"][$vroot_folder_id])?$PHORUM["mod_announcements"]["vroot"][$vroot_folder_id]:0)));
		
	}
	
	
}

$frm->show();


?>
