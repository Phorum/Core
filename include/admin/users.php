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
    'search_display_name',
    'search_email',
    'search_type',
    'search_status',
    'posts',
    'posts_op',
    'lastactive',
    'lastactive_op'
);


$error="";

// The referrer to use for the user edit page, to jump back to the user list.
if (isset($_POST['referrer'])) {
    $referrer = $_POST['referrer'];
    unset($_POST['referrer']);
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = $_SERVER['HTTP_REFERER'];
} else {
    $referrer = phorum_admin_build_url(array('module=users'));
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

        $frm->addrow("Username contains", $frm->text_box("search_username", $_REQUEST["search_username"], 30));
        if ($PHORUM['display_name_source'] != 'username') {
            $frm->addrow("Display name contains", $frm->text_box("search_display_name", $_REQUEST["search_display_name"], 30));
        }
        $frm->addrow("Email contains", $frm->text_box("search_email", $_REQUEST["search_email"], 30));
        $frm->addrow("User status and type", $frm->select_tag("search_status", $user_status_map, $_REQUEST['search_status']) . " " . $frm->select_tag("search_type", array('any' => 'Any type of user', 'user' => 'Regular users', 'admin' => 'Administrators'), $_REQUEST['search_type']));
        $frm->addrow("Number of forum posts ",
            $frm->text_box("posts", isset($_REQUEST["posts"]) && trim($_REQUEST["posts"]) != '' ? (int) $_REQUEST["posts"] : "", 5) . " " .
            $frm->select_tag("posts_op", array("gte" => "messages or more", "lte" => "messages or less"), $_REQUEST["posts_op"]));
        $frm->addrow("Last user activity",
            $frm->select_tag("lastactive_op", array("lt" => "Longer ago than", "gte" => "Within the last"), $_REQUEST["lastactive_op"]) . " " .
            $frm->text_box("lastactive", empty($_REQUEST["lastactive"]) ? "" : (int) $_REQUEST["lastactive"], 5) . " days"
            );
        $frm->show();
    }

