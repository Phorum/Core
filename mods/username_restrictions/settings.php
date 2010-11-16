<?php
    if (!defined("PHORUM_ADMIN")) return;

    require_once "./mods/username_restrictions/defaults.php";

    // Options for the valid username characters.
    $valid_chars_options = array(
        "l"    => 'Letters (a-z)',
        "n"    => 'Numbers (0-9)',
        "d"    => 'Dots "."',
        "h"    => 'Hyphens "-"',
        "u"    => 'Underscores "_"',
        "s"    => 'Spaces " "',
    );

    // Save module settings to the database.
    if(count($_POST))
    {
        // Build new settings array.
        $settings = array();
        $settings["max_length"]     = (int) $_POST["max_length"];
        $settings["min_length"]     = (int) $_POST["min_length"];
        $settings["only_lowercase"] = isset($_POST["only_lowercase"]) ? 1 : 0;

        // Valid chars is a bit special.
        $settings["valid_chars"] = isset($_POST["valid_chars"])
            ? implode("", array_keys($_POST["valid_chars"])) : "";

        // Take care of applying sane settings.
        if ($settings["min_length"] < 0) $settings["min_length"] = 0;
        if ($settings["max_length"] < $settings["min_length"] &&
            $settings["max_length"] != 0) {
            $settings["max_length"] = $settings["min_length"];
        }

        // Save settings array.
        $PHORUM["mod_username_restrictions"] = $settings;
        phorum_db_update_settings(array(
            "mod_username_restrictions" => $settings
        ));
        phorum_admin_okmsg("The module settings were successfully saved.");
    }

    include_once "./include/admin/PhorumInputForm.php";
    $frm = new PhorumInputForm ("", "post", "Save");
    $frm->hidden("module", "modsettings");
    $frm->hidden("mod", "username_restrictions"); 

    $frm->addbreak("Edit settings for the username restrictions module");

    $frm->addrow("Minimum username length (0 = no restriction)", $frm->text_box('min_length', $PHORUM["mod_username_restrictions"]["min_length"], 6));

    $frm->addrow("Maximum username length (0 = no restriction)", $frm->text_box('max_length', $PHORUM["mod_username_restrictions"]["max_length"], 6));

    $checkboxes = '';
    foreach ($valid_chars_options as $k => $v) {
        $enabled = strpos($PHORUM["mod_username_restrictions"]["valid_chars"], $k) === FALSE ? 0 : 1; 
        $checkboxes .= $frm->checkbox("valid_chars[$k]", "1", "", $enabled) . " $v<br/>"; 
    }
    $frm->addrow("Valid username characters (check none for no restrictions)", $checkboxes);

    $frm->addrow("Allow only lower case characters", $frm->checkbox("only_lowercase", "1", "", $PHORUM["mod_username_restrictions"]["only_lowercase"]) . ' Yes');

    $frm->show();
?>
