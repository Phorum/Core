<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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

define('phorum_page','openid');
require_once './common.php';
require_once './include/api/user.php';

if(empty($PHORUM["open_id"])){
    phorum_redirect_by_url($phorum->url->get(PHORUM_URL_LOGIN));
}

define("PHORUM_OPENID_ERROR_INVALID", 1);
define("PHORUM_OPENID_ERROR_REDIRECT", 2);
define("PHORUM_OPENID_ERROR_UNKOWN", 99);

require_once "./include/open_id/OpenID/Consumer.php";
require_once "./include/open_id/OpenID/PhorumStore.php";
require_once "./include/open_id/OpenID/SReg.php";
require_once "./include/open_id/OpenID/PAPE.php";

if (!session_id()) session_start();

if(isset($_POST["openid"])){

    // process open id auth

    $error = PHORUM_OPENID_ERROR_UNKOWN;

    $store = new Auth_OpenID_PhorumStore();
    $consumer = new Auth_OpenID_Consumer($store);

    $auth_request = $consumer->begin($_POST["openid"]);

    if (!$auth_request) {
        $error = PHORUM_OPENID_ERROR_INVALID;
    } else {

        $sreg_request = Auth_OpenID_SRegRequest::build(
                                         // Required
                                         array('nickname','fullname', 'email'),
                                         // Optional
                                         array());
        $auth_request->addExtension($sreg_request);


        $pape_request = new Auth_OpenID_PAPE_Request(array(PAPE_AUTH_PHISHING_RESISTANT));
        if ($pape_request) {
            $auth_request->addExtension($pape_request);
        }

        // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
        // form to send a POST request to the server.
        if ($auth_request->shouldSendRedirect()) {
            $redirect_url = $auth_request->redirectURL($PHORUM["http_path"],
                                                       $phorum->url->get(PHORUM_OPENID_URL, "forum_id=".$PHORUM["forum_id"]));

            // If the redirect URL can't be built, display an error
            // message.
            if (Auth_OpenID::isFailure($redirect_url)) {
                $error = PHORUM_OPENID_ERROR_REDIRECT;
            } else {
                // Send redirect.
                header("Location: ".$redirect_url);
                exit();
            }

        } else {

            // Generate form markup and render it.
            $form_id = 'openid_message';
            $form_html = $auth_request->formMarkup($PHORUM["http_path"],
                                                   $phorum->url->get(PHORUM_OPENID_URL, "forum_id=".$PHORUM["forum_id"]),
                                                   false, array('id' => $form_id));

            // Display an error if the form markup couldn't be generated;
            // otherwise, render the HTML.
            if (Auth_OpenID::isFailure($form_html)) {
                return PHORUM_OPENID_ERROR_REDIRECT;
            } else {
                $page_contents = array(
                   "<html><head><title>",
                   "OpenID transaction in progress",
                   "</title></head>",
                   "<body onload='document.getElementById(\"".$form_id."\").submit()'><div style='display:none;'>",
                   $form_html,
                   "</div></body></html>");

                echo implode("\n", $page_contents);
                exit();
            }
        }
    }

    // if you get here, there was an error
    if($error == PHORUM_OPENID_ERROR_INVALID){

        $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["OpenIDInvalid"];

    } elseif($error == PHORUM_OPENID_ERROR_REDIRECT){

        $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["OpenIDRedirect"];

    } else {

        $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["OpenIDUnknown"];
    }

    phorum_output("message");

} else {

    $store = new Auth_OpenID_PhorumStore();
    $consumer = new Auth_OpenID_Consumer($store);

    // Complete the authentication process using the server's response.
    $response = $consumer->complete($phorum->url->get(PHORUM_OPENID_URL, "forum_id=".$PHORUM["forum_id"]));

    // Check the response status.
    if ($response->status == Auth_OpenID_CANCEL) {

        // This means the authentication was cancelled.
        phorum_redirect_by_url($phorum->url->get(PHORUM_URL_LOGIN));

    } else if ($response->status == Auth_OpenID_FAILURE) {

        // Authentication failed; display the error message.
        $PHORUM["DATA"]["ERROR"]=$PHORUM["DATA"]["LANG"]["OpenIDUnknown"];
        phorum_output("message");

    } else if ($response->status == Auth_OpenID_SUCCESS) {

        // This means the authentication succeeded; extract the
        // identity URL and Simple Registration data (if it was
        // returned).
        $openid = $response->getDisplayIdentifier();
        $esc_identity = htmlspecialchars($openid, ENT_QUOTES);

        $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

        $sreg = $sreg_resp->contents();

        $field_id = 0;
        foreach($PHORUM["PROFILE_FIELDS"][PHORUM_CUSTOM_FIELD_USER] as $f){
            if($f["name"]=="open_id"){
                $field_id = $f["id"];
            }
        }

        if($field_id==0){
            trigger_error("There is no custom field configured for OpenID", E_USER_ERROR);
        }

        $user_id = phorum_api_user_search_custom_profile_field($field_id, $openid);

        if($user_id){
            $user = phorum_api_user_get($user_id);
        }

        if(empty($user_id) || empty($user) || $user["active"]!=PHORUM_USER_ACTIVE){

            setcookie( "phorum_tmp_cookie", "this will be destroyed once logged in", 0, $PHORUM["session_path"], $PHORUM["session_domain"] );

            phorum_build_common_urls();

            $_SESSION["open_id"] = $openid;

            $PHORUM["DATA"]["HEADING"] = $PHORUM["DATA"]["LANG"]["OpenIDComplete"];

            $PHORUM["DATA"]["HTML_DESCRIPTION"] = $PHORUM["DATA"]["LANG"]["OpenIDCompleteExplain"];

            $PHORUM["DATA"]["URL"]["ACTION"] = $phorum->url->get( PHORUM_REGISTER_ACTION_URL );

            $PHORUM["DATA"]["OPENID"]["open_id"] = htmlspecialchars($openid, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);

            if(isset($sreg["fullname"])){
                $PHORUM["DATA"]["OPENID"]["real_name"] = htmlspecialchars($sreg["fullname"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }

            if(isset($sreg["nickname"])){
                $PHORUM["DATA"]["OPENID"]["username"] = htmlspecialchars($sreg["nickname"], ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]);
            }

            if(isset($sreg["email"])){
                $PHORUM["DATA"]["OPENID"]["email"] = $sreg["email"];
            }

            phorum_output("openid_register");

        } else {

            phorum_api_user_set_active_user(PHORUM_FORUM_SESSION, $user_id, PHORUM_FLAG_SESSION_ST);
            phorum_api_user_session_create(PHORUM_FORUM_SESSION, PHORUM_SESSID_RESET_LOGIN);

            if (isset($_COOKIE["phorum_tmp_cookie"])) {
                setcookie(
                    "phorum_tmp_cookie", "", 0,
                    $PHORUM["session_path"], $PHORUM["session_domain"]
                );
            }

            phorum_redirect_by_url($phorum->url->get(PHORUM_URL_INDEX));

        }

    }

}

?>
