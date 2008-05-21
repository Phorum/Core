/**
 * This function can be called from "mark read" links to handle marking
 * forums, threads or messages read through Ajax calls.
 *
 * @param string mode
 *     One of "forums", "threads" or "messages".
 *
 * @param int item_id
 *     A forum_id, thread id or message_id (which one to use depends
 *     on the "mode" parameter).
 */
Phorum.UI.markread = function (mode, item_id)
{
    // Visual busy notification for the user.
    Phorum.UI.notification.increment();

    var req = {
        'call': 'markread',
        'onSuccess': function(data)
        {
            Phorum.UI.notification.decrement();

            // Call all registered new flag cleanup functions.
            var l = Phorum.UI.markreadSuccess;
            for (var i = 0; i < l.length; i++) {
                l[i](mode, item_id);
            }
        },
        'onFailure': function(data)
        {
            Phorum.UI.notification.decrement();
        }
    };

    // Because the "mode" is dynamic ("forums", "threads" or "messages"),
    // we have to assign this property like this.
    req[mode] = [item_id];

    // Dispatch the Ajax Phorum call.
    Phorum.Ajax.call(req);

    // Cancels the <a href> click.
    return false;
}

/**
 * An array of functions that need to be called when cleaning up newflags
 * through Phorum.UI.markread() is successful.
 *
 * The functions in this array will be called from the function
 * Phorum.UI.markread() with two parameters (when the markread call
 * was successful):
 * - mode = <forums|threads|messages>
 * - item_id = the forum_id, thread or message_id to clear
 *
 * This is made an array, so modules can add extra handling code for
 * marking forums/threads/messages read (e.g. the announcement module,
 * which needs customized handling for marking messages read).
 */
Phorum.UI.markreadSuccess = new Array();

/**
 * A utility function for easy handling of newflag DOM changes.
 *
 * @param string tag
 *     The name of the tags to process.
 *
 * @param string pre
 *     A prefix to check for in each DOM object's class name. This code
 *     expects that if the prefix matches, the suffix is formatted as
 *     <forum_id>[-<thread>[-<message_id>]]. E.g. with prefix "new-flag-"
 *     a DOM element could contain the class "new-flag-10-44" to identify
 *     a new flag for forum 10, thrad 44.
 *
 * @param string mode
 *     The mode that was used for Phorum.UI.markread(). This is one of
 *     "forums", "threads" or "messages".
 *
 * @param integer item_id
 *     The item_id that was used for Phorum.UI.markread(). Together with
 *     the mode parameter, this parameter is used to check if a DOM
 *     element from the elts argument should be processed by the callback
 *     function.
 *
 * @param function callback
 *     A callback function that has to process matching DOM elements.
 */
Phorum.UI.markreadHandleMatches = function(tag, pre, mode, item_id, callback)
{
    var elts = document.getElementsByTagName(tag);
    if (!elts) return;

    for (var i = 0; i < elts.length; i++)
    {
        // Walk over all classes for the current element.
        var classes = elts[i].className.split(' ');
        for (var j = 0; j < classes.length; j++)
        {
            var c = classes[j];
            if (c.length)
            {
                // Check if the class starts with the provided prefix match.
                if (c.substr(0, pre.length) != pre) continue;

                // Yes, match found. The postfix is formatted as
                // <forum_id>[-<thread>[-<message_id>]]
                var parts = c.substr(pre.length).split('-');

                // Run the callback function for messages that match
                // a forum that was marked read.
                if (mode == 'forums' && parts[0] && parts[0] == item_id)
                {
                    callback(elts[i]);
                }
                // Run the callback function for messages that match
                // a thread that was marked read.
                else if (mode == 'threads' && parts[1] && parts[1] == item_id)
                {
                    callback(elts[i]);
                }
                // Run the callback function for messages that match
                // a message that was marked read.
                else if (mode == 'messages' && parts[2] && parts[2] == item_id)
                {
                    callback(elts[i]);
                }
            }
        }
    }
}

// Add a newflag clearing function for the core template.
Phorum.UI.markreadSuccess.push(function (mode, item_id)
{
    // Clear newflags that are inside <span> elements and which
    // we will simply hide when clearing the newflag.
    Phorum.UI.markreadHandleMatches(
        'span', 'new-flag-', mode, item_id,
        function(elt) {
            elt.style.display = 'none';
        }
    );

    // Clear subjects that are flagged as new.
    Phorum.UI.markreadHandleMatches(
        'a', 'new-flag-subject-', mode, item_id,
        function(elt) {
            elt.style.fontWeight = 'normal';
        }
    );

    // Clear newflag icons.
    Phorum.UI.markreadHandleMatches(
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
