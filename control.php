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

define('phorum_page','control');
require_once './common.php';
require_once PHORUM_PATH.'/include/api/format/users.php';
require_once PHORUM_PATH.'/include/api/format/messages.php';

phorum_api_request_require_login(TRUE);

// CSRF protection: we do not accept posting to this script,
// when the browser does not include a Phorum signed token
// in the request.
phorum_api_request_check_token();

define("PHORUM_CONTROL_CENTER", 1);

$error_msg = false;
$error = "";
$okmsg = isset($PHORUM['args']['okmsg']) ? phorum_api_format_htmlspecialchars($PHORUM['args']['okmsg']) : "";
$template = "";

// Generating the panel id of the page to use.
if(isset($PHORUM['args']['panel'])){
    $panel = $PHORUM['args']['panel'];

} elseif(isset($_POST["panel"])){
    $panel = $_POST["panel"];

} else {
    $panel = PHORUM_CC_SUMMARY;
}

$panel = phorum_api_format_htmlspecialchars(basename($panel));

// Set all our URLs.
phorum_build_common_urls();

// Generate the control panel URLs.
$PHORUM['DATA']['URL']['CC0'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUMMARY);
$PHORUM['DATA']['URL']['CC1'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_THREADS);
$PHORUM['DATA']['URL']['CC2'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_FORUMS);
$PHORUM['DATA']['URL']['CC3'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERINFO);
$PHORUM['DATA']['URL']['CC4'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SIGNATURE);
$PHORUM['DATA']['URL']['CC5'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_MAIL);
$PHORUM['DATA']['URL']['CC6'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_BOARD);
$PHORUM['DATA']['URL']['CC7'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PASSWORD);
$PHORUM['DATA']['URL']['CC8'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED);
$PHORUM['DATA']['URL']['CC9'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_FILES);
$PHORUM['DATA']['URL']['CC10'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS);
$PHORUM['DATA']['URL']['CC14'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PRIVACY);
$PHORUM['DATA']['URL']['CC15'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MODERATION);
$PHORUM['DATA']['URL']['CC16'] = phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MEMBERSHIP);

// Determine if the user files functionality is available.
$PHORUM["DATA"]["MYFILES"] = ($PHORUM["file_uploads"] || $PHORUM["user"]["admin"]);

// Determine if the user is a moderator.
$PHORUM["DATA"]["MESSAGE_MODERATOR"] = phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES, PHORUM_ACCESS_ANY);
$PHORUM["DATA"]["USER_MODERATOR"] = phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_USERS, PHORUM_ACCESS_ANY);
$PHORUM["DATA"]["GROUP_MODERATOR"] = phorum_api_user_check_group_access(PHORUM_USER_GROUP_MODERATOR, PHORUM_ACCESS_ANY);
$PHORUM["DATA"]["MODERATOR"] = ($PHORUM["DATA"]["USER_MODERATOR"] + $PHORUM["DATA"]["MESSAGE_MODERATOR"] + $PHORUM["DATA"]["GROUP_MODERATOR"]) > 0;

// If global email hiding is not enabled, then give the user a chance
// to choose for hiding himself.
$PHORUM['DATA']['SHOW_EMAIL_HIDE'] = empty($PHORUM['hide_email_addr']) ? 1 : 0;

// If pm email notifications are enabled, then give the user a chance
// to disable it.
$PHORUM['DATA']['SHOW_PM_EMAIL_NOTIFY'] = !empty($PHORUM["allow_pm_email_notify"]);

// The form action for the common form.
$PHORUM["DATA"]["URL"]["ACTION"] = phorum_api_url(PHORUM_CONTROLCENTER_ACTION_URL);

// fill the breadcrumbs-info
$PHORUM['DATA']['BREADCRUMBS'][] = array(
    'URL'  => $PHORUM['DATA']['URL']['REGISTERPROFILE'],
    'TEXT' => $PHORUM['DATA']['LANG']['MyProfile'],
    'TYPE' => 'control'
);

$user = $PHORUM['user'];

