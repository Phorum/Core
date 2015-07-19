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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM_ADMIN")) return;

include('./include/format_functions.php');

$user_status_map = array(
    'any'                     => 'Any user status',
    'pending'                 => 'Any pending status',
    PHORUM_USER_PENDING_BOTH  => 'Pending user + moderator confirmation',
    PHORUM_USER_PENDING_EMAIL => 'Pending user confirmation',
    PHORUM_USER_PENDING_MOD   => 'Pending moderator confirmation',
    PHORUM_USER_INACTIVE      => 'Deactivated',
    PHORUM_USER_ACTIVE        => 'Active',
);

// A utility list of field names that are used for searching.
$user_search_fields = array(
    'search_username',
    'username_search_loc',
    'search_display_name',
    'display_name_search_loc',
    'search_email',
    'email_search_loc',
    'search_signature',
    'signature_search_loc',
    'search_type',
    'search_status',
    'posts',
    'posts_op',
    'lastactive',
    'lastactive_op',
    'registered',
    'registered_op',
    'member_of_group',
    'profile_field',
    'search_profile_field',
    'profile_field_search_loc',
    'forum_permissions',
    'forum_permissions_forums'
);


$error="";

// if the page and pagelength have been set from before, use them.
$page_args = "";
$page_args_array = array();
if (isset($_GET["page"])) {
    $page_args .= "&page=".(int)$_GET["page"];
    $page_args_array[] = "page=".(int)$_GET["page"];
}
if (isset($_GET["pagelength"])) {
    $page_args .= "&pagelength=".(int)$_GET["pagelength"];
    $page_args_array[] = "pagelength=".(int)$_GET["pagelength"];
}
if (isset($_GET["sort"])) {
    $page_args .= "&sort=".$_GET["sort"];
    $page_args_array[] = "sort=".$_GET["sort"];
}
if (isset($_GET["sort_dir"])) {
    $get_sort_dir = empty($_GET["sort_dir"]) ? "" : "-";
    $page_args .= "&sort_dir=".$get_sort_dir;
    $page_args_array[] = "sort_dir=".$get_sort_dir;
}
    
// The referrer to use for the user edit page, to jump back to the user list.
if (isset($_POST['referrer'])) {
    $referrer = $_POST['referrer'];
    unset($_POST['referrer']);
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = $_SERVER['HTTP_REFERER'] . $page_args;
} else {
    $input_args = array('module=users');
    if(count($page_args_array)) $input_args = array_merge($input_args,$page_args_array);
    $referrer = phorum_admin_build_url($input_args, TRUE);
}

if(count($_POST))
{
    if (isset($_POST['action']) && $_POST['action'] == "deleteUsers")
    {
        $count=count($_POST['deleteIds']);
        if($count > 0) {
            foreach($_POST['deleteIds'] as $id => $deluid) {
                phorum_api_user_delete($deluid);
            }
            phorum_admin_okmsg("$count User(s) deleted.");
        }

    //process new user data
    } elseif (isset($_POST["addUser"])) {

        $user_data = $_POST;

        //check for pre-existing username
        if (!empty($_POST["username"])) {
            $existing_user = phorum_api_user_search("username", $_POST["username"]);
            if (!empty($existing_user))
                $error = 'The user name "'.htmlspecialchars($_POST['username']).'" is already in use!';
        } else {
            $error = "You must provide a user name!";
        }

        //check for a valid email
        if (!empty($_POST["email"])) {
            include('./include/email_functions.php');
            $valid_email = phorum_valid_email($_POST["email"]);
            if ($valid_email !== true)
                $error = 'The email "'.htmlspecialchars($_POST[email]).'" is not valid!';
        } else {
            $error = "You must provide an e-mail!";
        }
        

        //check for password and password confirmation
        if(isset($_POST['password1']) && !empty($_POST['password1']) && !empty($_POST['password2']) && $_POST['password1'] != $_POST['password2']) {
            $error="Passwords don't match!";
        } elseif(!empty($_POST['password1']) && !empty($_POST['password2'])) {
            $user_data['password']=$_POST['password1'];
            $user_data['password_temp']=$_POST['password1'];
        } else {
            $error="You must assign a password!";
        }

        unset($user_data["password1"]);
        unset($user_data["password2"]);
        unset($user_data["module"]);
        unset($user_data["addUser"]);
        unset($user_data["phorum_admin_token"]);

        if(empty($error)){
            $user_data["user_id"] = NULL;
            $user_data["active"] = PHORUM_USER_ACTIVE;
            phorum_api_user_save($user_data);
            phorum_admin_okmsg("User Added");
        } else {
            $addUser_error = 1;
        }


    } else {

        $user_data=$_POST;

        switch( $_POST["section"] ) {


            case "forums":

                if($_POST["new_forum"]){
                    if(!is_array($_POST["new_forum_permissions"])){
                        $permission=0;
                    } else {
                        $permission = 0;
                        foreach($_POST["new_forum_permissions"] as $perm=>$check){
                           $permission = $permission | $perm;
                        }
                    }

                    $user_data["forum_permissions"][$_POST["new_forum"]]=$permission;
                    unset($user_data["new_forum"]);
                }

                if(isset($_POST["delforum"])){
                    foreach($_POST["delforum"] as $fid=>$val){
                        unset($user_data["forum_permissions"][$fid]);
                        unset($_POST["forums"][$fid]);
                    }
                    unset($user_data["delforum"]);
                }

                if(isset($_POST["forums"])){
                    foreach($_POST["forums"] as $forum_id){
                        $permission=0;

                        if(isset($user_data["forum_permissions"][$forum_id])){
                            foreach($user_data["forum_permissions"][$forum_id] as $perm=>$check){
                                $permission = $permission | $perm;
                            }
                        }

                        $user_data["forum_permissions"][$forum_id]=$permission;
                    }
                    unset($user_data["forums"]);
                }

                if(empty($user_data["forum_permissions"])) $user_data["forum_permissions"]=array();

                unset($user_data["delforum"]);
                unset($user_data["new_forum"]);
                unset($user_data["new_forum_permissions"]);

                break;

            case "groups":
                $groupdata = array();

                if($_POST["new_group"]){
                    // set the new group permission to approved
                    $groupdata[$_POST["new_group"]] = PHORUM_USER_GROUP_APPROVED;
                    unset($user_data['new_group']);
                }

                if(isset($_POST["group_perm"])){
                    foreach($_POST["group_perm"] as $group_id=>$perm){
                        // as long as we aren't removing them from the group, accept other values
                        if ($perm != "remove"){
                            $groupdata[$group_id] = $perm;
                        }
                    }
                    unset($user_data['group_perm']);
                }

                phorum_api_user_save_groups($_POST["user_id"], $groupdata);

                unset($user_data["new_group"]);

                break;
        }

        if(isset($_POST['password1']) && !empty($_POST['password1']) && !empty($_POST['password2']) && $_POST['password1'] != $_POST['password2']) {
            $error="Passwords don't match!";
        } elseif(!empty($_POST['password1']) && !empty($_POST['password2'])) {
            $user_data['password']=$_POST['password1'];
            $user_data['password_temp']=$_POST['password1'];
        }

        // clean up
        unset($user_data["module"]);
        unset($user_data["section"]);
        unset($user_data["password1"]);
        unset($user_data["password2"]);
        unset($user_data["submit"]);
        unset($user_data["phorum_admin_token"]);

        if (empty($error)){
            $user_data = phorum_hook("admin_users_form_save", $user_data);
            if (isset($user_data["error"])) {
                $error = $user_data["error"];
                unset($user_data["error"]);
            }
        }
        if(empty($error)){
            phorum_api_user_save($user_data);
            phorum_admin_okmsg("User Saved");
        }
    }

}

