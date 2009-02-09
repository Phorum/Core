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

// The version of this lib. This can be used by other code to
// check if the correct version of the library is loaded.
// The major number should be incremented in case backward
// compatibility is broken. The minor number should be incremented
// when new functionality is implemented.

Phorum.library_version = '1.0';

// ----------------------------------------------------------------------
// Basic console.log debugging support
//
// This uses the FireFox console for logging debugging information.
// When no console is available, then no logging is done at all.
// ----------------------------------------------------------------------

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

Phorum.debug.level = 10;

// ----------------------------------------------------------------------
// Caching functionality
//
// This is a basic caching layer that can be used by Phorum JavaScript
// code for client side data caching.
// ----------------------------------------------------------------------

Phorum.Cache = {};

Phorum.Cache.data = {};

// The default TTL for cache entries.
Phorum.Cache.TTL = 0; 

Phorum.Cache.put = function(cache_id, data, ttl)
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
}

Phorum.Cache.get = function(cache_id)
{
    // Check if there is a cache entry available.
    if (typeof Phorum.Cache.data[cache_id] === 'undefined') {
        Phorum.debug(5, 'Phorum cache miss for cache_id "'+cache_id+'"');
        return null;
    }
    var c = Phorum.Cache.data[cache_id];

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
}

// Invalidate a single cache item or the full cache.
Phorum.Cache.purge = function(key)
{
    if (typeof key  !== 'undefined') {
        Phorum.Ajax.cache[key] = null;
    } else {
        Phorum.Ajax.cache = {};
    }
}


// ----------------------------------------------------------------------
// Ajax communication
//
// Handles Ajax calls to the Phorum system. Calls are done to the
// ajax.php script, which will handle the call and return the
// result to the calling client.
// ----------------------------------------------------------------------

Phorum.Ajax = {};

// The URL that we can use to access the Phorum Ajax layer script.
// The 'callback=?' part is a special placeholder for jQuery's JSONP code.
Phorum.Ajax.URL='<?php print phorum_get_url(PHORUM_AJAX_URL, 'callback=?') ?>';

/**
 * Execute an Ajax Phorum call.
 *
 * @param Object req
 *     The request object. This is object needs at least the property
 *     "call". This req.call property determines what Ajax call has
 *     to be handled by the ajax.php script on the server.
 *     
 */
Phorum.Ajax.call = function(req)
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
    if (req.cache_id) {
        var data = Phorum.Cache.get(req.cache_id);
        if (data != null) {
            Phorum.debug(
                4, 'Phorum.Ajax.call calls onSuccess with cached data for ' +
                'cache_id "'+req.cache_id+'"', data
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
            if (req.cache_id) Phorum.Cache.put(req.cache_id, answer, 10);

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

// Parse out javascript blocks from the data to eval them. Adding them
// to the page using innerHTML does not invoke parsing by the browser.
Phorum.Ajax.evalJavaScript = function(data)
{
    var cursor = 0;
    var start  = 1;
    var end    = 1;

    while (cursor < data.length && start > 0 && end > 0) {
        start = data.indexOf('<script', cursor);
        end   = data.indexOf('</script', cursor);
        if (end > start && end > -1) {
            if (start > -1) {
                var res = data.substring(start, end);
                start = res.indexOf('>') + 1;
                res = res.substring(start);
                if (res.length != 0) {
                    eval(res);
                }
            }
            cursor = end + 1;
        }
    }
}

