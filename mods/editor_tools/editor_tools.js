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

// Javascript code for the Phorum editor_tools module.

// Valid object ids for textarea objects to handle. The first object
// that can be matched will be use as the object to work with.
// This is done to arrange for backward compatibility between 
// Phorum versions.
var editor_tools_textarea_ids = new Array(
    'phorum_textarea',
    'body'
);

// Some paths for the module.
var editor_tools_modpath = "./mods/editor_tools";
var editor_tools_iconpath = editor_tools_modpath + "/icons";

// Some variables for storing settings. These need to be filled
// before editor_tools_construct() is called.
var editor_tools_enabled = new Array();
var editor_tools_lang = new Array();

// A variable for storing the textarea object that we're working with.
var editor_tools_textarea_obj = null;

// A list of tools that can be added to the editor_tools panel.
// The array value contains two fields:
// 1) the key name for the language string to use for the button description.
// 2) the image to display as a button.
var editor_tools = new Array();
editor_tools['bold']        = new Array('bold',        'bold.gif');
editor_tools['italic']      = new Array('italic',      'italic.gif');
editor_tools['underline']   = new Array('underline',   'underline.gif');
editor_tools['strike']      = new Array('strike',      'strike.gif');
editor_tools['size']        = new Array('size',        'size.gif');
editor_tools['subscript']   = new Array('subscript',   'subscript.gif');
editor_tools['superscript'] = new Array('superscript', 'superscript.gif');
editor_tools['center']      = new Array('center',      'center.gif');
editor_tools['color']       = new Array('color',       'color.gif');
editor_tools['url']         = new Array('url',         'url.gif');
editor_tools['email']       = new Array('email',       'email.gif');
editor_tools['image']       = new Array('image',       'image.gif');
editor_tools['smiley']      = new Array('smiley',      'smiley.gif');
editor_tools['hr']          = new Array('hr',          'hr.gif');
editor_tools['code']        = new Array('code',        'code.gif');

// ----------------------------------------------------------------------
// Uitilty functions
// ----------------------------------------------------------------------

// Find the Phorum textarea object and return it. In case of
// problems, null will be returned.
function editor_tools_get_textarea()
{
    var i;

    if (editor_tools_textarea_obj != null) {
        return editor_tools_textarea_obj;
    }

    for (i=0; editor_tools_textarea_ids[i]; i++) {
        editor_tools_textarea_obj = 
            document.getElementById(editor_tools_textarea_ids[i]);
        if (editor_tools_textarea_obj) break;
    }

    if (! editor_tools_textarea_obj) {
        alert("editor_tools.js library reports: " +
              "no textarea found on the current page.");
        return null;
    }

    return editor_tools_textarea_obj;
}

function editor_tools_translate(str)
{
    if (editor_tools_lang[str]) {
        return editor_tools_lang[str];
    } else {
        return str;
    }
}

function editor_tools_strip_whitespace(str)
{
    // Strip whitespace from start of string.
    for (;;) {
        firstchar = str.substring(0,1);
        if (firstchar == ' ') {
            str = str.substring(1);
        } else {
            break;
        }
    }

    // Strip whitespace from end of string.
    for (;;) {
        lastchar = str.substring(str.length-1, str.length);
        if (lastchar == ' ') {
            str = str.substring(0, str.length-1);
        } else {
            break;
        }
    }

    return str;
}

function editor_tools_focus_textarea()
{
    var textarea_obj = editor_tools_get_textarea();
    if (textarea_obj == null) return;
    textarea_obj.focus();
}

// ----------------------------------------------------------------------
// Construction of the editor tools
// ----------------------------------------------------------------------

// Add the editor tools panel to the page.
function editor_tools_construct()
{
    var textarea_obj;
    var div_obj;
    var parent_obj;
    var a_obj;
    var img_obj;
    var i;
    var tool;
    var description;

    // If the browser does not support document.getElementById,
    // then the javascript code won't run. Do not display the
    // editor tools at all in that case.
    if (! document.getElementById) return;

    // No editor tools selected to display? Then we're done.
    if (editor_tools_enabled.length == 0) return;

    // Find the textarea object.
    textarea_obj = editor_tools_get_textarea();
    if (textarea_obj == null) return;

    // Insert a <div> for containing the buttons, just before the textarea.
    parent_obj = textarea_obj.parentNode;
    div_obj = document.createElement('div');
    div_obj.id = 'editor_tools';
    parent_obj.insertBefore(div_obj, textarea_obj);

    // Add the buttons to the new <div> for the enabled editor tools.
    for (i = 0; i < editor_tools_enabled.length; i++)
    {
        tool = editor_tools_enabled[i];
        if (! editor_tools[tool]) {
            alert("editor_tools.js library reports: " +
                  "illegal editor tool id in editor_tools_enabled[]: " + 
                  tool);
            continue;
        }

        description = editor_tools_translate(tool);
        a_obj = document.createElement('a');
        a_obj.id = "editor_tools_a_" + tool;
        a_obj.href = 
            "javascript:" +
            "editor_tools_handle_" + tool + "(); " +
            "editor_tools_focus_textarea()"; 
        a_obj.alt = description;
        a_obj.title = description;
        img_obj = document.createElement('img');
        img_obj.id = "editor_tools_img_" + tool;
        img_obj.src = editor_tools_iconpath + "/" + editor_tools[tool][1];
        a_obj.appendChild(img_obj);
        div_obj.appendChild(a_obj);
    }
}

