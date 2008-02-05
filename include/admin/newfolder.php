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

require_once('./include/api/forums.php');

$errors = array();

// ----------------------------------------------------------------------
// Handle posted form data
// ----------------------------------------------------------------------

if (count($_POST))
{
    // Build a folder data array based on the posted data.
    $folder = array();
    $enable_vroot = FALSE;
    foreach ($_POST as $field => $value)
    {
        // The inherit_id can be -1, in which case we need
        // to translate it into a NULL value for the back end.
        if ($field == 'inherit_id') {
            $folder[$field] = $value == -1 ? NULL : (int) $value;
        }
        // The "vroot" field is a virtual field for this form. It only
        // indicates that the folder has to be activated as a vroot.
        // In the data, the vroot indicates to what vroot a forum or
        // folder belongs. To make a certain folder a vroot folder, we
        // have to set the vroot field to the same value as the forum_id
        // field later on.
        elseif ($field == 'vroot') {
            $enable_vroot = TRUE;
        }
        // All other fields are simply copied.
        elseif (array_key_exists($field, $PHORUM['API']['folder_fields'])) {
          $folder[$field] = $value;
        }
    }

    // Was a title filled in for the folder?
    if (!defined('PHORUM_DEFAULT_OPTIONS') && trim($folder['name']) == '') {
        $errors[] = 'The "Title" field is empty. Please, fill in a title.';
    }

    // If there were no errors, then store the data in the database.
    if (empty($errors))
    {
        // Some statically assigned fields.
        $folder['folder_flag'] = 1;
        // For new folders.
        if (!defined('PHORUM_EDIT_FOLDER')) {
            $folder['forum_id'] = NULL;
        }

        // Store the forum data in the database.
        $newfolder = phorum_api_forums_save($folder);

        // Handle enabling and disabling vroot support.
        // Currently stored as a vroot folder?
        if ($newfolder['vroot'] == $newfolder['forum_id']) {
            // And requested to disable the vroot?
            if (! $enable_vroot) {
                phorum_api_forums_save(array(
                    'forum_id' => $newfolder['forum_id'],
                    'vroot'    => $newfolder['parent_id']
                ));
            }
        }
        // Currently not a vroot folder?
        else {
            // And requested to enable the vroot?
            if ($enable_vroot) {
                phorum_api_forums_save(array(
                    'forum_id' => $newfolder['forum_id'],
                    'vroot'    => $newfolder['forum_id']
                ));
            }
        }

        // The message to show on the next page.
        $okmsg = "Folder \"{$folder['name']}\" was successfully saved";

        // The URL to redirect to.
        $url = $PHORUM["admin_http_path"] .
               "?module=default" .
               "&parent_id=$folder[parent_id]" .
               "&okmsg=" . urlencode($okmsg);

        phorum_redirect_by_url($url);
        exit;
    }
}

// ----------------------------------------------------------------------
// Handle initializing the form for various cases
// ----------------------------------------------------------------------

// Initialize the form for editing an existing folder.
elseif (defined("PHORUM_EDIT_FOLDER"))
{
    $folder_id = isset($_POST['forum_id'])
               ? $_POST['forum_id'] : $_GET['forum_id'];
    $folder = phorum_api_forums_get($folder_id);
}

// Initialize the form for creating a new folder.
else
{
    // Prepare a folder data array for initializing the form.
    $folder = phorum_api_forums_save(array(
        'forum_id'    => NULL,
        'folder_flag' => 1,
        'inherit_id'  => 0,
        'name'        => ''
    ), PHORUM_FLAG_PREPARE);
}

extract($folder);

// The vroot parameter in the form is a checkbox, while the value in
// the database is a forum_id. We have to do a translation here.
if (isset($enable_vroot)) { // set when posting a form
    $vroot = $enable_vroot ? 1 : 0;
} elseif (!empty($forum_id) && $vroot == $forum_id) {
    $vroot = 1;
} else {
    $foreign_vroot = $vroot;
    $vroot = 0;
}

// If we're inheriting settings from a forum,
// then disable the inherited fields in the input.
$disabled_form_input = '';
if ($inherit_id !== NULL) {
    $disabled_form_input = 'disabled="disabled"';
} else {
    // NULL value for $inherit_id is stored in the form as -1.
    $inherit_id = -1;
}

// ----------------------------------------------------------------------
// Handle displaying the folder settings form
// ----------------------------------------------------------------------

if ($errors) {
    phorum_admin_error(join("<br/>", $errors));
}

require_once('./include/admin/PhorumInputForm.php');

$frm = new PhorumInputForm ("", "post");

// Edit an existing folder.
if (defined("PHORUM_EDIT_FOLDER"))
{
    $frm->hidden("module", "editfolder");
    $frm->hidden("forum_id", $forum_id);
    $title = "Edit existing folder";
}
// Create a new folder.
else
{
    $frm->hidden("module", "newfolder");
    $title="Add A Folder";
    $folders  = $folder_data;
}

$frm->addbreak($title);

$frm->addrow("Folder Title", $frm->text_box("name", $name, 30));

$frm->addrow("Folder Description", $frm->textarea("description", $description, $cols=60, $rows=10, "style=\"width: 100%;\""), "top");

$frm->addrow("Put this forum below folder", $frm->select_folder('parent_id', $parent_id, $folder_id));

$frm->addrow("Visible", $frm->select_tag("active", array("No", "Yes"), $active));

$frm->addrow("Virtual Root for descending forums/folders", $frm->checkbox("vroot","1","enabled",($vroot)?1:0));
if($foreign_vroot > 0) {
    $frm->addrow("This folder is in the Virtual Root of:",$folders[$foreign_vroot]);
}

$frm->addbreak("Inherit Folder Settings");

$forum_list = phorum_api_forums_get(NULL, NULL, NULL, NULL, PHORUM_FLAG_INHERIT_MASTERS);

// Remove the forum that we are currently handling from the list.
if (!empty($forum_id)) {
    unset($forum_list[$forum_id]);
}

// Prepare the forum names to show.
foreach ($forum_list as $id => $forum) {
    array_shift($forum['forum_path']);
    $forum_list[$id] = "Forum: " . implode("/", $forum['forum_path']);
}

// Add standard inheritance options.
$forum_list["0"]  = "The default forum settings";
$forum_list["-1"] = "No inheritance - I want to customize this forum's settings";

$row = $frm->addrow(
    "Inherit display settings from",
    $frm->select_tag(
        "inherit_id", $forum_list, $inherit_id
    ) . $add_inherit_text
);


$frm->addbreak("Display Settings");

$frm->addrow("Template", $frm->select_tag("template", phorum_get_template_info(), $template, $disabled_form_input));

$frm->addrow("Language", $frm->select_tag("language", phorum_get_language_info(), $language, $disabled_form_input));

phorum_hook("admin_editfolder_form", $frm, $forum_settings);

$frm->show();

?>
