<?php

    if(!defined("PHORUM_ADMIN")) return;

    $error="";

    if(count($_POST)){

        // set the defaults

        foreach($_POST as $field=>$value){

            switch($field){

                case "name":
                    if(empty($value)){
                        $error="Please fill in Title";
                    }
                    break;

                case "description":
                    if(empty($value)){
                        $error="Please fill in Description";
                    }
                    break;

            }

            if($error) break;

        }

        if(empty($error)){
            unset($_POST["module"]);

            if(defined("PHORUM_EDIT_FOLDER")){
                $res=phorum_db_update_forum($_POST);
            } else {
                $res=phorum_db_add_forum($_POST);
            }

            if($res){
                phorum_redirect_by_url($_SERVER['PHP_SELF']);
                exit();
            } else {
                $error="Database error while adding/updating folder.";
            }
        }

        foreach($_POST as $key=>$value){
            $$key=$value;
        }

    } elseif(defined("PHORUM_EDIT_FOLDER")) {

        $forum_settings = phorum_db_get_forums($_REQUEST["forum_id"]);
        extract($forum_settings[$_REQUEST["forum_id"]]);

    }

    if($error){
        phorum_admin_error($error);
    }

    include_once "./include/admin/PhorumInputForm.php";

    $frm =& new PhorumInputForm ("", "post");

    $folder_data=phorum_get_folder_info();

    if(defined("PHORUM_EDIT_FOLDER")){
        $frm->hidden("module", "editfolder");
        $frm->hidden("forum_id", $forum_id);
        $title="Edit Folder";

        $this_folder=$folder_data[$_REQUEST["forum_id"]];

        foreach($folder_data as $folder_id=> $folder){

            // remove children from the list
            if($folder_id!=$_REQUEST["forum_id"] && substr($folder, 0, strlen($this_folder))!=$this_folder){
                $folders[$folder_id]=$folder;
            }
        }

    } else {
        $frm->hidden("module", "newfolder");
        $title="Add A Folder";

        $folders=$folder_data;
        $active=1; // default to visible
    }



    $frm->hidden("folder_flag", "1");

    $frm->addbreak($title);

    $frm->addrow("Folder Title", $frm->text_box("name", $name, 30));

    $frm->addrow("Folder Description", $frm->textarea("description", $description, $cols=60, $rows=10, "style=\"width: 100%;\""), "top");

    $frm->addrow("Folder", $frm->select_tag("parent_id", $folders, $parent_id));

    $frm->addrow("Visible", $frm->select_tag("active", array("No", "Yes"), $active));

    $frm->addbreak("Display Settings");

    $frm->addrow("Template", $frm->select_tag("template", phorum_get_template_info(), $template));

    $frm->addrow("Language", $frm->select_tag("language", phorum_get_language_info(), $language));

    $frm->show();

?>