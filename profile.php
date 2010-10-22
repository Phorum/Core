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
define('phorum_page','profile');

include_once("./common.php");
include_once("./include/email_functions.php");
include_once("./include/format_functions.php");

// set all our URL's
phorum_build_common_urls();

$template = "profile";
$error = "";

// redirect if no profile id passed
if(!empty($PHORUM["args"][1])){
    $profile_id = (int)$PHORUM["args"][1];
}

if(empty($PHORUM["args"][1]) || empty($profile_id)){
    phorum_redirect_by_url(phorum_get_url(PHORUM_INDEX_URL));
    exit();
}

$user = phorum_api_user_get($profile_id, TRUE);

if(!is_array($user) || $user["active"]==0) {
    $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["UnknownUser"];
    $PHORUM['DATA']["URL"]["REDIRECT"]=phorum_get_url(PHORUM_LIST_URL);
    $PHORUM['DATA']["BACKMSG"]=$PHORUM["DATA"]["LANG"]["BackToList"];

    // have to include the header here for the Redirect
    phorum_output("message");
    return;
}

// security messures
unset($user["password"]);
unset($user["permissions"]);

// set any custom profile fields that are not present.
if (!empty($PHORUM["PROFILE_FIELDS"])) {
    foreach($PHORUM["PROFILE_FIELDS"] as $id => $field) {
        if ($id === 'num_fields' || !empty($field['deleted'])) continue;
        if (!isset($user[$field['name']])) $user[$field['name']] = "";
    }
}

// No need to show the real name in case it's the same
// as the display name.
if ($user["real_name"] == $user["display_name"]) {
    unset($user["real_name"]);
}

$PHORUM["DATA"]["PROFILE"] = $user;
$PHORUM["DATA"]["PROFILE"]["forum_id"] = $PHORUM["forum_id"];

$PHORUM["DATA"]["PROFILE"]["raw_date_added"]=$PHORUM["DATA"]["PROFILE"]["date_added"];
$PHORUM["DATA"]["PROFILE"]["date_added"]=phorum_date( $PHORUM['short_date_time'], $PHORUM["DATA"]["PROFILE"]["date_added"]);

if( (empty($PHORUM['hide_email_addr']) && !$user['hide_email']) ||
    !empty($PHORUM["user"]["admin"]) ||
    (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES) && PHORUM_MOD_EMAIL_VIEW) ||
    (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_USERS) && PHORUM_MOD_EMAIL_VIEW) ){
    $PHORUM["DATA"]["PROFILE"]["email"]=phorum_html_encode($user["email"]);
} else {
    $PHORUM["DATA"]["PROFILE"]["email"] = $PHORUM["DATA"]["LANG"]["Hidden"];
}

if( $PHORUM["track_user_activity"] &&
    (!empty($PHORUM["user"]["admin"]) ||
     (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) ||
     (phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_USERS)) ||
     !$user["hide_activity"])){

    $PHORUM["DATA"]["PROFILE"]["raw_date_last_active"]=$PHORUM["DATA"]["PROFILE"]["date_last_active"];
    $PHORUM["DATA"]["PROFILE"]["date_last_active"]=phorum_date( $PHORUM['short_date_time'], $PHORUM["DATA"]["PROFILE"]["date_last_active"]);
} else {
    unset($PHORUM["DATA"]["PROFILE"]["date_last_active"]);
}

$PHORUM["DATA"]["PROFILE"]["posts"] = number_format($PHORUM["DATA"]["PROFILE"]["posts"], 0, "", $PHORUM["thous_sep"]);

$PHORUM["DATA"]["PROFILE"]["URL"]["PM"] = phorum_get_url(PHORUM_PM_URL, "page=send", "to_id=".urlencode($user["user_id"]));
$PHORUM["DATA"]["PROFILE"]["URL"]["ADD_BUDDY"] = phorum_get_url(PHORUM_PM_URL, "page=buddies", "action=addbuddy", "addbuddy_id=".urlencode($user["user_id"]));
$PHORUM["DATA"]["PROFILE"]["is_buddy"] = phorum_db_pm_is_buddy($user["user_id"]);
// unset($PHORUM["DATA"]["PROFILE"]["signature"]);

$PHORUM["DATA"]["PROFILE"]["URL"]["SEARCH"] = phorum_get_url(PHORUM_SEARCH_URL, "author=".urlencode($PHORUM["DATA"]["PROFILE"]["user_id"]), "match_type=USER_ID", "match_dates=0", "match_threads=0");

$PHORUM["DATA"]["PROFILE"]["username"] =
    htmlspecialchars($PHORUM["DATA"]["PROFILE"]["username"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

if (isset($PHORUM["DATA"]["PROFILE"]["real_name"])) {
    $PHORUM["DATA"]["PROFILE"]["real_name"] =
        htmlspecialchars($PHORUM["DATA"]["PROFILE"]["real_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
}

if (empty($PHORUM["custom_display_name"])) {
    $PHORUM["DATA"]["PROFILE"]["display_name"] =
        htmlspecialchars($PHORUM["DATA"]["PROFILE"]["display_name"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
}

if (isset($PHORUM["hooks"]["profile"]))
    $PHORUM["DATA"]["PROFILE"] = phorum_hook("profile", $PHORUM["DATA"]["PROFILE"]);

$PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["UserProfile"];
$PHORUM["DATA"]["DESCRIPTION"] = "";
$PHORUM['DATA']['HTML_DESCRIPTION'] = ''; 


// fill the breadcrumbs-info.
$PHORUM['DATA']['BREADCRUMBS'][]=array(
    'URL'=>'', 'TEXT'=>strip_tags($PHORUM["DATA"]["HEADING"]),
    'TYPE'=>'profile'
);

// set all our URL's
phorum_build_common_urls();

phorum_output($template);

?>
