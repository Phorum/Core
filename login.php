<?php 
// //////////////////////////////////////////////////////////////////////////////
//                                                                             //
// Copyright (C) 2003  Phorum Development Team                                 //
// http://www.phorum.org                                                       //
//                                                                             //
// This program is free software. You can redistribute it and/or modify        //
// it under the terms of either the current Phorum License (viewable at        //
// phorum.org) or the Phorum License that was distributed with this file       //
//                                                                             //
// This program is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY, without even the implied warranty of              //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                        //
//                                                                             //
// You should have received a copy of the Phorum License                       //
// along with this program.                                                    //
// //////////////////////////////////////////////////////////////////////////////
define('phorum_page','login');

include_once( "./common.php" );
include_once( "./include/users.php" );
include_once( "./include/email_functions.php" );

if ( !empty( $PHORUM["args"]["logout"] ) ) {
    phorum_user_clear_session();
    
    if(isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER['HTTP_REFERER'])) {
        $url=$_SERVER["HTTP_REFERER"];
    } else {
        $url=phorum_get_url( PHORUM_LIST_URL );
    }
    if(stristr($_SERVER["HTTP_REFERER"], PHORUM_SESSION)){
        $url=str_replace(PHORUM_SESSION."=".urlencode($PHORUM["args"]["phorum_session_v5"]), "", $url);
    }
    phorum_redirect_by_url($url);    
    exit();
} 
// set all our URL's
phorum_build_common_urls();

$template = "login";

$error = "";

$username = "";

if ( count( $_POST ) > 0 ) {
    if ( isset( $_POST["lostpass"] ) ) {
        if ( empty( $_POST["lostpass"] ) ) {
            $error = $PHORUM["DATA"]["LANG"]["LostPassError"];
        } elseif ( $uid = phorum_user_check_email( $_POST["lostpass"] ) ) {

            $user = phorum_user_get($uid);

            if($user["active"] == PHORUM_USER_PENDING_MOD){
                $template="message";
                $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegVerifyMod"];
            } elseif($user["active"] == PHORUM_USER_PENDING_EMAIL || $user["active"] == PHORUM_USER_PENDING_BOTH){
    
                $tmp_user["user_id"] = $uid;
                $tmp_user["password_temp"] = substr(md5(microtime()), 0, 8);
                phorum_user_save( $tmp_user );

                $verify_url=phorum_get_url(PHORUM_REGISTER_URL, "approve=".$tmp_user["password_temp"]."$uid");
                $maildata["mailsubject"]=$PHORUM["DATA"]["LANG"]["VerifyRegEmailSubject"];
                $maildata["mailmessage"]=wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody1"], 72)."\n\n$verify_url\n\n".wordwrap($PHORUM["DATA"]["LANG"]["VerifyRegEmailBody2"], 72);
                phorum_email_user(array($user["email"]), $maildata);
    
                $PHORUM["DATA"]["MESSAGE"] = $PHORUM["DATA"]["LANG"]["RegVerifyEmail"];
                $template="message";

            } else {
                include_once( "./include/profile_functions.php" );
    
                $newpass = phorum_gen_password();
    
                $tmp_user["user_id"] = $uid;
    
                $tmp_user["password_temp"] = $newpass;
    
                phorum_user_save( $tmp_user );
    
                $user = phorum_user_get( $uid );
    
                $maildata = array();
                $maildata['mailmessage'] = wordwrap( $PHORUM["DATA"]["LANG"]["LostPassEmailBody1"], 72 ) . "\n\n" . $PHORUM["DATA"]["LANG"]["Username"] . ": $user[username]\n" . $PHORUM["DATA"]["LANG"]["Password"] . ": $newpass\n\n" . wordwrap( $PHORUM["DATA"]["LANG"]["LostPassEmailBody2"], 72 );
                $maildata['mailsubject'] = $PHORUM["DATA"]["LANG"]["LostPassEmailSubject"];
    
                phorum_email_user( array( 0 => $user['email'] ), $maildata );

                $error = $PHORUM["DATA"]["LANG"]["LostPassSent"];

            }
                
        } else {

            $error = $PHORUM["DATA"]["LANG"]["LostPassError"];

        } 
    } else {

        if($PHORUM["use_cookies"] && !isset($_COOKIE["phorum_tmp_cookie"])){
            $PHORUM["use_cookies"]=false;
        }
        
        $username = $_POST["username"];

        if ( phorum_user_check_login( $_POST["username"], $_POST["password"] ) ) {
            if(isset($_COOKIE["phorum_tmp_cookie"])){
                // destroy the temp cookie
                setcookie( "phorum_tmp_cookie", "", 0, $PHORUM["session_path"], $PHORUM["session_domain"] );
            }
            phorum_user_create_session(); 
            // redirecting to the register page is a little weird.  So, we just go to the list page if we came from the register page.
            if ( isset( $PHORUM['use_cookies'] ) && $PHORUM["use_cookies"] && !strstr( $_POST["redir"], "register." . PHORUM_FILE_EXTENSION ) ) {
                $redir = $_POST["redir"];
            } else {
                $redir = phorum_get_url( PHORUM_LIST_URL );
            } 

            phorum_redirect_by_url($redir);            

            exit();
        } else {
            $error = $PHORUM["DATA"]["LANG"]["InvalidLogin"];
        } 
    } 

} elseif($PHORUM["use_cookies"]) {
    // this is a get, so lets set our temp cookie
    // it has to be set just like the session cookie is set    
    setcookie( "phorum_tmp_cookie", "this will be destroyed once logged in", 0, $PHORUM["session_path"], $PHORUM["session_domain"] );
} 

$PHORUM["DATA"]["URL"]["INDEX"] = phorum_get_url( PHORUM_INDEX_URL );

$PHORUM["DATA"]["URL"]["REGISTER"] = phorum_get_url( PHORUM_REGISTER_URL );


if ( !empty( $PHORUM["args"]["redir"] ) ) {
    $PHORUM["DATA"]["LOGIN"]["redir"] = htmlspecialchars( urldecode( $PHORUM["args"]["redir"] ) );
} elseif ( !empty( $_REQUEST["redir"] ) ) {
    $PHORUM["DATA"]["LOGIN"]["redir"] = htmlspecialchars( $_REQUEST["redir"] );
} elseif ( !empty( $_SERVER["HTTP_REFERER"] ) ) {
    $PHORUM["DATA"]["LOGIN"]["redir"] = htmlspecialchars($_SERVER["HTTP_REFERER"]);
} else {
    $PHORUM["DATA"]["LOGIN"]["redir"] = phorum_get_url( PHORUM_INDEX_URL );
} 

$PHORUM["DATA"]["URL"]["ACTION"] = phorum_get_url( PHORUM_LOGIN_ACTION_URL );

$PHORUM["DATA"]["LOGIN"]["forum_id"] = ( int )$PHORUM["forum_id"];

$PHORUM["DATA"]["LOGIN"]["username"] = htmlspecialchars( $username );

$PHORUM["DATA"]["ERROR"] = htmlspecialchars( $error );

include phorum_get_template( "header" );
phorum_hook( "after_header" );

include phorum_get_template( $template );

phorum_hook( "before_footer" );
include phorum_get_template( "footer" );

?>