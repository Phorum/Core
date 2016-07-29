<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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

if (!defined("PHORUM_CONTROL_CENTER")) return;

require_once PHORUM_PATH.'/include/api/lang.php';
require_once PHORUM_PATH.'/include/api/template.php';

function phorum_cc_get_language_info()
{
    global $PHORUM;

    $langs = phorum_api_lang_list();
    $f_langs = array();
    $profile = $PHORUM['DATA']['PROFILE'];
    if ( !isset( $profile['user_language'] ) )
        $defsel = " selected=\"selected\"";
    else
        $defsel = "";
    $f_langs[] = array( 'file' => '', 'name' => $PHORUM['DATA']['LANG']['Default'], 'sel' => $defsel );

    foreach( $langs as $entry => $name ) {
        $sel = "";
        if ( isset( $profile['user_language'] ) && $profile['user_language'] == $entry ) {
            $sel = " selected=\"selected\"";
        }
        $f_langs[] = array( 'file' => $entry, 'name' => $name, 'sel' => $sel );
    }
    return $f_langs;
}

function phorum_cc_get_template_info()
{
    global $PHORUM;

    $langs = phorum_api_template_list();
    $profile = $PHORUM['DATA']['PROFILE'];

    $f_langs = array();
    if ( !isset( $profile['user_template'] ) )
        $defsel = " selected=\"selected\"";
    else
        $defsel = "";
    $f_langs[] = array( 'file' => '', 'name' => $PHORUM['DATA']['LANG']['Default'], 'sel' => $defsel );

    foreach( $langs as $entry => $name ) {
        $sel = "";
        if ( isset( $profile['user_template'] ) && $profile['user_template'] == $entry ) {
            $sel = " selected=\"selected\"";
        }
        $f_langs[] = array( 'file' => $entry, 'name' => $name, 'sel' => $sel );
    }
    return $f_langs;
}

if ( count( $_POST ) ) {
    // dst is time + 1 hour
    if(isset($_POST['tz_offset']) && $_POST['tz_offset'] != -99) {
        if($_POST['tz_offset'] && isset($_POST['is_dst']) && $_POST['is_dst']) {
            $_POST['tz_offset']=++$_POST['tz_offset']."";
        }
    }
    // unsetting dst if not checked
    if(!isset($_POST['is_dst'])) {
        $_POST['is_dst']=0;
    }

    $oldtemplate = $PHORUM["user"]["user_template"];

    list($error,$okmsg) = phorum_controlcenter_user_save( $panel );

    // No error and the template changed? The reload the page to
    // reflect the new template.
    if (empty($error) && !empty($_POST["user_template"]) &&
        $oldtemplate != $_POST["user_template"]) {
        phorum_api_redirect($PHORUM['DATA']['URL']['CC6']);
    }
}

if ( isset( $PHORUM["user_time_zone"] ) ) {
    $PHORUM['DATA']['PROFILE']['TZSELECTION'] = $PHORUM["user_time_zone"];
}
// compute the tz-array
if ( !isset( $PHORUM['DATA']['PROFILE']['tz_offset'] ) || $PHORUM['DATA']['PROFILE']['tz_offset'] == -99 ) {
    $defsel = " selected=\"selected\"";
} else {
    $defsel = "";
}

// remove dst from tz_offset
if(isset($PHORUM['DATA']['PROFILE']['is_dst']) && $PHORUM['DATA']['PROFILE']['is_dst']) {
    $PHORUM['DATA']['PROFILE']['tz_offset']=--$PHORUM['DATA']['PROFILE']['tz_offset'];
    $PHORUM['DATA']['PROFILE']['tz_offset']=number_format($PHORUM['DATA']['PROFILE']['tz_offset'],2);
}

$PHORUM["DATA"]["TIMEZONE"][] = array( 'tz' => '-99', 'str' => $PHORUM['DATA']['LANG']['Default'], 'sel' => $defsel );
foreach( $PHORUM['DATA']['LANG']['TIME'] as $tz => $str ) {
    if ( isset($PHORUM['DATA']['PROFILE']['tz_offset']) && $PHORUM['DATA']['PROFILE']['tz_offset'] === number_format($tz,2) ) {
        $sel = ' selected="selected"';
    } else {
        $sel = '';
    }
    $PHORUM["DATA"]["TIMEZONE"][] = array( 'tz' => $tz, 'str' => $str, 'sel' => $sel );
}

$PHORUM['DATA']['LANGUAGES'] = phorum_cc_get_language_info();
if (count($PHORUM['DATA']['LANGUAGES']) < 2 || empty($PHORUM['user_language'])){
    $PHORUM['DATA']['PROFILE']['LANGSELECTION'] = FALSE;
} else {
    $PHORUM['DATA']['PROFILE']['LANGSELECTION'] = TRUE;
}

if ( isset( $PHORUM["user_template"] ) ) {
    $PHORUM['DATA']['PROFILE']['TMPLSELECTION'] = $PHORUM["user_template"];
}
$PHORUM['DATA']['TEMPLATES'] = phorum_cc_get_template_info();

$PHORUM['DATA']['PROFILE']['BOARDSETTINGS'] = 1;
$template = "cc_usersettings";

?>
