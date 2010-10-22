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

// This script can initially be called in multiple ways to indicate what
// type of posting mode will be used. The parameters are:
//
// 1) The forum id.
//
// 2) The mode to use. Possibilities are:
//
//    - post        Post a new message (default if no mode is issued)
//    - edit        User edit of an already posted message
//    - moderation  Moderator edit of an already posted message
//    - reply       Reply to a message
//    - quote       Reply to a message, with quoting of the original message
//
// 3) If edit, moderation or reply is used: the message id.
//
// Examples:
// http://yoursite/phorum/posting.php?10,quote,15
// http://yoursite/phorum/posting.php?10,edit,20
// http://yoursite/phorum/posting.php?10,post
//
// This script can also be included in another page (for putting the editor
// screen inline in a page), by setting up the $PHORUM["postingargs"] before
// including:
//
// $PHORUM["postingargs"]["as_include"] any true value, to flag included state
// $PHORUM["postingargs"][0] the forum id
// $PHORUM["postingargs"][1] the mode to use (post,reply,quote,edit,moderation)
// $PHORUM["postingargs"][2] the message id to work with (omit for "post")
//

// ----------------------------------------------------------------------
// Basic setup and checks
// ----------------------------------------------------------------------

if (! defined('phorum_page')) {
    define('phorum_page', 'post');
}

include_once("./common.php");
include_once("include/format_functions.php");

// CSRF protection: we do not accept posting to this script,
// when the browser does not include a Phorum signed token
// in the request.
$posting_token = phorum_check_posting_token('post');

// Check if the Phorum is in read-only mode.
if(isset($PHORUM["status"]) && $PHORUM["status"]==PHORUM_MASTER_STATUS_READ_ONLY
   && empty($PHORUM['user']['admin']) ) {
    if(!(isset($PHORUM["postingargs"]["as_include"]) && $PHORUM["postingargs"]["as_include"])){
        phorum_build_common_urls();
        // Only show header and footer when not included in another page.
        phorum_output("message");
    }
    return;
}


// No forum id was set. Take the user back to the index.
if(!isset($PHORUM["forum_id"])){
    $dest_url = phorum_get_url(PHORUM_INDEX_URL);
    phorum_redirect_by_url($dest_url);
    exit();
}
// Somehow we got to a folder in posting.php. Take the
// user back to the folder.
if($PHORUM["folder_flag"]) {
    $dest_url = phorum_get_url(PHORUM_INDEX_URL, $PHORUM["forum_id"]);
    phorum_redirect_by_url($dest_url);
    exit();
}

// ----------------------------------------------------------------------
// Definitions
// ----------------------------------------------------------------------

// A list of valid posting modes.
$valid_modes = array(
    "post",       // Post a new message
    "reply",      // Post a reply to a message
    "quote",      // Post a reply with quoting of the message replied to
    "edit",       // Edit a message
    "moderation", // Edit a message in moderator modus
);

