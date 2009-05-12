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

define('phorum_page','control');
require_once './common.php';

phorum_require_login();

require_once './include/email_functions.php';

define("PHORUM_CONTROL_CENTER", 1);

// CSRF protection: we do not accept posting to this script,
// when the browser does not include a Phorum signed token
// in the request.
phorum_check_posting_token();

// A user has to be logged in to use his control-center.
if (!$PHORUM["DATA"]["LOGGEDIN"]) {
    $phorum->redirect(PHORUM_LIST_URL);
}

// If the user is not fully logged in, send him to the login page.
if(!$PHORUM["DATA"]["FULLY_LOGGEDIN"]){
    $phorum->redirect(PHORUM_LOGIN_URL, "redir=".PHORUM_CONTROLCENTER_URL);
}

$error_msg = false;
$error = "";
$okmsg = "";
$template = "";

// Generating the panel id of the page to use.
if(isset($PHORUM['args']['panel'])){
    $panel = $PHORUM['args']['panel'];

} elseif(isset($_POST["panel"])){
    $panel = $_POST["panel"];

} else {
    $panel = PHORUM_CC_SUMMARY;
}

$panel = htmlspecialchars(
    basename($panel), ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]
);

// Set all our URLs.
phorum_build_common_urls();

// Generate the control panel URLs.
$PHORUM['DATA']['URL']['CC0'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUMMARY);
$PHORUM['DATA']['URL']['CC1'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_THREADS);
$PHORUM['DATA']['URL']['CC2'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SUBSCRIPTION_FORUMS);
$PHORUM['DATA']['URL']['CC3'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERINFO);
$PHORUM['DATA']['URL']['CC4'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_SIGNATURE);
$PHORUM['DATA']['URL']['CC5'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_MAIL);
$PHORUM['DATA']['URL']['CC6'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_BOARD);
$PHORUM['DATA']['URL']['CC7'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PASSWORD);
$PHORUM['DATA']['URL']['CC8'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_UNAPPROVED);
$PHORUM['DATA']['URL']['CC9'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_FILES);
$PHORUM['DATA']['URL']['CC10'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_USERS);
$PHORUM['DATA']['URL']['CC14'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_PRIVACY);
$PHORUM['DATA']['URL']['CC15'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MODERATION);
$PHORUM['DATA']['URL']['CC16'] = $phorum->url(PHORUM_CONTROLCENTER_URL, "panel=" . PHORUM_CC_GROUP_MEMBERSHIP);

// Determine if the user files functionality is available.
$PHORUM["DATA"]["MYFILES"] = ($PHORUM["file_uploads"] || $PHORUM["user"]["admin"]);

// Determine if the user is a moderator.
$PHORUM["DATA"]["MESSAGE_MODERATOR"] = $phorum->user->check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES, PHORUM_ACCESS_ANY);
$PHORUM["DATA"]["USER_MODERATOR"] = $phorum->user->check_access(PHORUM_USER_ALLOW_MODERATE_USERS, PHORUM_ACCESS_ANY);
$PHORUM["DATA"]["GROUP_MODERATOR"] = $phorum->user->check_group_access(PHORUM_USER_GROUP_MODERATOR, PHORUM_ACCESS_ANY);
$PHORUM["DATA"]["MODERATOR"] = ($PHORUM["DATA"]["USER_MODERATOR"] + $PHORUM["DATA"]["MESSAGE_MODERATOR"] + $PHORUM["DATA"]["GROUP_MODERATOR"]) > 0;

// If global email hiding is not enabled, then give the user a chance
// to choose for hiding himself.
$PHORUM['DATA']['SHOW_EMAIL_HIDE'] = empty($PHORUM['hide_email_addr']) ? 1 : 0;

// If pm email notifications are enabled, then give the user a chance
// to disable it.
$PHORUM['DATA']['SHOW_PM_EMAIL_NOTIFY'] = !empty($PHORUM["allow_pm_email_notify"]);

// The form action for the common form.
$PHORUM["DATA"]["URL"]["ACTION"] = $phorum->url(PHORUM_CONTROLCENTER_ACTION_URL);

// fill the breadcrumbs-info
$PHORUM['DATA']['BREADCRUMBS'][]=array(
    'URL'=>$PHORUM['DATA']['URL']['REGISTERPROFILE'],
    'TEXT'=>$PHORUM['DATA']['LANG']['MyProfile'],
    'TYPE'=>'control'
);

$user = $PHORUM['user'];

// Security messures.
unset($user["password"]);
unset($user["password_temp"]);
unset($user["permissions"]);

// Fake a message here so we can run the sig through format_message.
$fake_messages = array(array("author"=>"", "email"=>"", "subject"=>"", "body"=>$user["signature"]));
$fake_messages = $phorum->message->format($fake_messages);
$user["signature_formatted"] = $fake_messages[0]["body"];

