<?php
/*
* SMTP-Mail-Module v0.8
* made by Thomas Seifert
* email: thomas (at) phorum.org
*
*/
if(!defined("PHORUM_ADMIN")) return;

$error="";

if(count($_POST)){
    // some sanity checking would be nice ;)

    $PHORUM["smtp_mail"] = array('host' => $_POST['host'],
                                 'port' => empty($_POST['port'])? "25" : $_POST['port'],
                                 'auth' => $_POST['auth'],
                                 'username' => $_POST['auth_username'],
                                 'password' => $_POST['auth_password']
                                 );

    if(empty($error)){
        if(!phorum_db_update_settings(array("smtp_mail"=>$PHORUM["smtp_mail"]))){
            $error="Database error while updating settings.";
        } else {
            echo "Settings Updated<br />";
        }
    }
}

include_once "./include/admin/PhorumInputForm.php";

$frm =& new PhorumInputForm ("", "post", "Save");

$frm->hidden("module", "modsettings");

$frm->hidden("mod", "smtp_mail");

$frm->addbreak("Settings for the SMTP-Mail-Module");


$frm->addrow("Hostname of mailserver", $frm->text_box("host", $PHORUM['smtp_mail']['host'], 50));
$frm->addrow("Port of mailserver", $frm->text_box("port", $PHORUM['smtp_mail']['port'], 5));

//print_var($PHORUM['smtp_mail']);

$frm->addrow("Use SMTP-Auth", $frm->select_tag("auth",array(1=>'Yes',0=>'No'),$PHORUM['smtp_mail']['auth']));

$frm->addrow("SMTP-Auth Username", $frm->text_box("auth_username", $PHORUM['smtp_mail']['username'], 50));
$frm->addrow("SMTP-Auth Password", $frm->text_box("auth_password", $PHORUM['smtp_mail']['password'], 50));

$frm->show();


?>