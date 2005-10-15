<?php

    if(!defined("PHORUM_ADMIN")) return;

    $error="";

    if(count($_POST)){
    

        if( isset($_POST['action']) && $_POST['action'] == "deleteUsers") {

            $count=count($_POST['deleteIds']);
            if($count > 0) {
                foreach($_POST['deleteIds'] as $id => $deluid) {
                    phorum_user_delete($deluid);
                }
                echo "$count User(s) deleted.<br />";
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
                    }
    
                    if(isset($_POST["delforum"])){
                        foreach($_POST["delforum"] as $fid=>$val){
                            unset($user_data["forum_permissions"][$fid]);
                            unset($_POST["forums"][$fid]);
                        }
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
                    }
    
                    if(isset($_POST["group_perm"])){
                        foreach($_POST["group_perm"] as $group_id=>$perm){
                            // as long as we aren't removing them from the group, accept other values
                            if ($perm != PHORUM_USER_GROUP_REMOVE){
                                $groupdata[$group_id] = $perm;
                            }
                        }
                    }

                    phorum_user_save_groups($_POST["user_id"], $groupdata);
                    break;
            }
            
            if(isset($_POST['password1']) && !empty($_POST['password1']) && !empty($_POST['password2']) && $_POST['password1'] != $_POST['password2']) {
                $error="Passwords don't match!";
            } elseif(!empty($_POST['password1']) && !empty($_POST['password2'])) {
                $user_data['password']=$_POST['password1'];
            }
            
            // clean up
            unset($user_data["module"]);
            unset($user_data["section"]);
            unset($user_data["password1"]);
            unset($user_data["password2"]);
    
            if(empty($error)){        
                phorum_user_save($user_data);
                echo "User Saved<br />";
            }
        }
        
    }

    if($error){
        phorum_admin_error($error);
    }

    include_once "./include/admin/PhorumInputForm.php";
    include_once "./include/profile_functions.php";
    include_once "./include/users.php";

    if(!defined("PHORUM_ORIGINAL_USER_CODE") || PHORUM_ORIGINAL_USER_CODE!==true){
        echo "Phorum User Admin only works with the Phorum User System.";
        return;
    }

    if(!isset($_GET["edit"]) && !isset($_POST['section'])){

        if(empty($_REQUEST["user_id"])){
    
            $frm =& new PhorumInputForm ("", "get", "Search");
        
            $frm->addbreak("Phorum User Admin");
        
            $frm->hidden("module", "users");
        
            $frm->addrow("Search", $frm->text_box("search", htmlspecialchars($_REQUEST["search"]), 50)."&nbsp;&nbsp;<a href=\"{$_SERVER['PHP_SELF']}?module=users&search=%\">All Users</a>");
        
            $frm->show();
    
        }
        
        echo "<hr class=\"PhorumAdminHR\" />";
    
        $search=$_REQUEST["search"];
    
        $url_safe_search=urlencode($_REQUEST["search"]);
    
        $users=phorum_db_search_users($_REQUEST["search"]);
    
        $total=count($users);
    
        settype($_REQUEST["start"], "integer");
    
        $display=30;
    
        $users=array_slice($users, $_REQUEST["start"], $display);
    
        if(count($users)) {
    
            $nav="";
    
            if($_REQUEST["start"]>0){
                $old_start=$_REQUEST["start"]-$display;
                $nav.="<a href=\"$_SERVER[PHP_SELF]?module=users&search=$url_safe_search&start=$old_start\">Previous Page</a>";
            }
    
            $nav.="&nbsp;&nbsp;";
    
            if($_REQUEST["start"]+$display<$total){
                $new_start=$_REQUEST["start"]+$display;
                $nav.="<a href=\"$_SERVER[PHP_SELF]?module=users&search=$url_safe_search&start=$new_start\">Next Page</a>";
            }
    
            echo "<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"module\" value=\"users\">\n";            
            echo "<input type=\"hidden\" name=\"action\" value=\"deleteUsers\">\n";
            echo "<table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=\"PhorumAdminTable\" width=\"100%\">\n";
            echo "<tr>\n";
            echo "    <td colspan=\"2\">$total users found</td>\n";
            echo "    <td align=\"right\">$nav</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
            echo "    <td class=\"PhorumAdminTableHead\">User</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Email</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Delete</td>\n";            
            echo "</tr>\n";
            
            foreach($users as $user){
                echo "<tr>\n";
                echo "    <td class=\"PhorumAdminTableRow\"><a href=\"$_SERVER[PHP_SELF]?module=users&user_id={$user['user_id']}&edit=1\">".htmlspecialchars($user['username'])."</a></td>\n";
                echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars($user['email'])."</td>\n";
                echo "    <td class=\"PhorumAdminTableRow\">Delete? <input type=\"checkbox\" name=\"deleteIds[]\" value=\"{$user['user_id']}\"></td>\n";                
                echo "</tr>\n";
            }
            echo "<tr><td colspan=\"3\" align=\"right\"><input type=\"submit\" name=\"submit\" value=\"Delete Selected\"></td></tr>";
            echo "</table>\n";
            echo "</form>\n";
    
        } else {
    
            echo "No Users Found.";
    
        }

    }
    
    // display edit form
    if(isset($_REQUEST["user_id"])){

        $user=phorum_user_get($_REQUEST["user_id"]);

        if(count($user)){

            $frm =& new PhorumInputForm ("", "post", "Update");

            $frm->hidden("module", "users");

            $frm->hidden("section", "main");

            $frm->hidden("user_id", $_REQUEST["user_id"]);

            $frm->addbreak("Edit User");

            $frm->addrow("User Name", htmlspecialchars($user["username"])."&nbsp;&nbsp;<a href=\"#forums\">Edit Forum Permissions</a>&nbsp;&nbsp;<a href=\"#groups\">Edit Groups</a>");

            $frm->addrow("Email", $frm->text_box("email", $user["email"], 50));
            $frm->addrow("Password (Enter to change)", $frm->text_box("password1",""));
            $frm->addrow("Password (Confirmation)", $frm->text_box("password2",""));
            

            $frm->addrow("Signature", $frm->textarea("signature", htmlspecialchars($user["signature"])));

            $frm->addrow("Active", $frm->select_tag("active", array("No", "Yes"), $user["active"]));

            $frm->addrow("Administrator", $frm->select_tag("admin", array("No", "Yes"), $user["admin"]));
            
            $frm->show();

            echo "<br /><hr class=\"PhorumAdminHR\" /><br /><a name=\"forums\"></a>";
                    
            $frm =& new PhorumInputForm ("", "post", "Update");

            $frm->hidden("user_id", $_REQUEST["user_id"]);

            $frm->hidden("module", "users");

            $frm->hidden("section", "forums");

            $row=$frm->addbreak("Edit Forum Permissions");

            $frm->addhelp($row, "Forum Permissions", "These are permissions set exclusively for this user.  You need to grant all permisssions you want the user to have for a forum here.  No permissions from groups or a forum's properties will be used once the user has specific permissions for a forum.");
            
            $forums=phorum_db_get_forums();

            $perm_frm = $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_READ."]", 1, "Read")."&nbsp;&nbsp;".
                        $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply")."&nbsp;&nbsp;".
                        $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics")."&nbsp;&nbsp;".
                        $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_EDIT."]", 1, "Edit&nbsp;Their&nbsp;Posts")."<br />".
                        $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files")."<br />".
                        $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_MODERATE_MESSAGES."]", 1, "Moderate Messages")."&nbsp;&nbsp;".
                        $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_MODERATE_USERS."]", 1, "Moderate Users")."&nbsp;&nbsp;".
                        $frm->checkbox("new_forum_permissions[".PHORUM_USER_ALLOW_FORUM_PROPERTIES."]", 1, "Edit Forum Properties");
        
            $arr[]="Add A Forum...";
            foreach($forums as $forum_id=>$forum){
                if(!isset($user["forum_permissions"][$forum_id]))
                    $arr[$forum_id]=$forum["name"];
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
                                $frm->checkbox("forum_permissions[$forum_id][".PHORUM_USER_ALLOW_FORUM_PROPERTIES."]", 1, "Edit Forum Properties", ($perms & PHORUM_USER_ALLOW_FORUM_PROPERTIES));
                
                    $frm->hidden("forums[$forum_id]", $forum_id);

                    $row=$frm->addrow($forums[$forum_id]["name"]."<br />".$frm->checkbox("delforum[$forum_id]", 1, "Delete"), $perm_frm);

                }     
            }

            $frm->show();

            echo "<br /><hr class=\"PhorumAdminHR\" /><br /><a name=\"groups\"></a>";

            $frm =& new PhorumInputForm ("", "post", "Update");
            
            $frm->hidden("user_id", $_REQUEST["user_id"]);

            $frm->hidden("module", "users");

            $frm->hidden("section", "groups");

            $extra_opts = "";
            // if its an admin, let the user know that the admin will be able to act as a moderator no matter what
            if ($user["admin"]){
                $row=$frm->addbreak("Edit Groups (Admins can act as a moderator of every group, regardless of these values)");
            }
            else{
                $row=$frm->addbreak("Edit Groups");
            }

            $groups= phorum_db_get_groups();
            $usergroups = phorum_user_get_groups($_REQUEST["user_id"]);

            $arr=array("Add A Group...");
            foreach($groups as $group_id=>$group){
                if(!isset($usergroups[$group_id]))
                    $arr[$group_id]=$group["name"];
            }
            
            if(count($arr)>1)
                $frm->addrow("Add A Group", $frm->select_tag("new_group", $arr));

            if(is_array($usergroups)){
                $group_options = array(PHORUM_USER_GROUP_REMOVE => "&lt; Remove User From Group &gt;", 
                        PHORUM_USER_GROUP_SUSPENDED => "Suspended",
                        PHORUM_USER_GROUP_UNAPPROVED => "Unapproved", 
                        PHORUM_USER_GROUP_APPROVED => "Approved", 
                        PHORUM_USER_GROUP_MODERATOR => "Group Moderator");
                foreach($usergroups as $group_id => $group_perm){
                    $group_info = phorum_db_get_groups($group_id);
                    $frm->hidden("groups[$group_id]", "$group_id");
                    $frm->addrow($group_info[$group_id]["name"], $frm->select_tag("group_perm[$group_id]", $group_options, $group_perm, $extra_opts));
                }     
            }
                
            $frm->show();

        } else {

            echo "User Not Found.";

        }

    }
    
?>
