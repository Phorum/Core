<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
// Copyright (C) 2010  Phorum Development Team                                //
// http://www.phorum.org                                                      //
//                                                                            //
// This program is free software. You can redistribute it and/or modify       //
// it under the terms of either the current Phorum License (viewable at       //
// phorum.org) or the Phorum License that was distributed with this file      //
//                                                                            //
// This program is distributed in the hope that it will be useful,            //
// but WITHOUT ANY WARRANTY, without even the implied warranty of             //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                       //
//                                                                            //
// You should have received a copy of the Phorum License                      //
// along with this program.                                                   //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// These language strings are set dynamically, so the language
// tool won't recognize them automatically. Therefore they are
// mentioned here.
// $PHORUM["DATA"]["LANG"]["PMFolderCreateSuccess"]
// $PHORUM["DATA"]["LANG"]["PMFolderRenameSuccess"]
// $PHORUM["DATA"]["LANG"]["PMFolderDeleteSuccess"]
// $PHORUM["DATA"]["LANG"]["PMSent"]

// PMTODO If reading from a mail notify, lookup the folder_id,
//        so the close button will work. Now the folder_id is empty.
// PMTODO implement pm_reply_flag functionality

define('phorum_page','pm');

include_once("./common.php");

phorum_require_login();

// CSRF protection: we do not accept posting to this script,
// when the browser does not include a Phorum signed token
// in the request.
phorum_check_posting_token();

// set all our common URL's
phorum_build_common_urls();

include_once("./include/email_functions.php");
include_once("./include/format_functions.php");

// a user has to be logged in to use the private messages system
if (!$PHORUM["DATA"]["LOGGEDIN"]) {
    phorum_redirect_by_url(phorum_get_url(PHORUM_LIST_URL));
    exit();
}

// if the user is not fully logged in, send him to the login page
if (!$PHORUM["DATA"]["FULLY_LOGGEDIN"]) {

    // Construct the URL to redirect to after logging in.
    $args = array(PHORUM_PM_URL);
    foreach ($PHORUM["args"] as $k => $v) {
        if (in_array("$k=$v", $PHORUM["DATA"]["GET_VARS"])) continue;
        if(is_numeric($k)) $args[] = $v; else $args[] = "$k=$v";
    }
    $redir = urlencode(call_user_func_array('phorum_get_url', $args));

    phorum_redirect_by_url(phorum_get_url(PHORUM_LOGIN_URL, "redir=$redir"));
    exit();
}

// If private messages are disabled, just show a simple error message.
if (! $PHORUM["enable_pm"]) {
    $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMDisabled"];
    phorum_output("stdblock");
    return;
}

// fill the breadcrumbs-info
$PHORUM['DATA']['BREADCRUMBS'][]=array(
    'URL'=>$PHORUM['DATA']['URL']['PM'],
    'TEXT'=>$PHORUM['DATA']['LANG']['PrivateMessages'],
    'TYPE'=>'pm'
);

// ------------------------------------------------------------------------
// Parameter handling
// ------------------------------------------------------------------------

// Retrieve a parameter from either the args-list or $_POST.
// Do typecasting if requested.
function phorum_getparam($name, $type = NULL)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $ret = NULL;
    if (isset($PHORUM["args"][$name])) {
        $ret = trim($PHORUM["args"][$name]);
    }elseif (isset($_POST[$name])) {
        $ret = trim($_POST[$name]);
    }

    // Apply typecasting if requested.
    if ($ret != NULL && $type != NULL) {
        switch ($type) {

            case 'integer':
                $ret = (int) $ret;
                break;

            case 'boolean':
                $ret = $ret ? 1 : 0;
                break;

            case 'folder_id':
                if ($ret != PHORUM_PM_INBOX && $ret != PHORUM_PM_OUTBOX) {
                    $ret = (int)$ret;
                }
                break;

            default:
                trigger_error(
                    "Internal error in phorum_getparam: " .
                    "illegal type for typecasting: ".htmlspecialchars($type),
                    E_USER_ERROR
                );
        }
    }

    return $ret;
}

// Get basic parameters.
$action          = phorum_getparam('action');
$page            = phorum_getparam('page');
$folder_id       = phorum_getparam('folder_id', 'folder_id');
$pm_id           = phorum_getparam('pm_id', 'integer');
$forum_id        = (int)$PHORUM["forum_id"];
$user_id         = (int)$PHORUM["user"]["user_id"];
$hide_userselect = phorum_getparam('hide_userselect', 'boolean');

// Cleanup array with checked PM items.
if (isset($_POST["checked"])) {
    $checked = array();
    foreach ($_POST["checked"] as $pm_id) {
        $checked[] = (int)$pm_id;
    }
    $_POST["checked"] = $checked;
}

// Get recipients from the form and create a valid list of recipients.
$recipients = array();
if (isset($_POST["recipients"]) && is_array($_POST["recipients"])) {
    foreach ($_POST["recipients"] as $id => $dummy) {
        $user = phorum_api_user_get($id);
        if ($user && $user["active"] == 1) {
            $recipients[$id] = $user;
        }
    }
}

// init error var
$error_msg = "";

// ------------------------------------------------------------------------
// Banlist checking
// ------------------------------------------------------------------------

//  Start editor       Post message         Post reply
if ($page == 'send' || $action == 'post' || ($action == 'list' && isset($pm_id)))
{
    include_once("./include/profile_functions.php");
    $error = phorum_check_bans(array(
        array($PHORUM["user"]["username"], PHORUM_BAD_NAMES),
        array($PHORUM["user"]["email"],    PHORUM_BAD_EMAILS),
        array($user_id,                    PHORUM_BAD_USERID),
        array(NULL,                        PHORUM_BAD_IPS),
    ));

    // Show an error in case we encountered a ban.
    if (! empty($error)) {
        $PHORUM["DATA"]["ERROR"] = $error;
        phorum_output("message");
        return;
    }
}

// ------------------------------------------------------------------------
// Perform actions
// ------------------------------------------------------------------------

// Initialize error and ok message.
$error = '';
$okmsg = '';

