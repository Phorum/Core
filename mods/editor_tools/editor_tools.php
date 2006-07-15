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
define('MOD_EDITOR_TOOLS_HELP', MOD_EDITOR_TOOLS_BASE . '/help');

// Default icon size to use.
define('MOD_EDITOR_TOOLS_DEFAULT_IWIDTH', 21);
define('MOD_EDITOR_TOOLS_DEFAULT_IHEIGHT', 20);

// Fields in the editor tool info arrays.
define('TOOL_ID',          0);
define('TOOL_DESCRIPTION', 1);
define('TOOL_ICON',        2);
define('TOOL_JSACTION',    3);
define('TOOL_IWIDTH',      4);
define('TOOL_IHEIGHT',     5);

// Load default settings.
require_once("./mods/editor_tools/defaults.php");

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

    $lang = $GLOBALS["PHORUM"]["language"];
    $langstr = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_editor_tools"];

    // Decide what tools we want to show. Later on we might replace
    // this by code to be able to configure this from the module
    // settings page.

    $tools = array();
    $help_chapters = array();

    // Add the tools and help page for supporting the bbcode module.
    if (isset($GLOBALS["PHORUM"]["mods"]["bbcode"]) &&
        $GLOBALS["PHORUM"]["mods"]["bbcode"] &&
        $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_bbcode"]) {
        foreach ($GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] as $toolinfo) {
            if ($toolinfo[0] != 'bbcode') continue;
            if (isset($GLOBALS["PHORUM"]["mod_editor_tools"]["disable_bbcode_tool"][$toolinfo[1][0]])) continue;
            $tools[$toolinfo[1][0]] = $toolinfo[1];
        }

        if (file_exists(MOD_EDITOR_TOOLS_HELP . "/$lang/bbcode.php")) {
            $help_url = MOD_EDITOR_TOOLS_HELP . "/$lang/bbcode.php";
        } else {
            $help_url = MOD_EDITOR_TOOLS_HELP . "/english/bbcode.php";
        }
        $help_chapters[] = array($langstr["bbcode help"], $help_url);
    }

    // Add a tool and help page for supporting the smileys module.
    if (isset($GLOBALS["PHORUM"]["mods"]["smileys"]) &&
        $GLOBALS["PHORUM"]["mods"]["smileys"] &&
        $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_smileys"]) {
        foreach ($GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] as $toolinfo)
            if ($toolinfo[0] == 'smiley')
                $tools[$toolinfo[1][0]] = $toolinfo[1];

        if (file_exists(MOD_EDITOR_TOOLS_HELP . "/$lang/smileys.php")) {
            $help_url = MOD_EDITOR_TOOLS_HELP . "/$lang/smileys.php";
        } else {
            $help_url = MOD_EDITOR_TOOLS_HELP . "/english/smileys.php";
        }
        $help_chapters[] = array($langstr["smileys help"], $help_url);
    }

    // Add the subject smileys editor tool.
    if (isset($GLOBALS["PHORUM"]["mods"]["smileys"]) &&
        $GLOBALS["PHORUM"]["mods"]["smileys"] &&
        $GLOBALS["PHORUM"]["mod_editor_tools"]["enable_subject_smileys"]) {
        foreach ($GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] as $toolinfo)
            if ($toolinfo[0] == 'subject_smiley')
                $tools[$toolinfo[1][0]] = $toolinfo[1];
    }

    // Store our information for later use.
    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"] = array (
        "ON_EDITOR_PAGE"    => false,
        "STARTED"           => false,
        "TOOLS"             => $tools,
        "JSLIBS"            => array(),
        "HELP_CHAPTERS"     => $help_chapters,
    );

    // Give other modules a chance to setup their plugged in
    // editor tools. This is done through a standard hook call.
    phorum_hook('editor_tool_plugin');

    // Keep track that the editor tools have been setup. From here
    // on, the API calls for registering tools, javascript libraries
    // and help chapters are no longer allowed.
    $PHORUM["MOD_EDITOR_TOOLS"]["STARTED"] = true;
}

