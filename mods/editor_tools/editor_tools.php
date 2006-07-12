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

// Add the javascript and CSS for the editor tools to the page header.
function phorum_mod_editor_tools_common()
{
    $GLOBALS["PHORUM"]["DATA"]["HEAD_TAGS"] .= 
      '<script type="text/javascript" src="./mods/editor_tools/editor_tools.js"></script>' .
      '<link rel="stylesheet" type="text/css" href="./mods/editor_tools/editor_tools.css"></link>' .
      '<link rel="stylesheet" href="./mods/editor_tools/colorpicker/js_color_picker_v2.css"/>' .
      '<script type="text/javascript" src="./mods/editor_tools/colorpicker/color_functions.js"></script>' .
      '<script type="text/javascript" src="./mods/editor_tools/colorpicker/js_color_picker_v2.js"></script>';

    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["SHOW"] = false;

    // Decide what tools we want to show. Later on we might replace
    // this by code to be able to configure this from the module
    // settings page.
    $tools = array();
    if (isset($GLOBALS["PHORUM"]["mods"]["bbcode"])) {
        $tools[] = 'bold';
        $tools[] = 'underline';
        $tools[] = 'italic';
        $tools[] = 'strike';
        $tools[] = 'subscript';
        $tools[] = 'superscript';
        $tools[] = 'color';
        $tools[] = 'size';
        $tools[] = 'center';
        $tools[] = 'image';
        $tools[] = 'url';
        $tools[] = 'email';
        $tools[] = 'code';
        $tools[] = 'hr';
    }
    if (isset($GLOBALS["PHORUM"]["mods"]["smileys"])) {
        $tools[] = 'smiley';
    }
    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["TOOLS"] = $tools;
}

// We want to run some javascript for displaying the editor tools in the
// before_footer hook. We can only run it there, because we need the
// textarea object to be available in the page. This hook is only used 
// for flagging the before_footer hook that the tools should be displayed.
function phorum_mod_editor_tools_before_editor($data)
{
    $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["SHOW"] = true;
    return $data;
}

// Add the javascript code for constructing and displaying the editor tools.
function phorum_mod_editor_tools_before_footer()
{ 
    $PHORUM = $GLOBALS["PHORUM"];
    $tools = $GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"]["TOOLS"];

    if (! $PHORUM["MOD_EDITOR_TOOLS"]["SHOW"]) return;

    // Add the javascript code to the page.
    ?>
    <script type="text/javascript">
        <?php
        print "// Add language strings for the editor_tools module.\n";
        foreach ($PHORUM["DATA"]["LANG"]["mod_editor_tools"] as $key => $val){
            print "        editor_tools_lang['" . addslashes($key) . "'] " .  
                  " = '" . addslashes($val) . "';\n";
        }
        ?>

        <?php 
        print "// Add enabled editor tools.\n";
        $idx = 0;
        foreach ($tools as $tool) {
            print "        editor_tools_enabled[$idx] = '".addslashes($tool)."';\n";
            $idx ++;
        }
        ?>

        // Construct and display the editor tools panel.
        editor_tools_construct();
    </script>
    <?php
}

?>