// init folder list
$pm_folders = phorum_db_pm_getfolders(NULL, true);

// Translate button clicks from the read page to appropriate actions.
if (isset($_POST['close_message'])) {
    $page = 'list';
} elseif (isset($_POST['delete_message'])) {
    $page = 'list';
    $_POST['delete'] = 1;
    $_POST['checked'] = array($pm_id);
    $action = 'list';
} elseif (isset($_POST['move_message'])) {
    $page = 'list';
    $_POST['move'] = 1;
    $_POST['checked'] = array($pm_id);
    $action = 'list';
} elseif (isset($_POST['reply']) || isset($_POST['reply_to_all'])) {
    $page = 'send';
    $action = '';
}

if (!empty($action)) {

    // Utility function to check if a foldername already exists.
    // No extreme checking with locking here. Technically
    // speaking duplicate foldernames will work. It's just
    // confusing for the user.
    function phorum_pm_folder_exists($foldername)
    {
        global $pm_folders;
        foreach ($pm_folders as $id => $data) {
            if (strcasecmp($foldername, $data["name"]) == 0) {
                return true;
            }
        }
        return false;
    }

    // Redirect will be set to a true value if after performing
    // the action we want to use a redirect to get to the
    // result page. This is done for two reasons:
    // 1) Let the result page use refreshed PM data;
    // 2) Prevent reloading of the action page (which could for
    //    example result in duplicate message sending).
    // The variable $redirect_message can be set to a language
    // key string to have a message displayed after redirection.
    $redirect = false;
    $redirect_message = '';

    switch($action) {

        // Actions which are triggered from the folder management interface.
        case "folders":

            $redirect = false;
            $page = "folders";

            // Create folder.
            if (!empty($_POST['create_folder']))
            {
                $foldername = trim($_POST["create_folder_name"]);

                if ($foldername != '')
                {
                    if (phorum_pm_folder_exists($foldername)) {
                        $error = $PHORUM["DATA"]["LANG"]["PMFolderExistsError"];
                    } else {
                        phorum_db_pm_create_folder($foldername);
                        $redirect_message = "PMFolderCreateSuccess";
                        $redirect = true;
                    }

                }
            }

            // Rename a folder.
            elseif (!empty($_POST['rename_folder']))
            {
                $from = $_POST['rename_folder_from'];
                $to = trim($_POST['rename_folder_to']);

                if (!empty($from) && $to != '') {
                    if (phorum_pm_folder_exists($to)) {
                        $error = $PHORUM["DATA"]["LANG"]["PMFolderExistsError"];
                    } else {
                        phorum_db_pm_rename_folder($from, $to);
                        $redirect_message = "PMFolderRenameSuccess";
                        $redirect = true;
                    }
                }
            }

            // Delete a folder.
            elseif (!empty($_POST['delete_folder']))
            {
                $folder_id = $_POST["delete_folder_target"];
                if (!empty($folder_id)) {
                    phorum_db_pm_delete_folder($folder_id);
                     /**
                     * [hook]
                     *     pm_delete_folder
                     *
                     * [availability]
                     *     Phorum 5 >= 5.2.13
                     *
                     * [description]
                     *     This hook can be used for working on deletion of a
                     *     private message folder. E.g. for deleting messages
                     *     in the folder before.
                     *
                     * [category]
                     *     Private message system
                     *
                     * [when]
                     *     Right before Phorum deletes the private message folder.
                     *
                     * [input]
                     *     The id of the private message folder going to be deleted.
                     *
                     * [output]
                     *     Same as input.
                     *
                     * [example]
                     *     <hookcode>
                     *     function phorum_mod_foo_pm_delete_folder($folder_id)
                     *     {
                     *         // do something with the folder going to be deleted
                     *
                     *         return $folder_id;
                     *     }
                     *     </hookcode>
                     */
                    if (isset($PHORUM['hooks']['pm_delete_folder'])) {
                        phorum_hook('pm_delete_folder', $folder_id);
                    }

                    $redirect_message = "PMFolderDeleteSuccess";
                    $redirect = true;

                    // Invalidate user cache, to update message counts.
                    phorum_cache_remove('user',$user_id);
                }
            }

            break;


        // Actions which are triggered from the list interface.
        case "list":

            // Delete all checked messages.
            if (isset($_POST["delete"]) && isset($_POST["checked"])) {
                foreach($_POST["checked"] as $pm_id) {
                    if (phorum_db_pm_get($pm_id, $folder_id)) {
                        phorum_db_pm_delete($pm_id, $folder_id);
                        /**
                         * [hook]
                         *     pm_delete
                         *
                         * [availability]
                         *     Phorum 5 >= 5.2.13
                         *
                         * [description]
                         *     This hook can be used for working deletion of a
                         *     private message.
                         *
                         * [category]
                         *     Private message system
                         *
                         * [when]
                         *     Right before Phorum deletes the private message.
                         *
                         * [input]
                         *     The id of the private message going to be deleted.
                         *
                         * [output]
                         *     Same as input.
                         *
                         * [example]
                         *     <hookcode>
                         *     function phorum_mod_foo_pm_delete($pm_id)
                         *     {
                         *         // do something with the message going to be deleted
                         *
                         *         return $pm_id;
                         *     }
                         *     </hookcode>
                         */
                         if (isset($PHORUM['hooks']['pm_delete'])) {
                             phorum_hook('pm_delete', $pm_id);
                         }

                    }
                }

                // Invalidate user cache, to update message counts.
                phorum_cache_remove('user',$user_id);
            }

            // Move checked messages to another folder.
            elseif (isset($_POST["move"]) && isset($_POST["checked"])) {
                $to = $_POST['target_folder'];
                if (! empty($to)) {
                    foreach($_POST["checked"] as $pm_id) {
                        if (phorum_db_pm_get($pm_id, $folder_id)) {
                            phorum_db_pm_move($pm_id, $folder_id, $to);
                        }
                    }
                }
            }

            $page = "list";
            $redirect = true;

            break;


        // Actions which are triggered from the post form.
        case "post":

            // Parse clicks on the image buttons that we use for
            // deleting recipients from the list of recipients.
            // These are not sent as name=value, but instead
            // name_x=xclickoffset and name_y=yclickoffset are sent.
            // Also accept normal button clicks with name="del_rcpt::<id>",
            // so template builders can use that.
            $del_rcpt = NULL;
            foreach ($_POST as $key => $val) {
                if (preg_match('/^del_rcpt::(\d+)(_x)?$/', $key, $m)) {
                    $del_rcpt = $m[1];
                    break;
                }
            }

            // Determine what action to perform.
            $action = "post";
            if (isset($_POST["preview"])) $action = "preview";
            if (isset($_POST["rcpt_add"])) $action = "rcpt_add";
            if (!is_null($del_rcpt)) $action = "del_rcpt";

            // Adding a recipient.
            if ($action == "rcpt_add" || $action == "preview" || $action == "post") {

                // Convert adding a recipient by name to adding by user id.
                if (isset($_POST["to_name"])) {
                    $to_name = trim($_POST["to_name"]);
                    if ($to_name != '') {
                        $to_user_ids = phorum_api_user_search(
                            'display_name', $to_name, '=', TRUE
                        );
                        if (empty($to_user_ids) || count($to_user_ids) > 1) {
                            $error = $PHORUM["DATA"]["LANG"]["UserNotFound"];
                        } else {
                            $_POST["to_id"] = array_shift($to_user_ids);
                            unset($_POST["to_name"]);
                        }
                    }
                }

                // Add a recipient by id.
                if (isset($_POST["to_id"]) && is_numeric($_POST["to_id"])) {
                    $user = phorum_api_user_get($_POST["to_id"]);
                    if ($user && $user["active"] == PHORUM_USER_ACTIVE) {
                        $recipients[$user["user_id"]] = $user;
                    } else {
                        $error = $PHORUM["DATA"]["LANG"]["UserNotFound"];
                    }
                }

                $page = "send";

            // Deleting a recipient.
            } elseif ($action == "del_rcpt") {

                unset($recipients[$del_rcpt]);
                $page = "send";

                // When deleting a recipient, we always have to
                // show the user selection. Put it back in, for
                // situations where we had the user selection
                // hidden intentionally.
                $hide_userselect = 0;
            }

            // For previewing the message, no action has to be taken.
            if ($action == "preview") {
                $page = "send";
            }

            // Posting the message.
            elseif ($action == "post") {

                // Only send the message if we have at least one recipient.
                if (count($recipients)) {
                    $_POST["subject"] = trim($_POST["subject"]);
                    $_POST["message"] = trim($_POST["message"]);

                    // Only send the message if all required message data is filled in.
                    if ($_POST["subject"] == '' || $_POST["message"] == '') {

                        $error = $PHORUM["DATA"]["LANG"]["PMRequiredFields"];

                    // Message data is okay. Post the message.
                    } else {

                        if (empty($_POST["keep"])) $_POST["keep"] = 0;

                        // Check if sender and recipients have not yet reached the
                        // maximum number of messages that may be stored on the server.
                        // Administrators may always send PM.
                        if (!$PHORUM['user']['admin'] && isset($PHORUM['max_pm_messagecount']) && $PHORUM['max_pm_messagecount'])
                        {
                            // Build a list of users to check.
                            $checkusers = $recipients;
                            if ($_POST['keep']) $checkusers[] = $PHORUM['user'];

                            // Check all users.
                            foreach ($checkusers as $user)
                            {
                                if ($user['admin']) continue; // No limits for admins
                                $current_count = phorum_db_pm_messagecount(PHORUM_PM_ALLFOLDERS, $user["user_id"]);
                                if ($current_count['total'] >= $PHORUM['max_pm_messagecount']) {
                                    if ($user['user_id'] == $PHORUM["user"]["user_id"]) {
                                        $error = $PHORUM["DATA"]["LANG"]["PMFromMailboxFull"];
                                    } else {
                                        $error = $PHORUM["DATA"]["LANG"]["PMToMailboxFull"];
                                        $recipient =
                                            (empty($PHORUM["custom_display_name"])
                                             ? htmlspecialchars($user["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
                                             : $user["display_name"]);
                                        $error = str_replace('%recipient%', $recipient, $error);
                                    }
                                }
                            }
                        }

                        /**
                         * [hook]
                         *     pm_before_send
                         *
                         * [availability]
                         *     Phorum 5 >= 5.2.15
                         *
                         * [description]
                         *     This hook can be used for doing modifications to
                         *     PM message data that is stored in the database.
                         *     This hook can also be used to apply checks to
                         *     the data that is to be posted and to return an
                         *     error in case the data should not be posted.
                         *
                         * [category]
                         *     Private message system
                         *
                         * [when]
                         *     Just before the private message is stored in
                         *     the database.
                         *
                         * [input]
                         *     An array containing private message data. The
                         *     fields in this data are "subject", "message",
                         *     "recipients" and "keep".
                         *
                         * [output]
                         *     The message data, possibly modified. A hook can
                         *     set the field "error" in the data. In that case,
                         *     sending the PM will be halted and the error
                         *     message is shown to the user.
                         *
                         * [example]
                         *     <hookcode>
                         *     function phorum_mod_foo_pm_send_init($message, $action)
                         *     {
                         *         if ($message['error'] !== NULL) return $message;
                         *
                         *         // Enable "keep copy" option by default.
                         *         if ($action === NULL) {
                         *             $message['keep'] = 1;
                         *         }
                         *
                         *         return $message;
                         *     }
                         *     </hookcode>
                         */
                        $pm_message = array(
                            'subject'       => $_POST['subject'],
                            'message'       => $_POST['message'],
                            'recipients'    => $recipients,
                            'keep'          => $_POST['keep'],
                            'error'         => NULL
                        );
                        if (isset($PHORUM['hooks']['pm_before_send'])) {
                            $pm_message = phorum_hook('pm_before_send', $pm_message);
                            if ($pm_message['error']) {
                                $error = $pm_message['error'];
                            }
                        }

                        // Send the private message if no errors occurred.
                        if (empty($error)) {

                            $pm_message_id = phorum_db_pm_send($pm_message["subject"], $pm_message["message"], array_keys($pm_message['recipients']), NULL, $pm_message["keep"]);

                            $pm_message['pm_message_id'] = $pm_message_id;
                            $pm_message['from_username'] = $PHORUM['user']['display_name'];
                            $pm_message['user_id']       = $user_id;

                            // Show an error in case of problems.
                            if (! $pm_message_id) {

                                $error = $PHORUM["DATA"]["LANG"]["PMNotSent"];

                            // Do e-mail notifications on successful sending.
                            } elseif (!empty($PHORUM['allow_pm_email_notify'])) {

                                include_once("./include/email_functions.php");

                                // Sort all recipients that want a notify by language.
                                $langrcpts = array();
                                foreach ($pm_message['recipients'] as $rcpt_id => $rcpt) {

                                    if ($rcpt["pm_email_notify"]) {
                                        if (!isset($langrcpts[$rcpt["user_language"]])) {
                                            $langrcpts[$rcpt["user_language"]] = array($rcpt);
                                        } else {
                                            $langrcpts[$rcpt["user_language"]][] = $rcpt;
                                        }
                                    }
                                }

                                phorum_email_pm_notice($pm_message, $langrcpts);

                            }

                            if (isset($PHORUM["hooks"]["pm_sent"])) {
                                phorum_hook("pm_sent", $pm_message, array_keys($pm_message['recipients']));
                            }
                        }

                        // Invalidate user cache, to update message counts.
                        phorum_cache_remove('user', $user_id);
                        foreach ($recipients as $rcpt) {
                            phorum_cache_remove('user', $rcpt["user_id"]);
                        }

                        $redirect_message = "PMSent";
                        $page = "list";
                        $folder_id = "inbox";
                    }

                } else {
                    $error = $PHORUM["DATA"]["LANG"]["PMNoRecipients"];
                }

                // Stay on the post page in case of errors. Redirect on success.
                if ($error) {
                    $page = "send";
                } else {
                    $redirect = true;
                }

            }

            break;


        // Actions that are triggered from the buddy list.
        case "buddies":

            // Delete all checked buddies.
            if (isset($_POST["delete"]) && isset($_POST["checked"])) {
                foreach($_POST["checked"] as $buddy_user_id) {
                    phorum_db_pm_buddy_delete($buddy_user_id);
                    if (isset($PHORUM["hooks"]["buddy_delete"]))
                        phorum_hook("buddy_delete", $buddy_user_id);
                }
            }

            // Send a PM to the checked buddies.
            if (isset($_POST["send_pm"]) && isset($_POST["checked"])) {
                $pm_rcpts = $_POST["checked"];
                if (count($pm_rcpts)) {
                    $redirect = true;
                    $page = "send";
                } else {
                    unset($pm_rcpts);
                }
            }

            break;


        // Add a user to this user's buddy list.
        case "addbuddy":

            $buddy_user_id = $PHORUM["args"]["addbuddy_id"];
            if (!empty($buddy_user_id)) {
                if (phorum_db_pm_buddy_add($buddy_user_id)) {
                    $okmsg = $PHORUM["DATA"]["LANG"]["BuddyAddSuccess"];
                    if (isset($PHORUM["hooks"]["buddy_add"]))
                        phorum_hook("buddy_add", $buddy_user_id);
                } else {
                    $error = $PHORUM["DATA"]["LANG"]["BuddyAddFail"];
                }
            }
            break;


        default:
            trigger_error(
                "Unhandled action for pm.php: " . htmlspecialchars($action),
                E_USER_ERROR
            );

    }

    // The action has been completed successfully.
    // Redirect the user to the result page.
    if ($redirect)
    {
        $args = array(
            PHORUM_PM_URL,
            "page=" . $page,
            "folder_id=" . $folder_id,
        );
        if (isset($pm_rcpts)) $args[]  = "to_id=" . implode(':', $pm_rcpts);
        if (!empty($pm_id)) $args[]  = "pm_id=" . $pm_id;
        if (!empty($redirect_message)) $args[] = "okmsg=" . $redirect_message;

        $redir_url = call_user_func_array('phorum_get_url', $args);

        phorum_redirect_by_url($redir_url);
        exit();
    }

}

// ------------------------------------------------------------------------
// Display a PM page
// ------------------------------------------------------------------------

if(empty($PHORUM["DATA"]["HEADING"])){
    $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["PrivateMessages"];
}

// unset default description
$PHORUM['DATA']['DESCRIPTION'] = '';
$PHORUM['DATA']['HTML_DESCRIPTION'] = '';

// Use the message list as the default page.
if (!$page){
    $page = "list";
    $folder_id = PHORUM_PM_INBOX;
}

// Show an OK message for a redirected page?
$okmsg_id = phorum_getparam('okmsg');
if ($okmsg_id && isset($PHORUM["DATA"]["LANG"][$okmsg_id])) {
    $okmsg = $PHORUM["DATA"]["LANG"][$okmsg_id];
}

// Make error and OK messages available in the template.
$PHORUM["DATA"]["ERROR"] = (empty($error)) ? "" : $error;
$PHORUM["DATA"]["OKMSG"] = (empty($okmsg)) ? "" : $okmsg;

$template = "";

switch ($page) {

    // Manage the PM folders.
    case "folders":

        $PHORUM["DATA"]["CREATE_FOLDER_NAME"] = isset($_POST["create_folder_name"]) ? htmlspecialchars($_POST["create_folder_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) : '';
        $PHORUM["DATA"]["RENAME_FOLDER_NAME"] = isset($_POST["rename_folder_name"]) ? htmlspecialchars($_POST["rename_folder_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) : '';
        $template = "pm_folders";
        break;


    // Manage the buddies.
    case "buddies":

        // Retrieve a list of users that are buddies for the current user.
        $buddy_list = phorum_db_pm_buddy_list(NULL, true);
        if (count($buddy_list)) {
            $buddy_users = phorum_api_user_get(array_keys($buddy_list));
            if (isset($PHORUM["hooks"]["read_user_info"]))
                $buddy_users = phorum_hook("read_user_info", $buddy_users);
        } else {
            $buddy_users = array();
        }

        // Sort the buddies by name.
        function phorum_sort_buddy_list($a,$b) {
            return strcasecmp($a["display_name"], $b["display_name"]);
        }
        uasort($buddy_users, 'phorum_sort_buddy_list');

        $buddies = array();
        foreach ($buddy_users as $id => $buddy_user) {
            $buddy = array(
                'user_id'     => $id,
                'display_name' =>
                    (empty($PHORUM["custom_display_name"])
                     ? htmlspecialchars($buddy_user["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
                     : $buddy_user["display_name"]),
                'mutual'      => $buddy_list[$id]["mutual"],
            );

            $buddy["URL"]["PROFILE"] =
                phorum_get_url(PHORUM_PROFILE_URL, $buddy_user["user_id"]);

            if (!$buddy_user['hide_activity']) {
              $buddy["raw_date_last_active"] = $buddy_user["date_last_active"];
              $buddy["date_last_active"] = phorum_date($PHORUM["short_date_time"], $buddy_user["date_last_active"]);
            } else {
              $buddy["date_last_active"] = "-";
            }
            $buddies[$id] = $buddy;
        }

        /**
         * [hook]
         *     buddy_list
         *
         * [availability]
         *     Phorum 5 >= 5.2.7
         *
         * [description]
         *     This hook can be used for reformatting a list of buddies.
         *     Reformatting could mean things like changing the sort
         *     order or modifying the fields in the buddy arrays.
         *
         * [category]
         *     Buddies system
         *
         * [when]
         *     Right after Phorum has formatted the buddy list. This is
         *     primarily done when the list of buddies is shown in the
         *     private message system.
         *
         * [input]
         *     An array of buddy info arrays. Each info array contains a
         *     couple of fields that describe the budy: user_id,
         *     display_name, mutual (0 = not mutual, 1 = mutual),
         *     URL->PROFILE, date_last_active (formatted date) and
         *     raw_date_last_active (Epoch timestamp).
         *
         * [output]
         *     The same array as was used for the hook call argument,
         *     possibly with some updated fields in it.
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_buddy_list($buddies)
         *     {
         *         // Add a CSS class around the display names for
         *         // the mutual buddies (of course this could also
         *         // easily be implemented as a pure template change,
         *         // but remember that this is just an example).
         *         foreach ($buddies as $id => $buddy)
         *         {
         *             if ($buddy['mutual'])
         *             {
         *                 $buddies[$id]['display_name'] =
         *                     '<span class="mutual_buddy">' .
         *                     $buddy['display_name'] .
         *                     '</span>';
         *             }
         *         }
         *
         *         return $buddies;
         *     }
         *     </hookcode>
         */
        if (isset($PHORUM['hooks']['buddy_list'])) {
            $buddies = phorum_hook('buddy_list', $buddies);
        }

        $PHORUM["DATA"]["USERTRACK"] = $PHORUM["track_user_activity"];
        $PHORUM["DATA"]["BUDDIES"] = $buddies;
        $PHORUM["DATA"]["BUDDYCOUNT"] = count($buddies);

        $PHORUM["DATA"]["PMLOCATION"] = $PHORUM["DATA"]["LANG"]["Buddies"];

        $template = "pm_buddies";
        break;


    // Show a listing of messages in a folder.
    case "list":

        // Check if the folder exists for the user.
        if (! isset($pm_folders[$folder_id])) {
            $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMFolderNotAvailable"];
            $template = "stdblock";
        } else {

            $list = null;

            /**
             * [hook]
             *     before_pm_list
             *
             * [availability]
             *     Phorum 5 >= 5.2.17
             *
             * [description]
             *     This hook can be used for retreiveing a list of messages
             *     via an alernate method other than the built in.
             *
             * [category]
             *     Private message system
             *
             * [when]
             *     Before the private message list is retreived from the database
             *
             * [input]
             *     A PM folder id
             *
             * [output]
             *     A list of private messages compatible with
             *     phourm_db_pm_list()
             *
             * [example]
             *     <hookcode>
             *     function phorum_mod_foo_before_pm_list($folder_id)
             *     {
             *         // Query the db directly and apply custom code
             *
             *         return $messages;
             *     }
             *     </hookcode>
             */
            if (isset($PHORUM['hooks']['before_pm_list'])) {
                $list = phorum_hook('before_pm_list', $folder_id);
            }

            if(is_null($list)){
                $list = phorum_db_pm_list($folder_id);
            }

            // Prepare data for the templates (formatting and XSS prevention).
            $list = phorum_pm_format($list);

            /**
             * [hook]
             *     pm_list
             *
             * [availability]
             *     Phorum 5 >= 5.2.7
             *
             * [description]
             *     This hook can be used for reformatting a list of
             *     private messages.
             *
             * [category]
             *     Private message system
             *
             * [when]
             *     Right after Phorum has formatted the private message list.
             *     This is primarily done when a list of private messages is
             *     shown in the private message system.
             *
             * [input]
             *     An array of private message info arrays.
             *
             * [output]
             *     The same array as was used for the hook call argument,
             *     possibly with some updated fields in it.
             *
             * [example]
             *     <hookcode>
             *     function phorum_mod_foo_pm_list($messages)
             *     {
             *         // Filter out private messages that are sent by
             *         // evil user X with user_id 666.
             *         foreach ($messages as $id => $message) {
             *             if ($message['user_id'] == 666) {
             *                 unset($messages[$id]);
             *             }
             *         }
             *         return $messages;
             *     }
             *     </hookcode>
             */
            if (isset($PHORUM['hooks']['pm_list'])) {
                $list = phorum_hook('pm_list', $list);
            }

            // Setup template variables.
            $PHORUM["DATA"]["MESSAGECOUNT"] = count($list);
            $PHORUM["DATA"]["MESSAGES"] = $list;
            $PHORUM["DATA"]["PMLOCATION"] = $pm_folders[$folder_id]["name"];

            $template = "pm_list";
        }

        break;


    // Read a single private message.
    case "read":

        if (($message=phorum_db_pm_get($pm_id, $folder_id))) {

            // Mark the message read.
            if (! $message['read_flag']) {
                phorum_db_pm_setflag($message["pm_message_id"], PHORUM_PM_READ_FLAG, true);

                // Invalidate user cache, to update message counts.
                phorum_cache_remove('user',$user_id);
            }

            // Run the message through the default message formatting.
            list($message) = phorum_pm_format(array($message));

            // We do not want to show a recipient list if there are
            // a lot of recipients.
            $message["show_recipient_list"] = ($message["recipient_count"]<10);

            /**
             * [hook]
             *     pm_read
             *
             * [availability]
             *     Phorum 5 >= 5.2.7
             *
             * [description]
             *     This hook can be used for reformatting a single private
             *     message for reading.
             *
             * [category]
             *     Private message system
             *
             * [when]
             *     Right after Phorum has formatted the private message.
             *     This is primarily done when a private message read page is
             *     shown in the private message system.
             *
             * [input]
             *     An array, describing a single private message.
             *
             * [output]
             *     The same array as was used for the hook call argument,
             *     possibly with some updated fields in it.
             *
             * [example}
             *     <hookcode>
             *     function phorum_mod_foo_pm_read($message)
             *     {
             *         // Add a notice to messages that were sent by
             *         // evil user X with user_id 666.
             *         if ($message['user_id'] == 666) {
             *             $message['subject'] .= ' [EVIL!]';
             *         }
             *
             *         return $message;
             *     }
             *     </hookcode>
             */
            if (isset($PHORUM['hooks']['pm_read'])) {
                $message = phorum_hook('pm_read', $message);
            }

            $PHORUM["DATA"]["MESSAGE"] = $message;
            $PHORUM["DATA"]["PMLOCATION"] = $PHORUM["DATA"]["LANG"]["PMRead"];

            // re-init folder list to account for change in read flags
            $pm_folders = phorum_db_pm_getfolders(NULL, true);

            // Set folder id to the right folder for this message.
            $folder_id = $message["pm_folder_id"];
            if ($folder_id == 0) {
                $folder_id = $message["special_folder"];
            }

            $template = "pm_read";

        } else {

            // The message was not found. Show an error.
            $PHORUM["DATA"]["BLOCK_CONTENT"] = $PHORUM["DATA"]["LANG"]["PMNotAvailable"];
            $template = "stdblock";
        }

        break;


    // Post a new private message.
    case "send":

        // Setup the default array with the message data.
        $msg = array(
            "user_id"       => $PHORUM["user"]["user_id"],
            "author"        => $PHORUM["user"]["display_name"],
            "keep"          => isset($_POST["keep"]) && $_POST["keep"] ? 1 : 0,
            "subject"       => isset($_POST["subject"]) ? $_POST["subject"] : '',
            "message"       => isset($_POST["message"]) ? $_POST["message"] : '',
            "preview"       => isset($_POST["preview"]) ? 1 : 0,
            "recipients"    => $recipients,
        );

        // Data initialization for posting messages on first request.
        if ($action === NULL || $action != "post")
        {
            // Setup data for sending a private message to specified recipients.
            // Recipients are passed on as a standard phorum argument "to_id"
            // containing a colon separated list of users.
            if (isset($PHORUM["args"]["to_id"])) {
                foreach (explode(":", $PHORUM["args"]["to_id"]) as $rcpt_id) {
                    settype($rcpt_id, "int");
                    $user = phorum_api_user_get($rcpt_id);
                    if ($user) {
                        $msg["recipients"][$rcpt_id] = array(
                            "display_name" => $user["display_name"],
                            "user_id"      => $user["user_id"]
                        );
                    }
                }

                $hide_userselect = 1;

            // Setup data for replying to a private message.
            } elseif (isset($pm_id)) {
                $message = phorum_db_pm_get($pm_id);
                $msg["subject"] = $message["subject"];
                $msg["message"] = $message["message"];
                $msg["recipients"][$message["user_id"]] = array(
                    "display_name" => $message["author"],
                    "user_id"  => $message["user_id"]
                );
                $msg = phorum_pm_quoteformat($message["author"], $message["user_id"], $msg);

                // Include the other recipient, excecpt the active
                // user himself, when replying to all.
                if (isset($_POST["reply_to_all"])) {
                    foreach($message["recipients"] as $rcpt) {
                        if ($user_id == $rcpt["user_id"]) continue;
                        $msg["recipients"][$rcpt["user_id"]] = array(
                            "display_name" => $rcpt["display_name"],
                            "user_id"  => $rcpt["user_id"],
                        );
                    }
                }

                $hide_userselect = 1;

            // Setup data for replying privately to a forum post.
            } elseif (isset($PHORUM["args"]["message_id"])) {

                $message = phorum_db_get_message($PHORUM["args"]["message_id"], "message_id", true);

                if (phorum_api_user_check_access(PHORUM_USER_ALLOW_READ) && ($PHORUM["forum_id"]==$message["forum_id"] || $message["forum_id"] == 0)) {

                    // get url to the message board thread
                    $origurl = phorum_get_url(PHORUM_READ_URL, $message["thread"], $message["message_id"]);

                    // Get the data for the user that we reply to.
                    $user = phorum_api_user_get($message["user_id"]);

                    $msg["subject"] = $message["subject"];
                    $msg["message"] = $message["body"];
                    $msg["recipients"][$message["user_id"]] = array(
                        'display_name' => $user["display_name"],
                        'user_id'  => $user["user_id"]
                    );
                    $msg = phorum_pm_quoteformat($user["display_name"], $user["user_id"], $msg, $origurl);
                }

                $hide_userselect = 1;
            }
        }

        /**
         * [hook]
         *     pm_send_init
         *
         * [availability]
         *     Phorum 5 >= 5.2.15
         *
         * [description]
         *     This hook can be used for doing modifications to the
         *     PM message data that is used for sending a PM at an
         *     early stage in the request.
         *
         * [category]
         *     Private message system
         *
         * [when]
         *     At the start of "send" page handling, after the code that sets
         *     up the message values on the first request.
         *
         * [input]
         *     Two arguments: the private message data array and the action that
         *     is being handled (one of NULL (initial request), rpct_add,
         *     preview, posting).
         *
         * [output]
         *     The private message data, possibly modified.
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_pm_send_init($message, $action)
         *     {
         *         // Enable "keep copy" option by default.
         *         if ($action === NULL) {
         *             $message['keep'] = 1;
         *         }
         *
         *         return $message;
         *     }
         *     </hookcode>
         */
        if (isset($PHORUM['hooks']['pm_send_init'])) {
            $msg = phorum_hook('pm_send_init', $msg, $action);
        }

        // Setup data for previewing a message.
        if ($msg["preview"]) {
            list($preview) = phorum_pm_format(array($msg));
            $PHORUM["DATA"]["PREVIEW"] = $preview;
        }

        // XSS prevention.
        foreach ($msg as $key => $val) {
            switch ($key) {
                case "recipients": {
                    foreach ($val as $id => $data) {
                        $msg[$key][$id]["display_name"] =
                          (empty($PHORUM["custom_display_name"])
                           ? htmlspecialchars($data["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
                           : $data["display_name"]);
                    }
                    break;
                }
                case "author": {
                    $msg[$key] =
                      (empty($PHORUM["custom_display_name"])
                       ? htmlspecialchars($val, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) : $val);
                    break;
                }
                default: {
                    $msg[$key] = htmlspecialchars($val, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
                    break;
                }
            }
        }


        $PHORUM["DATA"]["MESSAGE"] = $msg;
        $PHORUM["DATA"]["RECIPIENT_COUNT"] = count($msg["recipients"]);
        $PHORUM["DATA"]["SHOW_USERSELECTION"] = true;

        // Determine what input element gets the focus.
        $focus_id = 'userselection';
        if ($PHORUM["DATA"]["RECIPIENT_COUNT"]) $focus_id = 'subject';
        if (!empty($msg["subject"])) $focus_id = 'body';
        $PHORUM["DATA"]["FOCUS_TO_ID"] = $focus_id;

        // Create data for a user dropdown list, if configured.
        if ($PHORUM["DATA"]["SHOW_USERSELECTION"] && $PHORUM["enable_dropdown_userlist"])
        {
            $allusers = array();
            $userlist = phorum_api_user_list(PHORUM_GET_ACTIVE);
            foreach ($userlist as $user_id => $userinfo){
                if (isset($msg["recipients"][$user_id])) continue;
                $userinfo["display_name"] = htmlspecialchars($userinfo["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
                $userinfo["user_id"] = $user_id;
                $allusers[] = $userinfo;
            }
            $PHORUM["DATA"]["USERS"] = $allusers;
            if (count($allusers) == 0) $PHORUM["DATA"]["SHOW_USERSELECTION"] = false;
        }

        /**
         * [hook]
         *     pm_before_editor
         *
         * [availability]
         *     Phorum 5 >= 5.2.15
         *
         * [description]
         *     This hook can be used for tweaking the template data that
         *     is used for setting up the private message editor.
         *
         * [category]
         *     Private message system
         *
         * [when]
         *     Right after Phorum has formatted the template data for the
         *     editor and just before the editor template is loaded.
         *
         * [input]
         *     Two arguments: the private message data array and the action that
         *     is being handled (one of NULL (initial request), rpct_add,
         *     preview, posting).
         *
         * [output]
         *     The private message data, possibly modified.
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_pm_before_editor($message)
         *     {
         *         return $message;
         *     }
         *     </hookcode>
         */
        if (isset($PHORUM['hooks']['pm_before_editor'])) {
            $msg = phorum_hook('pm_before_editor', $msg, $action);
        }

        $PHORUM["DATA"]["PMLOCATION"] = $PHORUM["DATA"]["LANG"]["SendPM"];
        $template = "pm_post";
        break;

    default:

        trigger_error(
            "Illegal page requested: " . htmlspecialchars($page),
            E_USER_ERROR
        );
}

if ($hide_userselect) {
    $PHORUM["DATA"]["SHOW_USERSELECTION"] = 0;
}

// Make message count and quota information available in the templates.
$PHORUM['DATA']['MAX_PM_MESSAGECOUNT'] = 0;
if (! $PHORUM['user']['admin'] && isset($PHORUM['max_pm_messagecount']) && $PHORUM['max_pm_messagecount']) {
    $PHORUM['DATA']['MAX_PM_MESSAGECOUNT'] = $PHORUM['max_pm_messagecount'];
    if ($PHORUM['max_pm_messagecount'])
    {
        $current_count = phorum_db_pm_messagecount(PHORUM_PM_ALLFOLDERS);
        $PHORUM['DATA']['PM_MESSAGECOUNT'] = $current_count['total'];
        $space_left = $PHORUM['max_pm_messagecount'] - $current_count['total'];
        if ($space_left < 0) $space_left = 0;
        $PHORUM['DATA']['PM_SPACE_LEFT'] = $space_left;
        $PHORUM['DATA']['LANG']['PMSpaceLeft'] = str_replace('%pm_space_left%', $space_left, $PHORUM['DATA']['LANG']['PMSpaceLeft']);
    }
}

// Make a list of folders for use in the menu and a list of folders that
// the user created. The latter will be set to zero if no user folders
// are available.

$pm_userfolders = array();
foreach($pm_folders as $id => $data)
{
    $pm_folders[$id]["is_special"] = is_numeric($id) ? 0 : 1;
    $pm_folders[$id]["is_outgoing"] = $id == PHORUM_PM_OUTBOX;
    $pm_folders[$id]["id"] = $id;
    $pm_folders[$id]["name"] = htmlspecialchars($data["name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
    $pm_folders[$id]["url"] = phorum_get_url(PHORUM_PM_URL, "page=list", "folder_id=$id");

    if (!$pm_folders[$id]["is_special"]) {
        $pm_userfolders[$id] = $pm_folders[$id];
    }
}

$PHORUM["DATA"]["URL"]["PM_FOLDERS"] = phorum_get_url(PHORUM_PM_URL, "page=folders");
$PHORUM["DATA"]["URL"]["PM_SEND"] = phorum_get_url(PHORUM_PM_URL, "page=send");
$PHORUM["DATA"]["URL"]["BUDDIES"] = phorum_get_url(PHORUM_PM_URL, "page=buddies");

$PHORUM["DATA"]["PM_FOLDERS"] = $pm_folders;
$PHORUM["DATA"]["PM_USERFOLDERS"] = count($pm_userfolders) ? $pm_userfolders : 0;


// Set some default template data.
$PHORUM["DATA"]["URL"]["ACTION"]=phorum_get_url( PHORUM_PM_ACTION_URL );
$PHORUM["DATA"]["FOLDER_ID"] = $folder_id;
$PHORUM["DATA"]["FOLDER_IS_INCOMING"] = $folder_id == PHORUM_PM_OUTBOX ? 0 : 1;
$PHORUM["DATA"]["PM_PAGE"] = $page;
$PHORUM["DATA"]["PM_TEMPLATE"] = $template;
$PHORUM["DATA"]["HIDE_USERSELECT"] = $hide_userselect;

if ($error_msg) {
    $PHORUM["DATA"]["ERROR"] = $error_msg;
    unset($PHORUM["DATA"]["MESSAGE"]);
    phorum_output("message");
} else {
    phorum_output("pm");
}


// ------------------------------------------------------------------------
// Utility functions
// ------------------------------------------------------------------------

// Apply the default forum message formatting to a private message.
function phorum_pm_format($messages)
{
    $PHORUM = $GLOBALS["PHORUM"];

    include_once("./include/format_functions.php");

    // Reformat message so it looks like a forum message (so we can run it
    // through phorum_format_messages) and do some PM specific formatting.
    foreach ($messages as $id => $message)
    {
        // The formatting code expects a message id.
        $messages[$id]["message_id"] = $id;

        // Read URLs need a folder id, so we only create that URL if
        // one's available.
        if (isset($message['pm_folder_id'])) {
            $folder_id = $message['pm_folder_id']
                       ? $message['pm_folder_id']
                       : $message['special_folder'];
            $messages[$id]["URL"]["READ"] = phorum_get_url(PHORUM_PM_URL, "page=read", "folder_id=$folder_id", "pm_id=$id");
        }

        // The datestamp is only available for already posted messages.
        if (isset($message['datestamp'])) {
            $messages[$id]["raw_date"] = $message["datestamp"];
            $messages[$id]["date"] = phorum_date($PHORUM["short_date_time"], $message["datestamp"]);
        }

        if (isset($message['meta']) && !is_array($message['meta'])) {
            $messages[$id]['meta'] = unserialize($message['meta']);
        }

        $messages[$id]["body"] = isset($message["message"]) ? $message["message"] : "";
        $messages[$id]["email"] = "";

        $messages[$id]["URL"]["PROFILE"] = phorum_get_url(PHORUM_PROFILE_URL, $message["user_id"]);

        $messages[$id]["recipient_count"] = 0;
        $messages[$id]["receive_count"] = 0;

        if (isset($message["recipients"]) && is_array($message["recipients"]))
        {
            $receive_count = 0;

            foreach ($message["recipients"] as $rcpt_id => $rcpt)
            {
                if (!empty($rcpt["read_flag"])) $receive_count++;

                if (! isset($rcpt["display_name"])) {
                    $messages[$id]["recipients"][$rcpt_id]["display_name"]=
                        $PHORUM["DATA"]["LANG"]["AnonymousUser"];
                } else {
                    $messages[$id]["recipients"][$rcpt_id]["display_name"]=
                        (empty($PHORUM["custom_display_name"])
                         ? htmlspecialchars($rcpt["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"])
                         : $rcpt["display_name"]);
                    $messages[$id]["recipients"][$rcpt_id]["URL"]["PROFILE"] =
                        phorum_get_url(PHORUM_PROFILE_URL, $rcpt_id);
                }
            }

            $messages[$id]["recipient_count"] = count($message["recipients"]);
            $messages[$id]["receive_count"] = $receive_count;
        }
    }

    // Run the messages through the standard formatting code.
    $messages = phorum_format_messages($messages);

    // Reformat message back to a private message.
    foreach ($messages as $id => $message)
    {
        $messages[$id]["message"] = $message["body"];
        unset($messages[$id]["body"]);
    }

    return $messages;
}

// Apply message reply quoting to a private message.
function phorum_pm_quoteformat($orig_author, $orig_author_id, $message, $inreplyto = NULL)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Build the reply subject.
    if (substr($message["subject"], 0, 3) != "Re:") {
        $message["subject"] = "Re: ".$message["subject"];
    }

    // Lookup the plain text name that we have to use for the author that we reply to.
    $author = phorum_api_user_get_display_name($orig_author_id, '', PHORUM_FLAG_PLAINTEXT);

    // TODO we'll have to handle anonymous users in the PM box. Those are
    // TODO users which sent a PM to somebody, but signed out afterwards.
    // TODO Currently, there's no graceful handling for that I think
    // TODO (maybe it's handled already, but that would only be by accident).

    if (isset($PHORUM["hooks"]["quote"]))
        $quote = phorum_hook("quote", array($author, $message["message"], $orig_author_id));

    if (empty($quote) || is_array($quote))
    {
        // Build a quoted version of the message body.
        $quote = phorum_strip_body($message["message"]);
        $quote = str_replace("\n", "\n> ", $quote);
        $quote = wordwrap(trim($quote), 50, "\n> ", true);
        $quote = "$author {$PHORUM['DATA']['LANG']['Wrote']}:\n" .
                 str_repeat("-", 55)."\n> {$quote}\n\n\n";
    }

    $quote = ($inreplyto != NULL ? "{$PHORUM['DATA']['LANG']['InReplyTo']} {$inreplyto}\n\n" : '') . $quote;

    $message["message"] = $quote;

    return $message;
}

?>