// Form field configuration:
// -------------------------
//
// Configuration that we use for fields that we use in the editor form.
// The format for the array elements is:
//
// [0] The type of field. One of: string, integer, boolean, array.
// [1] Whether the value must be included as a hidden form field
//     This is used for identifying values which are always implemented
//     as hidden form fields.
// [2] Whether the field is read-only or not. If a field is marked to be
//     read-only, then the posting scripts will always use the field data
//     that is stored in the database for the edited message, regardless
//     what field data the client sent. Within the editing process,
//     this parameter can be changed to make the field writable.
//     (for example if a moderator is editing a message, some fields
//     become writable).
//     Put otherwise: client side read-only, server side read-only.
// [3] Whether to sign the field data. If this field is set to a true
//     value, then the data that is sent to the user is signed by Phorum.
//     When the data is sent back to Phorum, the signature is checked, to
//     see if the data did not change. This can be used for preventing
//     tampering with form data for fields that cannot be edited by the
//     user, but which can be edited by the Phorum software and modules.
//     Put otherwise: client side read-only, server side writable.
// [4] A default value to initialize the form field with.
//
// Common combinations for fields 1, 2 and 3:
//
// hidden r/o   signed   Description
// ---------------------------------------------------------------------------
// false  false false    A standard field that can always be edited.
//                       Typically, fields like subject and body use this.
// true   true  false    Totally read-only fields that are put as hidden
//                       fields in the message form. One could argue that
//                       these fields could be left out of the form
//                       completely, because the scripts will override this
//                       data with actual data from the database.
// false  true  false    Totally read-only fields that are not put in
//                       hidden fields in the message form. The templates
//                       might still display the field data.
// true   false true     Fields for which the data is put signed in hidden
//                       fields. These fields can be used for safely
//                       maintaining state between requests, by putting the
//                       state data directly in the form. The signing prevents
//                       tampering with the data by the user. An example
//                       field for this setup is the "meta" field, which
//                       carries the message's meta data. The user cannot
//                       directly change this field's data, but Phorum and
//                       modules can.
//
$PHORUM["post_fields"] = array(
# field name              data type  hidden r/o    signed default
#---------------------------------------------------------------
"message_id"     => array("integer", true,  false, true,  0),
"user_id"        => array("integer", true,  true,  false, 0),
"datestamp"      => array("string",  true,  true,  false, ''),
"status"         => array("integer", false, true,  false, 0),
"author"         => array("string",  false, true,  false, ''),
"email"          => array("string",  false, true,  false, ''),
"subject"        => array("string",  false, false, false, ''),
"body"           => array("string",  false, false, false, ''),
"forum_id"       => array("integer", true,  true,  false, $PHORUM["forum_id"]),
"thread"         => array("integer", true,  true,  false, 0),
"parent_id"      => array("integer", true,  true,  false, 0),
"allow_reply"    => array("boolean", false, true,  false, 1),
"special"        => array("string",  false, true,  false, ''),
"subscription"   => array("string",  false, false, false, 0),
"show_signature" => array("boolean", false, false, false, 0),
"attachments"    => array("array",   true,  false, true,  array()),
"meta"           => array("array",   true,  false, true,  array()),
"thread_count"   => array("integer", true,  true,  false, 0),
"mode"           => array("string",  true,  true,  false, ''),
);

// Indices for referencing the fields in $post_fields.
define("pf_TYPE",     0);
define("pf_HIDDEN",   1);
define("pf_READONLY", 2);
define("pf_SIGNED",   3);
define("pf_INIT",     4);

// Definitions for a clear $apply_readonly parameter in
// the function phorum_posting_merge_db2form().
define("ALLFIELDS", false);
define("READONLYFIELDS", true);

// ----------------------------------------------------------------------
// Gather information about the editor state and start processing
// ----------------------------------------------------------------------

