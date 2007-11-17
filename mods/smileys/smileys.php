<?php

if(!defined("PHORUM")) return;

require_once("./mods/smileys/defaults.php");

// Register the additional CSS code for this module.
function phorum_mod_smileys_css_register($data)
{
    $data['register'][] = array(
        "module" => "smileys",
        "where"  => "after",
        "source" => "file(mods/smileys/smileys.css)"
    );
    return $data;
}

// Register the additional JavaScript code for this module.
function phorum_mod_smileys_javascript_register($data)
{
    // We only need javascript for Editor Tools support.
    $PHORUM = $GLOBALS['PHORUM'];
    if (empty($PHORUM['mod_smileys']['smileys_tool_enabled']) &&
        empty($PHORUM['mod_smileys']['subjectsmileys_tool_enabled']))
        return $data;

    // The generated javascript depends on the settings, so we use
    // a specific cache_key for this module.
    $cache_key = (isset($GLOBALS['PHORUM']['mod_smileys']['cache_key'])
               ? $GLOBALS['PHORUM']['mod_smileys']['cache_key'] : 0) .
               '-' . @filemtime("mods/smileys/smileys_editor_tools.js.php");

    $data[] = array(
        "module"    => "smileys",
        "source"    => "file(mods/smileys/smileys_editor_tools.js.php)",
        "cache_key" => $cache_key
    );
    return $data;
}

function phorum_mod_smileys_after_header()
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Return immediately if we have no active smiley replacements.
    if (!isset($PHORUM["mod_smileys"])||!$PHORUM["mod_smileys"]["do_smileys"]){
        return;
    }
}

function phorum_mod_smileys_format_fixup($data)
{
    // Do not format smileys for the feeds.
    if (phorum_page == "feed") return $data;

    $PHORUM = $GLOBALS["PHORUM"];

    // Return immediately if we have no active smiley replacements.
    if (!isset($PHORUM["mod_smileys"])||!$PHORUM["mod_smileys"]["do_smileys"]){
        return $data;
    }

    // Run smiley replacements.
    $replace = $PHORUM["mod_smileys"]["replacements"];
    foreach ($data as $key => $message)
    {
        // Check for disabled formatting.
        if (!empty($PHORUM["mod_smileys"]["allow_disable_per_post"]) &&
            !empty($message['meta']['disable_smileys'])) {
            continue;
        }

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
    
    $description = isset($lang['smileys help'])
                   ? $lang['smileys help'] : 'smileys help';

    // Register the smileys help page.
    editor_tools_register_help(
        $description,
        phorum_get_url(PHORUM_ADDON_URL, 'module=smileys', 'action=help')
    );
}

// The addon hook is used for displaying a help info screen.
function phorum_mod_smileys_addon()
{
    $PHORUM = $GLOBALS['PHORUM'];

    if (empty($PHORUM["args"]["action"])) trigger_error(
        'Missing "action" argument for smileys module addon call',
        E_USER_ERROR
    );

    // Include the smileys help page.
    if ($PHORUM["args"]["action"] == 'help')
    {
        $lang = $GLOBALS['PHORUM']['language'];
        if (!file_exists("./mods/smileys/help/$lang/smileys.php")) {
            $lang = 'english';
        }
        include("./mods/smileys/help/$lang/smileys.php");
        exit(0);
    }

    trigger_error(
        'Illegal "action" argument ' .
        '"' . htmlspecialchars($PHORUM['args']['action']) . '"' .
        'for smileys module addon call',
        E_USER_ERROR
    );
}

// Add the "Disable smileys" option to the template. Note that the template
// should contain the code {HOOK "tpl_editor_disable_smileys"} at an
// appropriate place for this to work.
function phorum_mod_smileys_tpl_editor_disable_smileys()
{
    $PHORUM = $GLOBALS["PHORUM"];
    if (empty($PHORUM["mod_smileys"]["allow_disable_per_post"]))
        return;

    include(phorum_get_template('smileys::disable_option'));
}

// Process "Disable smileys" option from the message form.
function phorum_mod_smileys_posting_custom_action($message)
{
    $PHORUM = $GLOBALS["PHORUM"];
    if (empty($PHORUM["mod_smileys"]["allow_disable_per_post"])) {
        unset($message['meta']['disable_smileys']);
        return $message;
    }

    if (count($_POST)) {
        if (empty($_POST['disable_smileys'])) {
            unset($message['meta']['disable_smileys']);
        } else {
            $message['meta']['disable_smileys'] = 1;
        }
    }

    return $message;
}




?>
