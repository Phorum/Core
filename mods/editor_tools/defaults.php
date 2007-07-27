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

// A description of the tools that are implemented by this module.
// In the arrays, the first element indicates to what editor tool
// group the tool belongs. The other parameters are the same as
// what the API call editor_tools_register_tool() expects.
$GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] = array (
    array("help", array('help', NULL, NULL, NULL, NULL, NULL)),
);
?>
