<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2008  Phorum Development Team                              //
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

require_once('./include/format_functions.php');
require_once('./include/api/forums.php');
require_once('./include/upload_functions.php');
require_once('./include/admin/PhorumInputForm.php');

$errors = array();

// ----------------------------------------------------------------------
// Handle posted form data
// ----------------------------------------------------------------------

if (count($_POST))
{
    // Build a forum data array based on the posted data.
    $forum = array();
    foreach ($_POST as $field => $value)
    {
        // Create permission bitmasks. These are stored in the forum by means
        // of separate checkboxes per bit.
        if ($field == 'pub_perms' || $field == 'reg_perms')
        {
            $bits = empty($value) ? array() : $value;
            $bitmask = 0;
            foreach ($bits as $bit => $dummy) $bitmask |= $bit;
            $forum[$field] = $bitmask;
        }
        // The inherit_id can be the string "NULL", in which case we need
        // to translate it into a real NULL value.
        elseif ($field == 'inherit_id') {
            $forum[$field] = $value == 'NULL' ? NULL : (int) $value;
        }
        // All other fields are simply copied.
        elseif (array_key_exists($field, $PHORUM['API']['forum_fields'])) {
          $forum[$field] = $value;
        }
    }

    // Was a title filled in for the forum?
    if (!defined('PHORUM_DEFAULT_OPTIONS') && trim($forum['name']) == '') {
        $errors[] = 'The "Title" field is empty. Please, fill in a title.';
    }

    // If there were no errors, then store the data in the database.
    if (empty($errors))
    {
        // Store default settings.
        if (defined('PHORUM_DEFAULT_OPTIONS'))
        {
            // Store the default settings in the database.
            phorum_api_forums_save($forum, PHORUM_FLAG_DEFAULTS);

            // The URL to redirect to.
            $url = $PHORUM['admin_http_path']."?module=forum_defaults&saved=1";
        }
        // Create or update a forum.
        else
        {
            // Some statically assigned fields.
            $forum['folder_flag'] = 0;
            // For new forums.
            if (!defined('PHORUM_EDIT_FORUM')) {
                $forum['forum_id'] = NULL;
            }

            // Store the forum data in the database.
            phorum_api_forums_save($forum);

            // The URL to redirect to.
            $url = $PHORUM["admin_http_path"] .
                   "?module=default&parent_id=$forum[parent_id]";
        }

        phorum_redirect_by_url($url);
        exit;
    }

}

// ----------------------------------------------------------------------
// Handle initializing the form for various cases
// ----------------------------------------------------------------------

// Initialize the form for editing an existing forum.
elseif (defined("PHORUM_EDIT_FORUM"))
{
    $forum_id = isset($_POST['forum_id']) ? $_POST['forum_id'] : $_GET['forum_id'];
    $forum_settings = phorum_api_forums_get($forum_id);
    extract($forum_settings);
}
// Initialize the form for editing default settings.
elseif (defined("PHORUM_DEFAULT_OPTIONS"))
{
    extract($PHORUM["default_forum_options"]);
}
// Initialize the form for creating a new forum.
else
{
    // Prepare a forum data array for initializing the form.
    $forum = phorum_api_forums_save(array(
        'forum_id'    => NULL,
        'folder_flag' => 0,
        'inherit_id'  => 0,
        'name'        => ''
    ), PHORUM_FLAG_PREPARE);

    extract($forum);
}

// ----------------------------------------------------------------------
// Handle displaying the forum settings form
// ----------------------------------------------------------------------

if ($errors){
    phorum_admin_error(join("<br/>", $errors));
}

if (isset($_GET['saved'])) {
    phorum_admin_okmsg('The default settings were successfully saved');
}

$frm = new PhorumInputForm ("", "post");

if (defined("PHORUM_DEFAULT_OPTIONS")) {
    $frm->hidden("module", "forum_defaults");
    $title = "Edit the default forum settings";
} elseif(defined("PHORUM_EDIT_FORUM")) {
    $frm->hidden("module", "editforum");
    $frm->hidden("forum_id", $forum_id);
    $title = "Edit settings for forum \"$name\" ($forum_id)";
} else {
    $frm->hidden("module", "newforum");
    $title = "Create a new forum";
}

$frm->addbreak($title);

