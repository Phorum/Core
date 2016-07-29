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
require_once './include/api/system.php';

$errors = array();

// ----------------------------------------------------------------------
// Handle posted form data
// ----------------------------------------------------------------------

if (count($_POST))
{
    // Handling of checkboxes.
    if (!isset($_POST['pub_perms'])) $_POST['pub_perms'] = array();
    if (!isset($_POST['reg_perms'])) $_POST['reg_perms'] = array();
    if (!isset($_POST['allow_email_notify'])) $_POST['allow_email_notify'] = 0;

    // Build a forum data array based on the posted data.
    $forum = array();
    foreach ($_POST as $field => $value)
    {
        // Process permission bitmasks. These are stored in the form by means
        // of separate checkboxes per bit. Here we translate them into
        // a single permission value.
        if ($field == 'pub_perms' || $field == 'reg_perms')
        {
            $bits = empty($value) ? array() : $value;
            $bitmask = 0;
            foreach ($bits as $bit => $dummy) $bitmask |= $bit;
            $forum[$field] = $bitmask;
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

    if (empty($error)) {
        /*
         * [hook]
         *     admin_editforum_form_save_after_defaults
         *
         * [description]
         *     This hook is called whenever a forum is created/saved and passes
         *     the basic checks (i.e. no error is generated there). The raw
         *     <literal>$_POST</literal> request can be accessed and a new
         *     custom error can be generated.
         *
         *     At this stage, the <literal>$_POST</literal> is still
         *     accessible in it's (almost) original form.
         *
         * [category]
         *     Admin interface
         *
         * [when]
         *     Forum created or saved, passing basic phorum verification steps.
         *
         * [input]
         *     The $error variable (a single string message). The first hook
         *     being called always has an empty error (as the whole hook chain
         *     is only invoked in such a case), but each hook may generate an
         *     error which is passed on to other hooks in the chain. It is
         *     strongly advised that, once your hook gets called and it detects
         *     the input string containing an error (i.e. is non-zero string
         *     length), simply bail out and return the error instead of doing
         *     work and possible generating a new error. Only one error at a
         *     time can be passed to the end user through the whole chain.
         *
         * [output]
         *     Same as input. However, a non-zero length string signals to abort
         *     and <b>not</b> save any data!
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_admin_editforum_form_save_after_defaults ($error)
         *     {
         *         # Early bail out in case another hook generated already an error
         *         if (strlen($error) > 0) {
         *             return $error;
         *         }
         *         # Do your stuff, possible setting $error to a error string
         *         # for the user to be shown; or simply leave it untouched.
         *         return $error;
         *
         *     }
         *     </hookcode>
         */
        $error = phorum_api_hook("admin_editforum_form_save_after_defaults", $error);
    }

    // If there were no errors, then store the data in the database.
    if (empty($errors))
    {
        // Store default settings.
        if (defined('PHORUM_DEFAULT_OPTIONS'))
        {
            // Store the default settings in the database.
            phorum_api_forums_save($forum, PHORUM_FLAG_DEFAULTS);

            $url = phorum_admin_build_url(array('module=forum_defaults','okmsg='.rawurlencode('The default settings were successfully saved')), TRUE);

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

            // The message to show on the next page.
            $okmsg = "Forum \"{$forum['name']}\" was successfully saved";

            // The URL to redirect to.
            $url = phorum_admin_build_url(array('module=default',"parent_id=$forum[parent_id]", 'okmsg='.rawurlencode($okmsg)), TRUE);
        }

        phorum_api_redirect($url);
    }
    extract($forum);
}

// ----------------------------------------------------------------------
// Handle initializing the form for various cases
// ----------------------------------------------------------------------

// Initialize the form for editing an existing forum.
elseif (defined("PHORUM_EDIT_FORUM"))
{
    $forum_id = isset($_POST['forum_id']) ? $_POST['forum_id'] : $_GET['forum_id'];
    $forum = phorum_api_forums_by_forum_id(
        $forum_id, PHORUM_FLAG_INCLUDE_INACTIVE
    );
    extract($forum);
}
// Initialize the form for editing default settings.
elseif (defined("PHORUM_DEFAULT_OPTIONS"))
{
    extract($PHORUM["default_forum_options"]);
}
// Initialize the form for creating a new forum.
else
{
    $parent_id = $PHORUM['vroot'];
    if (!empty($_GET['parent_id'])) {
        $parent_id = (int) $_GET['parent_id'];
    }

    // Prepare a forum data array for initializing the form.
    $forum = phorum_api_forums_save(array(
        'forum_id'    => NULL,
        'folder_flag' => 0,
        'inherit_id'  => 0,
        'parent_id'   => $parent_id,
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

if (isset($_GET['okmsg'])) {
    phorum_admin_okmsg(htmlspecialchars($_GET['okmsg']));
}

require_once './include/admin/PhorumInputForm.php';
$frm = new PhorumInputForm ("", "post");

if (defined("PHORUM_DEFAULT_OPTIONS")) {
    $frm->hidden("module", "forum_defaults");
    $title = "Edit the default forum settings";
} elseif(defined("PHORUM_EDIT_FORUM")) {
    $frm->hidden("module", "editforum");
    $frm->hidden("forum_id", $forum_id);
    $title = "Edit settings for forum \"$name\" (Id: $forum_id)";
} else {
    $frm->hidden("module", "newforum");
    $title = "Create a new forum";
}

$frm->addbreak($title);

// Options that are only required when editing or creating a forum.
if (!defined("PHORUM_DEFAULT_OPTIONS"))
{
    $frm->addrow("Forum Title", $frm->text_box("name", $name, 30,50));

    $frm->addrow("Forum Description", $frm->textarea("description", $description, $cols=60, $rows=10, "style=\"width: 100%;\""), "top");

    $parent_id_options = phorum_api_forums_get_parent_id_options($forum_id);
    $frm->addrow(
        "Put this forum below folder",
        $frm->select_tag('parent_id', $parent_id_options, $parent_id)
    );

    if ($vroot > 0) {
        $frm->addrow(
            "This forum is in the Virtual Root of:",
            $folder_list[$vroot]
        );
    }

    $row = $frm->addrow(
        "Make this forum visible in the forum index?",
        $frm->select_tag("active", array("No", "Yes"), $active)
    );
    $frm->addhelp($row, "Make this forum visible in the forum index?",
        'If this option is set to "No", then this forum will not be visible
         in the forum index. This is mainly an option for temporarily keeping
         a forum hidden to your users (e.g. while you are creating and
         configuring a forum or for testing out features).<br/>
         <br/>
         <b>This is not a security option!</b><br/>
         <br/>
         A forum that is not visible is still accessible by all users
         that have at least read access for it. If you need a forum that is
         only accessible by some of your users, then use the permission system
         for that. Revoke all rights from both public and registered users in
         the forum settings and use group or user permissions to grant access
         to the users.'
    );

    // If we're inheriting settings from a different forum,
    // then disable the inherited fields in the input.
    $disabled_form_input = '';
    if ($inherit_id != -1) {
        $disabled_form_input = 'disabled="disabled"';
    }

    $frm->addbreak("Inherit Forum Settings");

    // First check if the settings for this forum are inherited by one or
    // more other forums and/or folders. Inherited inheritance is not
    // allowed, so if this is the case, choosing a forum to inherit from
    // is not allowed.
    $disabled_form_input_inherit = '';
    $add_inherit_text="";
    if ($forum_id) {
        $slaves = phorum_api_forums_by_inheritance($forum_id);
        if (!empty($slaves))
        {
            $disabled_form_input_inherit='disabled="disabled"';

            $add_inherit_text="<br />You cannot let this forum inherit its " .
                              "settings from another forum, because the " .
                              "following forums and or folders inherit from " .
                              "the current forum already:<br /><ul>\n";
            foreach($slaves as $id => $data) {
                array_shift($data['forum_path']);
                $edit_url = phorum_admin_build_url(array('module=edit'.($data['folder_flag'] ? 'folder' : 'forum'),"forum_id=$id"));
                $add_inherit_text .= "<li><a href=\"$edit_url\">" .
                                     implode(" / ", $data['forum_path']) .
                                     "</li>\n";
            }
            $add_inherit_text.="</ul>\n";
        }
    }

    $inherit_id_options = phorum_api_forums_get_inherit_id_options($forum_id);
    $row = $frm->addrow(
        "Inherit the settings below this option from",
        $frm->select_tag(
            "inherit_id", $inherit_id_options, $inherit_id,
            $disabled_form_input_inherit
        ) . $add_inherit_text
    );
}

$frm->addbreak("Moderation / Permissions");

$row=$frm->addrow("Moderate Messages", $frm->select_tag("moderation", array(PHORUM_MODERATE_OFF=>"Disabled", PHORUM_MODERATE_ON=>"Enabled"), $moderation, $disabled_form_input));

$frm->addhelp($row, "Moderate Messages", "This setting determines whether messages are visible to users immediately after they are posted.  If enabled, all messages will remain hidden until approved by a moderator.");

$frm->addrow("Email Messages To Moderators", $frm->select_tag("email_moderators", array(PHORUM_EMAIL_MODERATOR_OFF=>"Disabled", PHORUM_EMAIL_MODERATOR_ON=>"Enabled"), $email_moderators, $disabled_form_input));

$pub_perm_frm = $frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_READ."]", 1, "Read", $pub_perms & PHORUM_USER_ALLOW_READ, $disabled_form_input)."&nbsp;&nbsp;".
$frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply", $pub_perms & PHORUM_USER_ALLOW_REPLY, $disabled_form_input)."&nbsp;&nbsp;".
$frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics", $pub_perms & PHORUM_USER_ALLOW_NEW_TOPIC, $disabled_form_input)."<br />".
$frm->checkbox("pub_perms[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files", $pub_perms & PHORUM_USER_ALLOW_ATTACH, $disabled_form_input);

$frm->addrow("Public Anonymous Users", $pub_perm_frm);

$reg_perm_frm = $frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_READ."]", 1, "Read", $reg_perms & PHORUM_USER_ALLOW_READ, $disabled_form_input)."&nbsp;&nbsp;".
$frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_REPLY."]", 1, "Reply", $reg_perms & PHORUM_USER_ALLOW_REPLY, $disabled_form_input)."&nbsp;&nbsp;".
$frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_NEW_TOPIC."]", 1, "Create&nbsp;New&nbsp;Topics", $reg_perms & PHORUM_USER_ALLOW_NEW_TOPIC, $disabled_form_input)."<br />".
$frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_EDIT."]", 1, "Edit&nbsp;Their&nbsp;Posts", $reg_perms & PHORUM_USER_ALLOW_EDIT, $disabled_form_input)."&nbsp;&nbsp;".
$frm->checkbox("reg_perms[".PHORUM_USER_ALLOW_ATTACH."]", 1, "Attach&nbsp;Files", $reg_perms & PHORUM_USER_ALLOW_ATTACH, $disabled_form_input) . "<br/>".
$frm->checkbox('allow_email_notify', 1, 'Allow email notification for following topics', $allow_email_notify, $disabled_form_input);

