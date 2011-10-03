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

    // don't allow this page to be loaded directly
    if(!defined("PHORUM_ADMIN")) exit();

    include_once("./include/api/base.php");
    include_once("./include/api/user.php");

    if(isset($_POST["username"]) && isset($_POST["password"]))
    {
        $user_id = phorum_api_user_authenticate(
            PHORUM_ADMIN_SESSION,
            trim($_POST["username"]),
            trim($_POST["password"])
        );
        if ($user_id &&
            phorum_api_user_set_active_user(PHORUM_ADMIN_SESSION, $user_id) &&
            phorum_api_user_session_create(PHORUM_ADMIN_SESSION)) {

            // update the token and time
            $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token_time'] = time();
            $sig_data = $GLOBALS["PHORUM"]["user"]['user_id'].time().$GLOBALS["PHORUM"]["user"]['username'];
            $GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'] = phorum_generate_data_signature($sig_data);
            $GLOBALS["PHORUM"]['admin_token']=$GLOBALS["PHORUM"]["user"]['settings_data']['admin_token'];

            $tmp_user = array(
                        'user_id'=>$GLOBALS["PHORUM"]["user"]['user_id'],
                        'settings_data'=>$GLOBALS["PHORUM"]["user"]['settings_data']
            );
            phorum_api_user_save($tmp_user);


            if(!empty($_POST["target"])){
                $target_url = phorum_admin_build_url($_POST['target'], TRUE);
                phorum_redirect_by_url($target_url);
            } else {
                $redir_url = phorum_admin_build_url('', TRUE);
                phorum_redirect_by_url($redir_url);
            }
            exit();

        } else {
            phorum_hook("failed_login", array(
                "username" => $_POST["username"],
                "password" => $_POST["password"],
                "location" => "admin"
            ));
        }
    }

    include_once "./include/admin/PhorumInputForm.php";

    $frm = new PhorumInputForm ("", "post");

    if(!empty($_SERVER["QUERY_STRING"])){

        $frm->hidden("target", htmlspecialchars($_SERVER["QUERY_STRING"], ENT_COMPAT, $PHORUM["DATA"]['CHARSET']));

    }

    $frm->addrow("Username", $frm->text_box("username", "", 30));

    $frm->addrow("Password", $frm->text_box("password", "", 30, 0, true));

    $frm->show();

?>
