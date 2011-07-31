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
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// Javascript code for BBcode support in the Phorum editor_tools module.

// Some variables for storing objects that we need globally.
var editor_tools_size_picker_obj = null;
var editor_tools_list_picker_obj = null;

// Valid sizes to select from for the size picker. If you add or change sizes,
// remember to change the module language file to supply some display strings.
var editor_tools_size_picker_sizes = new Array(
    'x-large',
    'large',
    'medium',
    'small',
    'x-small'
);

// Valid list types to select from for the list picker. If you add or change
// types, remember to change the module language file to supply some
// display strings.
var editor_tools_list_picker_types = new Array(
    'b', // bullets
    '1', // numbers
    'a', // letters
    'A', // capital letters
    'i', // roman numbers
    'I'  // capital roman numbers
);

// Helper function: quote a bbcode argument if needed.
function quote_bbcode_argument(str)
{
    // Check if quoting is required.
    if (str.indexOf(' ') != -1 ||
        str.indexOf('"') != -1 ||
        str.indexOf(']') != -1)
    {
        var quoted = '';
        for (var i = 0; i < str.length; i++) {
            var c = str[i];
            if (c == '\\' || c == '"') {
                quoted += '\\';
            }
            quoted += c;
        }

        return '"' + quoted + '"';
    }
    else
    {
        return str;
    }
}

// ----------------------------------------------------------------------
// Tool: [hr] or [hline] (horizontal line)
// ----------------------------------------------------------------------