$row=$frm->addrow("Registered Users", $reg_perm_frm);

$frm->addhelp($row, "Registered Users", "These are the permissions that apply to registered users. Note that these permissions can be overridden by permissions that were granted directly to the user or to a group to which a user belongs.");

$frm->addbreak("Display Settings");

$frm->addrow("Fixed Display-Settings (user can't override them)", $frm->select_tag("display_fixed", array("No", "Yes"), $display_fixed, $disabled_form_input));

$frm->addrow("Template", $frm->select_tag("template", phorum_api_template_list(TRUE), $template, $disabled_form_input));

$frm->addrow("Language", $frm->select_tag("language", phorum_api_lang_list(TRUE), $language, $disabled_form_input));

$frm->addrow("List View", $frm->select_tag("threaded_list", array("Flat", "Threaded"), $threaded_list, $disabled_form_input));
$frm->addrow("Read View", $frm->select_tag("threaded_read", array("Flat", "Threaded", "Hybrid"), $threaded_read, $disabled_form_input));
$frm->addrow("Reverse Threading", $frm->select_tag("reverse_threading", array("No", "Yes"), $reverse_threading, $disabled_form_input));

$frm->addrow("Move Threads On Reply", $frm->select_tag("float_to_top", array("No", "Yes"), $float_to_top, $disabled_form_input));