if ($error) {
    phorum_admin_error($error);
}

include_once "./include/admin/PhorumInputForm.php";
include_once "./include/profile_functions.php";

if(!defined("PHORUM_ORIGINAL_USER_CODE") || PHORUM_ORIGINAL_USER_CODE!==true){
    echo "Phorum User Admin only works with the Phorum User System.";
    return;
}

if (!isset($_GET["edit"]) && !isset($_GET["add"]) && !isset($addUser_error) && !isset($_POST['section']))
{
    $users_url = phorum_admin_build_url(array('module=users'));
    $users_add_url = phorum_admin_build_url(array('module=users','add=1'));
    print "<a href=\"$users_url\">" .
          "Show all users</a> | <a href=\"$users_add_url\">Add User</a><br/>";

    if (empty($_REQUEST["user_id"]))
    {
        $frm = new PhorumInputForm ("", "get", "Search");

        $frm->addbreak("User Search");

        $frm->hidden("module", "users");
        
        $field_search_loc_array = array("any" => "Anywhere","start" => "Start of Field","end" => "End of Field");
        
        $frm->addrow("Username contains", $frm->text_box("search_username", $_REQUEST["search_username"], 30) ."&nbsp;". $frm->select_tag("username_search_loc", $field_search_loc_array, $_REQUEST["username_search_loc"]));
        if ($PHORUM['display_name_source'] != 'username') {
            $frm->addrow("Display name contains", $frm->text_box("search_display_name", $_REQUEST["search_display_name"], 30) ."&nbsp;". $frm->select_tag("display_name_search_loc", $field_search_loc_array, $_REQUEST["display_name_search_loc"]));
        }
        $frm->addrow("Email contains", $frm->text_box("search_email", $_REQUEST["search_email"], 30) ."&nbsp;". $frm->select_tag("email_search_loc", $field_search_loc_array, $_REQUEST["email_search_loc"]));
        $frm->addrow("Signature contains", $frm->text_box("search_signature", $_REQUEST["search_signature"], 30) ."&nbsp;". $frm->select_tag("signature_search_loc", $field_search_loc_array, $_REQUEST["signature_search_loc"]));
        $frm->addrow("User status and type", $frm->select_tag("search_status", $user_status_map, $_REQUEST['search_status']) . " " . $frm->select_tag("search_type", array('any' => 'Any type of user', 'user' => 'Regular users', 'admin' => 'Administrators'), $_REQUEST['search_type']));
        $frm->addrow("Number of forum posts ",
            $frm->text_box("posts", isset($_REQUEST["posts"]) && trim($_REQUEST["posts"]) != '' ? (int) $_REQUEST["posts"] : "", 5) . " " .
            $frm->select_tag("posts_op", array("gte" => "messages or more", "lte" => "messages or less"), $_REQUEST["posts_op"]));
        $frm->addrow("Last user activity",
            $frm->select_tag("lastactive_op", array("lt" => "Longer ago than", "gte" => "Within the last"), $_REQUEST["lastactive_op"]) . " " .
            $frm->text_box("lastactive", empty($_REQUEST["lastactive"]) ? "" : (int) $_REQUEST["lastactive"], 5) . " days"
            );
        $frm->addrow("Date user registered",
            $frm->select_tag("registered_op", array("lt" => "Longer ago than", "gte" => "Within the last"), $_REQUEST["registered_op"]) . " " .
            $frm->text_box("registered", empty($_REQUEST["registered"]) ? "" : (int) $_REQUEST["registered"], 5) . " days"
            );
        
        $forum_permissions_forums_list = array();

        $forum_permissions_forums = phorum_db_get_forums();

        $forum_permissions_forumpaths = phorum_get_forum_info(1);
        foreach($forum_permissions_forumpaths as $forum_id => $forumname) {
            if($forums[$forum_id]['folder_flag'] == 0)
                $forum_permissions_forums_list[$forum_id]=$forumname;
        }
        
        if(count($forum_permissions_forums_list)) {
            $forum_permissions_forums_select = "<select name=\"forum_permissions_forums[]\" multiple=\"multiple\" size=\"2\">\n";
            if(!empty($_REQUEST['forum_permissions_forums'])) {
                if (is_array($_REQUEST['forum_permissions_forums'])) {
                    foreach ($_REQUEST['forum_permissions_forums'] as $forum_permissions_forum) {
                        $selected_forum_permissions_forums[$forum_permissions_forum] = $forum_permissions_forum;
                    }
                } else {
                    $selected_forum_permissions_forums[(int)$_REQUEST['forum_permissions_forums']] = (int)$_REQUEST['forum_permissions_forums'];
                }
            }
            foreach ($forum_permissions_forums_list as $forum_id => $forumname) {
                $forum_permissions_forums_select .= "<option value=\"$forum_id\"";
                if (isset($selected_forum_permissions_forums[$forum_id]))
                    $forum_permissions_forums_select .= " selected='selected'";
                $forum_permissions_forums_select .= ">$forumname</option>";
            }        
            $forum_permissions_forums_select .= "</select>";
            
            $forum_permissions = array(
                PHORUM_USER_ALLOW_READ => "Read",
                PHORUM_USER_ALLOW_REPLY => "Reply",
                PHORUM_USER_ALLOW_NEW_TOPIC => "Create New Topics",
                PHORUM_USER_ALLOW_EDIT => "Edit Their Posts",
                PHORUM_USER_ALLOW_ATTACH => "Attach Files",
                PHORUM_USER_ALLOW_MODERATE_MESSAGES => "Moderate Messages",
                PHORUM_USER_ALLOW_MODERATE_USERS => "Moderate Users"
                );
            
            $forum_permissions_select = "<select name=\"forum_permissions[]\" multiple=\"multiple\" size=\"2\">\n";
            if(!empty($_REQUEST['forum_permissions'])) {
                if (is_array($_REQUEST['forum_permissions'])) {
                    foreach ($_REQUEST['forum_permissions'] as $forum_permission) {
                        $selected_forum_permissions[$forum_permission] = $forum_permission;
                    }
                } else {
                    $selected_forum_permissions[(int)$_REQUEST['forum_permissions']] = (int)$_REQUEST['forum_permissions'];
                }
            }
            
            foreach($forum_permissions as $forum_permission => $forum_permission_description) {
                
                $forum_permissions_select .= "<option value=\"".$forum_permission."\"";
                if (isset($selected_forum_permissions[$forum_permission]))
                    $forum_permissions_select .= " selected=\"selected\"";
                $forum_permissions_select .= ">".$forum_permission_description."</option>\n";
            }
            
            $forum_permissions_select .= "</select>\n";
            
            $frm->addrow("Personal permission to", $forum_permissions_select . "&nbsp;in&nbsp;" . $forum_permissions_forums_select);
        }
        
        if (isset($PHORUM['PROFILE_FIELDS']["num_fields"]))
            unset($PHORUM['PROFILE_FIELDS']["num_fields"]);
    
        $active_profile_fields = 0;
        foreach($PHORUM["PROFILE_FIELDS"] as $profile_field) {
            if (empty($profile_field['deleted']) && !empty($profile_field['show_in_admin'])) $active_profile_fields ++;
        }
    
        if ($active_profile_fields > 0) {
            $profile_field_select = "<select name=\"profile_field[]\"";
            if ($active_profile_fields > 1) 
                $profile_field_select .= " multiple=\"multiple\" size=\"2\"";
            $profile_field_select .= ">\n";
            
            if(!empty($_REQUEST['profile_field'])) {
                if (is_array($_REQUEST['profile_field'])) {
                    foreach ($_REQUEST['profile_field'] as $profile_field_id) {
                        $selected_profile_fields[$profile_field_id] = $profile_field_id;
                    }
                } else {
                    $selected_profile_fields[(int)$_REQUEST['profile_field']] = (int)$_REQUEST['profile_field'];
                }
            }
            
            foreach($PHORUM["PROFILE_FIELDS"] as $key => $profile_field) {
                // Do not show deleted fields.
                if (!empty($profile_field['deleted']) || empty($profile_field['show_in_admin'])) continue;
                
                $profile_field_select .= "<option value=\"".$profile_field["id"]."\"";
                if (isset($selected_profile_fields[$profile_field["id"]]))
                    $profile_field_select .= " selected=\"selected\"";
                $profile_field_select .= ">".$profile_field["name"]."</option>\n";
            }
            
            $profile_field_select .= "</select>\n";
            $frm->addrow("Custom Profiles", $frm->text_box("search_profile_field", $_REQUEST["search_profile_field"], 30) ."&nbsp;"
                . $frm->select_tag("profile_field_search_loc", $field_search_loc_array, $_REQUEST["profile_field_search_loc"])
                . "&nbsp;in&nbsp;" . $profile_field_select);
        }
        $db_groups = phorum_db_get_groups(0,true);
        if (count($db_groups)) {
            $multiple = (count($db_groups) > 1) ? "multiple=\"multiple\" size=\"3\"" : "";

            $group_select = "<select name=\"member_of_group[]\" $multiple>\n";
            if (!$multiple) {
                $group_select .= '<option value="">Any group</option>';
            }

            $selected_groups = array();
            if(!empty($_REQUEST['member_of_group'])) {
                if (is_array($_REQUEST['member_of_group'])) {
                    foreach ($_REQUEST['member_of_group'] as $group_id) {
                        $selected_groups[$group_id] = $group_id;
                    }
                } else {
                    $selected_groups[(int)$_REQUEST['member_of_group']] = (int)$_REQUEST['member_of_group'];
                }
            }
            
            ksort($db_groups);
            
            foreach ($db_groups as $group_id => $group) {
                $group_select .= "<option value=\"$group_id\"";
                if (isset($selected_groups[$group_id])) $group_select .= " selected=\"selected\"";
                $group_select .= ">".$group["name"]."</option>\n";
            }
            $group_select .= "</select>\n";
            $frm->addrow("Member of group", $group_select);
        }
            
        $frm->show();
    }

?>
    <hr class="PhorumAdminHR" />

    <script type="text/javascript">
    <!--
    function CheckboxControl(form, onoff) {
        for (var i = 0; i < form.elements.length; i++)
            if (form.elements[i].type == "checkbox")
                form.elements[i].checked = onoff;
    }
    // -->
    </script>
<?php

    if (!empty($_REQUEST["member_of_group"]) && is_array($_REQUEST["member_of_group"])) {
        $_REQUEST["member_of_group"] = implode(",",$_REQUEST["member_of_group"]);
    }
    if (!empty($_REQUEST["profile_field"]) && is_array($_REQUEST["profile_field"])) {
        $_REQUEST["profile_field"] = implode(",",$_REQUEST["profile_field"]);
    }
    if (!empty($_REQUEST["forum_permissions"]) && is_array($_REQUEST["forum_permissions"])) {
        $_REQUEST["forum_permissions"] = implode(",",$_REQUEST["forum_permissions"]);
    }
    if (!empty($_REQUEST["forum_permissions_forums"]) && is_array($_REQUEST["forum_permissions_forums"])) {
        $_REQUEST["forum_permissions_forums"] = implode(",",$_REQUEST["forum_permissions_forums"]);
    }
    
    // Build the search parameters query string items.
    $url_safe_search_arr = array();
    foreach ($user_search_fields as $field) {
        if (isset($_REQUEST[$field])) {
            $url_safe_search_arr[]= "$field=" . urlencode($_REQUEST[$field]);
        }
    }
    
    if (isset($_POST["sort"])) $_GET["sort"] = $_POST["sort"];
    $sort = isset($_GET["sort"]) ? $_GET["sort"] : "display_name";
    
    if (isset($_POST["sort_dir"])) $_GET["sort_dir"] = $_POST["sort_dir"];
    $sort_dir = empty($_GET["sort_dir"]) ? "" : "-";
    $reverse_sort_dir = (empty($sort_dir)) ? "-" : "";
    
    // Build the fields to search on.
    $search_fields = array();
    $search_values = array();
    $search_operators = array();
    if (isset($_REQUEST['search_username'])) {
        $search = trim($_REQUEST['search_username']);
        if ($search != '') {
            $search_fields[] = 'username';
            $search_values[] = $search;
            if ($_REQUEST['username_search_loc'] == "start") {
                $search_operators[] = '?*';
            } else if ($_REQUEST['username_search_loc'] == "end") {
                $search_operators[] = '*?';
            } else {
                $search_operators[] = '*';
            }
        }
    }
    if (isset($_REQUEST['search_display_name'])) {
        $search = trim($_REQUEST['search_display_name']);
        if ($search != '') {
            $search_fields[] = 'display_name';
            $search_values[] = $search;
            if ($_REQUEST['display_name_search_loc'] == "start") {
                $search_operators[] = '?*';
            } else if ($_REQUEST['display_name_search_loc'] == "end") {
                $search_operators[] = '*?';
            } else {
                $search_operators[] = '*';
            }
        }
    }
    if (isset($_REQUEST['search_email'])) {
        $search = trim($_REQUEST['search_email']);
        if ($search != '') {
            $search_fields[] = 'email';
            $search_values[] = $search;
            if ($_REQUEST['email_search_loc'] == "start") {
                $search_operators[] = '?*';
            } else if ($_REQUEST['email_search_loc'] == "end") {
                $search_operators[] = '*?';
            } else {
                $search_operators[] = '*';
            }
        }
    }
    if (isset($_REQUEST['search_signature'])) {
        $search = trim($_REQUEST['search_signature']);
        if ($search != '') {
            $search_fields[] = 'signature';
            $search_values[] = $search;
            if ($_REQUEST['signature_search_loc'] == "start") {
                $search_operators[] = '?*';
            } else if ($_REQUEST['signature_search_loc'] == "end") {
                $search_operators[] = '*?';
            } else {
                $search_operators[] = '*';
            }
        }
    }
    if (isset($_REQUEST['search_profile_field'])) {
        $search = trim($_REQUEST['search_profile_field']);
        if ($search != '' && !empty($_REQUEST['profile_field'])) {
            $profile_fields = explode(",",$_REQUEST['profile_field']);
            if ($_REQUEST['profile_field_search_loc'] == "start") {
                $search_operator = '?*';
            } else if ($_REQUEST['profile_field_search_loc'] == "end") {
                $search_operator = '*?';
            } else {
                $search_operator = '*';
            }
            foreach($profile_fields as $profile_field) {
                $profile_field_search_values[] = $search;
                $profile_field_search_operators[] = $search_operator;
            }
            $db_matching_users = phorum_api_user_search_custom_profile_field($profile_fields,$profile_field_search_values,$profile_field_search_operators, TRUE, 'OR');
            if (empty($db_matching_users)) $db_matching_users = array();
            $search_fields[] = 'user_id';
            $search_values[] = $db_matching_users;
            $search_operators[] = '()';
        }
    }
    if (isset($_REQUEST['search_type']) &&
        $_REQUEST['search_type'] != '') {
        if ($_REQUEST['search_type'] == 'user') {
            $search_fields[] = 'admin';
            $search_values[] = 0;
            $search_operators[] = '=';
        } elseif ($_REQUEST['search_type'] == 'admin') {
            $search_fields[] = 'admin';
            $search_values[] = 1;
            $search_operators[] = '=';
        }
    }
    if (isset($_REQUEST["posts"]) && trim($_REQUEST["posts"]) != '' && $_REQUEST["posts"] >= 0) {
        $search_fields[] = 'posts';
        $search_values[] = (int) $_REQUEST['posts'];
        $search_operators[] = $_REQUEST['posts_op'] == 'gte' ? '>=' : '<=';
    }
    if (!empty($_REQUEST["lastactive"]) && $_REQUEST["lastactive"] >= 0) {
        $time = time() - ($_REQUEST["lastactive"] * 86400);
        $search_fields[] = 'date_last_active';
        $search_values[] = $time;
        $search_operators[] = $_REQUEST['lastactive_op'] == 'gte' ? '>=' : '<';
    }
    if (!empty($_REQUEST["registered"]) && $_REQUEST["registered"] >= 0) {
        $time = time() - ($_REQUEST["registered"] * 86400);
        $search_fields[] = 'date_added';
        $search_values[] = $time;
        $search_operators[] = $_REQUEST['registered_op'] == 'gte' ? '>=' : '<';
    }
    if (isset($_REQUEST['search_status']) &&
        $_REQUEST['search_status'] != '' &&
        $_REQUEST['search_status'] != 'any') {

        $search_fields[] = 'active';
        if ($_REQUEST['search_status'] == 'pending') {
            $search_values[] = 0;
            $search_operators[] = '<';
        } else {
            $search_values[] = (int) $_REQUEST['search_status'];
            $search_operators[] = '=';
        }
    }
    if (!empty($_REQUEST["member_of_group"])) {
        $groups = explode(",",$_REQUEST["member_of_group"]);
        foreach($groups as $glid => $glrid) {
        	if($glrid < 1) {
        		unset($groups[$glid]);
        	}	
        }
        if(count($groups)) {
	        $db_group_members = phorum_db_get_group_members($groups);
	        $group_members = array();
	        foreach ($db_group_members as $user_id => $group_status) {
	            $group_members[] = $user_id;
	        }
	        $search_fields[] = 'user_id';
	        $search_values[] = $group_members;
	        $search_operators[] = '()';
        }
    }
    
    if (!empty($_REQUEST["forum_permissions"]) && !empty($_REQUEST["forum_permissions_forums"])) {
        $forum_permissions = explode(",",$_REQUEST["forum_permissions"]);
        $or_forum_permissions = "";
        foreach ($forum_permissions as $forum_permission) {
            if (isset($forum_permissions_first)) {
                $or_forum_permissions .= " OR ";
            } else {
                $forum_permissions_first = 1;
            }
            $or_forum_permissions .= "(perm.permission>=$forum_permission AND
                      (perm.permission & $forum_permission>0))";
        }
        phorum_db_sanitize_mixed($_REQUEST["forum_permissions_forums"],"string");
        $db_forum_permissions_users = phorum_db_interact(
            DB_RETURN_ROWS,
            "SELECT DISTINCT user.user_id AS user_id
             FROM   {$PHORUM['user_table']} AS user
                    LEFT JOIN {$PHORUM['user_permissions_table']} AS perm
                    ON perm.user_id = user.user_id
             WHERE  ($or_forum_permissions) AND perm.forum_id IN ({$_REQUEST['forum_permissions_forums']})"
        );
        $forum_permissions_users = array();
        foreach($db_forum_permissions_users as $user) {
            $forum_permissions_users[] = $user[0];
        }
        $search_fields[] = 'user_id';
        $search_values[] = $forum_permissions_users;
        $search_operators[] = '()';
    }

    // Find a list of all matching user_ids.
    $total = phorum_api_user_search(
        $search_fields, $search_values, $search_operators,
        TRUE, 'AND',NULL,0,0,true
    );
    
    $default_pagelength=30;
    
    settype($_REQUEST["page"], "integer");
    settype($_REQUEST["pagelength"], "integer");
    
    // The available page lengths.
    $pagelengths = array(
        10   =>  "10 users per page",
        20   =>  "20 users per page",
        30   =>  "30 users per page",
        50   =>  "50 users per page",
        100  => "100 users per page",
        250  => "250 users per page",
    );
    
    // What page length to use?
    if (isset($_POST["pagelength"])) $_GET["pagelength"] = $_POST["pagelength"];
    $pagelength = isset($_GET["pagelength"]) ? (int)$_GET["pagelength"] : $default_pagelength;

    if (!isset($pagelengths[$pagelength])) $pagelength = $default_pagelength;
    
    $totalpages = ceil($total/$pagelength);
    if ($totalpages <= 0) $totalpages = 1;
    
    // Which page to show?
    if (isset($_POST["prevpage"])) {
        $page = (int)$_POST["curpage"] - 1;
    } elseif (isset($_POST["nextpage"])) {
        $page = (int)$_POST["curpage"] + 1;
    } else {
        if (isset($_POST["page"])) $_GET["page"] = $_POST["page"];
        $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
    }
    
    if ($page <= 0) $page = 1;
    if ($page > $totalpages) $page = $totalpages;

    $search_start = ($page-1)*$pagelength;
    
    $db_sort = ($sort == "display_name") ? $sort_dir.$sort : array($sort_dir.$sort,"display_name");
    
    // Find a list of matching user_ids to display on the current page.
    $user_ids = phorum_api_user_search(
        $search_fields, $search_values, $search_operators,
        TRUE, 'AND', $db_sort,
        $search_start, $pagelength
    );

    // Retrieve the user data for the users on the current page.
    $users = empty($user_ids)
           ? array()
           : phorum_api_user_get($user_ids, FALSE);

    if (count($users))
    {
        // Create a page list for a drop down menu.
        $pagelist = array();
        for($p=1; $p<=$totalpages; $p++) {
            $pagelist[$p] = $p;
        }
        
        $cols = 6;
        
        $input_args = array('module=users');
        $input_args = array_merge($input_args,$url_safe_search_arr);
        
        $frm_url = phorum_admin_build_url($input_args);
        
        $sort_input_args = array('page='.$page,'pagelength='.$pagelength);
        $sort_input_args = array_merge($sort_input_args,$input_args);
        
        $display_name_sort_dir = ($sort == "display_name") ? $reverse_sort_dir : "";
        $display_name_sort_url_args = array_merge(array('sort=display_name','sort_dir='.$display_name_sort_dir),$sort_input_args);
        $display_name_sort_url = phorum_admin_build_url($display_name_sort_url_args);
        
        $email_sort_dir = ($sort == "email") ? $reverse_sort_dir : "";
        $email_sort_url_args = array_merge(array('sort=email','sort_dir='.$email_sort_dir),$sort_input_args);
        $email_sort_url = phorum_admin_build_url($email_sort_url_args);
        
        $status_sort_dir = ($sort == "active") ? $reverse_sort_dir : "";
        $status_sort_url_args = array_merge(array('sort=active','sort_dir='.$status_sort_dir),$sort_input_args);
        $status_sort_url = phorum_admin_build_url($status_sort_url_args);
        
        $posts_sort_dir = ($sort == "posts") ? $reverse_sort_dir : "";
        $posts_sort_url_args = array_merge(array('sort=posts','sort_dir='.$posts_sort_dir),$sort_input_args);
        $posts_sort_url = phorum_admin_build_url($posts_sort_url_args);
        
        $last_activity_sort_dir = ($sort == "date_last_active") ? $reverse_sort_dir : "";
        $last_activity_sort_url_args = array_merge(array('sort=date_last_active','sort_dir='.$last_activity_sort_dir),$sort_input_args);
        $last_activity_sort_url = phorum_admin_build_url($last_activity_sort_url_args);
        
        if (!empty($_REQUEST["registered"])) {
            $cols++;
            $registered_sort_dir = ($sort == "date_added") ? $reverse_sort_dir : "";
            $registered_sort_url_args = array_merge(array('sort=date_added','sort_dir='.$registered_sort_dir),$sort_input_args);
            $registered_sort_url = phorum_admin_build_url($registered_sort_url_args);
        }
        
        echo <<<EOT
        <form name="UsersForm" action="$frm_url" method="post">
        <input type="hidden" name="phorum_admin_token" value="{$PHORUM['admin_token']}">
        <input type="hidden" name="curpage" value="$page">
        <input type="hidden" name="sort" value="$sort">
        <input type="hidden" name="sort_dir" value="$sort_dir">
        <input type="hidden" name="module" value="users">
        <input type="hidden" name="action" value="deleteUsers">
        <table border="0" cellspacing="1" cellpadding="0"
               class="PhorumAdminTable" width="100%">
        <tr>
            <td colspan="$cols">
            <span style="float:right;margin-right:10px">
            <select name="pagelength" onchange="this.form.submit()">
EOT;
        foreach ($pagelengths as $value => $description) {
            echo "<option";
            if ($value == $pagelength) echo " selected=\"selected\"";
            echo " value=\"$value\">$description</option>";
        }
        echo "</select>&nbsp;&nbsp;&nbsp;";
        if ($page > 1) echo "<input type=\"submit\" name=\"prevpage\" value=\"&lt;&lt;\"/> ";
        echo "page <select name=\"page\" onchange=\"this.form.submit()\">";
        foreach ($pagelist as $value) {
            echo "<option";
            if ($value == $page) echo " selected=\"selected\"";
            echo " value=\"$value\">$value</option>";
        }
        echo "</select> of $totalpages ";
        if ($page < $totalpages) echo "<input type=\"submit\" name=\"nextpage\" value=\"&gt;&gt;\"/>";
        echo <<<EOT
            </span>Number of users: $total
            </td>
        </tr>
        <tr>
            <td class="PhorumAdminTableHead"><a href="$display_name_sort_url" style="color: #FFF;">Display Name</a></td>
            <td class="PhorumAdminTableHead"><a href="$email_sort_url" style="color: #FFF;">Email</a></td>
            <td class="PhorumAdminTableHead"><a href="$status_sort_url" style="color: #FFF;">Status</a></td>
            <td class="PhorumAdminTableHead"><a href="$posts_sort_url" style="color: #FFF;">Posts</a></td>
            <td class="PhorumAdminTableHead"><a href="$last_activity_sort_url" style="color: #FFF;">Last Activity</a></td>
EOT;
        if (!empty($_REQUEST["registered"])) {
            echo "<td class=\"PhorumAdminTableHead\"><a href=\"$registered_sort_url\" style=\"color: #FFF;\">Registered</a></td>";
        }
        echo <<<EOT
            <td class="PhorumAdminTableHead">Delete</td>
        </tr>
EOT;
        foreach($user_ids as $user_id)
        {
            $user = $users[$user_id];

            $status = $user_status_map[$user['active']];

            $posts = intval($user['posts']);

            $ta_class = "PhorumAdminTableRow".($ta_class == "PhorumAdminTableRow" ? "Alt" : "");
            
            $user_input_args = array('module=users','user_id='.$user['user_id'],'edit=1','page='.$page,'pagelength='.$pagelength,'sort='.$sort,'sort_dir='.$sort_dir);
            $user_input_args = array_merge($user_input_args,$url_safe_search_arr);
            $edit_url = phorum_admin_build_url($user_input_args);
            echo "<tr>\n";
            echo "    <td class=\"".$ta_class."\"><a href=\"$edit_url\">".(empty($PHORUM['custom_display_name']) ? htmlspecialchars($user['display_name']) : $user['display_name'])."</a></td>\n";
            echo "    <td class=\"".$ta_class."\">".htmlspecialchars($user['email'])."</td>\n";
            echo "    <td class=\"".$ta_class."\">{$status}</td>\n";
            echo "    <td class=\"".$ta_class."\" style=\"text-align:right\">{$posts}</td>\n";
            echo "    <td class=\"".$ta_class."\" align=\"right\">".(intval($user['date_last_active']) ? phorum_date($PHORUM['short_date'], intval($user['date_last_active'])) : "&nbsp;")."</td>\n";
            if (!empty($_REQUEST["registered"])) {
                echo "    <td class=\"".$ta_class."\" align=\"right\">".(intval($user['date_added']) ? phorum_date($PHORUM['short_date'], intval($user['date_added'])) : "&nbsp;")."</td>\n";
            }
            echo "    <td class=\"".$ta_class."\"><input type=\"checkbox\" name=\"deleteIds[]\" value=\"{$user['user_id']}\"></td>\n";
            echo "</tr>\n";
        }

        echo <<<EOT
        <tr>
          <td colspan="$cols" align="right">
          <input type="button" value="Check All"
           onClick="CheckboxControl(this.form, true);">
          <input type="button" value="Clear All"
           onClick="CheckboxControl(this.form, false);">
          <input type="submit" name="delete" value="Delete Selected Users"
           onClick="return confirm('Really delete the selected user(s)?')">
          </td>
        </tr>
        </table>
        </form>
EOT;

    } else {

        echo "No Users Found.";

    }

}

