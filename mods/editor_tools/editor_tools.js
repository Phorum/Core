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

// Javascript code for the Phorum editor_tools module.

// Valid object ids for textarea objects to handle. The first object
// that can be matched will be use as the object to work with.
// This is done to arrange for backward compatibility between
// Phorum versions.
var editor_tools_textarea_ids = new Array(
    'phorum_textarea',  // Phorum 5.1
    'body',             // Phorum 5.2
    'message'           // PM interface
);

// Valid object ids for subject text field objects to handle.
var editor_tools_subject_ids = new Array(
    'phorum_subject',   // Phorum 5.1
    'subject'           // Phorum 5.2
);

// Storage for language translation strings from the Phorum language system.
var editor_tools_lang = new Array();

// Some variables for storing objects that we need globally.
var editor_tools_textarea_obj = null;
var editor_tools_subject_obj = null;
var editor_tools_help_picker_obj = null;

// A variable for storing the current selection range of the 
// textarea. Needed for working around an MSIE problem.
var editor_tools_textarea_range = null;

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

// Find the Phorum textarea object and return it. In case of
// problems, null will be returned.
function editor_tools_get_textarea()
{
    if (editor_tools_textarea_obj != null) {
        return editor_tools_textarea_obj;
    }

    for (var i=0; editor_tools_textarea_ids[i]; i++) {
        editor_tools_textarea_obj =
            document.getElementById(editor_tools_textarea_ids[i]);
        if (editor_tools_textarea_obj) break;
    }

    if (! editor_tools_textarea_obj) {
        alert('editor_tools.js library reports: ' +
              'no textarea found on the current page.');
        return null;
    }

    return editor_tools_textarea_obj;
}

// Find the Phorum subject field object and return it. In case of
// problems, null will be returned.
function editor_tools_get_subjectfield()
{
    if (editor_tools_subject_obj != null) {
        return editor_tools_subject_obj;
    }

    for (var i=0; editor_tools_subject_ids[i]; i++) {
        editor_tools_subject_obj =
            document.getElementById(editor_tools_subject_ids[i]);
        if (editor_tools_subject_obj) break;
    }

    if (! editor_tools_subject_obj) {
        return null;
    }

    return editor_tools_subject_obj;
}

// Return a translated string, based on the Phorum language system.
function editor_tools_translate(str)
{
    if (editor_tools_lang[str]) {
        return editor_tools_lang[str];
    } else {
        return str;
    }
}

// Strip whitespace from the start and end of a string.
function editor_tools_strip_whitespace(str, return_stripped)
{
    var strip_pre = '';
    var strip_post = '';

    // Strip whitespace from end of string.
    for (;;) {
        var lastchar = str.substring(str.length-1, str.length);
        if (lastchar == ' '  || lastchar == '\r' ||
            lastchar == '\n' || lastchar == '\t') {
            strip_post = lastchar + strip_post;

            str = str.substring(0, str.length-1);
        } else {
            break;
        }
    }

    // Strip whitespace from start of string.
    for (;;) {
        var firstchar = str.substring(0,1);
        if (firstchar == ' '  || firstchar == '\r' ||
            firstchar == '\n' || firstchar == '\t') {
            strip_pre += firstchar;
            str = str.substring(1);
        } else {
            break;
        }
    }

    if (return_stripped) {
        return new Array(str, strip_pre, strip_post);
    } else {
        return str;
    }
} 

// Close all popup windows and move the focus to the textarea.
function editor_tools_focus_textarea()
{
    var textarea_obj = editor_tools_get_textarea();
    if (textarea_obj == null) return;
    editor_tools_hide_all_popups();
    textarea_obj.focus();
}

