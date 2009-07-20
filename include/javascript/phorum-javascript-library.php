// A non-conflicting jQuery library for use by Phorum. This way, our
// jQuery library will not conflict with existing javascript libraries
// (e.g. when Phorum is embedded in another application that uses
// a javascript library or when a module is loaded that also loads
// a library of its own).

$PJ = jQuery.noConflict();

// Phorum object. Other JavaScript code for Phorum can extend
// this one to implement functionality without risking name
// name space collissions.

Phorum = {};

// Phorum.UI is a namespace that is reserved for implementing user
// interface related functionality.

Phorum.UI = {};

// The version of this lib. This can be used by other code to
// check if the correct version of the library is loaded.
// The major number should be incremented in case backward
// compatibility is broken. The minor number should be incremented
// when new functionality is implemented.

Phorum.library_version = '1.0';


// ----------------------------------------------------------------------
// console.debug() debugging support
// ----------------------------------------------------------------------

/**
 * Use FireFox' console.debug() method for logging debugging information.
 * When no console is available, then no logging is done at all.
 *
 * @param Integer level
 *     The debug level of the message. Only messages that have a
 *     debugging level equal to or below the configuration variable
 *     Phorum.debug.level are logged.
 *
 * @param String message
 *     The message to write to the debug log.
 *
 * @param mixed data
 *     Optional argument containing a variable which' contents should be
 *     logged. The data will be written to the debug log as a JSON structure.
 */
Phorum.debug = function(level, message, data)
{
    level = parseInt(level);
    if (level === 'NaN') level = 1;
    if (Phorum.debug.level &&
        Phorum.debug.level >= level &&
        typeof window.console !== 'undefined')
    {
        if (typeof data !== 'undefined') {
            data = ': ' + $PJ.toJSON(data);
        } else {
            data = '';
        }

        console.debug('(' + level + ') ' + message + data);
    }
}

/**
 * The debug level. Set this variable to a higher number
 * for more debugging information.
 */
Phorum.debug.level = 0;


// ----------------------------------------------------------------------
// Caching functionality
//
// This is a caching layer that can be used by Phorum JavaScript
// code for client side data caching. It supports TTLs for automatically
// expiring the cached data.
// ----------------------------------------------------------------------

Phorum.Cache =
{
    TTL: 0,   // the default TTL for cache entries

    data: {}, // cache data storage

    put: function(cache_id, data, ttl)
    {
        // Determine the TTL to use for the cache entry.
        if (typeof ttl === 'undefined') {
            ttl = Phorum.Cache.TTL;
        } else {
            ttl = parseInt(ttl);
            if (ttl == 'NaN') {
                ttl = Phorum.Cache.TTL;
            }
        }

        // Determine the expire time. When ttl = 0, then
        // no expiration is done at all.
        var expire_t = 0;
        if (ttl != 0) {
            var d = new Date();
            expire_t = d.getTime() + 1000*ttl;
        }

        // Store the data in the cache.
        Phorum.Cache.data[cache_id] = [data, expire_t];
    },

    get: function(cache_id)
    {
        // Check if there is a cache entry available.
        if (typeof Phorum.Cache.data[cache_id] === 'undefined') {
            Phorum.debug(5, 'Phorum cache miss for cache_id "'+cache_id+'"');
            return null;
        }
        var c = Phorum.Cache.data[cache_id];
        console.debug(c[1]);

        // TTL set? Then check if the cache entry hasn't expired yet.
        if (c[1] != 0) {
            var d = new Date();
            var now = d.getTime();
            if (now > c[1]) {
                Phorum.debug(5, 'Phorum cache expired for cache_id "'+cache_id+'"');
                return null;
            }
        }

        Phorum.debug(5, 'Phorum cache hit for cache_id "'+cache_id+'"');
        return c[0];
    },

    // Invalidate a single cache item or the full cache.
    purge: function(key)
    {
        if (typeof key  !== 'undefined') {
            Phorum.Ajax.cache[key] = null;
        } else {
            Phorum.Ajax.cache = {};
        }
    }
};

// ----------------------------------------------------------------------
// Ajax communication
//
// Handles Ajax calls to the Phorum system. Calls are done to the
// ajax.php script, which will handle the call and return the
// result to the calling client.
// ----------------------------------------------------------------------

