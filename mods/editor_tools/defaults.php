<?php
// A simple helper script that will setup initial module
// settings in case one of these settings is missing.

if(!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"] = array();
}

// By default, we will display the bbcode tools.
if (! isset($GLOBALS["PHORUM"]["mod_editor_tools"]["enable_bbcode"])) {
    $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_bbcode"] = 1;
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
    array("bbcode",  array('bold',        NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('underline',   NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('italic',      NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('strike',      NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('subscript',   NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('superscript', NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('color',       NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('size',        NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('center',      NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('image',       NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('url',         NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('email',       NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('code',        NULL, NULL, NULL, NULL, NULL)),
    array("bbcode",  array('quote',       NULL, NULL, NULL, 20,   NULL)),
    array("bbcode",  array('hr',          NULL, NULL, NULL, NULL, NULL)),
    array("smiley",  array('smiley',      NULL, NULL, NULL, NULL, NULL)),
    array("help",    array('help',        NULL, NULL, NULL, NULL, NULL)),
    array('subjectsmiley', array('subjectsmiley', NULL, NULL, NULL, NULL, NULL)),
);
?>