// Security messures.
unset($user["password"]);
unset($user["password_temp"]);
unset($user["permissions"]);

// Fake a message here so we can run the sig through format_message.
$fake_messages = array(array("author"=>"", "email"=>"", "subject"=>"", "body"=>$user["signature"]));
$fake_messages = phorum_api_format_messages($fake_messages);
$user["signature_formatted"] = $fake_messages[0]["body"];

// Format the user signature using standard message body formatting
// or  HTML escape it
$user["signature"] = phorum_api_format_htmlspecialchars($user["signature"]);

// HTML escape all other fields that are used in the control center.
foreach ($user as $key => $val) {
  if (is_array($val) || substr($key, 0, 9) == 'signature') continue;
  $user[$key] = phorum_api_format_htmlspecialchars($user[$key]);
}

// Initialize any custom profile fields that are not present.
if (!empty($PHORUM["CUSTOM_FIELDS"][PHORUM_CUSTOM_FIELD_USER])) {
    foreach($PHORUM["CUSTOM_FIELDS"][PHORUM_CUSTOM_FIELD_USER] as $id => $field) {
        if ($id === 'num_fields' || !empty($field['deleted'])) continue;
        if (!isset($user[$field['name']])) $user[$field['name']] = "";
    }
}

// Setup template data.
$PHORUM["DATA"]["PROFILE"] = $user;
$PHORUM["DATA"]["PROFILE"]["forum_id"] = isset($PHORUM["forum_id"]) ? $PHORUM['forum_id'] : 0;
$PHORUM["DATA"]["PROFILE"]["PANEL"] = $panel;
// used in nearly all or all cc-panels
$PHORUM['DATA']['POST_VARS'].="<input type=\"hidden\" name=\"panel\" value=\"$panel\" />\n";

// Set the back-URL and -message.
if ($PHORUM['forum_id'] > 0 && $PHORUM['folder_flag'] == 0) {
    $PHORUM['DATA']['URL']['BACK'] = phorum_api_url(PHORUM_LIST_URL);
    $PHORUM['DATA']['URL']['BACKTITLE'] = $PHORUM['DATA']['LANG']['BacktoForum'];
} else {
    if(isset($PHORUM['forum_id'])) {
        $PHORUM['DATA']['URL']['BACK'] = phorum_api_url(PHORUM_INDEX_URL,$PHORUM['forum_id']);
    } else {
        $PHORUM['DATA']['URL']['BACK'] = phorum_api_url(PHORUM_INDEX_URL);
    }
    $PHORUM['DATA']['URL']['BACKTITLE'] = $PHORUM['DATA']['LANG']['BackToForumList'];
}

// Load the code for the current panel.
/**
 * [hook]
 *     cc_panel
 *
 * [description]
 *     This hook can be used to implement an extra control center panel
 *     or to override an existing panel if you like.
 *
 * [category]
 *     Control center
 *
 * [when]
 *     Right before loading a standard panel's include file.
 *
 * [input]
 *     An array containing the following fields:
 *     <ul>
 *     <li>panel:
 *         the name of the panel that has to be loaded. The module will
 *         have to check this field to see if it should handle the
 *         panel or not.</li>
 *     <li>template:
 *         the name of the template that has to be loaded. This field should
 *         be filled by the module if it wants to load a specific
 *         template.</li>
 *     <li>handled:
 *         if a module does handle the panel, then it can set this field
 *         to a true value, to prevent Phorum from running the standard
 *         panel code.</li>
 *     <li>error:
 *         modules can fill this field with an error message to show.</li>
 *     <li>okmsg:
 *         modules can fill this field with an ok message to show.</li>
 *     <li>force_okmsg:
 *         modules can fill this field if their okmsg should take precedence
 *         over the okmsg set from the core controlcenter panel.</li>
 *     <li>force_error:
 *         modules can fill this field if their error should take precedence
 *         over the error set from the core controlcenter panel.</li>
 *     </ul>
 *
 * [output]
 *     The same array as the one that was used for the hook call
 *     argument, possibly with the "template", "handled", "error",
 *     "okmsg", "force_okmsg" and "force_error" fields updated in it.
 */
