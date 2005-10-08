<?php

    if(!defined("PHORUM_ADMIN")) return;

    include_once "./include/users.php";
    include_once "./include/format_functions.php";

    $error="";

    if(count($_POST)){

        // set the defaults and check values

        foreach($_POST as $field=>$value){

            switch($field){

                case "name":
                    if(empty($value) && $_POST["module"]!="forum_defaults"){
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
                        $_POST["max_totalattachment_size"]=0;
                    }
                    break;

                case "max_attachment_size":
                case "max_totalattachment_size":
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

                case "inherit_id":
                    if( $_POST["inherit_id"] ) {
                        $forum_check_inherit =phorum_db_get_forums(intval($_POST["inherit_id"]));
                        if( $forum_check_inherit[$_POST["inherit_id"]]["inherit_id"] || ($_POST["inherit_id"]==$_POST["forum_id"]) ) {
                            $error="Settings can't be inherited by this forum, because this forum already inherits settings from another forum.";
                        }
                    }
                    break;
            }

            if($error) break;


        }

        if(empty($error)){
            unset($_POST["module"]);

            if(empty($_POST["pub_perms"])) $_POST["pub_perms"]=0;
            if(empty($_POST["reg_perms"])) $_POST["reg_perms"]=0;

            // handling vroots
            if($_POST['parent_id'] > 0) {
                $parent_folder=phorum_db_get_forums($_POST['parent_id']);
                if($parent_folder[$_POST['parent_id']]['vroot'] > 0) {
                    $_POST['vroot']=$parent_folder[$_POST['parent_id']]['vroot'];
                }
            } else {
                $_POST['vroot']=0;
            }

            if(defined("PHORUM_EDIT_FORUM") || defined("PHORUM_DEFAULT_OPTIONS")){

                if(!defined("PHORUM_DEFAULT_OPTIONS")){
                    if( $_POST["inherit_id"]!="NULL" ) {
                        // Load inherit forum settings
                        if(defined("PHORUM_DEFAULT_OPTIONS")){
                            $forum_settings_inherit[0] = $PHORUM["default_forum_options"];
                        } else {
                            $forum_settings_inherit = phorum_db_get_forums($_POST["inherit_id"]);
                        }

                        if( $forum_settings_inherit[$_POST["inherit_id"]]["forum_id"] ) {
                            // slave settings

                            $forum_settings_inherit=$forum_settings_inherit[$_POST["inherit_id"]];
                            $forum_settings_inherit["forum_id"] =$_POST["forum_id"];
                            $forum_settings_inherit["name"] =$_POST["name"];
                            $forum_settings_inherit["description"] =$_POST["description"];
                            $forum_settings_inherit["active"] =$_POST["active"];
                            $forum_settings_inherit["parent_id"] =$_POST["parent_id"];
                            $forum_settings_inherit["inherit_id"] =$_POST["inherit_id"];
                            // don't inherit this settings
                            unset($forum_settings_inherit["message_count"]);
                            unset($forum_settings_inherit["thread_count"]);
                            unset($forum_settings_inherit["last_post_time"]);
                            // we don't need to save the master forum
                            unset($forum_settings_inherit[$inherit_id]);
                            $_POST =$forum_settings_inherit;
                        } else {
                            $_POST["inherit_id"]="NULL";
                            unset($_POST["pub_perms"]);
                            unset($_POST["reg_perms"]);
                        }
                    }
                }

                $forum_settings=$_POST;

                if(defined("PHORUM_DEFAULT_OPTIONS")){
                    $res=phorum_db_update_settings(array("default_forum_options" => $forum_settings));
                } else {
                    $res=phorum_db_update_forum($forum_settings);
                }
                $forum_inherit_settings =phorum_db_get_forums(false,false,false,intval($_POST["forum_id"]));
                foreach($forum_inherit_settings as $inherit_setting) {
                    $forum_settings["forum_id"] =$inherit_setting["forum_id"];
                    // We don't need to inherit this settings
                    unset($forum_settings["name"]);
                    unset($forum_settings["description"]);
                    unset($forum_settings["active"]);
                    unset($forum_settings["parent_id"]);
                    unset($forum_settings["inherit_id"]);
                    unset($forum_settings["message_count"]);
                    unset($forum_settings["thread_count"]);
                    unset($forum_settings["last_post_time"]);
                    $res_inherit =phorum_db_update_forum($forum_settings);
                }
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
        $pub_perms=0;
        if(isset($_POST["pub_perms"])) foreach($_POST["pub_perms"] as $perm=>$check){
                $pub_perms = $pub_perms | $perm;
        }
        $reg_perms=0;
        if(isset($_POST["reg_perms"])) foreach($_POST["reg_perms"] as $perm=>$check){
                $reg_perms = $reg_perms | $perm;
        }


    } elseif(defined("PHORUM_EDIT_FORUM")) {

        $forum_settings = phorum_db_get_forums($_REQUEST["forum_id"]);
        extract($forum_settings[$_REQUEST["forum_id"]]);

    } elseif(defined("PHORUM_DEFAULT_OPTIONS")) {

        extract($PHORUM["default_forum_options"]);

    }

    if($error){
        phorum_admin_error($error);
    }

    include_once "./include/admin/PhorumInputForm.php";

    $frm =& new PhorumInputForm ("", "post");

    if(defined("PHORUM_DEFAULT_OPTIONS")){
        $frm->hidden("module", "forum_defaults");
        $frm->hidden("forum_id", 0);
        $title="Default Forum Settings";
    } elseif(defined("PHORUM_EDIT_FORUM")){
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
        $template='default';
    }

    $frm->addbreak($title);

    if(!defined("PHORUM_DEFAULT_OPTIONS")){

        $frm->addrow("Forum Title", $frm->text_box("name", htmlspecialchars($name), 30));

        $frm->addrow("Forum Description", $frm->textarea("description", htmlspecialchars($description), $cols=60, $rows=10, "style=\"width: 100%;\""), "top");

        $folder_list=phorum_get_folder_info();
        $frm->addrow("Folder", $frm->select_tag("parent_id", $folder_list, $parent_id));
        if($vroot > 0) {
            $frm->addrow("This folder is in the Virtual Root of:",$folder_list[$vroot]);
        }


        $frm->addrow("Visible", $frm->select_tag("active", array("No", "Yes"), $active));

        // Edit + inherit_id exists
        if(defined("PHORUM_EDIT_FORUM") && $inherit_id ) {
            $forum_settings_inherit = phorum_db_get_forums($inherit_id);
            // inherit_forum not exists
            if( !$forum_settings_inherit[$inherit_id] ) {
                $inherit_id ="0";
                unset($forum_settings_inherit);
            } else {
                $disabled_form_input="disabled=\"disabled\"";
            }
        } else {
            unset($disabled_form_input);
        }

        $frm->addbreak("Inherit Forum Settings");

        $forum_list=phorum_get_forum_info(true);

        $forum_list["0"] ="Use Default Forum Settings";
        $forum_list["NULL"] ="None - I want to customize this forum's settings";

        // Remove this Forum
        unset($forum_list[$forum_id]);

        // Check for Slaves
        if( intval($forum_id) ) {

            $forum_inherit_settings=phorum_db_get_forums(false,false,false,intval($forum_id));
            if( count($forum_inherit_settings)>0 ) {
                $disabled_form_input_inherit="disabled=\"disabled\"";
            }
        }

        // set to NULL if inherit is disabled
        if($inherit_id=="" && $inherit_id!==0) $inherit_id="NULL";

        $row=$frm->addrow("Inherit Settings from Forum", $frm->select_tag("inherit_id", $forum_list, $inherit_id, $disabled_form_input_inherit));

        // Set Settings from inherit forum
        if( $forum_settings_inherit ) {
            $forum_settings =$forum_settings_inherit;
            extract($forum_settings[$inherit_id]);
        }
    }

    $frm->addbreak("Moderation / Permissions");

    $row=$frm->addrow("Moderate Messages", $frm->select_tag("moderation", array(PHORUM_MODERATE_OFF=>"Disabled", PHORUM_MODERATE_ON=>"Enabled"), $moderation, $disabled_form_input));

    $frm->addhelp($row, "Moderate Messages", "This setting determines whether messages are visible to users immediately after they are posted.  If enabled, all messages will remain hidden until approved by a moderator.");

    $frm->addrow("Email Messages To Moderators", $frm->select_tag("email_moderators", array(PHORUM_EMAIL_MODERATOR_OFF=>"Disabled", PHORUM_EMAIL_MODERATOR_ON=>"Enabled"), $email_moderators, $disabled_form_input));

    $pub_perm_frm = $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_READ."]", 1, "Read", $pub_perms & PHORUM_USER_ALLOW_READ, $disabled_form_input)."&nbsp;&nbsp;".
                    $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply", $pub_perms & PHORUM_USER_ALLOW_REPLY, $disabled_form_input)."&nbsp;&nbsp;".
                    $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics", $pub_perms & PHORUM_USER_ALLOW_NEW_TOPIC, $disabled_form_input)."<br />".
                    $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files", $pub_perms & PHORUM_USER_ALLOW_ATTACH, $disabled_form_input);

    $frm->addrow("Public Users", $pub_perm_frm);

    $reg_perm_frm = $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_READ."]", 1, "Read", $reg_perms & PHORUM_USER_ALLOW_READ, $disabled_form_input)."&nbsp;&nbsp;".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply", $reg_perms & PHORUM_USER_ALLOW_REPLY, $disabled_form_input)."&nbsp;&nbsp;".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics", $reg_perms & PHORUM_USER_ALLOW_NEW_TOPIC, $disabled_form_input)."<br />".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_EDIT."]", 1, "Edit&nbsp;Their&nbsp;Posts", $reg_perms & PHORUM_USER_ALLOW_EDIT, $disabled_form_input)."&nbsp;&nbsp;".
                    $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files", $reg_perms & PHORUM_USER_ALLOW_ATTACH, $disabled_form_input);

    $row=$frm->addrow("Registered Users", $reg_perm_frm);

    $frm->addhelp($row, "Registered Users", "These settings do not apply to users that are granted permissions directly via the user admin or via a group permissions.");

    $frm->addbreak("Display Settings");

    $frm->addrow("Fixed Display-Settings (user can't override them)", $frm->select_tag("display_fixed", array("No", "Yes"), $display_fixed, $disabled_form_input));

    $frm->addrow("Template", $frm->select_tag("template", phorum_get_template_info(), $template, $disabled_form_input));

    $frm->addrow("Language", $frm->select_tag("language", phorum_get_language_info(), $language, $disabled_form_input));

    $frm->addrow("List Threads Expanded", $frm->select_tag("threaded_list", array("No", "Yes"), $threaded_list, $disabled_form_input));
    $frm->addrow("Read Threads Expanded", $frm->select_tag("threaded_read", array("No", "Yes"), $threaded_read, $disabled_form_input));
    $frm->addrow("Reverse Threading", $frm->select_tag("reverse_threading", array("No", "Yes"), $reverse_threading, $disabled_form_input));

    $frm->addrow("Move Threads On Reply", $frm->select_tag("float_to_top", array("No", "Yes"), $float_to_top, $disabled_form_input));

    $frm->addrow("Message List Length (Flat Mode)", $frm->text_box("list_length_flat", $list_length_flat, 10, false, false, $disabled_form_input));
    $frm->addrow("Message List Length (Threaded Mode, Nr. of Threads)", $frm->text_box("list_length_threaded", $list_length_threaded, 10, false, false, $disabled_form_input));

    $frm->addrow("Read Page Length", $frm->text_box("read_length", $read_length, 10, false, false, $disabled_form_input, $disabled_form_input));

    $frm->addrow("Display IP Addresses <small>(note: admins always see it)</small>", $frm->select_tag("display_ip_address", array("No", "Yes"), $display_ip_address, $disabled_form_input));

    $frm->addrow("Allow Email Notification", $frm->select_tag("allow_email_notify", array("No", "Yes"), $allow_email_notify, $disabled_form_input));

    $frm->addrow("Check for Duplicates", $frm->select_tag("check_duplicate", array("No", "Yes"), $check_duplicate, $disabled_form_input));

    $frm->addrow("Count views", $frm->select_tag("count_views", array(0 => "No", 1 => "Yes, show views added to subject", 2 => "Yes, show views as extra column"), $count_views, $disabled_form_input));

    $frm->addbreak("Attachment Settings");

    $frm->addrow("Number Allowed (0 to disable)", $frm->text_box("max_attachments", $max_attachments, 10, false, false, $disabled_form_input));

    $frm->addrow("Allowed Files (eg: gif;jpg;png, empty for any)", $frm->text_box("allow_attachment_types", $allow_attachment_types, 10, false, false, $disabled_form_input));

    $php_limit = ini_get('upload_max_filesize')*1024;
    $db_limit = phorum_db_maxpacketsize()/1024*.6;
    $max_size =  phorum_filesize(min($php_limit, $db_limit)*1024);

    $row=$frm->addrow("Max File Size In kB ($max_size maximum)", $frm->text_box("max_attachment_size", $max_attachment_size, 10, false, false, $disabled_form_input));
    $frm->addhelp($row, "Max File Size", "This is the maximum that one uploaded file can be.  If you see a maximum here, that is the maximum imposed by either your PHP installation, database server or both.  Leaving this field as 0 will use this maximum.");

    $frm->addrow("Max cumulative File Size In kB (0 for unlimited)", $frm->text_box("max_totalattachment_size", $max_totalattachment_size, 10, false, false, $disabled_form_input));

    $frm->show();

?>