if (!defined("PHORUM_DEFAULT_OPTIONS"))
{
    $frm->addrow("Forum Title", $frm->text_box("name", $name, 30,50));

    $frm->addrow("Forum Description", $frm->textarea("description", $description, $cols=60, $rows=10, "style=\"width: 100%;\""), "top");

    $folder_list=phorum_get_folder_info();
    $frm->addrow("Folder", $frm->select_tag("parent_id", $folder_list, $parent_id));
    if($vroot > 0) {
        $frm->addrow("This folder is in the Virtual Root of:",$folder_list[$vroot]);
    }


    $frm->addrow("Visible", $frm->select_tag("active", array("No", "Yes"), $active));

    // Edit + inherit_id exists
    if(defined("PHORUM_EDIT_FORUM") && strlen($inherit_id)>0 ) {

        if($inherit_id!=0){
            $forum_settings_inherit = phorum_db_get_forums($inherit_id);
        }
        // inherit_forum not exists
        if( $inherit_id==0 || isset($forum_settings_inherit[$inherit_id]) ) {
            $disabled_form_input="disabled=\"disabled\"";
        } else {
            $inherit_id ="0";
            unset($forum_settings_inherit);
        }
    } else {
        unset($disabled_form_input);
    }

    $frm->addbreak("Inherit Forum Settings");

    $forum_list=phorum_get_forum_info(1);

    $forum_list["0"] ="Use Default Forum Settings";
    $forum_list["NULL"] ="None - I want to customize this forum's settings";

    // Remove this Forum
    if($forum_id>0){
        unset($forum_list[$forum_id]);
    }

    $dbforums=phorum_db_get_forums();

    // remove forums that inherit
    foreach($dbforums as $dbforum_id=>$forum){
        if($forum["inherit_id"] !== NULL){
            unset($forum_list[$dbforum_id]);
        }
    }

    // Check for Slaves
    if( intval($forum_id) ) {

        $forum_inherit_settings=phorum_db_get_forums(false,false,false,intval($forum_id));
        if( count($forum_inherit_settings)>0 ) {
            $disabled_form_input_inherit="disabled=\"disabled\"";
        }
    }

    // set to NULL if inherit is disabled
    if($inherit_id=="" && $inherit_id!==0) $inherit_id="NULL";

    $add_inherit_text="";
    if(!empty($disabled_form_input_inherit)) {
        $add_inherit_text="<br />You can't inherit from another forum as these forums inherit from the current forum already:<br /><ul>\n";
        foreach($forum_inherit_settings as $set_id => $set_data) {
            $add_inherit_text.="<li>".$set_data['name']." ( Id: $set_id ) </li>\n";
        }
        $add_inherit_text.="</ul>\n";
    }

    $row=$frm->addrow("Inherit Settings from Forum", $frm->select_tag("inherit_id", $forum_list, $inherit_id, $disabled_form_input_inherit).$add_inherit_text);

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

$row = $frm->addrow("Allow Email Notification for following topics", $frm->select_tag("allow_email_notify", array("No", "Yes"), $allow_email_notify, $disabled_form_input));
$frm->addhelp($row, "Allow Email Notification", "This option determines if it is possible for users to use email notification when following topics within this forum.<br/><br/>This does not only apply to enabling email notification at post time, but it also applies to clicking on \"".$PHORUM["DATA"]["LANG"]["FollowThread"]."\" from the message read page and to managing subscriptions from the user control center.");

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

$frm->addrow("List View", $frm->select_tag("threaded_list", array("Flat", "Threaded"), $threaded_list, $disabled_form_input));
$frm->addrow("Read View", $frm->select_tag("threaded_read", array("Flat", "Threaded", "Hybrid"), $threaded_read, $disabled_form_input));
$frm->addrow("Reverse Threading", $frm->select_tag("reverse_threading", array("No", "Yes"), $reverse_threading, $disabled_form_input));

$frm->addrow("Move Threads On Reply", $frm->select_tag("float_to_top", array("No", "Yes"), $float_to_top, $disabled_form_input));

$frm->addrow("Message List Length (Flat Mode)", $frm->text_box("list_length_flat", $list_length_flat, 10, false, false, $disabled_form_input));
$frm->addrow("Message List Length (Threaded Mode, Nr. of Threads)", $frm->text_box("list_length_threaded", $list_length_threaded, 10, false, false, $disabled_form_input));

$frm->addrow("Read Page Length", $frm->text_box("read_length", $read_length, 10, false, false, $disabled_form_input, $disabled_form_input));

$frm->addrow("Display IP Addresses <small>(note: admins always see it)</small>", $frm->select_tag("display_ip_address", array("No", "Yes"), $display_ip_address, $disabled_form_input));

$frm->addrow("Count views", $frm->select_tag("count_views", array(0 => "No", 1 => "Yes, show views added to subject", 2 => "Yes, show views as extra column"), $count_views, $disabled_form_input));

$row = $frm->addrow("Count views per thread for non-threaded list views", $frm->select_tag("count_views_per_thread", array(0 => "No", 1 => "Yes"), $count_views_per_thread, $disabled_form_input));
$frm->addhelp($row, "Count views per thread for non-threaded list",
    "By default, Phorum only counts views per message. While this is okay
     for a forum that runs in threaded view (since there you will always
     show only one message at a time), it might not work well for forums
     that run in a non-threaded view (there only one message will get
     its view count updated, although multiple messages might show).
     Additionally, if the list view is flat and the read view is threaded, the
     view count on the list view will only show how often the first message
     in the thread was viewed.<br/>
     <br/>
     With this option enabled, a separate view counter will be updated
     for the full thread when viewing any of the read pages for that thread.
     For non-threaded list views, this counter will then be used as the view
     count for the thread. Note that this does require an extra SQL query
     to update the separate counter, so on very busy servers you might not
     want to enable this option.");

$frm->addbreak("Posting Settings");

$frm->addrow("Check for Duplicates", $frm->select_tag("check_duplicate", array("No", "Yes"), $check_duplicate, $disabled_form_input));

$frm->addbreak("Attachment Settings");

$frm->addrow("Number Allowed (0 to disable)", $frm->text_box("max_attachments", $max_attachments, 10, false, false, $disabled_form_input));

$frm->addrow("Allowed Files (eg: gif;jpg;png, empty for any)", $frm->text_box("allow_attachment_types", $allow_attachment_types, 10, false, false, $disabled_form_input));

$system_max_upload = phorum_get_system_max_upload();
$max_size = phorum_filesize($system_max_upload[0]);

$row=$frm->addrow("Max File Size In KB ($max_size maximum)", $frm->text_box("max_attachment_size", $max_attachment_size, 10, false, false, $disabled_form_input));
$frm->addhelp($row, "Max File Size", "This is the maximum that one uploaded file can be.  If you see a maximum here, that is the maximum imposed by either your PHP installation, database server or both.  Leaving this field as 0 will use this maximum.");

$frm->addrow("Max cumulative File Size In KB (0 for unlimited)", $frm->text_box("max_totalattachment_size", $max_totalattachment_size, 10, false, false, $disabled_form_input));

$frm->show();

?>
