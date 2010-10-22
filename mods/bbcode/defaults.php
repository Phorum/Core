<?php
///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2010  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

if (!defined("PHORUM") && !defined('PHORUM_ADMIN')) return;

if (! isset($GLOBALS["PHORUM"]["mod_bbcode"]) ||
    ! is_array($GLOBALS["PHORUM"]["mod_bbcode"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"] = array();
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["links_in_new_window"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["links_in_new_window"] = 0;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["rel_no_follow"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["rel_no_follow"] = 1;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["quote_hook"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["quote_hook"] = 0;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["show_full_urls"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["show_full_urls"] = 0;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_urls"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_urls"] = 1;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_email"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["process_bare_email"] = 1;
}

if (!isset($GLOBALS["PHORUM"]["mod_bbcode"]["enable_bbcode_escape"])) {
    $GLOBALS["PHORUM"]["mod_bbcode"]["enable_bbcode_escape"] = 0;
}

?>
