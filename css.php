<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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

define('phorum_page','css');
include_once("./common.php");

// Argument 1 should be the name of the css template to load.
if(isset($PHORUM["args"]["1"])){
    $css = basename((string)$PHORUM["args"]["1"]);
} else {
    trigger_error("Missing argument", E_USER_ERROR);
    exit(1);
}

// Find the modification time for the css file and the settings file.
list ($css_php, $css_tpl) = phorum_get_template_file($css);
list ($settings_php, $settings_tpl) = phorum_get_template_file('settings');
$css_t = filemtime($css_tpl);
$settings_t = filemtime($settings_tpl);
$last_modified = $css_t > $settings_t ? $css_t : $settings_t;

// Check if a If-Modified-Since header is in the request. If yes, then
// check if the CSS code has changed, based on the filemtime() data from
// above. If nothing changed, then we can return a 304 header, to tell the
// browser to use the cached data.
if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $header = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
    $if_modified_since = strtotime($header);

    if ($if_modified_since >= $last_modified) {
        header("HTTP/1.0 304 Not Modified");
        exit(0);
    }
}

// Send the RSS to the browser.
header("Content-Type: text/css");
header("Last-Modified: " . date("r", $last_modified));

include(phorum_get_template($css));

// Exit here explicitly for not giving back control to portable and
// embedded Phorum setups.
exit(0);

?>
