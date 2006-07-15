<?php
    if(!defined("PHORUM_ADMIN")) return;

    require_once("./mods/editor_tools/defaults.php");

    // save settings
    if(count($_POST))
    {
        if ($PHORUM["mod_editor_tools"]["enable_bbcode"]) {
            $PHORUM["mod_editor_tools"]["disable_bbcode_tool"] = array();
            foreach ($GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] as $toolinfo) {
                $tool = $toolinfo[1][0];
                if (! isset($_POST[enable_bbcode_tool][$tool])) {
                    $PHORUM["mod_editor_tools"]["disable_bbcode_tool"][$tool] = 1;
                }
            }
        }

        $PHORUM["mod_editor_tools"]["enable_bbcode"] = $_POST["enable_bbcode"] ? 1 : 0;
        $PHORUM["mod_editor_tools"]["enable_smileys"] = $_POST["enable_smileys"] ? 1 : 0;
        $PHORUM["mod_editor_tools"]["enable_subject_smileys"] = $_POST["enable_subject_smileys"] ? 1 : 0;
        $PHORUM["mod_editor_tools"]["enable_help"] = $_POST["enable_help"] ? 1 : 0;

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

    $frm->addbreak("Edit settings for the forum jumpmenu module");

    $frm->addrow("Enable BBcode tools for the message body", $frm->checkbox("enable_bbcode", "1", "", $PHORUM["mod_editor_tools"]["enable_bbcode"]));

    // Add options for bbcode tools, so people can disable them.
    if ($PHORUM["mod_editor_tools"]["enable_bbcode"]) {
        foreach ($GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] as $toolinfo) {
            if ($toolinfo[0] != 'bbcode') continue;
            $tool = $toolinfo[1][0];
            $checked = $PHORUM["mod_editor_tools"]["disable_bbcode_tool"][$tool] ? 0 : 1;
            $frm->addrow("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enable bbcode tool $tool", $frm->checkbox("enable_bbcode_tool[$tool]", 1, '', $checked));
        }
    }

    $frm->addrow("Enable Smiley tool for the message body", $frm->checkbox("enable_smileys", "1", "", $PHORUM["mod_editor_tools"]["enable_smileys"]));
    $frm->addrow("Enable Smiley tool for the message subject", $frm->checkbox("enable_subject_smileys", "1", "", $PHORUM["mod_editor_tools"]["enable_subject_smileys"]));
    $frm->addrow("Enable Help tool", $frm->checkbox("enable_help", "1", "", $PHORUM["mod_editor_tools"]["enable_help"]));

    $frm->show();
?>
