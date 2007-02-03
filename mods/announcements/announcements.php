<?php

if(!defined("PHORUM")) return;

function phorum_setup_announcements ()
{
    global $PHORUM;

    if(isset($PHORUM["mod_announcements"]["forum_id"]) && $PHORUM["forum_id"]!=$PHORUM["mod_announcements"]["forum_id"]){
        if(!empty($PHORUM["mod_announcements"]["pages"][phorum_page])){
            $PHORUM["DATA"]["HEAD_TAGS"] .= "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"".$PHORUM["http_path"]."/mods/announcements/styles.css\" media=\"screen\" />";
        }
    }
}

function phorum_show_announcements ()
{
    $PHORUM=$GLOBALS["PHORUM"];

    if(isset($PHORUM["mod_announcements"]["forum_id"]) && $PHORUM["forum_id"]!=$PHORUM["mod_announcements"]["forum_id"]){

        if(!empty($PHORUM["mod_announcements"]["pages"][phorum_page])){

            $messages = phorum_db_get_recent_messages($PHORUM["mod_announcements"]["number_to_show"], $PHORUM["mod_announcements"]["forum_id"], 0, true);

            unset($messages["users"]);

            if (count($messages) == 0) return;

            foreach($messages as $message){

                $message["lastpost"] = phorum_date($PHORUM["short_date_time"], $message["modifystamp"]);
                $message["URL"]["READ"] = phorum_get_url(PHORUM_FOREIGN_READ_URL, $message["forum_id"], $message["message_id"]);

                $PHORUM["DATA"]["ANNOUNCEMENTS"][] = $message;
            }

            $PHORUM["DATA"]["ANNOUNCEMENTS"] = phorum_hook("format", $PHORUM["DATA"]["ANNOUNCEMENTS"]);

            include phorum_get_template("announcements::announcements");

        }
    }
}

?>
