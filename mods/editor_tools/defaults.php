<?php
// A simple helper script that will setup initial module
// settings in case one of these settings is missing.

if(!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"] = array();
}

// By default, we will display the help tool.
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["enable_help"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_help"] = 1;
}
?>
