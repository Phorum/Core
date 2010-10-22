///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2010  Phorum Development Team                               //
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

// Javascript code for Smileys support in the Phorum editor_tools module.

// Some variables for storing objects that we need globally.
var editor_tools_smiley_picker_obj = null;
var editor_tools_subjectsmiley_picker_obj = null;

// Smileys for the smiley picker.
// *_s = search strings (smileys)
// *_r = replace strings (image urls)
var editor_tools_smileys = new Array();
var editor_tools_smileys_r = new Array();
var editor_tools_smileys_a = new Array();
var editor_tools_subjectsmileys = new Array();
var editor_tools_subjectsmileys_r = new Array();
var editor_tools_subjectsmileys_a = new Array();

// The width and offset to the left for the smiley picker popup menus.
// These values can be tweaked from the smiley module settings page.
var editor_tools_smileys_popupwidth = '<?php print (int) $PHORUM['mod_smileys']['smiley_popup_width'] ?>px';
var editor_tools_smileys_popupoffset = <?php print (int) $PHORUM['mod_smileys']['smiley_popup_offset'] ?>;
var editor_tools_subjectsmileys_popupwidth = '<?php print (int) $PHORUM['mod_smileys']['subjectsmiley_popup_width'] ?>px';
var editor_tools_subjectsmileys_popupoffset = <?php print (int) $PHORUM['mod_smileys']['subjectsmiley_popup_offset'] ?>;

// The available smileys.
<?php
$prefix = $PHORUM["http_path"] . "/" . $PHORUM["mod_smileys"]["prefix"];
$bsi = 0;
$ssi = 0;
foreach ($PHORUM["mod_smileys"]["smileys"] as $id => $smiley)
{
    // Skip inactive and duplicate smileys.
    if (! $smiley["active"] || $smiley["is_alias"]) continue;

    // Smileys that can be used in the body.
    if ($smiley["uses"] == 0 || $smiley["uses"] == 2)
    {
      print "editor_tools_smileys[$bsi] = '" . addslashes($smiley["search"]) . "';\n";
      print "editor_tools_smileys_r[$bsi] = '" . addslashes($prefix . $smiley["smiley"]) . "';\n";
      print "editor_tools_smileys_a[$bsi] = '" . addslashes($smiley["alt"]) . "';\n";
      $bsi ++;
    }

    // Smileys that can be used in the subject.
    if ($smiley["uses"] == 1 || $smiley["uses"] == 2)
    {
      print "editor_tools_subjectsmileys[$ssi] = '" . addslashes($smiley["search"]) . "';\n";
      print "editor_tools_subjectsmileys_r[$ssi] = '" . addslashes($prefix . $smiley["smiley"]) . "';\n";
      print "editor_tools_subjectsmileys_a[$ssi] = '" . addslashes($smiley["alt"]) . "';\n";
      $ssi ++;
    }
}
?>

// ----------------------------------------------------------------------
// Tool: smiley
// ----------------------------------------------------------------------

function editor_tools_handle_smiley()
{
    // Create the smiley picker on first access.
    if (!editor_tools_smiley_picker_obj)
    {
        // Create a new popup.
        var popup = editor_tools_construct_popup('editor-tools-smiley-picker','r');
        editor_tools_smiley_picker_obj = popup[0];
        var content_obj = popup[1];

        editor_tools_smiley_picker_obj.style.width = editor_tools_smileys_popupwidth;

        // Populate the new popup.
        for (var i = 0; i < editor_tools_smileys.length; i++)
        {
            var s = editor_tools_smileys[i];
            var r = editor_tools_smileys_r[i];
            var a = editor_tools_smileys_a[i];
            var a_obj = document.createElement('a');
            a_obj.href = 'javascript:editor_tools_handle_smiley_select("'+s+'")';
            var img_obj = document.createElement('img');
            img_obj.src = r;
            img_obj.title = a;
            img_obj.alt = a;
            a_obj.appendChild(img_obj);

            content_obj.appendChild(a_obj);
        }

        // Register the popup with the editor tools.
        editor_tools_register_popup_object(editor_tools_smiley_picker_obj);
    }

    // Display the popup.
    var button_obj = document.getElementById('editor-tools-img-smiley');
    editor_tools_toggle_popup(
        editor_tools_smiley_picker_obj,
        button_obj,
        editor_tools_smileys_popupwidth,
        editor_tools_smileys_popupoffset
    );
}

// Called by the smiley picker.
function editor_tools_handle_smiley_select(smiley)
{
    smiley = editor_tools_strip_whitespace(smiley);
    editor_tools_add_tags(smiley, '');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: subject smiley
// ----------------------------------------------------------------------

function editor_tools_handle_subjectsmiley()
{
    // Create the smiley picker on first access.
    if (!editor_tools_subjectsmiley_picker_obj)
    {
        // Create a new popup.
        var popup = editor_tools_construct_popup('editor-tools-subjectsmiley-picker','r');
        editor_tools_subjectsmiley_picker_obj = popup[0];
        var content_obj = popup[1];

        // Populate the new popup.
        for (var i = 0; i < editor_tools_subjectsmileys.length; i++)
        {
            var s = editor_tools_subjectsmileys[i];
            var r = editor_tools_subjectsmileys_r[i];
            var a = editor_tools_subjectsmileys_a[i];

            var a_obj = document.createElement('a');
            a_obj.href = 'javascript:editor_tools_handle_subjectsmiley_select("'+s+'")';
            var img_obj = document.createElement('img');
            img_obj.src = r;
            img_obj.alt = a;
            img_obj.title = a;
            a_obj.appendChild(img_obj);
            content_obj.appendChild(a_obj);
        }

        // Register the popup with the editor tools.
        editor_tools_register_popup_object(editor_tools_subjectsmiley_picker_obj);
    }

    // Display the popup.
    var button_obj = document.getElementById('editor-tools-img-subjectsmiley');
    editor_tools_toggle_popup(
        editor_tools_subjectsmiley_picker_obj,
        button_obj,
        editor_tools_subjectsmileys_popupwidth,
        editor_tools_subjectsmileys_popupoffset
    );
}

// Called by the subject smiley picker.
function editor_tools_handle_subjectsmiley_select(smiley)
{
    smiley = editor_tools_strip_whitespace(smiley);
    editor_tools_add_tags(smiley, '', editor_tools_subject_obj);
    editor_tools_focus_subjectfield();
}