$hook_info = array(
    'panel'       => $panel,
    'template'    => NULL,
    'handled'     => FALSE,
    'error'       => NULL,
    'okmsg'       => NULL,
    'force_okmsg' => FALSE,
    'force_error' => FALSE,
);
if (isset($PHORUM['hooks']['cc_panel'])) {
    $hook_info = phorum_api_hook('cc_panel', $hook_info);
}

// Retrieve template, error and okmsg info from the module info.
if ($hook_info['template'] !== NULL) { $template = $hook_info['template']; }
if ($hook_info['okmsg']    !== NULL) { $okmsg    = $hook_info['okmsg']; }
if ($hook_info['error']    !== NULL) { $error    = $hook_info['error']; }

// If no module did handle the control center panel, then try to load
// a standard control center panel file.
if (!$hook_info['handled']) {
    if (file_exists("./include/controlcenter/$panel.php")) {
        include "./include/controlcenter/$panel.php";
    } else {
        include "./include/controlcenter/summary.php";
    }
}

// set the page title correctly
$PHORUM['DATA']['HTML_TITLE'].=PHORUM_SEPARATOR.$PHORUM["DATA"]["LANG"]["MyProfile"];

if(empty($PHORUM["DATA"]["HEADING"])){
    $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["MyProfile"];
} else {
    // set the breadcrumb with the heading
    $PHORUM['DATA']['BREADCRUMBS'][] = array(
        'URL'  => phorum_api_url(PHORUM_CONTROLCENTER_URL, "panel=$panel"),
        'TEXT' => $PHORUM['DATA']['HEADING'],
        'TYPE' => 'control'
    );
    $PHORUM['DATA']['HTML_TITLE'].=PHORUM_SEPARATOR.$PHORUM['DATA']['HEADING'];
}

// unset default description
$PHORUM['DATA']['DESCRIPTION'] = '';
$PHORUM['DATA']['HTML_DESCRIPTION'] = '';

// The include file can set the template we have to use for
// displaying the main part of the control panel screen
// in the $template variable.
if (isset($template) && !empty($template)) {
    $PHORUM['DATA']['content_template'] = $template;
}

// The include file can also set an error message to show
// in the $error variable and a success message in $okmsg.
if (!$hook_info['force_error'] && isset($error) && !empty($error)) $PHORUM['DATA']['ERROR'] = $error;
if (!$hook_info['force_okmsg'] && isset($okmsg) && !empty($okmsg)) $PHORUM['DATA']['OKMSG'] = $okmsg;

if ($error_msg) { // Possibly set from the panel include file.
    $template = "message";
} else {
    $template = "cc_index";
}

// Display the control panel page.
phorum_api_output($template);

// ============================================================================

/**
 * A common function which is used to save the userdata from the post-data.
 * @param panel - The panel for which to save data.
 * @return array - An array containing $error and $okmsg.
 */
