<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;

if(count($_POST)) {

    // these two are flipped as we store if hidden in the db, but we ask if allowed in the UI
    $_POST["hide_email"] = ($_POST["hide_email"]) ? 0 : 1;
    $_POST["hide_activity"] = ($_POST["hide_activity"]) ? 0 : 1;
    
    $error = phorum_controlcenter_user_save($panel);
}


// these two are flipped as we store if hidden in the db, but we ask if allowed in the UI

if (!empty($PHORUM['DATA']['PROFILE']["hide_email"])) {
    $PHORUM["DATA"]["PROFILE"]["hide_email_checked"] = "";
} else {
    // more html stuff in the code. yuck.
    $PHORUM["DATA"]["PROFILE"]["hide_email_checked"] = " checked=\"checked\"";
} 

if (!empty($PHORUM['DATA']['PROFILE']["hide_activity"])) {
    $PHORUM["DATA"]["PROFILE"]["hide_activity_checked"] = "";
} else {
    // more html stuff in the code. yuck.
    $PHORUM["DATA"]["PROFILE"]["hide_activity_checked"] = " checked=\"checked\"";
} 

$PHORUM["DATA"]["PROFILE"]["block_title"] = $PHORUM["DATA"]["LANG"]["EditPrivacy"];

$PHORUM['DATA']['PROFILE']['PRIVACYSETTINGS'] = 1;
$template = "cc_usersettings";
        
?>
