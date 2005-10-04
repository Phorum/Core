<?php

    if(!defined("PHORUM_ADMIN")) return;

    include_once "./include/users.php";

    $error="";

    if(count($_POST)){

        // set the defaults and check values

        foreach($_POST as $field=>$value){

            switch($field){

                case "name":
                    if(empty($value)){
                        $error="Please fill in Title";
                    }
                    break;

                case "list_length_flat":
                    if(empty($value)){
                        $_POST[$field]=30;
                    } else {
                        $_POST[$field]=(int)$value;
                    }
                    break;

                case "list_length_threaded":
                    if(empty($value)){
                        $_POST[$field]=15;
                    } else {
                        $_POST[$field]=(int)$value;
                    }
                    break;

                case "max_attachments":
                    $_POST[$field]=(int)$value;
                    if(empty($_POST[$field])){
                        $_POST["allow_attachment_types"]="";
                        $_POST["max_attachment_size"]=0;
                    }
                    break;

                case "max_attachment_size":
                    $_POST[$field]=(int)$value;
                    break;

                case "display_fixed":
                    $_POST[$field]=(int)$value;
                    break;

                case "pub_perms":
                    $permission = 0;
                    foreach($_POST["pub_perms"] as $perm=>$check){
                        $permission = $permission | $perm;
                    }

                    $_POST["pub_perms"]=$permission;
                    break;

                case "reg_perms":
                    $permission = 0;
                    foreach($_POST["reg_perms"] as $perm=>$check){
                        $permission = $permission | $perm;
                    }

                    $_POST["reg_perms"]=$permission;
                    break;

            }

            if($error) break;


        }

        if(empty($error)){
            unset($_POST["module"]);

            if(empty($_POST["pub_perms"])) $_POST["pub_perms"]=0;
            if(empty($_POST["reg_perms"])) $_POST["reg_perms"]=0;

            /* print_var($_POST); */

            if(defined("PHORUM_EDIT_FORUM")){
                $res=phorum_db_update_forum($_POST);
            } else {
                $res=phorum_db_add_forum($_POST);
            }

            if($res){
                phorum_redirect_by_url($_SERVER['PHP_SELF']."?module=default&parent_id=$_POST[parent_id]");
                exit();
            } else {
                $error="Database error while adding/updating forum.";
            }
        }

        foreach($_POST as $key=>$value){
            $$key=$value;
        }
        /*
        $pub_perms=0;
        if(isset($_POST["pub_perms"])) foreach($_POST["pub_perms"] as $perm=>$check){
                $pub_perms = $pub_perms | $perm;
        }
        $reg_perms=0;
        if(isset($_POST["reg_perms"])) foreach($_POST["reg_perms"] as $perm=>$check){
                $reg_perms = $reg_perms | $perm;
        }
        */


    } elseif(defined("PHORUM_EDIT_FORUM")) {

        $forum_settings = phorum_db_get_forums($_REQUEST["forum_id"]);
        extract($forum_settings[$_REQUEST["forum_id"]]);

    }

    if($error){
        phorum_admin_error($error);
    }

    include_once "./include/admin/PhorumInputForm.php";

    $frm =& new PhorumInputForm ("", "post");

    if(defined("PHORUM_EDIT_FORUM")){
        $frm->hidden("module", "editforum");
        $frm->hidden("forum_id", $forum_id);
        $title="Edit Forum";
    } else {
        $frm->hidden("module", "newforum");
        $title="Add A Forum";
        $list_length_flat=20;
        $list_length_threaded=10;
        $read_length=10;
        $pub_perms = PHORUM_USER_ALLOW_READ | PHORUM_USER_ALLOW_REPLY | PHORUM_USER_ALLOW_NEW_TOPIC;
        $reg_perms = PHORUM_USER_ALLOW_READ | PHORUM_USER_ALLOW_REPLY | PHORUM_USER_ALLOW_NEW_TOPIC | PHORUM_USER_ALLOW_EDIT;
        $active=1;
        $float_to_top=1;
    }

    $frm->addbreak($title);

    $frm->addrow("Forum Title", $frm->text_box("name", htmlspecialchars($name), 30));

    $frm->addrow("Forum Description", $frm->textarea("description", htmlspecialchars($description), $cols=60, $rows=10, "style=\"width: 100%;\""), "top");

    $frm->addrow("Folder", $frm->select_tag("parent_id", phorum_get_folder_info(), $parent_id));

    $frm->addrow("Visible", $frm->select_tag("active", array("No", "Yes"), $active));

    $frm->addbreak("Moderation / Permissions");

    $row=$frm->addrow("Moderate Messages", $frm->select_tag("moderation", array(PHORUM_MODERATE_OFF=>"Disabled", PHORUM_MODERATE_ON=>"Enabled"), $moderation));

    $frm->addhelp($row, "Moderate Messages", "This setting determines whether messages are visible to users immediately after they are posted.  If enabled, all messages will remain hidden until approved by a moderator.");

    $frm->addrow("Email Messages To Moderators", $frm->select_tag("email_moderators", array(PHORUM_EMAIL_MODERATOR_OFF=>"Disabled", PHORUM_EMAIL_MODERATOR_ON=>"Enabled"), $email_moderators));

    $pub_perm_frm = $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_READ."]", 1, "Read", $pub_perms & PHORUM_USER_ALLOW_READ)."&nbsp;&nbsp;".
                    $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply", $pub_perms & PHORUM_USER_ALLOW_REPLY)."&nbsp;&nbsp;".
                    $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics", $pub_perms & PHORUM_USER_ALLOW_NEW_TOPIC)."<br />".
                    $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files", $pub_perms & PHORUM_USER_ALLOW_ATTACH);

    $frm->addrow("Public Users", $pub_perm_frm);

    $reg_perm_frm = $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_READ."]", 1, "Read", $reg_perms & PHORUM_USER_ALLOW_READ)."&nbsp;&nbsp;".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply", $reg_perms & PHORUM_USER_ALLOW_REPLY)."&nbsp;&nbsp;".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics", $reg_perms & PHORUM_USER_ALLOW_NEW_TOPIC)."<br />".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_EDIT."]", 1, "Edit&nbsp;Their&nbsp;Posts", $reg_perms & PHORUM_USER_ALLOW_EDIT)."&nbsp;&nbsp;".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files", $reg_perms & PHORUM_USER_ALLOW_ATTACH);

    $row=$frm->addrow("Registered Users", $reg_perm_frm);

    $frm->addhelp($row, "Registered Users", "These settings do not apply to users that are granted permissions directly via the user admin or via a group permissions.");

    $frm->addbreak("Display Settings");

    $frm->addrow("Fixed Display-Settings (user can't override them)", $frm->select_tag("display_fixed", array("No", "Yes"), $display_fixed));

    $frm->addrow("Template", $frm->select_tag("template", phorum_get_template_info(), $template));

    $frm->addrow("Language", $frm->select_tag("language", phorum_get_language_info(), $language));

    $frm->addrow("List Threads Expanded", $frm->select_tag("threaded_list", array("No", "Yes"), $threaded_list));
    $frm->addrow("Read Threads Expanded", $frm->select_tag("threaded_read", array("No", "Yes"), $threaded_read));

    $frm->addrow("Move Threads On Reply", $frm->select_tag("float_to_top", array("No", "Yes"), $float_to_top));

    $frm->addrow("Message List Length (Flat Mode)", $frm->text_box("list_length_flat", $list_length_flat, 10));
    $frm->addrow("Message List Length (Threaded Mode, Nr. of Threads)", $frm->text_box("list_length_threaded", $list_length_threaded, 10));

    $frm->addrow("Read Page Length", $frm->text_box("read_length", $read_length, 10));

    $frm->addrow("Display IP Addresses", $frm->select_tag("display_ip_address", array("No", "Yes"), $display_ip_address));

    $frm->addrow("Allow Email Notification", $frm->select_tag("allow_email_notify", array("No", "Yes"), $allow_email_notify));

    $frm->addrow("Check for Duplicates", $frm->select_tag("check_duplicate", array("No", "Yes"), $check_duplicate));

    $frm->addrow("Count views", $frm->select_tag("count_views", array(0 => "No", 1 => "Yes, show views added to subject", 2 => "Yes, show views as extra column"), $count_views));

    $frm->addbreak("Attachment Settings");

    $frm->addrow("Number Allowed (0 to disable)", $frm->text_box("max_attachments", $max_attachments, 10));

    $frm->addrow("Allowed Files (eg: gif;jpg;png)", $frm->text_box("allow_attachment_types", $allow_attachment_types, 10));

    $frm->addrow("Max File Size In kb", $frm->text_box("max_attachment_size", $max_attachment_size, 10));

    $frm->show();

?>