/*
 * [hook]
 *     posting_init
 *
 * [description]
 *     This hook can be used for doing modifications to the environment of the
 *     posting scripts at an early stage. One of the intended purposes of this
 *     hook is to give mods a chance to change the configuration of the posting
 *     fields in <literal>$PHORUM["post_fields"]</literal>.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     Right after the <filename>posting.php</filename> script's configuration
 *     setup and before starting the posting script processing.
 *
 * [input]
 *     None
 *
 * [output]
 *     None
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_posting_init()
 *     {
 *         global $PHORUM;
 *
 *         //add the default, descriptive text to the message body
 *         $PHORUM["post_fields"]["body"][pf_INIT] = $PHORUM["DATA"]["LANG"]["mod_foo"]["default_body_text"];
 *
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["posting_init"]))
    phorum_hook("posting_init", "");

// Is this an initial request?
$initial = ! isset($_POST["message_id"]);

// If templates use <input type="image" name="foo" ...>, then the name
// parameter will be sent as "foo_x" and "foo_y" by some browsers (to
// indicate where the image was clicked). Rewrite that kind of form
// field data.
foreach (array("finish", "cancel", "preview") as $field) {
    if (!isset($_POST[$field]) && isset($_POST[$field.'_x'])) {
        $_POST[$field] = $_POST[$field.'_x'];
    }
}

// Is finish, cancel or preview clicked?
$finish  = (! $initial && isset($_POST["finish"]));
$cancel  = (! $initial && isset($_POST["cancel"]));
$preview = (! $initial && isset($_POST["preview"]));

// Do we already have postingargs or do we use the global args?
if (! isset($PHORUM["postingargs"])) {
    $PHORUM["postingargs"] = $PHORUM["args"];
}

// The template to load in the end.
$PHORUM["posting_template"] = "posting";

// Find out what editing mode we're running in.
if ($initial) {
    $mode = isset($PHORUM["postingargs"][1]) ? $PHORUM["postingargs"][1] : "post";

    // Quote may also be passed as a phorum parameter (quote=1).
    if ($mode == "reply" && isset($PHORUM["postingargs"]["quote"]) && $PHORUM["postingargs"]["quote"]) {
        $mode = "quote";
    }

} else {
    if (! isset($_POST["mode"])) trigger_error(
        "Missing parameter \"mode\" in request", E_USER_ERROR
    );
    $mode = $_POST["mode"];
}
if (! in_array($mode, $valid_modes)) trigger_error(
    "Illegal mode issued: " . htmlspecialchars($mode), E_USER_ERROR
);

// Find out if we are detaching an attachment.
// If we are, $do_detach will be set to the attachment's file_id.
$do_detach = false;
foreach ($_POST as $var => $val) {
    if (substr($var, 0, 7) == "detach:") {
        $do_detach = substr($var, 7);
    }
}

// Check if the user uploads an attachment. We remove file uploads
// with no name set, because that simply means the user did not select
// a file to upload. Not an error condition in this case.
foreach ($_FILES as $key => $val) {
    if (!isset($val["name"]) || $val["name"] == "") {
        unset($_FILES[$key]);
    }
}
$do_attach = count($_FILES) ? true : false;

// Set all our URL's
phorum_build_common_urls();
$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url(PHORUM_POSTING_ACTION_URL);

// Keep track of errors.
$PHORUM["DATA"]["ERROR"] = null;

// Do things that are specific for first time or followup requests.
if ($initial) {
    include("./include/posting/request_first.php");
} else {
    include("./include/posting/request_followup.php");
}

// Store the posting mode in the form parameters, so we can remember
// the mode throughout the editing cycle (for example to be able to
// create page titles which match the editing mode).
$PHORUM["DATA"]["MODE"] = $mode;

// Set the page title, description and breadcrumbs accordingly
switch($mode){
    case "post":
        $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["StartNewTopic"];
        $PHORUM["DATA"]["DESCRIPTION"] = "";
        $PHORUM['DATA']['BREADCRUMBS'][] = array(
            'URL'  => '',
            'TEXT' => $PHORUM['DATA']['LANG']['NewTopic']
        );
        break;
    case "moderation":
    case "edit":
        $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["EditMessage"];
        $PHORUM["DATA"]["DESCRIPTION"] = "";
        $PHORUM['DATA']['BREADCRUMBS'][] = array(
            'URL'  => '',
            'TEXT' => $PHORUM['DATA']['LANG']['EditMessage']
        );
        break;
}

// ----------------------------------------------------------------------
// Permission and ability handling
// ----------------------------------------------------------------------

// Make a descision on what posting mode we're really handling, based on
// the data that we have. The posting modes "reply" and "quote" will
// both be called "reply" from here. Modes "edit" and "moderation" will
// be called "edit" from here. The exact editor behaviour for editing is
// based on the user's permissions, not on posting mode.
$mode = "post";
if ($message["message_id"]) {
    $mode = "edit";
} elseif ($message["parent_id"]) {
    $mode = "reply";
}

// Do ban list checks. Only check the bans on entering and
// on finishing up. No checking is needed on intermediate requests.
if ($initial || $finish || $preview) {
    include("./include/posting/check_banlist.php");
}

// Determine the abilities that the current user has.
// Is the forum running in a moderated state?
$PHORUM["DATA"]["MODERATED"] =
    $PHORUM["moderation"] == PHORUM_MODERATE_ON &&
    !phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

// Does the user have administrator permissions?
$PHORUM["DATA"]["ADMINISTRATOR"] = $PHORUM["user"]["admin"];

// Does the user have moderator permissions?
$PHORUM["DATA"]["MODERATOR"] =
    phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES);

// Ability: Do we allow attachments?
$PHORUM["DATA"]["ATTACHMENTS"] = $PHORUM["max_attachments"] > 0 && phorum_api_user_check_access(PHORUM_USER_ALLOW_ATTACH);

// What options does this user have for a message?
$PHORUM["DATA"]["OPTION_ALLOWED"] = array(
    "sticky"        => FALSE, // Sticky flag for message sorting
    "allow_reply"   => FALSE, // Replies in the thread
    "subscribe"     => FALSE, // Subscribing to a thread
    "subscribe_mail"=> FALSE, // Subscribing to a thread mail notify
);

// Subscribing to threads for new messages by authenticated users or for
// editing messages posted by authenticated users (in which case the
// thread subscription for the user that posted the message can be
// updated).
if ((($mode == "post" || $mode == "reply") && $PHORUM["DATA"]["LOGGEDIN"]) ||
    ($mode == "edit" && !empty($message["user_id"]))) {
    $PHORUM["DATA"]["OPTION_ALLOWED"]["subscribe"] = TRUE;
    $PHORUM["DATA"]["OPTION_ALLOWED"]["subscribe_mail"] =
        !empty($PHORUM['allow_email_notify']) ? TRUE : FALSE;
}

// For moderators and administrators.
if (($PHORUM["DATA"]["MODERATOR"] || $PHORUM["DATA"]["ADMINISTRATOR"]) && $message["parent_id"] == 0) {
    $PHORUM["DATA"]["OPTION_ALLOWED"]["sticky"] = true;
    $PHORUM["DATA"]["OPTION_ALLOWED"]["allow_reply"] = true;
}

// Whether the user is allowed to change the author.
$PHORUM["DATA"]["OPTION_ALLOWED"]["edit_author"] = false;
// Allowed if author was made a read/write field.
if (!$PHORUM["post_fields"]["author"][pf_READONLY]) {
    $PHORUM["DATA"]["OPTION_ALLOWED"]["edit_author"] = true;
} else {
    // Allowed if a moderator edits a message for an anonymous user.
    if ($mode == "edit" && empty($message["user_id"])) {
        if ($PHORUM["DATA"]["MODERATOR"]) {
            $PHORUM["DATA"]["OPTION_ALLOWED"]["edit_author"] = true;
        }
    // Allowed if an anonymous user posts a new message or a reply.
    } else {
        if (! $PHORUM["DATA"]["LOGGEDIN"]) {
            $PHORUM["DATA"]["OPTION_ALLOWED"]["edit_author"] = true;
        }
    }
}

/*
 * [hook]
 *     posting_permissions
 *
 * [description]
 *     This hook can be used for setting up custom abilities and permissions for
 *     users, by updating the applicable fields in 
 *     <literal>$GLOBALS["PHORUM"]["DATA"]</literal> (e.g. for giving certain
 *     users the right to make postings sticky, without having to make the full
 *     moderator for a forum).<sbr/>
 *     <sbr/>
 *     Read the code in <filename>posting.php</filename> before this hook is
 *     called to find out what fields can be used.<sbr/>
 *     <sbr/>
 *     Beware: Only use this hook if you know what you are doing and understand
 *     Phorum's editor permission code. If used wrong, you can open up security
 *     holes in your Phorum installation!
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In <filename>posting.php</filename> right after Phorum has determined all
 *     abilities that apply to the logged in user.
 *
 * [input]
 *     None
 *
 * [output]
 *     None
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_posting_permissions()
 *     {
 *         global $PHORUM;
 *
 *         // get the previously stored id for the "sticky_allowed" group
 *         $mod_foo_group_id = $PHORUM["mod_foo"]["sticky_allowed_group_id"];
 *
 *         // allow creating sticky posts for users in the "sticky_allowed"
 *         // group, if the option has not already been enabled.
 *         if (!$PHORUM["DATA"]["OPTION_ALLOWED"]["sticky"])
 *             $PHORUM["DATA"]["OPTION_ALLOWED"]["sticky"] = phorum_api_user_check_group_access (PHORUM_USER_GROUP_APPROVED, $mod_foo_group_id);
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["posting_permissions"]))
    phorum_hook("posting_permissions");

// Show special sort options in the editor? These only are
// honoured for the thread starter messages, so we check the
// parent_id for that.
$PHORUM["DATA"]["SHOW_SPECIALOPTIONS"] =
    $message["parent_id"] == 0 &&
    $PHORUM["DATA"]["OPTION_ALLOWED"]["sticky"];

// Show special sort options or allow_reply in the editor?
$PHORUM["DATA"]["SHOW_THREADOPTIONS"] =
    $PHORUM["DATA"]["SHOW_SPECIALOPTIONS"] ||
    $PHORUM["DATA"]["OPTION_ALLOWED"]["allow_reply"];

// Set extra writeable fields, based on the user's abilities.
if (isset($PHORUM["DATA"]["ATTACHMENTS"]) && $PHORUM["DATA"]["ATTACHMENTS"]) {
    // Keep it as a hidden field.
    $PHORUM["post_fields"]["attachments"][pf_READONLY] = false;
}
if (isset($PHORUM["DATA"]["MODERATOR"]) && $PHORUM["DATA"]["MODERATOR"]) {
    if (! $message["user_id"]) {
        $PHORUM["post_fields"]["author"][pf_READONLY] = false;
        $PHORUM["post_fields"]["email"][pf_READONLY] = false;
    }
}
if (isset($PHORUM["DATA"]["SHOW_SPECIALOPTIONS"]) && $PHORUM["DATA"]["SHOW_SPECIALOPTIONS"]) {
    $PHORUM["post_fields"]["special"][pf_READONLY] = false;
}
if (isset($PHORUM["DATA"]["OPTION_ALLOWED"]["allow_reply"]) && $PHORUM["DATA"]["OPTION_ALLOWED"]["allow_reply"]) {
    $PHORUM["post_fields"]["allow_reply"][pf_READONLY] = false;
}

// Check permissions and apply read-only data.
// Only do this on entering and on finishing up.
// No checking is needed on intermediate requests.
if ($initial || $finish) {
    include("./include/posting/check_permissions.php");
    if ($PHORUM["posting_template"] == 'message' && empty($PHORUM["postingargs"]["as_include"])) {
        return phorum_output('message');
    }
}

// Do permission checks for attachment management.
if ($do_attach || $do_detach) {
    if (! $PHORUM["DATA"]["ATTACHMENTS"]) {
        $PHORUM["DATA"]["ERROR"] = $PHORUM["DATA"]["LANG"]["AttachNotAllowed"];
    }
}

// ----------------------------------------------------------------------
// Perform actions
// ----------------------------------------------------------------------

/*
 * [hook]
 *     posting_custom_action
 *
 * [description]
 *     This hook can be used by modules to handle (custom) data coming from the
 *     posting form. The module is allowed to change the data that is in the
 *     input message. When a module needs to change the meta data for a message,
 *     then this is the designated hook for that task.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In <filename>posting.php</filename> right after all the initialization
 *     tasks are done and just before the posting script starts its own action
 *     processing.
 *
 * [input]
 *     Array containing message data.
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_posting_custom_actions ($message)
 *     {
 *         global $PHORUM;
 *
 *         // for some reason, create an MD5 signature for the original body
 *         if (!empty($message["body"])
 *             $message["meta"]["mod_foo"]["body_md5"] = md5($message["body"]);
 *
 *         return $message;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["posting_custom_action"]))
    $message = phorum_hook("posting_custom_action", $message);

// Only check the integrity of the data on finishing up. During the
// editing process, the user may produce garbage as much as he likes.
if ($finish || $preview) {
    include("./include/posting/check_integrity.php");
}

// Handle cancel request.
if ($cancel) {
    include("./include/posting/action_cancel.php");
}

// Count the number and total size of active attachments
// that we currently have.
$attach_count = 0;
$attach_totalsize = 0;
foreach ($message["attachments"] as $attachment) {
    if ($attachment["keep"]) {
        $attach_count ++;
        $attach_totalsize += $attachment["size"];
    }
}

// Attachment management. This will update the
// $attach_count and $attach_totalsize variables.
if ($do_attach || $do_detach) {
    include("./include/posting/action_attachments.php");
}

// Handle finishing actions.
if ( !$PHORUM["DATA"]["ERROR"] && $finish )
{
    // Posting mode
    if ($mode == "post" || $mode == "reply") {
        include("./include/posting/action_post.php");
    }
    // Editing mode.
    elseif ($mode == "edit") {
        include("./include/posting/action_edit.php");
    }
    // A little safety net.
    else trigger_error(
        "Internal error: finish action for \"$mode\" not available",
        E_USER_ERROR
    );
}

// ----------------------------------------------------------------------
// Display the page
// ----------------------------------------------------------------------

if ($PHORUM["posting_template"] == 'posting')
{
    // Make up the text which must be used on the posting form's submit button.
    $button_txt = $mode == "edit"
                ? $PHORUM["DATA"]["LANG"]["SaveChanges"]
                : $PHORUM["DATA"]["LANG"]["Post"];
    $message["submitbutton_text"] = $button_txt;

    // Attachment config
    if($PHORUM["max_attachments"]){

        // Retrieve upload limits as imposed by the system.
        require_once('./include/upload_functions.php');
        $system_max_upload = phorum_get_system_max_upload();

        // Apply the upload limits to the max attachment size.
        if($PHORUM["max_attachment_size"]==0) $PHORUM["max_attachment_size"]=$system_max_upload[0]/1024;
        $PHORUM["max_attachment_size"] = min($PHORUM["max_attachment_size"],$system_max_upload[0]/1024);

        if ($PHORUM["max_totalattachment_size"]) {
            if ($PHORUM["max_totalattachment_size"] < $PHORUM["max_attachment_size"]) {
                $PHORUM["max_attachment_size"] = $PHORUM["max_totalattachment_size"];
            }
        }

        // Data for attachment explanation.
        if ($PHORUM["allow_attachment_types"]) {
            $PHORUM["DATA"]["ATTACH_FILE_TYPES"] = str_replace(";", ", ", $PHORUM["allow_attachment_types"]);
            $PHORUM["DATA"]["EXPLAIN_ATTACH_FILE_TYPES"] = str_replace("%types%", $PHORUM["DATA"]["ATTACH_FILE_TYPES"], $PHORUM["DATA"]["LANG"]["AttachFileTypes"]);
        }
        if ($PHORUM["max_attachment_size"]) {
            $PHORUM["DATA"]["ATTACH_FILE_SIZE"] = $PHORUM["max_attachment_size"];
            $PHORUM["DATA"]["ATTACH_FORMATTED_FILE_SIZE"] = phorum_filesize($PHORUM["max_attachment_size"] * 1024);
            $PHORUM["DATA"]["EXPLAIN_ATTACH_FILE_SIZE"] = str_replace("%size%", $PHORUM["DATA"]["ATTACH_FORMATTED_FILE_SIZE"], $PHORUM["DATA"]["LANG"]["AttachFileSize"]);
        }
        if ($PHORUM["max_totalattachment_size"] && $PHORUM["max_attachments"]>1) {
            $PHORUM["DATA"]["ATTACH_TOTALFILE_SIZE"] = $PHORUM["max_totalattachment_size"];
            $PHORUM["DATA"]["ATTACH_FORMATTED_TOTALFILE_SIZE"] = phorum_filesize($PHORUM["max_totalattachment_size"] * 1024);
            $PHORUM["DATA"]["EXPLAIN_ATTACH_TOTALFILE_SIZE"] = str_replace("%size%", $PHORUM["DATA"]["ATTACH_FORMATTED_TOTALFILE_SIZE"], $PHORUM["DATA"]["LANG"]["AttachTotalFileSize"]);
        }
        if ($PHORUM["max_attachments"] && $PHORUM["max_attachments"]>1) {
            $PHORUM["DATA"]["ATTACH_MAX_ATTACHMENTS"] = $PHORUM["max_attachments"];
            $PHORUM["DATA"]["ATTACH_REMAINING_ATTACHMENTS"] = $PHORUM["max_attachments"] - $attach_count;
            $PHORUM["DATA"]["EXPLAIN_ATTACH_MAX_ATTACHMENTS"] = str_replace("%count%", $PHORUM["DATA"]["ATTACH_REMAINING_ATTACHMENTS"], $PHORUM["DATA"]["LANG"]["AttachMaxAttachments"]);
        }

        // A flag for the template building to be able to see if the
        // attachment storage space is full.
        $PHORUM["DATA"]["ATTACHMENTS_FULL"] =
            $attach_count >= $PHORUM["max_attachments"] ||
            ($PHORUM["max_totalattachment_size"] &&
            $attach_totalsize >= $PHORUM["max_totalattachment_size"]*1024);
    }

    // Let the templates know if we're running as an include.
    $PHORUM["DATA"]["EDITOR_AS_INCLUDE"] =
        isset($PHORUM["postingargs"]["as_include"]) && $PHORUM["postingargs"]["as_include"];

    // Process data for previewing.
    if ($preview) {
        include("./include/posting/action_preview.php");
    }

    // Always put the current mode in the message, so hook
    // writers can use this for identifying what we're doing.
    $message["mode"] = $mode;

    // Create hidden form field code. Fields which are read-only are
    // all added as a hidden form fields in the form. Also the fields
    // for which the pf_HIDDEN flag is set will be added to the
    // hidden fields. Also add signing data for all fields that
    // need signing.
    $hidden = "";
    foreach ($PHORUM["post_fields"] as $var => $spec)
    {
        $signval = NULL;
        if ($var == "mode") {
            $val = $mode;
            if ($spec[pf_SIGNED]) $signval = $mode;
        } elseif ($spec[pf_TYPE] == "array") {
            // base64_encode to convert newlines into data that can be
            // tranferred safely back and forth to the browser, without
            // getting converted (e.g. \r\n to \n).
            $val = base64_encode(serialize($message[$var]));
            if ($spec[pf_SIGNED]) $signval = $val;
        } else {
            $val = htmlspecialchars($message[$var], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            if ($spec[pf_SIGNED]) $signval = $message[$var];
        }

        if ($spec[pf_READONLY] || $spec[pf_HIDDEN]) {
            $hidden .= '<input type="hidden" name="' . $var .  '" ' .
                       'value="' . $val . "\" />\n";
        }

        if ($signval !== NULL) {
            $signature = phorum_generate_data_signature($signval);
            $hidden .= '<input type="hidden" name="' . $var . ':signature" ' .
                       'value="' . htmlspecialchars($signature, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) . "\" />\n";
        }
    }
    $PHORUM["DATA"]["POST_VARS"] .= $hidden;

    // Process data for XSS prevention.
    foreach ($message as $var => $val)
    {
        // The meta information should not be used in templates, because
        // nothing is escaped here. But we might want to use the data in
        // mods which are run after this code. We continue here, so the
        // data won't be stripped from the message data later on.
        if ($var == "meta") continue;

        // This one is filled from the language file, so there's no need
        // to run htmlspecialchars on this one.
        if ($var == "submitbutton_text") continue;

        if ($var == "attachments") {
            if (is_array($val)) {
                foreach ($val as $nr => $data)
                {
                    // Do not show attachments which are not kept.
                    if (! $data["keep"]) {
                        unset($message["attachments"][$nr]);
                        continue;
                    }

                    $message[$var][$nr]["name"] = htmlspecialchars($data["name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
                    $message[$var][$nr]["size"] = phorum_filesize(round($data["size"]));
                }
            }
        } elseif ($var == "author") {
            if (empty($PHORUM["custom_display_name"])) {
                $message[$var] = htmlspecialchars($val, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }
        } else {
            if (is_scalar($val)) {
                $message[$var] = htmlspecialchars($val, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            } else {
                // Not used in the template, unless proven otherwise.
                $message[$var] = '[removed from template data]';
            }
        }
    }

    // A cancel button is not needed if the editor is included in a page.
    // This can also be used by the before_editor hook to disable the
    // cancel button in all pages.
    $PHORUM["DATA"]["SHOW_CANCEL_BUTTON"] = (isset($PHORUM["postingargs"]["as_include"]) ? false : true);

    /*
     * [hook]
     *     before_editor
     *
     * [description]
     *     This hook can be used for changing message data, just before the 
     *     editor is displayed. This is done after escaping message data for XSS
     *     prevention is done. So in the hook, the module writer will have to be
     *     aware that data is escaped and that he has to escape data himself if
     *     needed.<sbr/>
     *     <sbr/>
     *     This hook is called every time the editor is displayed. If modifying
     *     the message data does not have to be done on every request (for
     *     example only on the first request when replying to a message), the
     *     module will have to check the state the editor is in. Here's some
     *     hints on what you could do to accomplish this:<sbr/>
     *     <sbr/>
     *     <ul>
     *     <li>Check the editor mode: this can be done by looking at the
     *         "mode" field in the message data. This field can be one of
     *         "post", "reply" and "edit".</li>
     *     <li>Check if it's the first request: this can be done by looking
     *         at the <literal>$_POST</literal> array. If no field "message_id"
     *         can be found in there, the editor is handing the first
     *         request.</li>
     *     </ul>
     *     Beware: this hook function only changes message data before it is
     *     displayed in the editor. From the editor, the user can still change
     *     the data. Therefore, this hook cannot be used to control the data
     *     which will be stored in the database. If you need that functionality,
     *     then use the hooks <hook>before_edit</hook> and/or
     *     <hook>before_post</hook> instead.
     *
     * [category]
     *     Message handling
     *
     * [when]
     *     In <filename>posting.php</filename> just before the message editor is
     *     displayed.
     *
     * [input]
     *     Array containing data for the message that will be shown in the
     *     editor screen.
     *
     * [output]
     *     Same as input.
     *
     * [example]
     *     <hookcode>
     *     // Using this, an example hook function that appends the string 
     *     // "FOO!" to the subject when replying to a message (how useful ;-) 
     *     // could look like this:
     *     function phorum_mod_foo_before_editor ($data)
     *     {
     *         if ($data["mode"] == "reply" && ! isset($_POST["message_id])) {
     *             $data["reply"] = $data["reply"] . " FOO!";
     *         }
     *
     *         return $data;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["before_editor"]))
        $message = phorum_hook("before_editor", $message);

    // Make the message data available to the template engine.
    $PHORUM["DATA"]["POSTING"] = $message;

    // Set the field to focus. Only set the focus if we have
    // no message to display to the user and if we're not in a preview.
    // In those cases, it's better to stay at the top of the
    // page, so the user can see it.
    if (phorum_page=="post" && !isset($PHORUM["DATA"]["OKMSG"]) && !isset($PHORUM["DATA"]["ERROR"]) && !$preview) {
        $focus = "subject";
        if (!empty($message["subject"])) $focus = "body";
        $PHORUM["DATA"]["FOCUS_TO_ID"] = $focus;
    }
}

if (isset($PHORUM["postingargs"]["as_include"]) && isset($templates)) {
    $templates[] = $PHORUM["posting_template"];
} else {
    phorum_output( $PHORUM["posting_template"] );
}

// ----------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------

// Merge data from a database message record into the form fields
// that we use. If $apply_readonly is set to a true value, then
// only the fields which are flagged as read-only will be copied.
function phorum_posting_merge_db2form($form, $db, $apply_readonly = false)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // If we have a user linked to the current message, then get the
    // user data from the database, if it has to be applied as
    // read-only data. We fetch the data here, so later on we
    // can apply it to the message.
    if (($PHORUM["post_fields"]["email"][pf_READONLY] ||
         $PHORUM["post_fields"]["author"][pf_READONLY]) &&
         !empty($db["user_id"])) {
        $user_info = phorum_api_user_get($db["user_id"]);
        $user_info["author"] = $user_info["display_name"];
    }

    foreach ($PHORUM["post_fields"] as $key => $info)
    {
        // Skip writeable fields if we only have to apply read-only ones.
        if ($apply_readonly && ! $info[pf_READONLY]) continue;

        switch ($key) {
            case "show_signature":
                $form[$key] = !empty($db["meta"]["show_signature"]);
                break;

            case "allow_reply":
                $form[$key] = ! $db["closed"];
                break;

            case "subscription":
                $type = phorum_api_user_get_subscription(
                    $db["user_id"], $db["forum_id"], $db["thread"]);
                switch ($type) {
                    case NULL:
                        $form[$key] = "";
                        break;
                    case PHORUM_SUBSCRIPTION_BOOKMARK:
                        $form[$key] = "bookmark";
                        break;
                    case PHORUM_SUBSCRIPTION_MESSAGE:
                        $form[$key] = "message";
                        break;
                    default:
                        $form[$key] = "";
                        break;
                }
                break;

            case "forum_id":
                $form["forum_id"] = $db["forum_id"] ? $db["forum_id"] : $PHORUM["forum_id"];
                break;

            case "attachments":
                $form[$key] = array();
                if (isset($db["meta"]["attachments"])) {
                    foreach ($db["meta"]["attachments"] as $data) {
                        $data["keep"] = true;
                        $data["linked"] = true;
                        $form["attachments"][] = $data;
                    }
                }
                break;

            case "author":
            case "email":
                if ($db["user_id"] &&
                    $PHORUM["post_fields"][$key][pf_READONLY]) {
                    $form[$key] = $user_info[$key];
                } else {
                    $form[$key] = $db[$key];
                }
                break;

            case "special":
                if ($db["sort"] == PHORUM_SORT_STICKY) {
                    $form["special"] = "sticky";
                } else {
                    $form["special"] = "";
                }
                break;

            case "mode":
                // NOOP
                break;

            default:
                $form[$key] = $db[$key];
        }
    }
    return $form;
}

?>
