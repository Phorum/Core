<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;

if(count($_POST)) {
     list($error,$okmsg) = phorum_controlcenter_user_save($panel);
}

foreach($PHORUM["DATA"]["PROFILE"] as $key => $data) {
       if(!is_array($data)) {
            $PHORUM["DATA"]["PROFILE"][$key]=htmlspecialchars($data);
       }       
}

$PHORUM["DATA"]["PROFILE"]["block_title"] = $PHORUM["DATA"]["LANG"]["EditUserinfo"];
$PHORUM['DATA']['PROFILE']['USERPROFILE'] = 1;
$template = "cc_usersettings";
        
?>
