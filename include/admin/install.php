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
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM_ADMIN")) return;

define("PHORUM_INSTALL", 1);

include_once("./include/api/base.php");
include_once("./include/api/user.php");

if(!phorum_db_check_connection()){
    echo "A database connection could not be established.  Please edit include/db/config.php.";
    return;
}

if(empty($_POST["step"])){
    $step = "start";
} else {
    $step = $_POST["step"];
}

// Setup some default options (we need to do this at this point,
// because the sanity checks need them).
$default_cache_dir = (substr(__FILE__, 0, 1)=="/") ? "/tmp" : "C:\\Windows\\Temp";

// Run sanity checks prior to installing Phorum. Here we do some
// checks to see if the environment is setup correctly for running
// Phorum.
if ($step == 'start' && !isset($_POST["sanity_checks_done"]))
{
    // Setup some fake environment data for the checks.
    $GLOBALS["PHORUM"]["default_forum_options"]["language"] = PHORUM_DEFAULT_LANGUAGE;
    $GLOBALS["PHORUM"]["cache"] = $default_cache_dir;
    $GLOBALS["PHORUM"]["real_cache"] = $default_cache_dir . "/install_tmp_sanity_check_cache_dir";

    // Load and run all available checks.
    include("./include/admin/sanity_checks.php");

    ?>
    <h1>Checking your system</h1>

    Prior to installing Phorum, your system will be checked to see
    if there are any problems that might prevent Phorum from running
    correctly. Below you will find the results of the checks. Warnings
    indicate that some problem needs attention, but that the problem
    will not keep Phorum from running. Errors indicate critical
    problems, which need to be fixed before running Phorum.
    <br/><br/>

    <script type="text/javascript">
    function toggle_sanity_info(check_id)
    {
        info_div = document.getElementById("sanity_info_" + check_id);
        info_link = document.getElementById("sanity_info_link_" + check_id);
        if (info_div && info_link) {
            if (info_div.style.display == "block") {
                info_div.style.display = "none";
                info_link.innerHTML = "show problem info";
            } else {
                info_div.style.display = "block";
                info_link.innerHTML = "hide problem info";
            }
        }
    }
    </script>
    <?php

    // Display the results of the sanity checks.
    $got_crit = false;
    $got_warn = false;
    foreach ($PHORUM["SANITY_CHECKS"]["CHECKS"] as $check)
    {
        if ($check["status"] == PHORUM_SANITY_SKIP) continue;
        if ($check["status"] == PHORUM_SANITY_CRIT) $got_crit = true;
        if ($check["status"] == PHORUM_SANITY_WARN) $got_warn = true;
        $display = $status2display[$check["status"]];
        print "<div style=\"padding: 10px; background-color:#f5f5f5;border: 1px solid #ccc; margin-bottom: 5px;\">";
        print "<div style=\"float:left; text-align:center; margin-right: 10px; width:100px; border: 1px solid #444; background-color:{$display[0]}; color:{$display[1]}\">{$display[2]}</div>";
        print '<b>' . $check["description"] . '</b>';

        if ($check["status"] != PHORUM_SANITY_OK)
        {
            print " (<a id=\"sanity_info_link_{$check["id"]}\" href=\"javascript:toggle_sanity_info('{$check["id"]}')\">show problem info</a>)";
            print "<div id=\"sanity_info_{$check["id"]}\" style=\"display: none; padding-top: 15px\">";
            print "<b>Problem:</b><br/>";
            print $check["error"];
            print "<br/><br/><b>Possible solution:</b><br/>";
            print $check["solution"];
            print "</div>";
        }
        print "</div>";
    }

    // Display navigation options, based on the check results.
    ?>
    <form method="post" action="<?php print $_SERVER["PHP_SELF"] ?>">
    <input type="hidden" name="module" value="install" />
    <?php
    if ($got_crit) {
        ?>
        <br/>
        One or more critical errors were encountered while checking
        your system. To see what is causing these errors and what you
        can do about them, click the "show problem info" links.
        Please fix these errors and restart the system checks.
        <br/><br/>
        <input type="submit" value="Restart the system checks" />
        <?php

    } elseif ($got_warn) {
        ?>
        <br/>
        One or more warnings were encountered while checking
        your system. To see what is causing these warnings and what you
        can do about them, click the "show problem info" links.
        Phorum probably will run without fixing the warnings, but
        it's a good idea to fix them anyway for ensuring optimal
        performance.
        <br/><br/>
        <input type="submit" value="Restart the system checks" />
        <input type="submit" name="sanity_checks_done" value="Continue without fixing the warnings -&gt;" />
        <?php
    } else {
        ?>
        <br/>
        No problems were encountered while checking your system.
        You can now continue with the Phorum installation.
        <br/><br/>
        <input type="submit" name="sanity_checks_done" value="Continue -&gt;" />
        <?php
    }

    ?>
    </form>
    <?php

    return;
}

