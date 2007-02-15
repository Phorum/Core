<?php

if(!defined("PHORUM")) return;

function phorum_setup_announcements ()
{
    global $PHORUM;

    if(isset($PHORUM["mod_announcements"]["forum_id"]) && $PHORUM["forum_id"]!=$PHORUM["mod_announcements"]["forum_id"] && !empty($PHORUM["mod_announcements"]["pages"][phorum_page])){

        // Inlcude style sheet information for this module, if the css template isn't empty.
        ob_start();
        include phorum_get_template("announcements::css");
        $css = ob_get_contents();
        ob_end_clean();
        if ($css != '') 
          $PHORUM["DATA"]["HEAD_TAGS"] .= "<style type=\"text/css\" media=\"screen\">$css</style>";
    }
}

function phorum_show_announcements ()
{
    $PHORUM=$GLOBALS["PHORUM"];

    if(isset($PHORUM["mod_announcements"]["forum_id"]) && $PHORUM["forum_id"]!=$PHORUM["mod_announcements"]["forum_id"] && !empty($PHORUM["mod_announcements"]["pages"][phorum_page])) {

        // Retrieve the last number of posts from the announcement forum.
        $messages = phorum_db_get_recent_messages($PHORUM["mod_announcements"]["number_to_show"], $PHORUM["mod_announcements"]["forum_id"], 0, true);
        unset($messages["users"]);

        // No announcements to show? Then we are done.
        if (count($messages) == 0) return;

        // Read the newflags information for authenticated users.
        $newinfo = NULL;
        if (!empty($PHORUM["user"])) {
            $newflagkey = $PHORUM["mod_announcements"]["forum_id"]."-".$PHORUM['user']['user_id'];
            if ($PHORUM['cache_newflags']) {
                $newinfo = phorum_cache_get('newflags',$newflagkey,$PHORUM['cache_version']);
            }
            if($newinfo == NULL) {
                $newinfo = phorum_db_newflag_get_flags($PHORUM["mod_announcements"]["forum_id"]);
                if ($PHORUM['cache_newflags']) {
                    phorum_cache_put('newflags',$newflagkey,$newinfo,86400,$PHORUM['cache_version']);
                }
            }
        }

        // Process the announcements.
        foreach($messages as $message)
        {
            // Check if there are new messages in the thread.
            if (isset($newinfo)) {
                $new = 0;
                foreach ($message["meta"]["message_ids"] as $id) {
                    if (!isset($newinfo[$id]) && 
                        $id > $newinfo['min_id'][$PHORUM["mod_announcements"]["forum_id"]]) {
                        $new = 1;
                        break;
                    }
                }
                // There are new messages. Setup the template data for showing a new flag.
                if ($new) {
                    $message["new"] = ($new ? $PHORUM["DATA"]["LANG"]["newflag"] : NULL);
                    $message["URL"]["NEWPOST"] = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["thread"],"gotonewpost");
                }
            }

            // Setup template data for the message.
            $message["lastpost"] = phorum_date($PHORUM["short_date_time"], $message["modifystamp"]);
            $message["raw_datestamp"] = $message["datestamp"];
            $message["datestamp"] = phorum_date($PHORUM["short_date_time"], $message["datestamp"]);
            $message["URL"]["READ"] = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["message_id"]);
            $PHORUM["DATA"]["ANNOUNCEMENTS"][] = $message;
        }

        // Apply standard formatting to the messages.
        $PHORUM["DATA"]["ANNOUNCEMENTS"] = phorum_hook("format", $PHORUM["DATA"]["ANNOUNCEMENTS"]);

        // Display the announcements.
        include phorum_get_template("announcements::announcements");
    }
}

?>