/**
 * Sets a flag which tell us that we are on a page containing
 * a posting editor.
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
 * Implements a fallback mechanism for users that do not have
 * javascript enabled in their browser. For those users, we
 * supply links to the help pages.
 */
function phorum_mod_editor_tools_tpl_editor_before_textarea()
{
    $help = $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["HELP_CHAPTERS"];
    $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_editor_tools"];

    if (!count($help)) return;

    print '<noscript><br/><br/><font size="-1">';
    print $lang['help'] . "<br/><ul>";
    foreach ($help as $helpinfo) {
      print "<li><a href=\"" . htmlspecialchars($helpinfo[1]) . "\" " .
            "target=\"editor_tools_help\">" .
            htmlspecialchars($helpinfo[0]) . "</a><br/>";
    }
    print '</ul><br/></font></noscript>';
}

/**
 * Adds the javascript code for constructing and displaying the editor
 * tools to the page. The editor tools will be built completely using
 * only Javascript/DOM technology.
 */
function phorum_mod_editor_tools_before_footer()
{ 
    // Detect if we are handling a PM editor and show
    // the editor tools in the PM interface as well.
    // This is a bit hacked in now.
    if (isset($GLOBALS["PHORUM"]["DATA"]["PM_PAGE"]) && $GLOBALS["PHORUM"]["DATA"]["PM_PAGE"] == 'send') $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["ON_EDITOR_PAGE"] = true;

    if (! $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["ON_EDITOR_PAGE"]) return;

    $PHORUM = $GLOBALS["PHORUM"];
    $tools  = $PHORUM["MOD_EDITOR_TOOLS"]["TOOLS"];
    $jslibs = $PHORUM["MOD_EDITOR_TOOLS"]["JSLIBS"];
    $help   = $PHORUM["MOD_EDITOR_TOOLS"]["HELP_CHAPTERS"];
    $lang   = $PHORUM["DATA"]["LANG"]["mod_editor_tools"];

    // Add a help tool. We add it as the first tool, so we can
    // shift it nicely to the right side of the page using CSS float.
    if ($GLOBALS["PHORUM"]["mod_editor_tools"]["enable_help"]) {
        foreach ($GLOBALS["PHORUM"]["mod_editor_tools"]["tools"] as $toolinfo)
            if ($toolinfo[0] == 'help') array_unshift($tools, $toolinfo[1]);
    }

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

        // Default for the icon size to use.
        if (!isset($toolinfo[TOOL_IWIDTH]) || empty($toolinfo[TOOL_IWIDTH])) {
            $toolinfo[TOOL_IWIDTH] = 21;
        }
        if (!isset($toolinfo[TOOL_IHEIGHT]) || empty($toolinfo[TOOL_IHEIGHT])) {
            $toolinfo[TOOL_IHEIGHT] = 20;
        }

        $tools[$id] = $toolinfo;
    }

    // Add javascript libraries for the color picker.
    if (editor_tools_get_tool('color')) {
        $jslibs[] = './mods/editor_tools/colorpicker/color_functions.js';
        $jslibs[] = './mods/editor_tools/colorpicker/js_color_picker_v2.js';
    }

    // Construct the javascript code for constructing the editor tools.
    print '<script type="text/javascript">';

    // Make language strings available for the javascript code.
    foreach ($PHORUM["DATA"]["LANG"]["mod_editor_tools"] as $key => $val) {
        print "editor_tools_lang['" . addslashes($key) . "'] " .
              " = '" . addslashes($val) . "';\n";
    }

    // Make default icon height available for the javascript code.
    print 'editor_tools_default_iconheight = ' . MOD_EDITOR_TOOLS_DEFAULT_IHEIGHT . ";\n";

    // Add help chapters.
    $idx = 0;
    foreach ($help as $helpinfo) {
        list ($description, $url) = $helpinfo;
        print "editor_tools_help_chapters[$idx] = new Array(" .
              "'" . addslashes($description) . "', " .
              "'" . addslashes($url) . "');\n";
        $idx ++;
    }

    // Add the list of enabled editor tools.
    $idx = 0;
    foreach ($tools as $toolinfo) {
        list ($tool, $description, $icon, $jsfunction, $iw, $ih) = $toolinfo;
        print "editor_tools[$idx] = new Array(" .
              "'" . addslashes($tool) . "', " .
              "'" . addslashes($description) . "', " .
              "'" . addslashes($icon) . "', " .
              "'" . addslashes($jsfunction) . "', " .
              (int)$iw . ", " . (int)$ih . ");\n";
        $idx ++;
    }

    // Add available smileys for the smiley picker.
    if (isset($PHORUM["mods"]["smileys"]) && $PHORUM["mods"]["smileys"]) {
        $prefix = $PHORUM["mod_smileys"]["prefix"];
        foreach ($PHORUM["mod_smileys"]["smileys"] as $id => $smiley) {
            if (! $smiley["active"] || $smiley["is_alias"]) continue;
            if ($smiley["uses"] == 0 || $smiley["uses"] == 2)
              print "editor_tools_smileys['" . addslashes($smiley["search"]) . "'] = '" . addslashes($prefix . $smiley["smiley"]) . "';\n";
            if ($smiley["uses"] == 1 || $smiley["uses"] == 2)
              print "editor_tools_subject_smileys['" . addslashes($smiley["search"]) . "'] = '" . addslashes($prefix . $smiley["smiley"]) . "';\n";
        }
    }
    print "</script>\n";

    // Load all dynamic javascript libraries.
    foreach ($jslibs as $jslib) {
        $qjslib = htmlspecialchars($jslib);
        print "<script type=\"text/javascript\" src=\"$qjslib\"></script>\n";
    }

    // Construct and display the editor tools panel.
    print '<script type="text/javascript">editor_tools_construct();</script>';


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
 * In case this call is made for an already registered tool id, the
 * existing tool definition will be replaced with the new one. This
 * can be used to override the functionality of existing editor tool
 * buttons.
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
 * @param $iconwidth - The width of the icon. If this parameter is omitted
 *                or is NULL, then the default value 21 will be used instead.
 * @param $iconheight - The height of the icon. If this parameter is omitted
 *                or is NULL, then the default value 20 will be used instead.
 */
