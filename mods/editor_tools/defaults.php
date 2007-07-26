<?php
// A simple helper script that will setup initial module
// settings in case one of these settings is missing.

if(!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"] = array();
}

// By default, we will display the smiley tool.
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["enable_smileys"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_smileys"] = 1;
}

// By default, we will not display the subject smiley tool.
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["enable_subjectsmileys"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_subjectsmileys"] = 0;
}

// By default, we will display the help tool.
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["enable_help"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_help"] = 1;
}

// Smiley popup box dimension control.
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["smiley_popup_offset"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["smiley_popup_offset"] = 0;
}
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["smiley_popup_width"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["smiley_popup_width"] = 150;
}
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["subjectsmiley_popup_offset"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["subjectsmiley_popup_offset"] = 0;
}
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["subjectsmiley_popup_width"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["subjectsmiley_popup_width"] = 150;
}

// A description of the tools that are implemented by this module.
// In the arrays, the first element indicates to what editor tool
// group the tool belongs. The other parameters are the same as
// what the API call editor_tools_register_tool() expects.
$GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] = array (
    array("smiley",  array('smiley',      NULL, NULL, NULL, NULL, NULL)),
    array("help",    array('help',        NULL, NULL, NULL, NULL, NULL)),
    array('subjectsmiley', array('subjectsmiley', NULL, NULL, NULL, NULL, NULL)),
);
?>