// Close all popup windows and move the focus to the subject field.
function editor_tools_focus_subjectfield()
{
    var subjectfield_obj = editor_tools_get_subjectfield();
    if (subjectfield_obj == null) return;
    editor_tools_hide_all_popups();
    subjectfield_obj.focus();
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

    // If the browser does not support document.getElementById,
    // then the javascript code won't run. Do not display the
    // editor tools at all in that case.
    if (! document.getElementById) return;

    // No editor tools selected to display? Then we're done.
    if (editor_tools.length == 0) return;

    // Find the textarea and subject field object.
    textarea_obj = editor_tools_get_textarea();
    if (textarea_obj == null) return; // we consider this fatal.
    var subjectfield_obj = editor_tools_get_subjectfield();

    // Insert a <div> for containing the buttons, just before the textarea,
    // unless there is already an object with id "editor-tools". In that
    // case, the existing object is used instead.
    div_obj = document.getElementById('editor-tools');
    if (! div_obj) {
        parent_obj = textarea_obj.parentNode;
        div_obj = document.createElement('div');
        div_obj.id = 'editor-tools';
        parent_obj.insertBefore(div_obj, textarea_obj);
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

        a_obj = document.createElement('a');
        a_obj.id = 'editor-tools-a-' + tool;
        a_obj.href = 'javascript:' + jsaction;

        img_obj = document.createElement('img');
        img_obj.id = 'editor-tools-img-' + tool;
        img_obj.className = 'editor-tools-button';
        img_obj.src = icon;
        img_obj.width = iwidth;
        img_obj.height = iheight;
        img_obj.style.padding = '2px';
        img_obj.alt = description;
        img_obj.title = description;

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
        a_obj.appendChild(img_obj);

        // Add the button to the page.
        // target = subject is a feature that was added for supporting
        // the subjectsmiley tool. This one is added to the subject field
        // instead of the textarea. 
        if (target == 'subject') {
            // Find the subject text field. If we can't find one,
            // then simply ignore this tool.
            if (subjectfield_obj) {
                img_obj.style.verticalAlign = 'top';
                var parent = subjectfield_obj.parentNode;
                var sibling = subjectfield_obj.nextSibling;
                parent.insertBefore(a_obj, sibling);
            }
        } else {
            div_obj.appendChild(a_obj);
        }
    }

    // Hide any open popup when the user clicks the textarea or subject field.
    textarea_obj.onclick = function() {
        editor_tools_hide_all_popups();
    };
    if (subjectfield_obj) {
        subjectfield_obj.onclick = function() {
            editor_tools_hide_all_popups();
        }
    }
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

// Save the selection range of the textarea. This is needed because
// sometimes clicking in a popup can clear the selection in MSIE.
function editor_tools_store_range()
{
    var ta = editor_tools_get_textarea();
    if (ta == null || ta.setSelectionRange || ! document.selection) return;
    ta.focus();
    editor_tools_textarea_range = document.selection.createRange();
}

// Restored a saved textarea selection range.
function editor_tools_restore_range()
{
    if (editor_tools_textarea_range != null)
    {
        editor_tools_textarea_range.select();
        editor_tools_textarea_range = null;
    }
}

// ----------------------------------------------------------------------
// Textarea manipulation
// ----------------------------------------------------------------------

// Add tags to the textarea. If some text is selected, then place the
// tags around the selected text. If no text is selected and a prompt_str
// is provided, then prompt the user for the data to place inside
// the tags.
function editor_tools_add_tags(pre, post, target, prompt_str)
{
    var text;
    var pretext;
    var posttext;
    var range;
    var ta = target ? target : editor_tools_get_textarea();
    if (ta == null) return;

    // Store the current scroll offset, so we can restore it after
    // adding the tags to its contents.
    var offset = ta.scrollTop;

    if (ta.setSelectionRange)
    {
        // Get the currently selected text.
        pretext = ta.value.substring(0, ta.selectionStart);
        text = ta.value.substring(ta.selectionStart, ta.selectionEnd);
        posttext = ta.value.substring(ta.selectionEnd, ta.value.length);

        // Prompt for input if no text was selected and a prompt is set.
        if (text == '' && prompt_str) {
            text = prompt(prompt_str, '');
            if (text == null) return;
        }

        // Strip whitespace from text selection and move it to the
        // pre- and post.
        var res = editor_tools_strip_whitespace(text, true);
        text = res[0];
        pre = res[1] + pre;
        post = post + res[2];

        ta.value = pretext + pre + text + post + posttext;

        // Reselect the selected text.
        var cursorpos1 = pretext.length + pre.length;
        var cursorpos2 = cursorpos1 + text.length;
        ta.setSelectionRange(cursorpos1, cursorpos2);
        ta.focus();
    }
    else if (document.selection) /* MSIE support */
    {
        // Get the currently selected text.
        ta.focus();
        range = document.selection.createRange();

        // Fumbling to work around newline selections at the end of
        // the text selection. MSIE does not include them in the
        // range.text, but it does replace them when setting range.text
        // to a new value :-/
        var virtlen = range.text.length;
        if (virtlen > 0) {
            while (range.text.length == virtlen) {
                range.moveEnd('character', -1);
            }
            range.moveEnd('character', +1);
        }

        // Prompt for input if no text was selected and a prompt is set.
        text = range.text;
        if (text == '' && prompt_str) {
            text = prompt(prompt_str, '');
            if (text == null) return;
        }

        // Strip whitespace from text selection and move it to the
        // pre- and post.
        var res = editor_tools_strip_whitespace(text, true);
        text = res[0];
        pre = res[1] + pre;
        post = post + res[2];

        // Add pre and post to the text.
        range.text = pre + text + post;

        // Reselect the selected text. Another MSIE anomaly has to be
        // taken care of here. MSIE will include carriage returns
        // in the text.length, but it does not take them into account
        // when using selection range moving methods :-/
        // By setting the range.text before, the cursor is now after
        // the replaced code, so we will move the start and the end
        // back in the text.
        var mvstart = post.length + text.length -
                      ((text + post).split('\r').length - 1);
        var mvend   = post.length +
                      (post.split('\r').length - 1);
        range.moveStart('character', -mvstart);
        range.moveEnd('character', -mvend);
        range.select();
    }
    else /* Support for really limited browsers, e.g. MSIE5 on MacOS */
    {
        ta.value = ta.value + pre + post;
    }

    ta.scrollTop = offset;
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