// display edit form
if (isset($_REQUEST["user_id"]))
{
    print "<a href=\"".htmlspecialchars($referrer)."\">Back to the user overview</a>&nbsp;|&nbsp;<a href=\"#forums\">Edit Forum Permissions</a>&nbsp;|&nbsp;<a href=\"#groups\">Edit Groups</a><br />";

    $user = phorum_api_user_get($_REQUEST["user_id"], TRUE);

    if(count($user)){

        $frm = new PhorumInputForm ("", "post", "Update");

        $frm->hidden("module", "users");

        $frm->hidden("section", "main");

        $frm->hidden("referrer", $referrer);

        $frm->hidden("user_id", $_REQUEST["user_id"]);

        $frm->addbreak("Edit User");

        $frm->addrow("User Name", $frm->text_box("username", $user["username"], 50));

        $frm->addrow("Real Name", $frm->text_box("real_name", $user["real_name"], 50));

        $frm->addrow("Email", $frm->text_box("email", $user["email"], 50));
        $frm->addrow("Password (Enter to change)", $frm->text_box("password1",""));
        $frm->addrow("Password (Confirmation)", $frm->text_box("password2",""));


        $frm->addrow("Signature", $frm->textarea("signature", $user["signature"]));

        $frm->addrow("Active", $frm->select_tag("active", array("No", "Yes"), $user["active"]));

        $frm->addrow("Forum posts",$user["posts"]);

        $frm->addrow("Registration Date", phorum_date($PHORUM['short_date_time'], $user['date_added']));

        $row=$frm->addrow("Date last active", phorum_date($PHORUM['short_date_time'], $user['date_last_active']));

        $frm->addrow("Administrator", $frm->select_tag("admin", array("No", "Yes"), $user["admin"]));

        $frm->addhelp($row, "Date last active", "This shows the date, when the user was last seen in the forum. Check your setting on \"Track user usage\" in the \"General Settings\". As long as this setting is not enabled, the activity will not be tracked.");

        $cf_header_shown=0;
        foreach($PHORUM["PROFILE_FIELDS"] as $key => $item){
            if ($key === 'num_rows' || !empty($item['deleted'])) continue;
            if(!empty($item['show_in_admin'])) {
                if(!$cf_header_shown) {
                    $frm->addbreak('Custom Profile Fields');
                    $cf_header_shown=1;
                }
                $itemval = "[EMPTY]";
                if (isset($user[$item['name']]) && trim($user[$item['name']]) != '') {
                    $itemval = trim($user[$item['name']]);
                }
                $frm->addrow($item['name'],$itemval);
            }
        }

        phorum_hook("admin_users_form", $frm, $user);

        $frm->show();

        echo "<br /><hr class=\"PhorumAdminHR\" /><br /><a name=\"forums\"></a>";

        $frm = new PhorumInputForm ("", "post", "Update");

        $frm->hidden("user_id", $_REQUEST["user_id"]);

        $frm->hidden("module", "users");

        $frm->hidden("section", "forums");

        $frm->hidden("referrer", $referrer);

        $row=$frm->addbreak("Edit Forum Permissions");

        $frm->addhelp($row, "Forum Permissions", "These are permissions set exclusively for this user.  You need to grant all permisssions you want the user to have for a forum here.  No permissions from groups or a forum's properties will be used once the user has specific permissions for a forum.");

        $forums=phorum_db_get_forums();

        $forumpaths = phorum_get_forum_info(1);

        $perm_frm = $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_READ."]", 1, "Read")."&nbsp;&nbsp;".
                    $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply")."&nbsp;&nbsp;".
                    $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics")."&nbsp;&nbsp;".
                    $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_EDIT."]", 1, "Edit&nbsp;Their&nbsp;Posts")."<br />".
                    $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files")."<br />".
                    $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_MODERATE_MESSAGES."]", 1, "Moderate Messages")."&nbsp;&nbsp;".
                    $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_MODERATE_USERS."]", 1, "Moderate Users")."&nbsp;&nbsp;";

        $arr[]="Add A Forum...";

        foreach($forumpaths as $forum_id=>$forumname){
            if(!isset($user["forum_permissions"][$forum_id]) && $forums[$forum_id]['folder_flag'] == 0)
                $arr[$forum_id]=$forumname;
        }

        if(count($arr)>1)
            $frm->addrow($frm->select_tag("new_forum", $arr), $perm_frm);


        if(is_array($user["forum_permissions"])){
            foreach($user["forum_permissions"] as $forum_id=>$perms){
                $perm_frm = $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_READ."]", 1, "Read", ($perms & PHORUM_USER_ALLOW_READ))."&nbsp;&nbsp;".
                            $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply", ($perms & PHORUM_USER_ALLOW_REPLY))."&nbsp;&nbsp;".
                            $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics", ($perms & PHORUM_USER_ALLOW_NEW_TOPIC))."&nbsp;&nbsp;".
                            $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_EDIT."]", 1, "Edit&nbsp;Their&nbsp;Posts", ($perms & PHORUM_USER_ALLOW_EDIT))."<br />".
                            $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files", ($perms & PHORUM_USER_ALLOW_ATTACH))."<br />".
                            $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_MODERATE_MESSAGES."]", 1, "Moderate Messages", ($perms & PHORUM_USER_ALLOW_MODERATE_MESSAGES))."&nbsp;&nbsp;".
                            $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_MODERATE_USERS."]", 1, "Moderate Users", ($perms & PHORUM_USER_ALLOW_MODERATE_USERS))."&nbsp;&nbsp;".

                $frm->hidden("forums[$forum_id]", $forum_id);

                $row=$frm->addrow($forumpaths[$forum_id]."<br />".$frm->checkbox("delforum[$forum_id]", 1, "Delete"), $perm_frm);

            }
        }

        $frm->show();

        echo "<br /><hr class=\"PhorumAdminHR\" /><br /><a name=\"groups\"></a>";

        $frm = new PhorumInputForm ("", "post", "Update");

        $frm->hidden("user_id", $_REQUEST["user_id"]);

        $frm->hidden("module", "users");

        $frm->hidden("referrer", $referrer);

        $frm->hidden("section", "groups");

        $extra_opts = "";
        // if its an admin, let the user know that the admin will be able to act as a moderator no matter what
        if ($user["admin"]){
            $row=$frm->addbreak("Edit Groups (Admins can act as a moderator of every group, regardless of these values)");
        }
        else{
            $row=$frm->addbreak("Edit Groups");
        }

        $groups= phorum_db_get_groups(0, TRUE);
        $usergroups = phorum_api_user_check_group_access(PHORUM_USER_GROUP_SUSPENDED, PHORUM_ACCESS_LIST, $_REQUEST["user_id"]);

        $arr=array("Add A Group...");
        foreach($groups as $group_id=>$group){
            if(!isset($usergroups[$group_id]))
                $arr[$group_id]=$group["name"];
        }

        if(count($arr)>1)
            $frm->addrow("Add A Group", $frm->select_tag("new_group", $arr));

        if(is_array($usergroups)){
            $group_options = array(
                    "remove" => "< Remove User From Group >",
                    PHORUM_USER_GROUP_SUSPENDED => "Suspended",
                    PHORUM_USER_GROUP_UNAPPROVED => "Unapproved",
                    PHORUM_USER_GROUP_APPROVED => "Approved",
                    PHORUM_USER_GROUP_MODERATOR => "Group Moderator");
            foreach($usergroups as $group_id => $group){
                $group_perm = $group['user_status'];
                $group_info = phorum_db_get_groups($group_id);
                $frm->hidden("groups[$group_id]", "$group_id");
                $frm->addrow($group_info[$group_id]["name"], $frm->select_tag("group_perm[$group_id]", $group_options, $group_perm, $extra_opts));
            }
        }

        $frm->show();

    } else {

        echo "User Not Found.";

    }

