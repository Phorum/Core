<?php
///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2006  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
///////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM")) return;

define('MOD_EDITOR_TOOLS_BASE', './mods/editor_tools');
define('MOD_EDITOR_TOOLS_ICONS', MOD_EDITOR_TOOLS_BASE . '/icons');

// Fields in the editor tool info arrays.
define('TOOL_ID',          0);
define('TOOL_DESCRIPTION', 1);
define('TOOL_ICON',        2);
define('TOOL_JSACTION',    3);

/**
 * Adds the javascript and CSS for the editor tools to the page header.
 * Sets up internal datastructures for the editor tools module.
 */
function phorum_mod_editor_tools_common()
{
    $GLOBALS["PHORUM"]["DATA"]["HEAD_TAGS"] .= 
      '<script type="text/javascript" src="./mods/editor_tools/editor_tools.js"></script>' .
      '<link rel="stylesheet" type="text/css" href="./mods/editor_tools/editor_tools.css"></link>' .
      '<link rel="stylesheet" href="./mods/editor_tools/colorpicker/js_color_picker_v2.css"/>';

    // Decide what tools we want to show. Later on we might replace
    // this by code to be able to configure this from the module
    // settings page.

    $tools = array();

    // Add the tools for supporting the bbcode module.
    if (isset($GLOBALS["PHORUM"]["mods"]["bbcode"]) && $GLOBALS["PHORUM"]["mods"]["bbcode"]) {
        $tools[] = array('bold',        NULL, NULL, NULL);
        $tools[] = array('underline',   NULL, NULL, NULL);
        $tools[] = array('italic',      NULL, NULL, NULL);
        $tools[] = array('strike',      NULL, NULL, NULL);
        $tools[] = array('subscript',   NULL, NULL, NULL);
        $tools[] = array('superscript', NULL, NULL, NULL);
        $tools[] = array('color',       NULL, NULL, NULL);
        $tools[] = array('size',        NULL, NULL, NULL);
        $tools[] = array('center',      NULL, NULL, NULL);
        $tools[] = array('image',       NULL, NULL, NULL);
        $tools[] = array('url',         NULL, NULL, NULL);
        $tools[] = array('email',       NULL, NULL, NULL);
        $tools[] = array('code',        NULL, NULL, NULL);
        $tools[] = array('quote',       NULL, NULL, NULL);
        $tools[] = array('hr',          NULL, NULL, NULL);
    }

    // Add a tool for supporting the smileys module.
    if (isset($GLOBALS["PHORUM"]["mods"]["smileys"]) && $GLOBALS["PHORUM"]["mods"]["smileys"]) {
        $tools[] = array('smiley',      NULL, NULL, NULL);
    }

    // Store our information for later use.
    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"] = array (
        "ON_EDITOR_PAGE"    => false,
        "STARTED"           => false,
        "TOOLS"             => $tools,
        "JSLIBS"            => array(),
    );
}

/**
 * Sets a flag which tell us that we are on a page containing
 * a posting editor. Note: this method of detection will only work
 * for Phorum 5.1+
 *
 * @param $data - Standard Phorum before_editor hook data.
 * @return $data - The unmodified input data.
 */
function phorum_mod_editor_tools_before_editor($data)
{
    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["ON_EDITOR_PAGE"] = true;
    return $data;
}

/**
 * Adds the javascript code for constructing and displaying the editor
 * tools to the page. The editor tools will be built completely using
 * only Javascript/DOM technology.
 */