function editor_tools_handle_hr() {
    editor_tools_add_tags('\n[hr]\n', '');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [b]...[/b] (bold)
// ----------------------------------------------------------------------

function editor_tools_handle_b() {
    editor_tools_add_tags('[b]', '[/b]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [s]...[/s] (strike through)
// ----------------------------------------------------------------------

function editor_tools_handle_s() {
    editor_tools_add_tags('[s]', '[/s]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [u]...[/u] (underline)
// ----------------------------------------------------------------------

function editor_tools_handle_u() {
    editor_tools_add_tags('[u]', '[/u]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [i]...[/i] (italic)
// ----------------------------------------------------------------------

function editor_tools_handle_i() {
    editor_tools_add_tags('[i]', '[/i]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [center]...[/center] (center text)
// ----------------------------------------------------------------------

function editor_tools_handle_center() {
    editor_tools_add_tags('[center]', '[/center]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [sub]...[/sub] (subscript)
// ----------------------------------------------------------------------

function editor_tools_handle_sub() {
    editor_tools_add_tags('[sub]', '[/sub]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [sup]...[/sup] (superscript)
// ----------------------------------------------------------------------

function editor_tools_handle_sup() {
    editor_tools_add_tags('[sup]', '[/sup]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [small]...[/small] (small font)
// ----------------------------------------------------------------------

function editor_tools_handle_small() {
    editor_tools_add_tags('[small]', '[/small]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [large]...[/large] (large font)
// ----------------------------------------------------------------------

function editor_tools_handle_large() {
    editor_tools_add_tags('[large]', '[/large]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [code]...[/code] (formatted code)
// ----------------------------------------------------------------------

function editor_tools_handle_code() {
    editor_tools_add_tags('[code]\n', '\n[/code]\n');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [email]...[/email] (email address link)
// ----------------------------------------------------------------------

function editor_tools_handle_email()
{
    var email = prompt(editor_tools_translate("enter email"), '');
    if (email == null) return;
    email = editor_tools_strip_whitespace(email);

    var subject = prompt(editor_tools_translate("enter subject"), '');
    if (subject == null) return;
    subject = editor_tools_strip_whitespace(subject);
    if (subject != '') {
        subject = ' subject=' + quote_bbcode_argument(subject);
    }

    if (email == '') {
        editor_tools_add_tags('[email'+subject+']', '[/email]');
    } else {
        editor_tools_add_tags('[email'+subject+']'+email+'[/email]', '');
    }

    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [url=...]...[/url] (URL link)
// ----------------------------------------------------------------------

function editor_tools_handle_url()
{
    var url = 'http://';

    for (;;)
    {
        // Read input.
        url = prompt(editor_tools_translate("enter url"), url);
        // Cancel clicked? Empty string is also handled as cancel here,
        // because Safari returns an empty string for cancel. Without this
        // check, this loop would never end.
        if (url == '' || url == null) return;
        url = editor_tools_strip_whitespace(url);

        // Check the URL scheme (http, https, ftp and mailto are allowed).
        copy = url.toLowerCase();
        if (copy == 'http://' || (
            copy.substring(0,7) != 'http://' &&
            copy.substring(0,8) != 'https://' &&
            copy.substring(0,6) != 'ftp://' &&
            copy.substring(0,7) != 'mailto:')) {
            alert(editor_tools_translate("invalid url"));
            continue;
        }

        break;
    }

    editor_tools_add_tags('[url=' + url + ']', '[/url]', null, editor_tools_translate("enter url description"));
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [color=...]...[/color] (text color)
// ----------------------------------------------------------------------

function editor_tools_handle_color()
{
    editor_tools_store_range();

    // Display the color picker.
    var img_obj = document.getElementById('editor-tools-img-color');
    showColorPicker(img_obj);
    return;
}

// Called by the color picker library.
function editor_tools_handle_color_select(color)
{
    editor_tools_restore_range();

    editor_tools_add_tags('[color=' + color + ']', '[/color]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [size=...]...[/size] (text size)
// ----------------------------------------------------------------------

function editor_tools_handle_size()
{
    editor_tools_store_range();

    // Create the size picker on first access.
    if (!editor_tools_size_picker_obj)
    {
        // Create a new popup.
        var popup = editor_tools_construct_popup('editor-tools-size-picker','l');
        editor_tools_size_picker_obj = popup[0];
        var content_obj = popup[1];

        // Populate the new popup.
        for (var i = 0; i < editor_tools_size_picker_sizes.length; i++)
        {
            var size = editor_tools_size_picker_sizes[i];
            var a_obj = document.createElement('a');
            a_obj.href = 'javascript:editor_tools_handle_size_select("' + size + '")';
            a_obj.style.fontSize = size;
            a_obj.innerHTML = editor_tools_translate(size);
            content_obj.appendChild(a_obj);

            var br_obj = document.createElement('br');
            content_obj.appendChild(br_obj);
        }

        // Register the popup with the editor tools.
        editor_tools_register_popup_object(editor_tools_size_picker_obj);
    }

    // Display the popup.
    var button_obj = document.getElementById('editor-tools-img-size');
    editor_tools_toggle_popup(editor_tools_size_picker_obj, button_obj);
}

function editor_tools_handle_size_select(size)
{
    editor_tools_hide_all_popups();
    editor_tools_restore_range();
    size = editor_tools_strip_whitespace(size);
    editor_tools_add_tags('[size=' + size + ']', '[/size]');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [img]...[/img] (Image URL)
// ----------------------------------------------------------------------

function editor_tools_handle_img()
{
    var url = 'http://';

    for (;;)
    {
        // Read input.
        url = prompt(editor_tools_translate("enter image url"), url);
        // Cancel clicked? Empty string is also handled as cancel here,
        // because Safari returns an empty string for cancel. Without this
        // check, this loop would never end.
        if (url == '' || url == null) return;
        url = editor_tools_strip_whitespace(url);

        // Check the URL scheme (http, https, ftp and mailto are allowed).
        var copy = url.toLowerCase();
        if (copy == 'http://' || (
            copy.substring(0,7) != 'http://' &&
            copy.substring(0,8) != 'https://' &&
            copy.substring(0,6) != 'ftp://')) {
            alert(editor_tools_translate("invalid image url"));
            continue;
        }

        break;
    }

    editor_tools_add_tags('[img]' + url + '[/img]', '');
    editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [quote]...[/quote] (add a quote)
// ----------------------------------------------------------------------

function editor_tools_handle_quote()
{
    // Read input.
    var who = prompt(editor_tools_translate("enter who you quote"), '');
    if (who == null) return;
    who = editor_tools_strip_whitespace(who);

    if (who == '') {
        editor_tools_add_tags('[quote]', '[/quote]');
    }
    else
    {
        who = quote_bbcode_argument(who);
        editor_tools_add_tags('[quote=' + who + "]\n", "\n[/quote]");
    }

    editor_tools_focus_textarea();
}

//----------------------------------------------------------------------
//Tool: [left]...[/left] (add left aligned content)
//----------------------------------------------------------------------

function editor_tools_handle_left() {
    editor_tools_add_tags('[left]', '[/left]');
    editor_tools_focus_textarea();
}

//----------------------------------------------------------------------
//Tool: [right]...[/right] (add right aligned content)
//----------------------------------------------------------------------

function editor_tools_handle_right() {
  editor_tools_add_tags('[right]', '[/right]');
  editor_tools_focus_textarea();
}

// ----------------------------------------------------------------------
// Tool: [list] [*]item1 [*]item2 [/list]
// ----------------------------------------------------------------------

function editor_tools_handle_list()
{
    // Create the list picker on first access.
    if (!editor_tools_list_picker_obj)
    {
        // Create a new popup.
        var popup = editor_tools_construct_popup('editor-tools-list-picker', 'l');
        editor_tools_list_picker_obj = popup[0];
        var content_obj = popup[1];

        // Populate the new popup.
        var wrapper = document.createElement('div');
        wrapper.style.marginLeft = '1em';
        for (var i = 0; i < editor_tools_list_picker_types.length; i++)
        {
            var type = editor_tools_list_picker_types[i];

            var list;
            if (type == 'b') {
                list = document.createElement('ul');
            } else {
                list = document.createElement('ol');
                list.type = type;
            }
            list.style.padding = 0;
            list.style.margin = 0;
            var item = document.createElement('li');

            var a_obj = document.createElement('a');
            a_obj.href = 'javascript:editor_tools_handle_list_select("' + type + '")';
            a_obj.innerHTML = editor_tools_translate('list type ' + type);

            item.appendChild(a_obj);
            list.appendChild(item);
            wrapper.appendChild(list);
        }
        content_obj.appendChild(wrapper);

        // Register the popup with the editor tools.
        editor_tools_register_popup_object(editor_tools_list_picker_obj);
    }

    // Display the popup.
    var button_obj = document.getElementById('editor-tools-img-list');
    editor_tools_toggle_popup(editor_tools_list_picker_obj, button_obj);
}

function editor_tools_handle_list_select(type)
{
    editor_tools_hide_all_popups();

    var items = new Array();
    var idx = 0;

    // Read items.
    for (;;)
    {
        var item = prompt(editor_tools_translate('enter new list item'), '');
        if (item == null) return;
        item = editor_tools_strip_whitespace(item);
        if (item == '') break;
        items[idx++] = item;
    }

    if (items.length == 0) {
        items = new Array(
            '...',
            '...'
        );
    }

    var itemlist = '';
    for (var i = 0; i < items.length; i++) {
        itemlist += '[*] ' + items[i] + "\n";
    }

    if (type == 'b') {
        type = '';
    } else {
        type = '='+type;
    }

    editor_tools_add_tags("[list"+type+"]\n"+itemlist+"[/list]\n", '');
}
