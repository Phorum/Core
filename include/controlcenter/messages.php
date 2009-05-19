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

if (!defined("PHORUM_CONTROL_CENTER")) return;

if (!$PHORUM["DATA"]["MESSAGE_MODERATOR"]) {
    $phorum->redirect(PHORUM_CONTROLCENTER_URL);
}

// the number of days to show
if (isset($_POST['moddays']) && is_numeric($_POST['moddays'])) {
    $moddays = (int)$_POST['moddays'];
} elseif(isset($PHORUM['args']['moddays']) && !empty($PHORUM["args"]['moddays']) && is_numeric($PHORUM["args"]['moddays'])) {
    $moddays = (int)$PHORUM['args']['moddays'];
} else {
    $moddays=$phorum->user->get_setting("cc_messages_moddays");
}
if ($moddays === NULL) {
    $moddays = 2;
}


if (isset($_POST['onlyunapproved']) && is_numeric($_POST['onlyunapproved'])) {
    $showwaiting = (int)$_POST['onlyunapproved'];
} elseif(isset($PHORUM['args']['onlyunapproved']) && !empty($PHORUM["args"]['onlyunapproved']) && is_numeric($PHORUM["args"]['onlyunapproved'])) {
    $showwaiting = (int)$PHORUM['args']['onlyunapproved'];
} else {
    $showwaiting = $phorum->user->get_setting('cc_messages_onlyunapproved');
}
if (empty($showwaiting)) {
    $showwaiting = 0;
}
$PHORUM['DATA']['SELECTED'] = $moddays;
$PHORUM['DATA']['SELECTED_2'] = $showwaiting?true:false;

// Store current selection for the user.
$phorum->user->save_settings(array(
    "cc_messages_moddays"        => $moddays,
    "cc_messages_onlyunapproved" => $showwaiting
));

// some needed vars
$numunapproved = 0;
$oldforum = $PHORUM['forum_id'];

$mod_forums = $phorum->user->check_access(
    PHORUM_USER_ALLOW_MODERATE_MESSAGES,
    PHORUM_ACCESS_LIST
);
$gotforums = (count($mod_forums) > 0);


if ($gotforums && isset($_POST['deleteids']) && count($_POST['deleteids']))
{
    //print_var($_POST['deleteids']);
    $deleteids = $_POST['deleteids'];
    foreach($deleteids as $did => $did_var) {
        $deleteids[$did] = (int)$did_var;
    }
    $delete_messages = phorum_db_get_message(array_keys($deleteids),'message_id',true);
    //print_var($delete_messages);
    foreach($deleteids as $msgthd_id => $doit) {

        // A hook to allow modules to implement extra or different
        // delete functionality.
        if($doit && isset($mod_forums[$delete_messages[$msgthd_id]['forum_id']])) {


            $delete_handled = 0;
            if (isset($PHORUM["hooks"]["before_delete"]))
                list($delete_handled,$msg_ids,$msgthd_id,$delete_messages[$msgthd_id],$delete_mode) = $phorum->modules->hook("before_delete", array(0,0,$msgthd_id,$delete_messages[$msgthd_id],PHORUM_DELETE_MESSAGE));

            // Handle the delete action, unless a module already handled it.
            if (!$delete_handled) {

                // Delete the message from the database.
                phorum_db_delete_message($msgthd_id, PHORUM_DELETE_MESSAGE);

                // Delete the message attachments from the database.
                $files=phorum_db_get_message_file_list($msgthd_id);
                foreach($files as $file_id=>$data) {
                    if ($phorum->file->check_delete_access($file_id)) {
                        $phorum->file->delete($file_id);
                    }
                }
            }

            // Run a hook for performing custom actions after cleanup.
            if (isset($PHORUM["hooks"]["delete"])) {
                $phorum->modules->hook("delete", array($msgthd_id));
            } 
        }

    }
}

$PHORUM['DATA']['PREPOST'] = array();

if ($gotforums)
    $foruminfo = $phorum->forums->get($mod_forums, NULL, NULL, $PHORUM['vroot']);
else
    $foruminfo = array();

foreach($mod_forums as $forum => $rest) {

    $checkvar = 1;
    // Get the threads
    $rows = array();
    // get the thread set started
    $rows = phorum_db_get_unapproved_list($forum,$showwaiting,$moddays);

    // loop through and read all the data in.
    foreach($rows as $key => $row) {
        $numunapproved++;
        $rows[$key]['forumname'] = $foruminfo[$forum]['name'];
        $rows[$key]['checkvar'] = $checkvar;
        if ($checkvar)
            $checkvar = 0;
        $rows[$key]['forum_id'] = $forum;
        $rows[$key]["URL"]["READ"] = $phorum->url(PHORUM_FOREIGN_READ_URL, $forum, $row["thread"], $row['message_id']);
        // we need to fake the forum_id here
        $PHORUM["forum_id"] = $forum;
        $rows[$key]["URL"]["APPROVE_MESSAGE"] = $phorum->url(PHORUM_MODERATION_URL, PHORUM_APPROVE_MESSAGE, $row["message_id"], "prepost=1", "old_forum=" . $oldforum,"onlyunapproved=".$showwaiting,"moddays=".$moddays);
        $rows[$key]["URL"]["APPROVE_TREE"] = $phorum->url(PHORUM_MODERATION_URL, PHORUM_APPROVE_MESSAGE_TREE, $row["message_id"], "prepost=1", "old_forum=" . $oldforum,"onlyunapproved=".$showwaiting,"moddays=".$moddays);
        $rows[$key]["URL"]["DELETE"] = $phorum->url(PHORUM_MODERATION_URL, PHORUM_DELETE_TREE, $row["message_id"], "prepost=1", "old_forum=" . $oldforum,"onlyunapproved=".$showwaiting,"moddays=".$moddays);
        $PHORUM["forum_id"] = $oldforum;
        $rows[$key]["raw_short_datestamp"] = $row["datestamp"];
        $rows[$key]["short_datestamp"] = $phorum->format->date($PHORUM["short_date_time"], $row["datestamp"]);
    }

    $rows = $phorum->format->message($rows);
    $PHORUM['DATA']['PREPOST'] = array_merge($PHORUM['DATA']['PREPOST'], $rows);
}


if (!$numunapproved) {
    $PHORUM["DATA"]["UNAPPROVEDMESSAGE"] = $PHORUM["DATA"]["LANG"]["NoUnapprovedMessages"];
}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["UnapprovedMessages"];

$template = "cc_prepost";
?>