function editor_tools_register_tool($tool_id, $description, $icon, $jsaction, $iwidth=NULL, $iheight=NULL)
{
    if ($GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["STARTED"]) {
        die("Internal error for the editor_tools module: " .
            "tool ".htmlspecialchars($toold_id)." is registered " .
            "after the editor_tools were started up. Tools must " .
            "be registered within or before the \"editor_tool_plugin\" hook.");
    }

    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["TOOLS"][$tool_id] = array(
        $tool_id,
        $description,
        $icon,
        $jsaction,
        $iwidth, $iheight
    );
}

/**
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
            "be registered within or before the \"editor_tool_plugin\" hook.");
    }

    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["JSLIBS"][] = $jslib;
}

/**
 * Register a help chapter that has to be linked to the editor tools
 * help button.
 *
 * @param $title - The title for the help chapter. This will be used
 *                 as the text for the link to the help page.
 * @param $url - The URL for the help page to display. 
 */
function editor_tools_register_help($title, $url)
{
    if ($GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["STARTED"]) {
        die("Internal error for the editor_tools module: " .
            "help chapter ".htmlspecialchars($title)." is registered " .
            "after the editor_tools were started up. Help chapters must " .
            "be registered within or before the \"editor_tool_plugin\" hook.");
    }

    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["HELP_CHAPTERS"][] = array($title, $url);
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
