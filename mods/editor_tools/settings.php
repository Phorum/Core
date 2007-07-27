<?php
    if(!defined("PHORUM_ADMIN")) return;

    // Apply default settings.
    require_once("./mods/editor_tools/defaults.php");

    // Save the settings to the database.
    if(count($_POST))
    {
        $PHORUM["mod_editor_tools"] = array(
            "enable_help" => $_POST["enable_help"] ? 1 : 0
        );

        phorum_db_update_settings(array(
            "mod_editor_tools" => $PHORUM["mod_editor_tools"]
        ));

        phorum_admin_okmsg("The settings were successfully saved.");
    }

    // Build the settings form.
    include_once "./include/admin/PhorumInputForm.php";
    $frm = new PhorumInputForm ("", "post", "Save");
    $frm->hidden("module", "modsettings");
    $frm->hidden("mod", "editor_tools");

    $frm->addbreak("Edit settings for the Editor Tools module");

    $row = $frm->addrow("Enable Help tool", $frm->checkbox("enable_help", "1", "", $PHORUM["mod_editor_tools"]["enable_help"]) . ' Yes');
    $frm->addhelp($row,
        "Enable Help tool",
        "If you enable this option, then a help button will be added to
         the tool bar. This help button can be used to open help pages
         that are registered by other modules (e.g. to show a list of
         smileys or available BBcode tags).");

    $frm->show();
?>
