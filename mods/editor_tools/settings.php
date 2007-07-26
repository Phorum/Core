<?php
    if(!defined("PHORUM_ADMIN")) return;

    require_once("./mods/editor_tools/defaults.php");

    // save settings
    if(count($_POST))
    {
        $PHORUM["mod_editor_tools"]["enable_smileys"] = $_POST["enable_smileys"] ? 1 : 0;
        $PHORUM["mod_editor_tools"]["enable_subjectsmileys"] = $_POST["enable_subjectsmileys"] ? 1 : 0;
        $PHORUM["mod_editor_tools"]["enable_help"] = $_POST["enable_help"] ? 1 : 0;

        if (isset($_POST[smiley_popup_width])) $PHORUM["mod_editor_tools"]["smiley_popup_width"] = (int)$_POST["smiley_popup_width"];
        if (isset($_POST[smiley_popup_offset])) $PHORUM["mod_editor_tools"]["smiley_popup_offset"] = (int)$_POST["smiley_popup_offset"];
        if (isset($_POST[subjectsmiley_popup_width])) $PHORUM["mod_editor_tools"]["subjectsmiley_popup_width"] = (int)$_POST["subjectsmiley_popup_width"];
        if (isset($_POST[subjectsmiley_popup_offset])) $PHORUM["mod_editor_tools"]["subjectsmiley_popup_offset"] = (int)$_POST["subjectsmiley_popup_offset"];

        if(!phorum_db_update_settings(array("mod_editor_tools"=>$PHORUM["mod_editor_tools"]))){
            phorum_admin_error("A database error occured while updating the settings.");
        } else {
            phorum_admin_okmsg("The settings were successfully saved.");
        }
    }

    include_once "./include/admin/PhorumInputForm.php";
    $frm =& new PhorumInputForm ("", "post", "Save");
    $frm->hidden("module", "modsettings");
    $frm->hidden("mod", "editor_tools");

    $frm->addbreak("Edit settings for the Editor Tools module");

    $frm->addrow("Enable Smiley tool for the message body", $frm->checkbox("enable_smileys", "1", "", $PHORUM["mod_editor_tools"]["enable_smileys"]));
    if ($PHORUM["mod_editor_tools"]["enable_smileys"]) {
        $frm->addrow("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The width to use for the smileys popup", $frm->text_box("smiley_popup_width", $PHORUM["mod_editor_tools"]["smiley_popup_width"], 5));
        $frm->addrow("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;How far to shift the smileys popup to the left", $frm->text_box("smiley_popup_offset", $PHORUM["mod_editor_tools"]["smiley_popup_offset"], 5));
    }

    $frm->addrow("Enable Smiley tool for the message subject", $frm->checkbox("enable_subjectsmileys", "1", "", $PHORUM["mod_editor_tools"]["enable_subjectsmileys"]));
    if ($PHORUM["mod_editor_tools"]["enable_subjectsmileys"]) {
        $frm->addrow("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The width to use for the subject smileys popup", $frm->text_box("subjectsmiley_popup_width", $PHORUM["mod_editor_tools"]["subjectsmiley_popup_width"], 5));
        $frm->addrow("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;How far to shift the subject smileys popup to the left", $frm->text_box("subjectsmiley_popup_offset", $PHORUM["mod_editor_tools"]["subjectsmiley_popup_offset"], 5));
    }

    $frm->addrow("Enable Help tool", $frm->checkbox("enable_help", "1", "", $PHORUM["mod_editor_tools"]["enable_help"]));

    $frm->show();
?>
