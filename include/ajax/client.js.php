// Setup the Phorum.Ajax namespace if it has not yet been setup.
if (Phorum === undefined) Phorum = {};
if (Phorum.Ajax === undefined) Phorum.Ajax = {};

// The version of this lib
Phorum.Ajax.version = '1.0.0';

// The URL that we use to access the Phorum Ajax layer.
Phorum.Ajax.URL = '<?php print addslashes($PHORUM['DATA']['URL']['AJAX']) ?>';

// Storage for Ajax call return data. This acts as a local cache
// for keeping track of already retrieved items.
Phorum.Ajax.cache = {};

/**
 * Create an XMLHttpRequest object.
 * Used internally by Phorum.Ajax.call().
 * Raise an onFailure event in case no object can be created.
 * Return either an object or null if the object creation failed.
 */
Phorum.Ajax.getXMLHttpRequest = function(req)
{
    var xhr;
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        var versions = [
            'MSXML2.XMLHttp.5.0',
            'MSXML2.XMLHttp.4.0',
            'MSXML2.XMLHttp.3.0',
            'MSXML2.XMLHttp',
            'Microsoft.XMLHttp'
        ];
        for (var i=0; i < versions.length; i++) {
            try { xhr = new ActiveXObject(versions[i]); } catch (e) { }
        }
    }

    if (xhr) {
        return xhr;
    }

    if (req.onFailure) req.onFailure(
        'Phorum: Unable to create an XMLHttpRequest object',
        -1, null
    );
    return null;
};

/**
 * Execute an Ajax Phorum call.
 */
Phorum.Ajax.call = function(req)
{
    // If the store property is set for the request, then check
    // if the data for the request is already available in the
    // local cache. If yes, then return the data immediately.
    if (req.store) {
        if (req.store != null && Phorum.Ajax.cache[req.store]) {
            if (req.onSuccess) {
                // true = data retrieved from cache.
                req.onSuccess(Phorum.Ajax.cache[req.store], true);
            }
            return;
        }
    }

    // Check the request data.
    if (! req['call']) {
        if (req.onFailure) req.onFailure(
            'Phorum.Ajax.call() error: missing property ' +
            '"call" for the request object.',
            -1, null
        );
        return;
    }

    // Check if there is an XMLHttpRequest object available.
    var xhr = Phorum.Ajax.getXMLHttpRequest(req);
    if (! xhr) return;

    // Convert the request object to JSON.
    var json = Phorum.JSON.encode(req);

    // Notify the start of the request loading stage.
    if (req.onRequest) req.onRequest(json);

    xhr.open("post", Phorum.Ajax.URL, true);
    xhr.setRequestHeader("Content-Type", "text/x-json");
    xhr.onreadystatechange = function()
    {
      if (req.onReadStateChange) req.onReadyStateChange(req);

      switch (xhr.readyState)
      {
          case 1:

              if (req.onLoading) req.onLoading(xhr);
              break;

          case 2:

              if (req.onLoaded) req.onLoaded(xhr);
              break;

          case 3:

              if (req.onInteractive) req.onInteractive(xhr);
              break;

          case 4:

              if (req.onComplete)req.onComplete(xhr);

              if (req.onResponse) req.onResponse(xhr.responseText);

              if (xhr.status == 200) {

                  // Evaluate the returned JSON code. If evaluation fails,
                  // then run the onFailure event for the Phorum.Ajax.call.
                  try {
                      var res = Phorum.JSON.decode(xhr.responseText);
                  } catch (e) {
                      if (req.onFailure) req.onFailure(
                        'Ajax Phorum API call succeeded, but the return ' +
                        'data could not be parsed as JSON data.',
                        xhr.status, xhr.responseText
                      );
                      return;
                  }

                  // If the req.store property is set, then we store
                  // the result data in the Phorum cache.
                  if (req.store) Phorum.Ajax.cache[req.store] = res;

                  // false = data not retrieved from store.
                  if (req.onSuccess) req.onSuccess(res, false);

              } else {

                  if (req.onFailure) req.onFailure(xhr.responseText);
              }

              break;
      }
    };
    xhr.send(json);
};