include_once "./include/admin/PhorumInputForm.php";

if(count($_POST)){

    // THIS IS THE WORK STEP
    switch ($step){

        case "start":
            $step = "create_tables";
            break;

        case "create_tables":
            $step = "create_admin_user";
            break;


        case "create_admin_user":

            if(!empty($_POST["admin_user"]) && !empty($_POST["admin_pass"]) && !empty($_POST["admin_pass2"]) && !empty($_POST["admin_email"])){
                if($_POST["admin_pass"]!=$_POST["admin_pass2"]){
                    echo "The password fields do not match<br />";
                } elseif(phorum_api_user_authenticate(PHORUM_ADMIN_SESSION, $_POST["admin_user"],$_POST["admin_pass"])){
                    if($PHORUM["user"]["admin"]){
                        echo "Admin user already exists and has permissions.<br />";
                    } else {
                        echo "That user already exists but does not have admin permissions.<br />";
                    }
                } else {

                    // add the user
                    $user = array( "user_id"=>NULL, "username"=>$_POST["admin_user"], "password"=>$_POST["admin_pass"], "email"=>$_POST["admin_email"], "active"=>1, "admin"=>1 );

                    if(!phorum_api_user_save($user)){
                        echo "There was an error adding the user.<br />";
                    }

                    // set the default http_path so we can continue.
                    if(!empty($_SERVER["HTTP_REFERER"])) {
                        $http_path=$_SERVER["HTTP_REFERER"];
                    } elseif(!empty($_SERVER['HTTP_HOST'])) {
                        $http_path="http://".$_SERVER['HTTP_HOST'];
                        $http_path.=$_SERVER['PHP_SELF'];
                    } else {
                        $http_path="http://".$_SERVER['SERVER_NAME'];
                        $http_path.=$_SERVER['PHP_SELF'];
                    }
                    phorum_db_update_settings(array("http_path"=>dirname($http_path)));
                    phorum_db_update_settings(array("system_email_from_address"=>$_POST["admin_email"]));

                    $step = "modules";

                }
            } else {
                echo "Please fill in all fields.<br />";
            }

            break;

        case "modules":
            include_once "./include/admin/mods.php";
            break;
    }

}

// THIS IS THE OUTPUT STEP

if(isset($PHORUM["installed"]) && $PHORUM["installed"]) $step="done";

