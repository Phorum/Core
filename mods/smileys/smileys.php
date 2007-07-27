<?php

if(!defined("PHORUM")) return;

require_once("./mods/smileys/defaults.php");

function phorum_mod_smileys_after_header()
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Return immediately if we have no active smiley replacements.
    if (!isset($PHORUM["mod_smileys"])||!$PHORUM["mod_smileys"]["do_smileys"]){
        return;
    }
}

function phorum_mod_smileys_format($data)
{
    // Do not format smileys for the feeds.
    if (phorum_page == "feed") return $data;

    $PHORUM = $GLOBALS["PHORUM"];

    // Return immediately if we have no active smiley replacements.
    if (!isset($PHORUM["mod_smileys"])||!$PHORUM["mod_smileys"]["do_smileys"]){
        return $data;
    }

    // Add the stylesheet for the smileys to the header.
    if (! isset($PHORUM["mod_smileys"]["added_stylesheet"])) {
        $GLOBALS["PHORUM"]["DATA"]["HEAD_TAGS"] .= 
            "<style type=\"text/css\">\n" .
            ".mod_smileys_img {\n" .
            "    vertical-align: middle;\n" .
            "    margin: 0px 3px 0px 3px;\n" .
            "    border: none;\n" .
            "}\n" .
            "</style>\n";
            $PHORUM["mod_smileys"]["added_stylesheet"] = true;
    }

    // Run smiley replacements.
    $replace = $PHORUM["mod_smileys"]["replacements"];
    foreach ($data as $key => $message)
    {
        // Do subject replacements.
        if (isset($replace["subject"]) && isset($message["subject"])) {
            $data[$key]['subject'] = str_replace ($replace["subject"][0] , $replace["subject"][1], $message['subject'] );
        }
        // Do body replacements.
        if (isset($replace["body"]) && isset($message["body"])) {
            $data[$key]['body'] = str_replace ($replace["body"][0] , $replace["body"][1], $message['body'] );
        }
    }

    return $data;
}

// Add a smiley tool button to the Editor Tools module's tool bar.
function phorum_mod_smileys_editor_tool_plugin()
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Register the javascript library for supporting smiley tool buttons.
    editor_tools_register_jslib('./mods/smileys/smileys_editor_tools.js');
}

?>
