<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2005  Phorum Development Team                              //
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

define('phorum_page','reply');

include_once("./common.php");
include_once("./include/format_functions.php");

// for post_form
$reply_page=true;

$thread = $PHORUM["args"][1];
$message_id = $PHORUM["args"][2];

// set all our URL's
phorum_build_common_urls();

$message = phorum_db_get_message($message_id);

if(isset($PHORUM["args"]["quote"])){
    if($PHORUM["hooks"]["quote"]){
        $PHORUM["DATA"]["POST"]["body"] = phorum_hook( "quote", array($message['author'], $message['body']));
    }
    if(empty($PHORUM["DATA"]["POST"]["body"])){
        $phorum_quote_body = phorum_strip_body($message['body']);
        $phorum_quote_body=str_replace("\n", "\n> ", $phorum_quote_body);
        $phorum_quote_body=wordwrap(trim($phorum_quote_body), 50, "\n> ", true);
        $PHORUM["DATA"]["POST"]["body"]="{$message['author']} {$PHORUM['DATA']['LANG']['Wrote']}:\n".str_repeat("-", 55)."\n> $phorum_quote_body\n\n\n";
    }
}

$PHORUM["DATA"]["POST"]["thread"]=$thread;
$PHORUM["DATA"]["POST"]["parentid"]=$message_id;
$PHORUM["DATA"]["POST"]["subject"]=$message["subject"];
if(substr($PHORUM["DATA"]["POST"]["subject"], 0, 4) != "Re: ") $PHORUM["DATA"]["POST"]["subject"] = "Re: " . $PHORUM["DATA"]["POST"]["subject"];

include phorum_get_template("header");
phorum_hook("after_header");
include "./include/post_form.php";
phorum_hook("before_footer");
include phorum_get_template("footer");


?>