$frm->addrow("Message List Length (Flat Mode)", $frm->text_box("list_length_flat", $list_length_flat, 10, false, false, $disabled_form_input));
$frm->addrow("Message List Length (Threaded Mode, Nr. of Threads)", $frm->text_box("list_length_threaded", $list_length_threaded, 10, false, false, $disabled_form_input));

$frm->addrow("Read Page Length", $frm->text_box("read_length", $read_length, 10, false, false, $disabled_form_input));

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

list ($system_max_upload, $php_max_upload, $db_max_upload) =
    phorum_get_system_max_upload();
$max_size = phorum_api_format_filesize($system_max_upload);

$row=$frm->addrow("Max File Size In KB ($max_size maximum)", $frm->text_box("max_attachment_size", $max_attachment_size, 10, false, false, $disabled_form_input));
$frm->addhelp($row, "Max File Size", "This is the maximum that one uploaded file can be.  If you see a maximum here, that is the maximum imposed by either your PHP installation, database server or both.  Leaving this field as 0 will use this maximum.");

$frm->addrow("Max cumulative File Size In KB (0 for unlimited)", $frm->text_box("max_totalattachment_size", $max_totalattachment_size, 10, false, false, $disabled_form_input));

$frm->show();

?>

<script type="text/javascript">
//<![CDATA[