// Invalidate a single cache item of the full cache.
Phorum.Ajax.invalidateCache = function(key)
{
    if (key) {
        Phorum.Ajax.cache[key] = null;
    } else {
        Phorum.Ajax.cache = new Array();
    }
};

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
};

// ======================================================================
// JSON encoder and decoder
// Based on byteson by Andrea Giammarchi
// (http://www.devpro.it/byteson/)
// ======================================================================

Phorum.JSON = {};

Phorum.JSON.common =
{
  // characters object, useful to convert some char in a JSON compatible way
  c:{'\b':'b','\t':'t','\n':'n','\f':'f','\r':'r','"':'"','\\':'\\','/':'/'},

  // decimal function, returns a string with length === 2 for date convertion
  d:function(n){return n < 10 ? '0'.concat(n) : n},

  // integer function, returns integer value from a piece of string
  i:function(e, p, l){return parseInt(e.substr(p, l))},

  // slash function, add a slash before a common.c char
  s:function(i,d){return '\\'.concat(Phorum.JSON.common.c[d])},

  // unicode function, return respective unicode string
  u:function(i,d){var n = d.charCodeAt(0).toString(16);return '\\u'.concat(n.length < 2 ? '000' : '00', n)}
};

Phorum.JSON.convert = function(params, result)
{
    switch(params.constructor) {
        case Number:
            result = isFinite(params) ? String(params) : 'null';
            break;
        case Boolean:
            result = String(params);
            break;
        case Date:
            result = concat(
                '"',
                params.getFullYear(), '-',
                Phorum.JSON.common.d(params.getMonth() + 1), '-',
                Phorum.JSON.common.d(params.getDate()), 'T',
                Phorum.JSON.common.d(params.getHours()), ':',
                Phorum.JSON.common.d(params.getMinutes()), ':',
                Phorum.JSON.common.d(params.getSeconds()),
                '"'
            );
            break;
        case String:
            if(/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/.test(params)){
                result = new Date;
                result.setHours(Phorum.JSON.common.i(params, 11, 2));
                result.setMinutes(Phorum.JSON.common.i(params, 14, 2));
                result.setSeconds(Phorum.JSON.common.i(params, 17, 2));
                result.setMonth(Phorum.JSON.common.i(params, 5, 2) - 1);
                result.setDate(Phorum.JSON.common.i(params, 9, 2));
                result.setFullYear(Phorum.JSON.common.i(params, 0, 4));
            };
            break;
        default:
            var n, tmp = [];
            if(result) {
                for(n in params) result[n] = params[n];
            } else {
                for(n in params) {
                    if(params.hasOwnProperty(n) && !!(result = Phorum.JSON.encode(params[n])))
                        tmp.push(Phorum.JSON.encode(n).concat(':', result));
                };
                result = '{'.concat(tmp.join(','), '}');
            };
            break;
    };
    return result;
};

Phorum.JSON.encode = function(params)
{
    var result = '';

    if(params === null)
    {
        result = 'null';
    }
    else if(!{'function':1,'undefined':1,'unknown':1}[typeof(params)])
    {
        switch(params.constructor)
        {
            case Array:
                for(var i = 0, j = params.length, tmp = []; i < j; i++) {
                    if(!!(result = Phorum.JSON.encode(params[i])))
                        tmp.push(result);
                };
                result = '['.concat(tmp.join(','), ']');
                break;

            case String:
                result = '"'.concat(params.replace(
                        /(\x5c|\x2F|\x22|[\x0c-\x0d]|[\x08-\x0a])/g, Phorum.JSON.common.s
                    ).replace(
                        /([\x00-\x07]|\x0b|[\x0e-\x1f])/g, Phorum.JSON.common.u
                    ), '"');
                break;

            default:
                result = Phorum.JSON.convert(params);
                break;
        };
    };
    return result;
};

Phorum.JSON.decode = function(json)
{
    var res = JSON.parse(json);
    if (res === undefined) {
        throw new SyntaxError('The Phorum JSON data cannot be parsed');
    }
    return res;
};

