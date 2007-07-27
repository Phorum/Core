<?php
// A simple helper script that will setup initial module
// settings in case one of these settings is missing.

if(!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

if (! isset($GLOBALS['PHORUM']['mod_smileys'])           ||
    ! isset($GLOBALS['PHORUM']['mod_smileys']['prefix']) ||
    ! isset($GLOBALS['PHORUM']['mod_smileys']['smileys'])) {
    require_once("./mods/smileys/smileyslib.php");
    $GLOBALS['PHORUM']['mod_smileys'] = phorum_mod_smileys_initsettings();
}

if (!isset($GLOBALS['PHORUM']['mod_smileys']['smileys_tool_enabled'])) {
    $GLOBALS['PHORUM']['mod_smileys']['smileys_tool_enabled'] = 1;
}
if (!isset($GLOBALS['PHORUM']['mod_smileys']['subjectsmileys_tool_enabled'])) {
    $GLOBALS['PHORUM']['mod_smileys']['subjectsmileys_tool_enabled'] = 1;
}
if (empty($GLOBALS['PHORUM']['mod_smileys']['smiley_popup_width'])) {
    $GLOBALS['PHORUM']['mod_smileys']['smiley_popup_width'] = 150;
}
if (empty($GLOBALS['PHORUM']['mod_smileys']['smiley_popup_offset'])) {
    $GLOBALS['PHORUM']['mod_smileys']['smiley_popup_offset'] = 0;
}
if (empty($GLOBALS['PHORUM']['mod_smileys']['subjectsmiley_popup_width'])) {
    $GLOBALS['PHORUM']['mod_smileys']['subjectsmiley_popup_width'] = 150;
}
if (empty($GLOBALS['PHORUM']['mod_smileys']['subjectsmiley_popup_offset'])) {
    $GLOBALS['PHORUM']['mod_smileys']['subjectsmiley_popup_offset'] = 0;
}

?>