switch ($step){

    case "start":

        $frm = new PhorumInputForm ("", "post", "Continue ->");
        $frm->addbreak("Welcome to Phorum");
        $frm->addmessage("This wizard will setup Phorum on your server. First, the database will be prepared.  Phorum has already confirmed that it can connect to your database.  Press continue when you are ready.");
        $frm->hidden("module", "install");
        $frm->hidden("sanity_checks_done", "1");
        $frm->hidden("step", "start");
        $frm->show();

        break;

    case "create_tables":
        // ok, fresh install

        $err=phorum_db_create_tables();

        if($err){
            $message="Could not create tables, database said:<blockquote>$err</blockquote>";
            $message.="Your database user will need to have create table permissions.  If you know what the error is (tables already exist, etc.) and want to continue, click the button below.";
        } else {
            $message="Tables created.  Next we will create the administrator account. Press continue when ready.";

            $default_forum_options=array(
            'forum_id'=>0,
            'moderation'=>0,
            'email_moderators'=>0,
            'pub_perms'=>1,
            'reg_perms'=>15,
            'display_fixed'=>0,
            'template'=>PHORUM_DEFAULT_TEMPLATE,
            'language'=>PHORUM_DEFAULT_LANGUAGE,
            'threaded_list'=>0,
            'threaded_read'=>0,
            'reverse_threading'=>0,
            'float_to_top'=>1,
            'list_length_flat'=>30,
            'list_length_threaded'=>15,
            'read_length'=>30,
            'display_ip_address'=>0,
            'allow_email_notify'=>1,
            'check_duplicate'=>1,
            'count_views'=>2,
            'max_attachments'=>0,
            'allow_attachment_types'=>'',
            'max_attachment_size'=>0,
            'max_totalattachment_size'=>0,
            'vroot'=>0,
            );

            // insert the default module settings
            // hooks



            $mods_initial = array (
                'announcements' => 1,
                'bbcode' => 1,
                'editor_tools' => 1,
                'event_logging' => 0,
                'html' => 0,
                'smtp_mail' => 0,
                'modules_in_use' => 0,
                'replace' => 0,
                'smileys' => 1,
                'spamhurdles' => 0,
                'mod_tidy' => 0,
                'username_restrictions' => 0
            );

            $hooks_initial = array (
                'after_header' => array (
                    'mods' =>array ( 0 => 'announcements', 1 => 'editor_tools' ),
                    'funcs' => array ( 0 => 'phorum_show_announcements', 1 => 'phorum_mod_editor_tools_after_header' )
                ),
                'common' => array (
                    'mods' => array ( 0 => 'announcements', 1 => 'editor_tools' ),
                    'funcs' => array ( 0 => 'phorum_setup_announcements', 1 => 'phorum_mod_editor_tools_common' )
                ),
                'before_editor' => array (
                    'mods' => array ( 0 => 'editor_tools' ),
                    'funcs' => array ( 0 => 'phorum_mod_editor_tools_before_editor' )
                ),
                'tpl_editor_before_textarea' => array (
                    'mods' => array ( 0 => 'editor_tools' ),
                    'funcs' => array ( 0 => 'phorum_mod_editor_tools_tpl_editor_before_textarea' )
                ),
                'before_footer' => array (
                    'mods' => array ( 0 => 'editor_tools' ),
                    'funcs' => array ( 0 => 'phorum_mod_editor_tools_before_footer' )
                ),
                'format' => array (
                    'mods' => array ( 0 => 'smileys',  1 => 'bbcode' ),
                    'funcs' => array ( 0 => 'phorum_mod_smileys', 1 => 'phorum_bb_code' )
                ),
                'quote' => array (
                    'mods' => array ( 0 => 'bbcode' ),
                    'funcs' => array ( 0 => 'phorum_bb_code_quote' )
                )
            );

            // set initial settings
            $settings=array(
            "title" => "Phorum 5",
            "description" => "Congratulations!  You have installed Phorum 5!  To change this text, go to your admin, choose General Settings and change the description",
            "cache" => $default_cache_dir,
            "session_timeout" => "30",
            "short_session_timeout" => "60",
            "tight_security" => "0",
            "session_path" => "/",
            "session_domain" => "",
            "admin_session_salt" => microtime(),
            "cache_users" => "0",
            "cache_rss" => "0",
            "cache_newflags" => "0",
            "cache_messages" => "0",
            "cache_css" => "1",
            "cache_javascript" => "1",
            "use_cookies" => "1",
            "use_bcc" => "1",
            "use_rss" => "1",
            "default_feed" => "rss",
            "internal_version" => "" . PHORUM_SCHEMA_VERSION . "",
            "internal_patchlevel" => "" . PHORUM_SCHEMA_PATCHLEVEL . "",
            "PROFILE_FIELDS" => array(),
            "enable_pm" => "1",
            "display_name_source" => "username",
            "user_edit_timelimit" => "0",
            "enable_new_pm_count" => "1",
            "enable_dropdown_userlist" => "1",
            "enable_moderator_notifications" => "1",
            "show_new_on_index" => "1",
            "dns_lookup" => "1",
            "tz_offset" => "0",
            "user_time_zone" => "1",
            "user_template" => "0",
            "registration_control" => "1",
            "file_uploads" => "0",
            "file_types" => "",
            "max_file_size" => "",
            "file_space_quota" => "",
            "file_offsite" => "0",
            "system_email_from_name" => "",
            "hide_forums" => "1",
            "enable_new_pm_count" => "1",
            "track_user_activity" => "86400",
            "track_edits" => 0,
            "html_title" => "Phorum",
            "head_tags" => "",
            "cache_users" => 0,
            "cache_newflags" => 0,
            "cache_messages" => 0,
            "redirect_after_post" => "list",
            "reply_on_read_page" => 1,
            "status" => "normal",
            "use_new_folder_style" => 1,
            "default_forum_options" => $default_forum_options,
            "hooks"=> $hooks_initial,
            "mods" => $mods_initial,
            "mod_announcements" => array('module'=>'modsettings','mod'=>'announcements','forum_id'=>1,'pages'=>array('index'=>'1','list'=>'1'),'number_to_show'=>5,'only_show_unread'=>NULL,'days_to_show'=>0)

            );
            
            // check for the fileinfo extension 
            if(function_exists("finfo_open")) {
            	$settings['file_fileinfo_ext']=1;
            } else {
            	$settings['file_fileinfo_ext']=0;
            }

            phorum_db_update_settings($settings);

            // posting forum and test-message

            // create an announcements forum
            $forum=array(
            "name"=>'Announcements',
            "active"=>1,
            "description"=>'Read this forum first to find out the latest information.',
            "template"=>            $default_forum_options['template'],
            "folder_flag"=>0,
            "parent_id"=>0,
            "list_length_flat"=>    $default_forum_options['list_length_flat'],
            "list_length_threaded"=>$default_forum_options['list_length_threaded'],
            "read_length"=>         $default_forum_options['read_length'],
            "moderation"=>          $default_forum_options['moderation'],
            "threaded_list"=>       $default_forum_options['threaded_list'],
            "threaded_read"=>       $default_forum_options['threaded_read'],
            "float_to_top"=>        $default_forum_options['float_to_top'],
            "display_ip_address"=>  $default_forum_options['display_ip_address'],
            "allow_email_notify"=>  $default_forum_options['allow_email_notify'],
            "language"=>            $default_forum_options['language'],
            "email_moderators"=>    $default_forum_options['email_moderators'],
            "display_order"=>99,
            "edit_post"=>1,
            "pub_perms" =>  $default_forum_options['pub_perms'],
            "reg_perms" =>  $default_forum_options['reg_perms'],
            "template_settings" => "",
            "inherit_id"=>0,            
            "forum_path" => 'a:2:{i:0;s:8:"Phorum 5";i:1;s:13:"Announcements";}'
            );

            phorum_db_add_forum($forum);

            // create a test forum
            $forum=array(
            "name"=>'Test Forum',
            "active"=>1,
            "description"=>'This is a test forum.  Feel free to delete it or edit after installation, using the admin interface.',
            "template"=>            $default_forum_options['template'],
            "folder_flag"=>0,
            "parent_id"=>0,
            "list_length_flat"=>    $default_forum_options['list_length_flat'],
            "list_length_threaded"=>$default_forum_options['list_length_threaded'],
            "read_length"=>         $default_forum_options['read_length'],
            "moderation"=>          $default_forum_options['moderation'],
            "threaded_list"=>       $default_forum_options['threaded_list'],
            "threaded_read"=>       $default_forum_options['threaded_read'],
            "float_to_top"=>        $default_forum_options['float_to_top'],
            "display_ip_address"=>  $default_forum_options['display_ip_address'],
            "allow_email_notify"=>  $default_forum_options['allow_email_notify'],
            "language"=>            $default_forum_options['language'],
            "email_moderators"=>    $default_forum_options['email_moderators'],
            "display_order"=>0,
            "edit_post"=>1,
            "pub_perms" =>  $default_forum_options['pub_perms'],
            "reg_perms" =>  $default_forum_options['reg_perms'],
            "template_settings" => "",
            "inherit_id"=>0,
            "forum_path" => 'a:2:{i:0;s:8:"Phorum 5";i:2;s:10:"Test Forum";}'
            );

            $GLOBALS["PHORUM"]['forum_id']=phorum_db_add_forum($forum);
            $GLOBALS["PHORUM"]['vroot']=0;

            // create a test post
            $test_message=array(
            "forum_id" => $GLOBALS['PHORUM']["forum_id"],
            "thread" => 0,
            "parent_id" => 0,
            "author" => 'Phorum Installer',
            "subject" => 'Test Message',
            "email" => '',
            "ip" => '127.0.0.1',
            "user_id" => 0,
            "moderator_post" => 0,
            "closed" => 0,
            "status" => PHORUM_STATUS_APPROVED,
            "sort" => PHORUM_SORT_DEFAULT,
            "msgid" => '',
            "body" => "This is a test message. You can delete it after installation using the moderation tools. These tools will be visible in this screen if you log in as the administrator user that you created during install.\n\nPhorum 5 Team"
            );

            phorum_db_post_message($test_message);

            include_once ("./include/thread_info.php");

            phorum_update_thread_info($test_message["thread"]);

            phorum_db_update_forum_stats(true);

        }

        $frm = new PhorumInputForm ("", "post", "Continue ->");
        $frm->addbreak("Creating tables....");
        $frm->addmessage($message);
        $frm->hidden("step", "create_tables");
        $frm->hidden("module", "install");
        $frm->hidden("sanity_checks_done", "1");
        $frm->show();

        break;

    case "create_admin_user":

        $frm = new PhorumInputForm ("", "post");
        $frm->hidden("step", "create_admin_user");
        $frm->hidden("module", "install");
        $frm->hidden("sanity_checks_done", "1");
        $frm->addbreak("Creating An Administrator");
        $frm->addmessage("Please enter the following information.  This can be your user information or you can create an administrator that is separate from yourself.<br /><br />Note: If you are using a pre-existing authentication database, please enter the username and password of the admin user that already exists.");
        $admin_user = isset($_POST["admin_user"]) ? $_POST["admin_user"] : "";
        $admin_email = isset($_POST["admin_email"]) ? $_POST["admin_email"] : "";
        $frm->addrow("Admin User Name", $frm->text_box("admin_user", $admin_user, 30));
        $frm->addrow("Admin Email Address", $frm->text_box("admin_email", $admin_email, 30));
        $frm->addrow("Admin Password", $frm->text_box("admin_pass", "", 30, 0, true));
        $frm->addrow("(again)", $frm->text_box("admin_pass2", "", 30, 0, true));
        $frm->show();

        break;

    case "done":

    	$cont_url = phorum_admin_build_url('');
        phorum_db_update_settings( array("installed"=>1) );
        echo "The setup is complete.  You can now go to <a href=\"$cont_url\">the admin</a> and start making Phorum all your own.<br /><br /><strong>Here are some things you will want to look at:</strong><br /><br /><a href=\"$_SERVER[PHP_SELF]?module=settings\">The General Settings page</a><br /><br /><a href=\"$_SERVER[PHP_SELF]?module=mods\">Pre-installed modules</a><br /><br /><a href=\"docs/faq.txt\">The FAQ</a><br /><br /><a href=\"docs/performance.txt\">How to get peak performance from Phorum</a><br /><br /><strong>For developers:</strong><br /><br /><a href=\"docs/creating_mods.txt\">Module Creation</a><br /><br /><a href=\"docs/permissions.txt\">How Phorum permisssions work</a><br /><br /><a href=\"docs/CODING-STANDARDS\">The Phorum Team's codings standards</a>";

        break;

    case "sanity_checks":
        // try to figure out if we can write to the cache directory
        $message = "";
        error_reporting(0);
        $err = false;
        if ($fp = fopen($PHORUM["cache"] . "/phorum-install-test", "w+")) {
            unlink($PHORUM["cache"] . "/phorum-install-test");
        }
        else {
            // in this case the normal setting is wrong, so try ./cache
            $PHORUM["cache"] = "./cache";
            $settings = array("cache" => $PHORUM["cache"]);
            if (!phorum_db_update_settings($settings)) {
                $message .= "Database error updating settings.<br />";
                $err = true;
            }
            elseif ($fp = fopen($PHORUM["cache"] . "/phorum-install-test", "w+")) {
                unlink($PHORUM["cache"] . "/phorum-install-test");
            }
            else {
                $err = true;
            }

        }
        error_reporting(E_WARN);
        if ($message == "") {
            if($err){
                $message.="Your cache directory is not writable. Please change the permissions on '/cache' inside the Phorum directory to allow writing. In Unix, you may have to use this command: chmod 777 cache<br /><br />If you want to continue anyway and set a cache directory manually, press continue. Note that you must do this, Phorum will not work without a valid cache.";
            } else {
                $message.="Cache directory set.  Next we will create a user with administrator privileges.  Press continue when ready.";
            }
        }

        $frm = new PhorumInputForm ("", "post", "Continue ->");
        $frm->hidden("module", "install");
        $frm->hidden("sanity_checks_done", "1");
        $frm->addbreak("Checking cache....");
        $frm->addmessage($message);
        $frm->hidden("step", "modules");
        $frm->show();

        break;

    case "modules":

        include_once "./include/admin/mods.php";
        break;
}

?>
