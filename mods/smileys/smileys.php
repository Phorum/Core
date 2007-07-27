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
    $lang = $PHORUM["DATA"]["LANG"]["mod_smileys"];

    // Register the javascript library for supporting smiley tool buttons.
    editor_tools_register_jslib(
        phorum_get_url(PHORUM_ADDON_URL, 'module=smileys', 'action=javascript')
    );

    // Register the smiley tool button for the message body.
    if (!empty($PHORUM['mod_smileys']['smileys_tool_enabled']))
    {
        editor_tools_register_tool(
            'smiley',                              // Tool id
            $lang['smiley'],                       // Tool description
            "./mods/smileys/icon.gif",             // Tool button icon
            "editor_tools_handle_smiley()",        // Javascript click action
            NULL,                                  // Tool icon width
            NULL,                                  // Tool icon height
            'body'                                 // Tool target
        );
    }

    // Register the smiley tool button for the message subject.
    if (!empty($PHORUM['mod_smileys']['subjectsmileys_tool_enabled']))
    {
        editor_tools_register_tool(
            'subjectsmiley',                       // Tool id
            $lang['subjectsmiley'],                // Tool description
            "./mods/smileys/icon.gif",             // Tool button icon
            "editor_tools_handle_subjectsmiley()", // Javascript click action
            NULL,                                  // Tool icon width
            NULL,                                  // Tool icon height
            'subject'                              // Tool target
        );
    }

    // Register the smileys help page.
    editor_tools_register_help(
        'smileys help',
        phorum_get_url(PHORUM_ADDON_URL, 'module=smileys', 'action=help')
    );
}

// The addon hook is used for displaying a help info screen and for
// supplying the javascript library.
function phorum_mod_smileys_addon()
{
    $PHORUM = $GLOBALS['PHORUM'];

    if (empty($PHORUM["args"]["action"])) return;

    // Include the smileys help page.
    if ($PHORUM["args"]["action"] == 'help')
    {
        $lang = $GLOBALS['PHORUM']['language'];
        if (!file_exists('./mods/smileys/help/$lang/smileys.php')) {
            $lang = 'english';
        }
        include("./mods/smileys/help/$lang/smileys.php");
        return;
    }

    // Include the javascript library for the smileys editor tools.
    if ($PHORUM["args"]["action"] == 'javascript') {

        include("./mods/smileys/smileys_editor_tools.js.php");
        return;
    }
}

?>