function phorum_mod_editor_tools_before_footer()
{ 
    if (! $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["ON_EDITOR_PAGE"]) return;

    // Give other modules a chance to setup their plugged in
    // editor tools. This is done through a standard hook call.
    phorum_hook('editor_tool_plugin');

    $PHORUM = $GLOBALS["PHORUM"];
    $tools  = $PHORUM["MOD_EDITOR_TOOLS"]["TOOLS"];
    $jslibs = $PHORUM["MOD_EDITOR_TOOLS"]["JSLIBS"];
    $lang   = $PHORUM["DATA"]["LANG"]["mod_editor_tools"];

    // Fill in default values for the tools.
    foreach ($tools as $id => $toolinfo)
    {
        $tool_id = $toolinfo[TOOL_ID];

        // Default for description is the mod_editor_tools language string.
        if ($toolinfo[TOOL_DESCRIPTION] == NULL) {
            $toolinfo[TOOL_DESCRIPTION] = isset($lang[$tool_id]) ? $lang[$tool_id] : $tool_id;
        }

        // Default for the icon to use.
        if ($toolinfo[TOOL_ICON] == NULL) {
            $toolinfo[TOOL_ICON] = MOD_EDITOR_TOOLS_ICONS . "/{$tool_id}.gif";
        }

        // Default for the javascript action to use.
        if ($toolinfo[TOOL_JSACTION] == NULL) {
            $toolinfo[TOOL_JSACTION] = "editor_tools_handle_{$tool_id}()";
        }

        $tools[$id] = $toolinfo;
    }

    // Add javascript libraries for the color picker.
    if (editor_tools_get_tool('color')) {
        $jslibs[] = './mods/editor_tools/colorpicker/color_functions.js';
        $jslibs[] = './mods/editor_tools/colorpicker/js_color_picker_v2.js';
    }

    // Load all dynamic javascript libraries.
    foreach ($jslibs as $jslib) {
        $qjslib = htmlspecialchars($jslib);
        print "<script type=\"text/javascript\" src=\"$qjslib\"></script>\n";
    }

    // Construct javascript code.
    print '<script type="text/javascript">';

    // Make language strings available for the javascript code.\n";
    foreach ($PHORUM["DATA"]["LANG"]["mod_editor_tools"] as $key => $val) {
        print "editor_tools_lang['" . addslashes($key) . "'] " .  
              " = '" . addslashes($val) . "';\n";
    }

    // Add the editor tools.
    $idx = 0;
    foreach ($tools as $toolinfo) {
        list ($tool, $description, $icon, $jsfunction) = $toolinfo;
        print "editor_tools[$idx] = new Array(" .
              "'" . addslashes($tool) . "', " .
              "'" . addslashes($description) . "', " .
              "'" . addslashes($icon) . "', " .
              "'" . addslashes($jsfunction) . "');\n";
        $idx ++;
    }

    // Add available smileys for the smiley picker.
    if (isset($PHORUM["mods"]["smileys"]) && $PHORUM["mods"]["smileys"]) {
        $prefix = $PHORUM["mod_smileys"]["prefix"]; 
        foreach ($PHORUM["mod_smileys"]["smileys"] as $id => $smiley) {
            if (! $smiley["active"] || $smiley["is_alias"] || $smiley["uses"] == 1) continue;
            print "editor_tools_smileys['" . addslashes($smiley["search"]) . "'] = '" . addslashes($prefix . $smiley["smiley"]) . "';\n";
        }
    }

    // Construct and display the editor tools panel.
    print "editor_tools_construct();\n";

    print "</script>\n";

    // Keep track that the editor tools have been setup.
    $PHORUM["MOD_EDITOR_TOOLS"]["STARTED"] = true;
}

// ----------------------------------------------------------------------
// Editor tools API
// ----------------------------------------------------------------------

/**
 * Registers an editor tool. This can be used by another module, to
 * easily add buttons to the editor tools. The other module will
 * have to setup an "editor_tool_plugin" hook. In that hook, javascript
 * code that has to be run can be added and the tool must be registered
 * through this call. Registering the tool will take care of adding an
 * extra button to the button bar.
 *
 * @param $tool_id - A unique identifier to use for the tool.
 * @param $description - The description of the tool (this will be
 *                used as the title popup for the image button).
 *                NULL is allowed as the value. In that case,
 *                the $tool_id will be looked up in the editor tools
 *                module's language file. If it's available, the
 *                translation string is used as the description. If
 *                not, the $tool_id will be used directly.
 * @param $icon - The path to the icon image that has to be used for
 *                the button. This path is relative to the Phorum
 *                web directory.
 *                NULL is allowed as the value. In that case,
 *                the icon will be <module icon path>/<$tool_id>.gif.
 * @param $jsaction - The javascript code to execute when a user 
 *                clicks on the editor tool button.
 *                NULL is allowed as the value. In that case,
 *                the javascript function editor_tools_handle_<$tool_id>()
 *                will be used.
 */
function editor_tools_register_tool($tool_id, $description, $icon, $jsaction) 
{
    if ($GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["STARTED"]) {
        die("Internal error for the editor_tools module: " .
            "tool ".htmlspecialchars($toold_id)." is registered " .
            "after the editor_tools were started up. Tools must " .
            "be registered within the \"editor_tool_plugin\" hook.");
    }

    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["TOOLS"][] = array(
        $tool_id,
        $description,
        $icon,
        $jsaction
    );
}

/*
 * Register a javascript library that has to be loaded by the editor
 * tools. The library file will be loaded before the editor tools
 * javascript code is written to the page.
 *
 * @param $jslib - The path to the javascript library to load.
 *                 This path is relative to the Phorum web directory.
 */
function editor_tools_register_jslib($jslib)
{
    if ($GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["STARTED"]) {
        die("Internal error for the editor_tools module: " .
            "javascript library ".htmlspecialchars($jslib)." is registered " .
            "after the editor_tools were started up. Libraries must " .
            "be registered within the \"editor_tool_plugin\" hook.");
    }

    $jslibs = $PHORUM["MOD_EDITOR_TOOLS"]["JSLIBS"];
}

/**
 * Returns the info for a single tool or NULL if that tool has
 * not been registered.
 *
 * @param $tool_id - The tool id to lookup.
 * @return $toolinfo - The tool's info array or NULL if the tool
 *                     is not available.
 */
function editor_tools_get_tool($tool_id)
{
    $tools = $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["TOOLS"];
    foreach ($tools as $id => $toolinfo) {
        if ($toolinfo[0] == $tool_id) {
            return $toolinfo;
        }
    }
    return NULL;
}

?>