Phorum.Ajax =
{
    // The URL that we can use to access the Phorum Ajax layer script.
    // The 'callback=?' part is a special placeholder for jQuery's JSONP code.
    URL: '<?php print phorum_api_url(PHORUM_AJAX_URL,'callback=?')?>',

    /**
     * Execute an Ajax Phorum call.
     *
     * @param Object req
     *     The request object. This is object needs at least the property
     *     "call". This req.call property determines what Ajax call has
     *     to be handled by the ajax.php script on the server.
     *
     *     When the req.cache_id property is set, then the Phorum.Cache is
     *     used for caching the call result data. It is the task of the
     *     caller to make sure that the provided cache_id is unique for
     *     the call that is done.
     *
     *     The req.onFailure property can be set for implementing error
     *     handling. Its value should be a function that handles the error.
     *     The function will be called with the error message as its argument.
     *
     *     The req.onSuccess property can be set for implementing handling for
     *     a successful Ajax call. Its value should be a function that handles
     *     the data that was returned by the Ajax call. The function will be
     *     called with two arguments:
     *     - the data that was returned by the Ajax call
     *     - whether (true) or not (false) the result was returned from cache
     *
     *     Other properties are sent to the ajax.php script as call arguments.
     *     What call arguments are available depends on the Phorum Ajax call
     *     that is called. Check the documentation of the call for details.
     */
    call: function(req)
    {
        // Check if a call was provided in the request data.
        if (! req.call) {
            Phorum.debug(
                1, 'Phorum.Ajax.call() error: missing property ' +
                '"call" for the request object', req
            );
            if (req.onFailure) req.onFailure(
                'Phorum.Ajax.call() error: missing property ' +
                '"call" for the request object.',
                -1, null
            );
            return;
        }

        // If the req.cache_id property is set for the request, then check
        // if the data for the request is already available in the
        // local cache. If yes, then return the data immediately.
        console.debug(req.cache_id);
        if (req.cache_id) {
            var data = Phorum.Cache.get(req.cache_id);
        console.debug(data);

            if (data != null) {
                Phorum.debug(
                    4, 'Phorum.Ajax.call calls onSuccess with cached data ' +
                    'for cache_id "'+req.cache_id+'"', data
                );
                if (req.onSuccess) {
                    // true = data retrieved from cache.
                    req.onSuccess(data, true);
                }
                return;
            }
        }

        // Create a filtered argument list (without functions and control args).
        var args = {};
        for (var key in req) {
            if (typeof req[key] != 'function' && key != 'cache_id') {
                // Convert complex arguments to JSON, otherwise they will
                // not survive the translation to a JSONP request URL.
                if (typeof(req[key]) == 'object') {
                    args[key] = '$JSON$' + $PJ.toJSON(req[key]);
                } else {
                    args[key] = req[key];
                }
            }
        }

        // Notify the start of the request loading stage.
        Phorum.debug(5, 'Phorum.Ajax.call calls server with args', args);
        if (req.onRequest) req.onRequest(args);

        $PJ.getJSON(Phorum.Ajax.URL, args, function(answer)
        {
            Phorum.debug(5, 'Phorum.Ajax.call receives answer from server', answer);
            if (typeof answer['error'] === 'undefined')
            {
                // If the req.cache_id property is set, then we cache the results.
                if (req.cache_id) Phorum.Cache.put(req.cache_id, answer);

                // false = data not retrieved from cache.
                Phorum.debug(4, 'Phorum.Ajax.call calls onSuccess with', answer);
                if (req.onSuccess) req.onSuccess(answer, false);
            }
            else
            {
                Phorum.debug(4, 'Phorum.Ajax.call calls onFailure with', answer['error']);
                if (req.onFailure) req.onFailure(answer['error']);
            }
        });
    }
};

// ----------------------------------------------------------------------
// UI functionality that is related to notifying the user about
// running async calls to the server (request busy notification).
//
// The default implementation will show a message box in the top left of
// the screen, notifying the user about the number of pending actions.
// This box can be styling by CSS by styling the class "phorum_busy_message".
// For example:
//
//   .phorum_busy_message {
//       border: 1px solid #cc7;
//       padding: 5px;
//       background: #ff9;
//   }   
//
// The template author can fully override Phorum.UI.notification to implement
// a customized notification mechanism. The minimal interface that has
// to be implemented is:
//
//   - Phorum.UI.busy.increment(): increments the number of busy actions
//   - Phorum.UI.busy.decrement(): decrements the number of busy actions
//
// All other methods from the busy implementation from below are used for
// implementing the default message box.
// ----------------------------------------------------------------------