// Format the user signature using standard message body formatting
// or  HTML escape it
$user["signature"] = htmlspecialchars($user["signature"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

// Initialize any custom profile fields that are not present.
if (!empty($PHORUM["PROFILE_FIELDS"][PHORUM_CUSTOM_FIELD_USER])) {
    foreach($PHORUM["PROFILE_FIELDS"][PHORUM_CUSTOM_FIELD_USER] as $id => $field) {
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
    $PHORUM['DATA']['URL']['BACK'] = $phorum->url(PHORUM_LIST_URL);
    $PHORUM['DATA']['URL']['BACKTITLE'] = $PHORUM['DATA']['LANG']['BacktoForum'];
} else {
    if(isset($PHORUM['forum_id'])) {
        $PHORUM['DATA']['URL']['BACK'] = $phorum->url(PHORUM_INDEX_URL,$PHORUM['forum_id']);
    } else {
        $PHORUM['DATA']['URL']['BACK'] = $phorum->url(PHORUM_INDEX_URL);
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
 *     </ul>
 *
 * [output]
 *     The same array as the one that was used for the hook call
 *     argument, possibly with the "template", "handled", "error" and
 *     "okmsg" fields updated in it.
 */
$hook_info = array(
    'panel'    => $panel,
    'template' => NULL,
    'handled'  => FALSE,
    'error'    => NULL,
    'okmsg'    => NULL
);
if (isset($PHORUM['hooks']['cc_panel'])) {
    $hook_info = $phorum->modules->hook('cc_panel', $hook_info);
}

// Retrieve template, error and okmsg info from the module info.
if ($hook_info['template'] !== NULL) { $template = $hook_info['template']; }
if ($hook_info['okmsg'] !== NULL)    { $okmsg    = $hook_info['okmsg']; }
if ($hook_info['error'] !== NULL)    { $error    = $hook_info['error']; }

// If no module did handle the control center panel, then try to load
// a standard control center panel file.
if (!$hook_info['handled']) {
    if (file_exists("./include/controlcenter/$panel.php")) {
        include "./include/controlcenter/$panel.php";
    } else {
        include "./include/controlcenter/summary.php";
    }
}

if(empty($PHORUM["DATA"]["HEADING"])){
    $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["MyProfile"];
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
if (isset($error) && !empty($error)) $PHORUM['DATA']['ERROR'] = $error;
if (isset($okmsg) && !empty($okmsg)) $PHORUM['DATA']['OKMSG'] = $okmsg;

if ($error_msg) { // Possibly set from the panel include file.
    $template = "message";
} else {
    $template = "cc_index";
}

// Display the control panel page.
phorum_output($template);

// ============================================================================

/**
 * A common function which is used to save the userdata from the post-data.
 * @param panel - The panel for which to save data.
 * @return array - An array containing $error and $okmsg.
 */
function phorum_controlcenter_user_save($panel)
{
    global $PHORUM;
    $phorum = Phorum::API();

    $error = "";
    $okmsg = "";

    // Setup the default userdata fields that can be changed
    // from the control panel interface.
    $userdata = array(
        'signature'       => NULL,
        'hide_email'      => NULL,
        'hide_activity'   => NULL,
        'password'        => NULL,
        'password_temp'   => NULL,
        'tz_offset'       => NULL,
        'is_dst'          => NULL,
        'user_language'   => NULL,
        'threaded_list'   => NULL,
        'threaded_read'   => NULL,
        'email_notify'    => NULL,
        'show_signature'  => NULL,
        'pm_email_notify' => NULL,
        'email'           => NULL,
        'email_temp'      => NULL,
        'user_template'   => NULL,
        'moderation_email'=> NULL,
        'real_name'       => NULL,
    );
    // Add custom profile fields as acceptable fields.
    foreach ($PHORUM["PROFILE_FIELDS"][PHORUM_CUSTOM_FIELD_USER] as $id => $field) {
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
        $userdata = $phorum->modules->hook("cc_save_user", $userdata);

    // Set $error, in case the cc_save_user hook did set an error.
    if (isset($userdata['error'])) {
        $error=$userdata['error'];
        unset($userdata['error']);
    // Try to update the userdata in the database.
    } elseif (!$phorum->user->save($userdata)) {
        // Updating the user failed.
        $error = $PHORUM["DATA"]["LANG"]["ErrUserAddUpdate"];
    } else {
        // Updating the user was successful.
        $okmsg = $PHORUM["DATA"]["LANG"]["ProfileUpdatedOk"];

        // Let the userdata be reloaded.
        $phorum->user->set_active_user(
            PHORUM_FORUM_SESSION,
            $userdata["user_id"]
        );

        // If a new password was set, then reset all session id(s), so
        // other computers or browser will lose any active session that
        // they are running.
        if (isset($userdata["password"]) && $userdata["password"] != '') {
            $phorum->user->session_create(
                PHORUM_FORUM_SESSION,
                PHORUM_SESSID_RESET_ALL
            );
        }

        // Copy data from the updated user back into the user template data.
        $formatted = $phorum->user->format(array($PHORUM['user']));
        foreach ($formatted[0] as $key => $val) {
            $PHORUM['DATA']['USER'][$key] = $val;
        }

        // Copy data from the updated user back into the profile template data.
        // Leave PANEL and forum_id alone (these are injected into the
        // userdata in the template from this script).
        foreach ($PHORUM["DATA"]["PROFILE"] as $key => $val) {
            if ($key == "PANEL" || $key == "forum_id") continue;
            if (isset($PHORUM["user"][$key])) {
                $PHORUM["DATA"]["PROFILE"][$key] = $PHORUM["user"][$key];
            } else {
                $PHORUM["DATA"]["PROFILE"][$key] = "";
            }
        }
    }

    return array($error, $okmsg);
}

?>