?>
    <hr class=\"PhorumAdminHR\" />

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

    // Build the search parameters query string items.
    $url_safe_search_arr = array();
    foreach ($user_search_fields as $field) {
        if (isset($_REQUEST[$field])) {
            $url_safe_search_arr[]= "$field=" . urlencode($_REQUEST[$field]);
        }
    }

    settype($_REQUEST["start"], "integer");

    $display=30;

    $search_start = (int)$_REQUEST['start'];

    // Build the fields to search on.
    $search_fields = array();
    $search_values = array();
    $search_operators = array();
    if (isset($_REQUEST['search_username'])) {
        $search = trim($_REQUEST['search_username']);
        if ($search != '') {
            $search_fields[] = 'username';
            $search_values[] = $search;
            $search_operators[] = '*';
        }
    }
    if (isset($_REQUEST['search_display_name'])) {
        $search = trim($_REQUEST['search_display_name']);
        if ($search != '') {
            $search_fields[] = 'display_name';
            $search_values[] = $search;
            $search_operators[] = '*';
        }
    }
    if (isset($_REQUEST['search_email'])) {
        $search = trim($_REQUEST['search_email']);
        if ($search != '') {
            $search_fields[] = 'email';
            $search_values[] = $search;
            $search_operators[] = '*';
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

    // Find a list of all matching user_ids.
    $all_user_ids = phorum_api_user_search(
        $search_fields, $search_values, $search_operators,
        TRUE, 'AND'
    );

    // Find a list of matching user_ids to display on the current page.
    $user_ids = phorum_api_user_search(
        $search_fields, $search_values, $search_operators,
        TRUE, 'AND', '+username',
        $search_start, $display
    );

    // Retrieve the user data for the users on the current page.
    $users = empty($user_ids)
           ? array()
           : phorum_api_user_get($user_ids, FALSE);

    $total = empty($all_user_ids) ? 0 : count($all_user_ids);

    if (count($users))
    {
        $nav="";

        if($_REQUEST["start"]>0){
            $old_start=$_REQUEST["start"]-$display;
            $input_args = array('module=users','start='.$old_start);
            $input_args = array_merge($input_args,$url_safe_search_arr);
            $prev_url = phorum_admin_build_url($input_args);
            $nav.="<a href=\"$prev_url\">Previous Page</a>";
        }

        $nav.="&nbsp;&nbsp;";

        if($_REQUEST["start"]+$display<$total){
            $new_start=$_REQUEST["start"]+$display;
            $input_args = array('module=users','start='.$new_start);
            $input_args = array_merge($input_args,$url_safe_search_arr);
            $next_url = phorum_admin_build_url($input_args);
            $nav.="<a href=\"$next_url\">Next Page</a>";
        }

        $totalpages = ceil($total/$display);
        $page = ceil($_REQUEST['start']/$display) + 1;

        $frm_url = phorum_admin_build_url('base');
        echo <<<EOT
        <form name="UsersForm" action="$frm_url" method="post">
        <input type="hidden" name="phorum_admin_token" value="{$PHORUM['admin_token']}">
        
        <input type="hidden" name="module" value="users">
        <input type="hidden" name="action" value="deleteUsers">
        <table border="0" cellspacing="1" cellpadding="0"
               class="PhorumAdminTable" width="100%">
        <tr>
            <td colspan="4">
                $total users in total,
                showing page $page of $totalpages
            <td colspan="2" align="right">$nav</td>
        </tr>
        <tr>
            <td class="PhorumAdminTableHead">Display Name</td>
            <td class="PhorumAdminTableHead">Email</td>
            <td class="PhorumAdminTableHead">Status</td>
            <td class="PhorumAdminTableHead">Posts</td>
            <td class="PhorumAdminTableHead">Last Activity</td>
            <td class="PhorumAdminTableHead">Delete</td>
        </tr>
EOT;

        foreach($user_ids as $user_id)
        {
            $user = $users[$user_id];

            $status = $user_status_map[$user['active']];

            $posts = intval($user['posts']);

            $ta_class = "PhorumAdminTableRow".($ta_class == "PhorumAdminTableRow" ? "Alt" : "");
            $edit_url = phorum_admin_build_url(array('module=users','user_id='.$user['user_id'],'edit=1'));
            echo "<tr>\n";
            echo "    <td class=\"".$ta_class."\"><a href=\"$edit_url\">".(empty($PHORUM['custom_display_name']) ? htmlspecialchars($user['display_name']) : $user['display_name'])."</a></td>\n";
            echo "    <td class=\"".$ta_class."\">".htmlspecialchars($user['email'])."</td>\n";
            echo "    <td class=\"".$ta_class."\">{$status}</td>\n";
            echo "    <td class=\"".$ta_class."\" style=\"text-align:right\">{$posts}</td>\n";
            echo "    <td class=\"".$ta_class."\" align=\"right\">".(intval($user['date_last_active']) ? phorum_date($PHORUM['short_date'], intval($user['date_last_active'])) : "&nbsp;")."</td>\n";
            echo "    <td class=\"".$ta_class."\"><input type=\"checkbox\" name=\"deleteIds[]\" value=\"{$user['user_id']}\"></td>\n";
            echo "</tr>\n";
        }

        echo <<<EOT
        <tr>
          <td colspan="6" align="right">
          <input type="button" value="Check All"
           onClick="CheckboxControl(this.form, true);">
          <input type="button" value="Clear All"
           onClick="CheckboxControl(this.form, false);">
          <input type="submit" name="submit" value="Delete Selected Users"
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
    print "<a href=\"".htmlspecialchars($referrer)."\">Back to the user overview</a><br/>";

    $user = phorum_api_user_get($_REQUEST["user_id"], TRUE);

    if(count($user)){

        $frm = new PhorumInputForm ("", "post", "Update");

        $frm->hidden("module", "users");

        $frm->hidden("section", "main");

        $frm->hidden("referrer", $referrer);

        $frm->hidden("user_id", $_REQUEST["user_id"]);

        $frm->addbreak("Edit User");

        $frm->addrow("User Name", htmlspecialchars($user["username"])."&nbsp;&nbsp;<a href=\"#forums\">Edit Forum Permissions</a>&nbsp;&nbsp;<a href=\"#groups\">Edit Groups</a>");

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
