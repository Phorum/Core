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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM_ADMIN")) return;

$error="";

if(count($_POST)){
    // some sanity checking would be nice ;)

    $PHORUM["smtp_mail"] = array('host' => $_POST['host'],
                                 'port' => empty($_POST['port'])? "25" : $_POST['port'],
                                 'auth' => $_POST['auth'],
                                 'username' => $_POST['auth_username'],
                                 'password' => $_POST['auth_password'],
                                 'conn' => $_POST['conn'],
                                 'log_successful' => $_POST['log_successful'],
                                 'show_errors' => $_POST['show_errors']
                                 );

    if (empty($error)) {
        phorum_db_update_settings(array(
            "smtp_mail" => $PHORUM["smtp_mail"]
        ));
        phorum_admin_okmsg("Settings updated");
    }
}

include_once "./include/admin/PhorumInputForm.php";

$frm = new PhorumInputForm ("", "post", "Save");

$frm->hidden("module", "modsettings");

$frm->hidden("mod", "smtp_mail");

$frm->addbreak("Settings for the SMTP Mail Module");


$frm->addrow("Hostname of mailserver", $frm->text_box("host", $PHORUM['smtp_mail']['host'], 50));
$frm->addrow("Port of mailserver", $frm->text_box("port", $PHORUM['smtp_mail']['port'], 5)." (Default Port is 25, unencrypted. Encrypted Port is usually 465)");

$frm->addrow("Connection Type", $frm->select_tag("conn", array('plain'=>'Plain Connection','ssl'=>'SSL-Encryption','tls'=>'TLS-Encryption'), $PHORUM['smtp_mail']['conn'])." (e.g. Google-Mail connection needs TLS)");


$frm->addrow("Use SMTP Auth", $frm->select_tag("auth",array(1=>'Yes',0=>'No'),$PHORUM['smtp_mail']['auth']));

$frm->addrow("SMTP Auth Username", $frm->text_box("auth_username", $PHORUM['smtp_mail']['username'], 50));
$frm->addrow("SMTP Auth Password", $frm->text_box("auth_password", $PHORUM['smtp_mail']['password'], 50,0,true));

$frm->addsubbreak("Logging / Errorhandling");

$row = $frm->addrow("Show errors on screen",$frm->select_tag("show_errors",array(1=>"Yes",0=>"No"),$PHORUM['smtp_mail']['show_errors']));
$frm->addhelp($row,"Show errors on screen","This option enables to show errors on screen (default). If disabled you should make sure that you have the Event Logging Module enabled which will log errors in email sending.");

$row = $frm->addrow("Log successful mails to the Event Logging Module",$frm->select_tag("log_successful",array(0=>"No",1=>"Yes"),$PHORUM['smtp_mail']['log_successful']));
$frm->addhelp($row,"Logging of successful emails to the Event Logging Module","This option logs successful email messages to the Event Logging Module if that is enabled too.\nErrors are logged there always (if the module is enabled).");
$frm->show();


?>