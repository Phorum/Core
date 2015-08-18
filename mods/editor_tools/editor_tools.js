////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Javascript code for the Phorum editor_tools module.

// Storage for language translation strings from the Phorum language system.
var editor_tools_lang = new Array();

// Objects that we need globally.
var editor_tools_help_picker_obj = null;
var editor;
var body_element;
var subject_element;

// A variable for storing all popup objects that we have, so we
// can hide them all at once.
var editor_tools_popup_objects = new Array();

// Storage for the tools that have to be added to the editor tools panel.
// The array value contains the following fields:
//
// 1) the id for the tool (must be unique)
// 2) a description to use as the tooltip title for the button
// 3) the icon image to display as a button.
// 4) the javascript action to run when the user clicks the button
// 5) optional: the width of the icon image
// 6) optional: the height of the icon image (presumed 20px by default)
//
// This array will be filled from PHP-generated javascript.
var editor_tools = new Array();

// Storage for help chapters that must be put under the editor tools
// help button. The array value contains the following fields:
//
// 1) a description that will be used as the clickable link text.
// 2) the url for the help page (absolute or relative to the Phorum dir).
//
// This array will be filled from PHP-generated javascript.
var editor_tools_help_chapters = new Array();

// The dimensions of the help window.
var editor_tools_help_width = '400px';
var editor_tools_help_height = '400px';

// The default height for our icons.
// This one is filled from PHP-generated javascript.
var editor_tools_default_iconheight;

// A simple browser check. We need to know the browser version, because
// the color picker won't work on at least MacOS MSIE 5.
var OLD_MSIE =
    navigator.userAgent.indexOf('MSIE')>=0 &&
    navigator.appVersion.replace(/.*MSIE (\d\.\d).*/g,'$1')/1 < 6;

// ----------------------------------------------------------------------
// Uitilty functions
// ----------------------------------------------------------------------

// Return a translated string, based on the Phorum language system.
function editor_tools_translate(str)
{
    if (editor_tools_lang[str]) {
        return editor_tools_lang[str];
    } else {
        return str;
    }
}

// Close all popup windows and move the focus to the textarea.
function editor_tools_focus_textarea()
{
    editor_tools_hide_all_popups();
    body_element.focus();
}

// Close all popup windows and move the focus to the subject field.
function editor_tools_focus_subjectfield()
{
    if (subject_element.found()) {
        editor_tools_hide_all_popups();
        subject_element.focus();
    }
}

// ----------------------------------------------------------------------
// Construction of the editor tools
// ----------------------------------------------------------------------

// Add the editor tools panel to the page.
function editor_tools_construct()
{
    $PJ(document).ready(function () {

        // No editor tools selected to display? Then we're done.
        if (editor_tools.length == 0) return;

        // Retrieve the body and subject element.
        var editor = new Phorum.UI.Editor();
        body_element    = editor.body;
        subject_element = editor.subject;

        // Insert a <div> for containing the buttons, just before the textarea,
        // unless there is already an object with id "editor-tools". In that
        // case, the existing object is used instead.
        var $div_obj = $PJ('#editor-tools');
        if (!$div_obj.length) {
            $div_obj = $PJ('<div id="editor-tools"/>');
            $div_obj.insertBefore(body_element.$object);
        }

        // Add the buttons to the new <div> for the editor tools.
        for (var i = 0; i < editor_tools.length; i++)
        {
            var toolinfo    = editor_tools[i];
            var tool        = toolinfo[0];
            var description = toolinfo[1];
            var icon        = toolinfo[2];
            var jsaction    = toolinfo[3];
            var iwidth      = toolinfo[4];
            var iheight     = toolinfo[5];
            var target      = toolinfo[6];

            // Do not use the color picker on MSIE 5. I tested this on a
            // Macintosh OS9 system and the color picker about hung MSIE.
            if (tool == 'color' && OLD_MSIE) continue;

            var a_obj = document.createElement('a');
            a_obj.id              = 'editor-tools-a-' + tool;
            a_obj.href            = 'javascript:' + jsaction;

            var img_obj = document.createElement('img');
            img_obj.id            = 'editor-tools-img-' + tool;
            img_obj.className     = 'editor-tools-button';
            img_obj.src           = icon;
            img_obj.width         = iwidth;
            img_obj.height        = iheight;
            img_obj.style.padding = '2px';
            img_obj.alt           = description;
            img_obj.title         = description;

            // Skip over the editor tool buttons in the first tabbing run.
            // This makes it a lot quicker for the user to jump from the
            // subject field to the message body textarea when writing
            // a message.
            $PJ(a_obj).attr('tabindex', 1);
            $PJ(img_obj).attr('tabindex', 1);

            // If an icon is added that is less high than our default icon
            // height, we try to make the button the same height as the
            // others by adding some dynamic padding to it.
            if (iheight < editor_tools_default_iconheight) {
                var fill = editor_tools_default_iconheight - iheight;
                var addbottom = Math.round(fill / 2);
                var addtop = fill - addbottom;
                img_obj.style.paddingTop = (addtop + 2) + 'px';
                img_obj.style.paddingBottom = (addbottom + 2) + 'px';
            }
            $PJ(a_obj).append(img_obj);

            // Add the button to the page.
            // target = subject is a feature that was added for supporting
            // the subjectsmiley tool. This one is added to the subject field
            // instead of the textarea.
            if (target === 'subject') {
                if (!subject_element.$.is('[type=hidden]')) {
                  img_obj.style.verticalAlign = 'top';
                  $PJ(a_obj).insertAfter(subject_element.$object);
                }
            } else {
                $div_obj.append(a_obj);
            }
        }

        // Hide any open popup when the user clicks the textarea
        // or subject field.
        body_element.$object.click(editor_tools_hide_all_popups);
        subject_element.$object.click(editor_tools_hide_all_popups);
    });
}

