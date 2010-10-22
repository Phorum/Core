<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM_CONTROL_CENTER")) return;

if(count($_POST)) {

    $old_password = trim($_POST["password_old"]);
    $new_password = trim($_POST['password_new']);

    // attempt to authenticate the user
    if(empty($old_password) || !phorum_api_user_authenticate(
                                PHORUM_FORUM_SESSION,
                                $PHORUM['user']['username'],
                                $old_password) ) {

        $error = $PHORUM["DATA"]["LANG"]["ErrOriginalPassword"];

    } elseif(empty($new_password) || empty($_POST['password_new2']) ||
            $_POST['password_new'] !== $_POST['password_new2']) {

        $error = $PHORUM["DATA"]["LANG"]["ErrPassword"];

    } else {

        // everything's good, save
        $_POST['password_temp'] = $_POST['password'] = $new_password;
        list($error,$okmsg) = phorum_controlcenter_user_save($panel);

        // Redirect to the password page, to make sure that the
        // CSRF token is refreshed. This token is partly based on the
        // session id and this session id changed along with the password.
        phorum_redirect_by_url(phorum_get_url(
            PHORUM_CONTROLCENTER_URL,
            "panel=" . PHORUM_CC_PASSWORD,
            "okmsg=" . urlencode($okmsg)
        )); 
    }
}

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["ChangePassword"];
$PHORUM['DATA']['PROFILE']['CHANGEPASSWORD'] = 1;
$template = "cc_usersettings";

?>
