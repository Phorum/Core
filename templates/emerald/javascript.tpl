// ---------------------------------------------------------------------
// Javascript code for Emerald template (autoloaded by javascript.php)
// ---------------------------------------------------------------------

// Add a newflag clearing callback function for the Emerald template.
//
// This callback is called by the code from Phorum.UI.markread(),
// after a successful Ajax "mark read" call. The callback's task is
// to modify the user interface to show the user that previously new
// messages are no longer new.
//
Phorum.UI.markread.callbacks.push(function (mode, item_id)
{
    // Clear newflags that are inside <span> elements and which
    // we will simply hide when clearing the newflag.
    Phorum.UI.markread.matchElements(
        'span', 'new-flag-', mode, item_id,
        function(elt) { elt.style.display = 'none'; }
    );

    // Clear subjects that are flagged as new.
    // Subjects are changed from bold to normal text.
    Phorum.UI.markread.matchElements(
        'a', 'new-flag-subject-', mode, item_id,
        function(elt) { elt.style.fontWeight = 'normal'; }
    );

    // Clear newflag icons.
    // Normal messages get comment.png instead of the newflag icon.
    // Sticky messages get the bell.png instead of the newflag icon.
    Phorum.UI.markread.matchElements(
        'img', 'new-flag-icon-', mode, item_id,
        function(elt) {
            // Sticky messages use a different icon.
            if (elt.className.indexOf('sticky') > -1) {
                elt.src = '{URL->TEMPLATE}/images/bell.png';
            } else {
                elt.src = '{URL->TEMPLATE}/images/comment.png';
            }
        }
    );
});