// Handle changes to the setting inheritance select list.
$PJ('select[name=inherit_id]').change(function updateInheritedFields()
{
    var inherit = $PJ('select[name=inherit_id]').val();

    // No inheritance. All fields will be made read/write.
    if (inherit == -1) {
        updateInheritedSettings(null);
    }
    // An inheritance option is selected. Retrieve the settings for
    // the selection option and update the form with those. All
    // inherited settings are made read only.
    else {
        Phorum.call({
            call: 'getforumsettings',
            forum_id: inherit,
            cache_id: 'forum_settings_' + inherit,
            onSuccess: function (data) {
                updateInheritedSettings(data);
            },
            onFailure: function (err) {
                alert("Could not retrieve inherited settings: " + err);
            }
        });
    }
});

function updateInheritedSettings(data)
{
    // Find the settings form.
    $PJ('input.input-form-submit').parents('form').each(function (idx, frm) {
        // Loop over all form fields.
        $PJ(frm).find('input[type!=hidden],textarea,select')
            .each(function (idx, f) {

                $f = $PJ(f);

                // Skip the form submit button.
                if ($f.hasClass('input-form-submit')) return;

                // SKip fields that are not inherited.
                if (f.name == 'name' ||
                    f.name == 'description' ||
                    f.name == 'parent_id' ||
                    f.name == 'active' ||
                    f.name == 'inherit_id') return;

                // When no data is provided, then we make the field read/write.
                if (!data)
                {
                    $PJ(f).removeAttr('disabled');
                }
                // Data is provided. Fill the default value and make the
                // field read only.
                else
                {
                    // Some browsers will not update the field when it
                    // is disabled. Therefor, we temporarily enable it here.
                    $PJ(f).removeAttr('disabled');

                    // Special handling for bit-wise permission fields.
                    var m = f.name.match(/^(\w+)\[(\d+)\]$/);
                    if (m) {
                        var checked = 0;
                        if (data[m[1]] !== undefined) {
                            if ((m[2] & data[m[1]]) == m[2]) {
                                checked = 1;
                            }
                        }
                        if (checked) {
                            $f.attr('checked', 'checked');
                        } else {
                            $f.removeAttr('checked');
                        }
                    }
                    // Handling for standard fields.
                    else {
                        if (data[f.name] !== undefined) {
                            $f.val(data[f.name]);
                        }
                    }

                    // Make the field read only.
                    $PJ(f).attr('disabled', 'disabled');
                }
            });
        });
}
// ]]>
</script>

