<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
        // The "vroot" field is a virtual field for this form. It only
        // indicates that the folder has to be activated as a vroot.
        // In the data, the vroot indicates to what vroot a forum or
        // folder belongs. To make a certain folder a vroot folder, we
        // have to set the vroot field to the same value as the forum_id
        // field later on.
        if ($field == 'vroot') {
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
        $url = phorum_admin_build_url(array('module=default',"parent_id=".$folder['parent_id'],'okmsg='.urlencode($okmsg)));

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
    $folder = phorum_api_forums_by_forum_id(
        $folder_id, PHORUM_FLAG_INCLUDE_INACTIVE
    );
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

// If we are inheriting settings from a forum,
// then disable the inherited fields in the input.
$disabled_form_input = '';
if ($inherit_id != -1) {
    $disabled_form_input = 'disabled="disabled"';
}

// ----------------------------------------------------------------------
// Handle displaying the folder settings form
// ----------------------------------------------------------------------

if ($errors) {
    phorum_admin_error(join("<br/>", $errors));
}

require_once './include/admin/PhorumInputForm.php';

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

$parent_id_options = phorum_api_forums_get_parent_id_options($forum_id);
$frm->addrow(
    "Put this forum below folder",
    $frm->select_tag('parent_id', $parent_id_options, $parent_id)
);

$frm->addrow("Make this forum visible in the forum index?", $frm->select_tag("active", array("No", "Yes"), $active));

$row = $frm->addrow("Virtual Root for descending forums/folders", $frm->checkbox("vroot","1","enabled",($vroot)?1:0));
$frm->addhelp($row,
    "Virtual Root for descending forums/folders",
    "If you enable the virtual root feature for a folder, then this folder
     will act as a separate Phorum installation. The folder will not be
     visible in its parent folder anymore and if you visit the folder, it will
     behave as if it were a Phorum root folder. This way you can run
     multiple separated forums on a single Phorum installation.<br/><br/>
     The users will be able to access all virtual root folders, unless you
     use the permission system to setup different access rules."
);
if ($foreign_vroot > 0) {
    $frm->addrow(
        "This folder is in the Virtual Root of:",
        $folders[$foreign_vroot]
    );
}

$frm->addbreak("Inherit Folder Settings");

$inherit_id_options = phorum_api_forums_get_inherit_id_options($forum_id);
$row = $frm->addrow(
    "Inherit the settings below this option from",
    $frm->select_tag(
        "inherit_id", $inherit_id_options, $inherit_id
    ) . $add_inherit_text
);

$frm->addbreak("Display Settings");

$frm->addrow("Template", $frm->select_tag("template", phorum_get_template_info(), $template, $disabled_form_input));

$frm->addrow("Language", $frm->select_tag("language", phorum_get_language_info(), $language, $disabled_form_input));

phorum_hook("admin_editfolder_form", $frm, $forum_settings);

$frm->show();

?>
