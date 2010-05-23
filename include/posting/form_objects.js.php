// Valid object ids for textarea objects to handle. The first object
// that can be matched will be use as the object to work with.
// This is done to arrange for backward compatibility between
// Phorum versions.
Phorum.textarea_ids = new Array(
    'phorum_textarea',  // Phorum 5.1
    'body',             // Phorum 5.2
    'message'           // PM interface
);

// Valid object ids for subject text field objects to handle.
Phorum.subject_ids = new Array(
    'phorum_subject',   // Phorum 5.1
    'subject'           // Phorum 5.2
);

// Some variables for storing objects that we need globally.
Phorum.textarea_obj = null;
Phorum.subject_obj = null;

// A variable for storing the current selection range of the 
// textarea. Needed for working around an MSIE problem.
Phorum.textarea_range = null;

// ----------------------------------------------------------------------
// Uitilty functions
// ----------------------------------------------------------------------

// Find the Phorum textarea object and return it. In case of
// problems, null will be returned.
Phorum.get_textarea = function()
{
    if (Phorum.textarea_obj != null) {
        return Phorum.textarea_obj;
    }

    for (var i=0; Phorum.textarea_ids[i]; i++) {
        Phorum.textarea_obj =
            document.getElementById(Phorum.textarea_ids[i]);
        if (Phorum.textarea_obj) break;
    }

    if (! Phorum.textarea_obj) {
        alert('No textarea found on the current page.');
        return null;
    }

    return Phorum.textarea_obj;
}

// Find the Phorum subject field object and return it. In case of
// problems, null will be returned.
Phorum.get_subjectfield = function()
{
    if (Phorum.subject_obj != null) {
        return Phorum.subject_obj;
    }

    for (var i=0; Phorum.subject_ids[i]; i++) {
        Phorum.subject_obj =
            document.getElementById(Phorum.subject_ids[i]);
        if (Phorum.subject_obj) break;
    }

    if (! Phorum.subject_obj) {
        return null;
    }

    return Phorum.subject_obj;
}

// Strip whitespace from the start and end of a string.
Phorum.strip_whitespace = function(str, return_stripped)
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

// Save the selection range of the textarea. This is needed because
// sometimes clicking in a popup can clear the selection in MSIE.
Phorum.store_range = function()
{
    var ta = Phorum.get_textarea();
    if (ta == null || ta.setSelectionRange || ! document.selection) return;
    ta.focus();
    Phorum.textarea_range = document.selection.createRange();
}

// Restored a saved textarea selection range.
Phorum.restore_range = function()
{
    if (Phorum.textarea_range != null)
    {
        Phorum.textarea_range.select();
        Phorum.textarea_range = null;
    }
}

// Move the focus to the textarea.
Phorum.focus_textarea = function()
{
    var textarea_obj = Phorum.get_textarea();
    if (textarea_obj == null) return;
    textarea_obj.focus();
}

// Move the focus to the subject field.
Phorum.focus_subjectfield = function()
{
    var subjectfield_obj = Phorum.get_subjectfield();
    if (subjectfield_obj == null) return;

    subjectfield_obj.focus();
}

// ----------------------------------------------------------------------
// Textarea manipulation
// ----------------------------------------------------------------------

// Add tags to the textarea. If some text is selected, then place the
// tags around the selected text. If no text is selected and a prompt_str
// is provided, then prompt the user for the data to place inside
// the tags.
Phorum.add_tags = function(pre, post, target, prompt_str)
{
    var text;
    var pretext;
    var posttext;
    var range;
    var ta = target ? target : Phorum.get_textarea();
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
        var res = Phorum.strip_whitespace(text, true);
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
        var res = Phorum.strip_whitespace(text, true);
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
