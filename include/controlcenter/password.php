<?php
if(!defined("PHORUM_CONTROL_CENTER")) return;

if(count($_POST)) {
    if((isset($_POST["password"]) && !empty($_POST["password"]) && $_POST["password"] != $_POST["password2"]) || !isset($_POST["password"]) || empty($_POST['password'])) {
            $error = $PHORUM["DATA"]["LANG"]["ErrPassword"];
    } else {
            $_POST['password_temp']=$_POST['password'];
            $error = phorum_controlcenter_user_save($panel);
    }
}

$PHORUM["DATA"]["PROFILE"]["block_title"] = $PHORUM["DATA"]["LANG"]["ChangePassword"];
$PHORUM['DATA']['PROFILE']['CHANGEPASSWORD'] = 1;
$template = "cc_usersettings";
        
?>