function phorum_controlcenter_user_save($panel)
{
    global $PHORUM;

    $error = "";
    $okmsg = "";

    // Setup the default userdata fields that can be changed
    // from the control panel interface.
    $userdata = array(
        'signature'       => NULL,
        'hide_email'      => NULL,
        'hide_activity'   => NULL,
        'tz_offset'       => NULL,
        'is_dst'          => NULL,
        'user_language'   => NULL,
        'threaded_list'   => NULL,
        'threaded_read'   => NULL,
        'email_notify'    => NULL,
        'show_signature'  => NULL,
        'pm_email_notify' => NULL,
        'user_template'   => NULL,
        'moderation_email'=> NULL,
        'real_name'       => NULL,
    );
    // Password related fields can only be updated from the password panel.
    if ($panel == 'password') {
      $userdata['password'] = NULL;
      $userdata['password_temp'] = NULL;
    }
    // E-mail address related fields can only be updated from the email panel.
    if ($panel == 'email') {
      $userdata['email'] = NULL;
      $userdata['email_temp'] = NULL;
    }
    // E-mail address related fields can only be updated from the email panel.
    if ($panel == 'email') {
      $userdata['email'] = NULL;
      $userdata['email_temp'] = NULL;
    }
    // Add custom profile fields as acceptable fields.
    foreach ($PHORUM["CUSTOM_FIELDS"][PHORUM_CUSTOM_FIELD_USER] as $id => $field) {
        if ($id === "num_fields" || !empty($field['deleted'])) continue;
        $userdata[$field["name"]] = NULL;
    }
    // Update userdata with $_POST information.
    foreach ($_POST as $key => $val) {
       if (array_key_exists($key, $userdata)) {
           $userdata[$key] = $val;
       }
    }
    // Remove unused profile fields.
    foreach ($userdata as $key => $val) {
        if (is_null($val)) {
            unset($userdata[$key]);
        }
    }

    // Set static userdata.
    $userdata["user_id"] = $PHORUM["user"]["user_id"];

    // Run a hook, so module writers can update and check the userdata.
    if (isset($PHORUM["hooks"]["cc_save_user"]))
        $userdata = phorum_api_hook("cc_save_user", $userdata);

    // Set $error, in case the cc_save_user hook did set an error.
    if (isset($userdata['error'])) {
        $error=$userdata['error'];
        unset($userdata['error']);
    // Try to update the userdata in the database.
    } elseif (!phorum_api_user_save($userdata)) {
        // Updating the user failed.
        $error = $PHORUM["DATA"]["LANG"]["ErrUserAddUpdate"];
    } else {
        // Updating the user was successful.
        $okmsg = $PHORUM["DATA"]["LANG"]["ProfileUpdatedOk"];

        // Let the userdata be reloaded.
        phorum_api_user_set_active_user(
            PHORUM_FORUM_SESSION,
            $userdata["user_id"]
        );

        // If a new password was set, then reset all session id(s), so
        // other computers or browser will lose any active session that
        // they are running.
        if (isset($userdata["password"]) && $userdata["password"] != '') {
            phorum_api_user_session_create(
                PHORUM_FORUM_SESSION,
                PHORUM_SESSID_RESET_ALL
            );
        }

        // Copy data from the updated user back into the user template data.
        $formatted = phorum_api_format_users(array($PHORUM['user']));
        foreach ($formatted[0] as $key => $val) {
            $PHORUM['DATA']['USER'][$key] = $val;
        }

        // Copy data from the updated user back into the template data.
        // Leave PANEL and forum_id alone (these are injected into the
        // userdata in the template from this script).
        foreach ($PHORUM["DATA"]["PROFILE"] as $key => $val) {
            if ($key == "PANEL" || $key == "forum_id") continue;
            if (isset($PHORUM["user"][$key])) {
                 if (is_array($val)) {
                    // array-data would be (most often) broken when html encoded
                    $PHORUM["DATA"]["PROFILE"][$key] = $PHORUM["user"][$key];
                 } elseif(substr($key, 0, 9) == 'signature') {
                    // the signature needs special care - e.g. for the formatted sig

                    // Fake a message here so we can run the sig through format_message.
                    $fake_messages = array(array("author"=>"", "email"=>"", "subject"=>"", "body"=>$PHORUM["user"]["signature"]));
                    $fake_messages = phorum_format_messages( $fake_messages );
                    $PHORUM["DATA"]["PROFILE"]["signature_formatted"] = $fake_messages[0]["body"];

                    // Format the user signature using standard message body formatting
                    // or  HTML escape it
                    $PHORUM["DATA"]["PROFILE"]["signature"] = phorum_api_format_htmlspecialchars($PHORUM["user"]["signature"]);
                 } else {
                    // same handling as when loading the page for the first time
                    $PHORUM["DATA"]["PROFILE"][$key] = phorum_api_format_htmlspecialchars($PHORUM["user"][$key]);
                 }
            } else {
                $PHORUM["DATA"]["PROFILE"][$key] = "";
            }
        }
    }

    return array($error, $okmsg);
}

?>