// ----------------------------------------------------------------------
// Textarea manipulation
// ----------------------------------------------------------------------

// Add tags to the textarea. If some text is selected, then place the
// tags around the selected text.
function editor_tools_add_tags(pre, post)
{
    var text;
    var pretext;
    var posttext;
    var range;
    var ta = editor_tools_get_textarea();
    if (ta == null) return;

    if(ta.setSelectionRange)
    {
        // Add pre and post to the text.
        pretext = ta.value.substring(0, ta.selectionStart);
        text = ta.value.substring(ta.selectionStart, ta.selectionEnd);
        posttext = ta.value.substring(ta.selectionEnd, ta.value.length);
        ta.value = pretext + pre + text + post + posttext;

        // Set the cursor to a logical position.
        cursorpos = pretext.length + pre.length;
        if (text.length != 0) cursorpos += text.length + post.length;
        ta.setSelectionRange(cursorpos, cursorpos);
        ta.focus();
    }
    else /* MSIE support */
    {
        // Add pre and post to the text.
        ta.focus();
        range = document.selection.createRange();
        text = range.text;
        if (text.length <= 0) {
            // Add pre and post to the text.
            range.text = pre + post;

            // Set the cursor to a logical position.
            range.moveStart("character", -(post.length));
            range.moveEnd("character", -(post.length));
            range.select();
        } else {
            // Add pre and post to the text.
            range.text = pre + text + post;

            // Set the cursor to a logical position.
            range.select();
        }
    }
}

// ----------------------------------------------------------------------
// Tool handlers
// ----------------------------------------------------------------------

function editor_tools_handle_hr() {
    editor_tools_add_tags('\n[hr]\n', ''); 
}

function editor_tools_handle_bold() {
    editor_tools_add_tags('[b]', '[/b]'); 
}

function editor_tools_handle_strike() {
    editor_tools_add_tags('[s]', '[/s]'); 
}

function editor_tools_handle_underline() {
    editor_tools_add_tags('[u]', '[/u]'); 
}

function editor_tools_handle_italic() {
    editor_tools_add_tags('[i]', '[/i]'); 
}

function editor_tools_handle_center() {
    editor_tools_add_tags('[center]', '[/center]'); 
}

function editor_tools_handle_subscript() {
    editor_tools_add_tags('[sub]', '[/sub]'); 
}

function editor_tools_handle_superscript() {
    editor_tools_add_tags('[sup]', '[/sup]'); 
}

function editor_tools_handle_code() {
    editor_tools_add_tags('[code]', '[/code]'); 
}

function editor_tools_handle_email() {
    editor_tools_add_tags('[email]', '[/email]'); 
}

function editor_tools_handle_url()
{
    var url = 'http://';

    for (;;)
    {
        // Read input.
        url = prompt(editor_tools_translate("enter url"), url);
        if (url == null) return; // Cancel clicked.
        url = editor_tools_strip_whitespace(url);
        
        // Check the URL scheme (http, https, ftp and mailto are allowed).
        copy = url.toLowerCase();
        if (copy.substring(0,7) != 'http://' &&
            copy.substring(0,8) != 'https://' &&
            copy.substring(0,6) != 'ftp://' &&
            copy.substring(0,7) != 'mailto:') {
            alert(editor_tools_translate("invalid url"));
            continue;
        }

        break;
    }

    editor_tools_add_tags('[url=' + url + ']', '[/url]'); 
}

function editor_tools_handle_color()
{
    // Display the color picker.
    var img_obj = document.getElementById('editor_tools_img_color');
    showColorPicker(img_obj);
    return;

}
// Called by the color picker library.
function editor_tools_handle_color_select(color)
{
    editor_tools_add_tags('[color=' + color + ']', '[/color]'); 
}

// TODO create a cool size selection tool.
function editor_tools_handle_size()
{
    // Read input.
    var size = prompt(editor_tools_translate("enter size"), '');
    if (size == null) return; // Cancel clicked.
    size = editor_tools_strip_whitespace(size);
    if (size == '') return; // No size entered.

    editor_tools_add_tags('[size=' + size + ']', '[/size]'); 
}

function editor_tools_handle_image()
{
    var url = 'http://';

    for (;;)
    {
        // Read input.
        url = prompt(editor_tools_translate("enter image url"), url);
        if (url == null) return; // Cancel clicked.
        url = editor_tools_strip_whitespace(url);
        
        // Check the URL scheme (http, https, ftp and mailto are allowed).
        copy = url.toLowerCase();
        if (copy.substring(0,7) != 'http://' &&
            copy.substring(0,8) != 'https://' &&
            copy.substring(0,6) != 'ftp://') {
            alert(editor_tools_translate("invalid image url"));
            continue;
        }

        break;
    }

    editor_tools_add_tags('[img]' + url + '[/img]', ''); 
}

function editor_tools_handle_smiley()
{
    alert("Not yet implemented: smiley");
}