Phorum.UI.busy =
{
    'box': null,   // storage for our box <div>
    'boxtop': 5,   // top notify box offset
    'boxleft': 5,  // left notify box offset
    'count': 0,    // the number of pending actions
    'timer': null, // used for timing the fading effect

    /**
     * Increments the number of pending actions.
     */
    'increment': function() { this.count ++; this.set(); },

    /**
     * Decrements the number of pending actions.
     */
    'decrement': function() { this.count --; this.set(); },

    /**
     * Fades out the busy box.
     */
    'fade': function()
    {
        if (!this.box) return;
        if (this.box.style.opacity > 0) {
            var o = this.box.style.opacity;
            o = o - 0.05; if (o < 0) o = 0;
            p = o * 100;
            this.box.style.opacity = o;                     // Standard opacity
            this.box.style.filter = 'alpha(opacity='+p+')'; // MSIE opacity
            this.timer = setTimeout(function() {
                Phorum.UI.busy.fade();
            }, 20);
        }
        else {
            this.box.style.display = 'none';
        }
    },

    /**
     * Set the busy message.
     */
    'set': function()
    {
        if (this.timer) clearTimeout(this.timer);

        // If no actions are pending, clear up the busy message
        // after a little 250ms pause (to keep the busy message
        // from being irritatingly flashy on screen).
        if (this.count == 0) {
            if (!this.box) return;
            this.timer = setTimeout(function() {
                Phorum.UI.busy.fade();
            }, 250);
            return;
        }

        // Create the notify box if it is not available yet.
        if (!this.box)
        {
            // Create the box.
            this.box = document.createElement('div');
            this.box.style.position = 'absolute';
            this.box.style.zIndex = '1000';
            this.box.style.display = 'none';

            // So template authors can style the message box.
            this.box.className = 'phorum_busy_message';

            // Add it to the page.
            document.body.insertBefore(this.box, document.body.childNodes[0]);

            // Move the busy message in view if the page scrolls.
            if (window.onscroll) {
                var orig = window.onscroll;
                window.onscroll = function() {
                    orig();
                    Phorum.UI.busy.place();
                };
            } else {
                window.onscroll = function() {
                    Phorum.UI.busy.place();
                }
            }
        }

        <?php $lang = $PHORUM['DATA']['LANG']; ?>
        var message = this.count == 1
            ? '<?php print addslashes($lang['ActionPending']) ?>'
            : '<?php print addslashes($lang['ActionsPending']) ?>';

        // Replace %count% in the message with the current action count.
        var pos = message.indexOf('%count%');
        if (pos > -1) {
            message = message.substr(0,pos)+this.count+message.substr(pos+7);
        }

        // Show the busy message.
        this.box.innerHTML = message;
        this.place();
        this.box.style.display = 'block';
        this.box.style.opacity = 1;                   // Standard opacity
        this.box.style.filter = 'alpha(opacity=100)'; // MSIE opacity
    },

    'place': function()
    {
        if (!this.box) return;

        var s = document.documentElement.scrollTop
              ? document.documentElement.scrollTop
              : document.body.scrollTop;

        var t = (s+this.boxtop) + 'px';

        this.box.style.top = t;
        this.box.style.left = this.boxleft;
    }
};

// ----------------------------------------------------------------------
// UI functionality that is related to marking forums / threads as read
//
// This code implements a framework for handling "mark read" Ajax
// communication to the server. It is the task of the template author
// to implement the template specific interfacing. This interfacing
// consists of:
//
// - Handling mark read calls by calling Phorum.UI.markread(...).
// - Implementing callback functions for updating the UI after a
//   successful "mark read" Ajax call.
// ----------------------------------------------------------------------

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
Phorum.UI.markread = function(mode, item_id)
{
    // Request busy notification for the user.
    Phorum.UI.busy.increment();

    var req = {
        'call': 'markread',
        'onSuccess': function(data)
        {
            Phorum.UI.busy.decrement();

            // Call all registered new flag cleanup callback functions.
            var l = Phorum.UI.markread.callbacks;
            for (var i = 0; i < l.length; i++) {
                l[i](mode, item_id);
            }
        },
        'onFailure': function(data)
        {
            Phorum.UI.busy.decrement();
        }
    };

    // Because the "mode" is dynamic ("forums", "threads" or "messages"),
    // we have to assign this property of the request like this.
    req[mode] = [item_id];

    // Dispatch the Ajax Phorum call.
    Phorum.Ajax.call(req);

    // So "return Phorum.UI.markread(...)" can be used to cancel
    // an <a href> click.
    return false;
};

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
 * Since this is an array of callback functions, modules can add extra
 * handling code for marking forums/threads/messages read (e.g. the
 * announcement module, which needs customized handling for marking
 * messages in the announcements block as read when the user clicks on
 * "mark read" for the announcement forum on the index page).
 */
Phorum.UI.markread.callbacks = new Array();

/**
 * A utility function for easy handling of newflag DOM changes.
 * This code will normally be used by mark read callback functions.
 *
 * @param string tag
 *     The name of the tags to process.
 *
 * @param string pre
 *     A prefix to check for in each DOM object's class name. This code
 *     expects that if the prefix matches, the suffix is formatted as
 *     <forum_id>[-<thread>[-<message_id>]]. E.g. with prefix "new-flag-"
 *     a DOM element could contain the class "new-flag-10-44" to identify
 *     a new flag for forum 10, thread 44.
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
Phorum.UI.markread.matchElements = function(tag, pre, mode, item_id, callback)
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