// ----------------------------------------------------------------------
// Popup window utilities
// ----------------------------------------------------------------------

// Create a popup window.
function editor_tools_construct_popup(create_id, anchor)
{
    // Create the outer div for the popup window.
    var popup_obj = document.createElement('div');
    popup_obj.id = create_id;
    popup_obj.className = 'editor-tools-popup';
    popup_obj.style.display = 'none';
    document.body.appendChild(popup_obj);

    popup_obj._anchor = anchor;

    // Create the inner content div.
    var content_obj = document.createElement('div');
    content_obj.id = create_id + '-content';
    popup_obj.appendChild(content_obj);

    return new Array(popup_obj, content_obj);
}

// Toggle a popup window.
function editor_tools_toggle_popup(popup_obj, button_obj, width, leftoffset)
{
    // Determine where to show the popup on screen.
    var $button_obj = $PJ(button_obj);
    var pos  = $button_obj.offset();
    var top  = pos.top + 2 + $button_obj.outerHeight();
    var left = pos.left;

    if (leftoffset) left -= leftoffset;
    if (width) popup_obj.style.width = width;

    // Move the popup window to the right place.
    if (popup_obj._anchor == 'r')
    {
        // Determine the screen width.
        var scrwidth = null;
        if (document.documentElement.clientWidth) {
            // Firefox screen width.
            scrwidth = document.documentElement.clientWidth;
        } else {
            scrwidth = document.body.clientWidth;
            // -16 for scrollbar that is counted in in some browsers.
            if (document.getElementById && !document.all) {
                scrwidth -= 16;
            }
        }

        var right = scrwidth - left - button_obj.offsetWidth;

        popup_obj.style.right = right + 'px';
        popup_obj.style.top = top + 'px';
    } else {
        popup_obj.style.left = left + 'px';
        popup_obj.style.top = top + 'px';
    }

    // Toggle the popup window's visibility.
    if (popup_obj.style.display == 'none') {
        editor_tools_hide_all_popups();
        popup_obj.style.display = 'block';
    } else {
        popup_obj.style.display = 'none';
        editor_tools_focus_textarea();
    }
}

// Register an object as a popup, so editor_tools_hide_all_popups()
// can hide it.
function editor_tools_register_popup_object(object)
{
    if (! object) return;
    editor_tools_popup_objects[editor_tools_popup_objects.length] = object;
}

// Hide all objects that were registered as a popup.
function editor_tools_hide_all_popups()
{
    for (var i = 0; i < editor_tools_popup_objects.length; i++) {
        var object = editor_tools_popup_objects[i];
        object.style.display = 'none';
    }
}

// ----------------------------------------------------------------------
// Tool: Help
// ----------------------------------------------------------------------

function editor_tools_handle_help()
{
    var c = editor_tools_help_chapters;

    // Shouldn't happen.
    if (c.length == 0) {
        alert('No help chapters available');
        return;
    }

    // Exactly one help chapter available. Immediately open the chapter.
    if (c.length == 1) {
        editor_tools_handle_help_select(c[0][1]);
        return;
    }

    // Multiple chapters available. Show a help picker menu with some
    // choices. Create the help picker on first access.
    if (!editor_tools_help_picker_obj)
    {
        // Create a new popup.
        var popup = editor_tools_construct_popup('editor-tools-help-picker','r');
        editor_tools_help_picker_obj = popup[0];
        var content_obj = popup[1];

        // Populate the new popup.
        for (var i = 0; i < editor_tools_help_chapters.length; i++)
        {
            var helpinfo = editor_tools_help_chapters[i];
            var a_obj = document.createElement('a');
            a_obj.href = 'javascript:editor_tools_handle_help_select("' + helpinfo[1] + '")';
            a_obj.innerHTML = helpinfo[0];
            content_obj.appendChild(a_obj);
            content_obj.appendChild(document.createElement('br'));
        }

        // Register the popup with the editor tools.
        editor_tools_register_popup_object(editor_tools_help_picker_obj);
    }

    // Display the popup.
    var button_obj = document.getElementById('editor-tools-img-help');
    editor_tools_toggle_popup(editor_tools_help_picker_obj, button_obj);
}

function editor_tools_handle_help_select(url)
{
    var help_window = window.open(
        url,
        'editor_tools_help',
        'resizable=yes,' +
        'menubar=no,' +
        'directories=no,' +
        'scrollbars=yes,' +
        'toolbar=no,' +
        'status=no,' +
        'width=' + editor_tools_help_width + ',' +
        'height=' + editor_tools_help_height
    );

    editor_tools_focus_textarea();
    help_window.focus();
}

// ----------------------------------------------------------------------
// Backward compatibility functions
// ----------------------------------------------------------------------

function editor_tools_add_tags(pre, post, target, prompt_str) {
    var field = target === 'subject' ? subject_element : body_element;
    field.addTags(pre, post, prompt_str);
}

function editor_tools_store_range() {
    body_element.storeSelection();
}

function editor_tools_restore_range() {
    body_element.restoreSelection();
}

function editor_tools_strip_whitespace(str, return_stripped) {
    return Phorum.trim(str, return_stripped);
}