//display add user form
} elseif (isset($_REQUEST["add"]) || isset($addUser_error)) {

    $username = isset($user_data["username"]) ? $user_data["username"] : "";
    $real_name = isset($user_data["real_name"]) ? $user_data["real_name"] : "";
    $email = isset($user_data["email"]) ? $user_data["email"] : "";
    $admin = isset($user_data["admin"]) ? $user_data["admin"] : "";

    print "<a href=\"".htmlspecialchars($referrer)."\">Back to the user overview</a><br/>";

    $frm = new PhorumInputForm ("", "post", "Add User");

    $frm->hidden("module", "users");

    $frm->hidden("referrer", $referrer);

    $frm->hidden("addUser", 1);

    $frm->addbreak("Add User");

    $frm->addrow("User Name", $frm->text_box("username", $username, 50));
    $frm->addrow("Real Name", $frm->text_box("real_name", $real_name, 50));

    $frm->addrow("Email", $frm->text_box("email", $email, 50));
    $frm->addrow("Password", $frm->text_box("password1","", 0, 0, true));
    $frm->addrow("Password (Confirmation)", $frm->text_box("password2","", 0, 0, true));

    $frm->addrow("Administrator", $frm->select_tag("admin", array("No", "Yes"), $admin));

    $frm->show();
}
?>
