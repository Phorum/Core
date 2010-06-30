<?php

if(!defined("PHORUM")) return;

function phorum_setup_announcements ()
{
    global $PHORUM;

    // This variable will be used to store the formatted announcements.
    $PHORUM['DATA']['MOD_ANNOUNCEMENTS'] = '';

    // Check if we are on a page on which the announcements have to be shown.
    if (phorum_page == 'index') {
        // Hide the announcements, unless enabled for "index".
        $hide = empty($PHORUM["mod_announcements"]["pages"]["index"]);
        // Show announcements for the root page if "home" is enabled.
        if ($PHORUM['vroot'] == $PHORUM['forum_id'] &&
            !empty($PHORUM["mod_announcements"]["pages"]["home"])) {
            $hide = FALSE;
        }
        if ($hide) return;
    } else {
        if (empty($PHORUM["mod_announcements"]["pages"][phorum_page]))
            return;
    }

    // Check if we need to show announcements.
    $ann_forum_id = NULL;

    // Inside a vroot, where we have a vroot configuration for the forum
    // to use for announcements and the current forum is not that
    // announcement forum.
    if ($PHORUM['vroot'] > 0 && !empty($PHORUM["mod_announcements"]["vroot"][$PHORUM['vroot']]) && $PHORUM["forum_id"] != $PHORUM["mod_announcements"]["vroot"][$PHORUM['vroot']]) {

        $ann_forum_id = $PHORUM["mod_announcements"]["vroot"][$PHORUM['vroot']];

    // Inside the top level folder, where we have a forum that is configured
    // to be used for announcements and the current forum is not that
    // announcement forum.
    } elseif($PHORUM['vroot'] == 0 && !empty($PHORUM["mod_announcements"]["forum_id"]) && $PHORUM["forum_id"] != $PHORUM["mod_announcements"]["forum_id"]) {

        $ann_forum_id = $PHORUM["mod_announcements"]["forum_id"];

    }

    // If no announcement forum_id is found, no announcements
    // have to be shown.
    if ($ann_forum_id === NULL) return;

    // Retrieve the last number of posts from the announcement forum.
    $messages = phorum_db_get_recent_messages(
        $PHORUM["mod_announcements"]["number_to_show"],
        0, $ann_forum_id, 0, true
    );
    unset($messages["users"]);

    // No announcements to show? Then we are done.
    if (count($messages) == 0) return;

    // Read the newflags information for authenticated users.
    $newinfo = NULL;
    if ($PHORUM["DATA"]["LOGGEDIN"]) {
        $newflagkey = $ann_forum_id."-".$PHORUM['user']['user_id'];
        if ($PHORUM['cache_newflags']) {
            $newinfo = phorum_cache_get('newflags',$newflagkey,$PHORUM['cache_version']);
        }
        if($newinfo == NULL) {
            $newinfo = phorum_db_newflag_get_flags($ann_forum_id);
            if ($PHORUM['cache_newflags']) {
                phorum_cache_put('newflags',$newflagkey,$newinfo,86400,$PHORUM['cache_version']);
            }
        }
    }

    require_once("./include/format_functions.php");

    // Process the announcements.
    foreach($messages as $message)
    {
        // Skip this message if it's older than the number of days that was
        // configured in the settings screen.
        if (!empty($PHORUM["mod_announcements"]["days_to_show"]) &&
            $message["datestamp"] < (time()-($PHORUM["mod_announcements"]["days_to_show"]*86400))) continue;

        // Check if there are new messages in the thread.
        if (isset($newinfo)) {
            $new = 0;
            foreach ($message["meta"]["message_ids"] as $id) {
                if (!isset($newinfo[$id]) && $id > $newinfo['min_id']) {
                    $new = 1;
                    break;
                }
            }

            // There are new messages. Setup the template data for showing
            // a new flag.
            if ($new)
            {
                $message["new"] = $new
                                ? $PHORUM["DATA"]["LANG"]["newflag"]
                                : NULL;
                $message["URL"]["NEWPOST"] = phorum_get_url(
                    PHORUM_FOREIGN_READ_URL,
                    $message["forum_id"],
                    $message["thread"],
                    "gotonewpost"
                );
            }
            // No new messages. Skip this thread if only unread announcement
            // messages have to be shown.
            elseif ($PHORUM["mod_announcements"]["only_show_unread"]) {
                continue;
            }
        }

        // Setup template data for the message.
        unset($message['body']);
        $message["lastpost"] = phorum_date($PHORUM["short_date_time"], $message["modifystamp"]);
        $message["raw_datestamp"] = $message["datestamp"];
        $message["datestamp"] = phorum_date($PHORUM["short_date_time"], $message["datestamp"]);
        $message["URL"]["READ"] = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["message_id"]);
        $PHORUM["DATA"]["ANNOUNCEMENTS"][] = $message;
    }

    // If all announcements were skipped, then we are done.
    if (!isset($PHORUM["DATA"]["ANNOUNCEMENTS"])) return;

    // format / clean etc. the messages found
    $PHORUM["DATA"]["ANNOUNCEMENTS"]= phorum_format_messages($PHORUM["DATA"]["ANNOUNCEMENTS"]);
    
    // Build the announcements code.
    ob_start();
    include phorum_get_template("announcements::announcements");
    $PHORUM['DATA']['MOD_ANNOUNCEMENTS'] = ob_get_contents();
    ob_end_clean();
}

// Register the additional CSS code for this module.
function phorum_mod_announcements_css_register($data)
{
    $data['register'][] = array(
        "module" => "announcements",
        "where"  => "after",
        "source" => "template(announcements::css)"
    );
    return $data;
}

function phorum_show_announcements ()
{
    $PHORUM = $GLOBALS['PHORUM'];

    // No announcements setup or automatic displaying disabled?
    if (empty($PHORUM['DATA']['MOD_ANNOUNCEMENTS']) ||
        !empty($PHORUM['mod_announcements']['disable_autodisplay'])) return;

    print $GLOBALS['PHORUM']['DATA']['MOD_ANNOUNCEMENTS'];
}

?>
