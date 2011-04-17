<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

define('phorum_page','javascript');
require_once("./common.php");

// When loaded from the admin interface, the admin parameter will be set
// in the URL. We use that parameter to not load template and module
// related javascript code.
$for_admin = !empty($PHORUM['args']['admin']);

// So we can use {URL->HTTP_PATH} in the templates.
phorum_build_common_urls();

// An array that is used for gathering the parts that
// have to be added to the javascript code.
$module_registrations = array();

// Add core JSON parser library code
$module_registrations[] = array(
    'module'    => 'core',
    'source'    => 'file(include/ajax/json2.js.php)',
    'cache_key' => filemtime('./include/ajax/json2.js.php')
);

// Add the jQuery JavaScript library code.
$module_registrations[] = array(
    'module'    => 'core',
    'source'    => 'file(include/javascript/jquery-1.4.4.min.js)',
    'cache_key' => '1.4.4.min'
);

// Add the jQuery JSON plugin.
$module_registrations[] = array(
    'module'    => 'core',
    'source'    => 'file(include/javascript/jquery.json-1.3.min.js)',
    'cache_key' => '1.3.min'
);

// Add the jQuery bgiframe plugin.
$module_registrations[] = array(
    'module'    => 'core',
    'source'    => 'file(include/javascript/jquery.bgiframe-2.1.2.min.js)',
    'cache_key' => '2.1.2.min'
);

// Add the Phorum JavaScript library.
$module_registrations[] = array(
    'module'    => 'core',
    'source'    => 'file(include/javascript/phorum-javascript-library.php)'
);

// Add core Phorum posting form object manipulation client JavaScript code.
$module_registrations[] = array(
    'module'    => 'core',
    'source'    => 'file(include/posting/form_objects.js.php)',
    'cache_key' => filemtime('./include/posting/form_objects.js.php')
);


// Add core Phorum Ajax layer client JavaScript code.
$module_registrations[] = array(
    'module'    => 'core',
    'source'    => 'file(include/ajax/client.js.php)',
    'cache_key' => filemtime('./include/ajax/client.js.php') .
                   $PHORUM['DATA']['URL']['AJAX']
);

// Add template specific javascript code, if available. The template writer
// can put the javascript code to include in the file
// "templates/<name>/javascript.tpl" or "templates/<name>/javascript.php".
if (!$for_admin) {
    if (file_exists("./templates/{$PHORUM['template']}/javascript.tpl") ||
        file_exists("./templates/{$PHORUM['template']}/javascript.php")) {
        $module_registrations[] = array(
            'module' => $PHORUM['template'] . ' template',
            'source' => 'template(javascript)'
        );
    }
}

/**
 * [hook]
 *     javascript_register
 *
 * [description]
 *     Modules can provide JavaScript code that has to be added to the
 *     Phorum pages. Modules that make use of this facility should
 *     register the JavaScript code using this hook.
 *
 * [category]
 *     Templating
 *
 * [when]
 *     At the start of the javascript.php script.
 *
 * [input]
 *     An array of registrations. Modules can register their JavaScript
 *     code for inclusion by adding a registration to this array.
 *     A registration is an array, containing the following fields:
 *     <ul>
 *     <li><b>module</b><br>
 *         The name of the module that adds the registration.
 *     </li>
 *     <li><b>source</b><br>
 *         Specifies the source of the JavaScript data. This can be one of:
 *         <ul>
 *         <li><b>file(&lt;path to filename&gt;)</b><br>
 *             For including a static JavaScript file. The path should be
 *             absolute or relative to the Phorum install directory,
 *             e.g. "<literal>file(mods/foobar/baz.js</literal>)".
 *             Because this file is loaded using a PHP include() call,
 *             it is possible to include PHP code in this file. Mind that
 *             this code is stored interpreted in the cache.</li>
 *         <li><b>template(&lt;template name&gt;)</b><br>
 *             For including a Phorum template,
 *             e.g. "<literal>template(foobar::baz)</literal>"</li>
 *         <li><b>function(&lt;function name&gt;)</b><br>
 *             For calling a function to retrieve the JavaScript code,
 *             e.g. "<literal>function(mod_foobar_get_js</literal>)"</li>
 *         </ul>
 *     </li>
 *     <li><b>cache_key</b><br>
 *         To make caching of the generated JavaScript code
 *         possible, the module should provide a cache key using this
 *         field. This cache key needs to change if the module will
 *         provide different JavaScript code.<br>
 *         <br>
 *         Note: in case "file" or "template" is used as the source,
 *         you are allowed to omit the cache_key. In that case, the
 *         modification time of the involved file(s) will be used as
 *         the cache key.<br>
 *         <br>
 *         It is okay for the module to provide multiple cache keys
 *         for different situations (e.g. if the JavaScript code depends on
 *         a group). Keep in mind though that for each different
 *         cache key, a separate cache file is generated. If you are
 *         generating different JavaScript code per user or so, then it might
 *         be better to add the JavaScript code differently (e.g. through a
 *         custom JavaScript generating script or by adding the code to
 *         the <literal>$PHORUM['DATA']['HEAD_DATA']</literal> variable).
 *         Also, do not use this to only add JavaScript code to certain
 *         phorum pages. Since the resulting JavaScript data is cached,
 *         it is no problem if you add the JavaScript code for your module
 *         to the code for every page.
 *     </li>
 *     </ul>
 *
 * [output]
 *     The same array as the one that was used as the hook call
 *     argument, possibly extended with one or more registrations.
 */
if (!$for_admin && isset($PHORUM['hooks']['javascript_register'])) {
    $module_registrations = phorum_hook(
        'javascript_register', $module_registrations
    );
}

// Generate the cache key. While adding cache keys for the module
// registrations, we also check the validity of the registration data.
// We start the cache key with the template and language, so template
// javascript is taken care of and so language strings can be used
// inside the javascript files.
$cache_key = $PHORUM['template'] . ':' . $PHORUM['language'];
foreach ($module_registrations as $id => $r)
{
    if (!isset($r['module'])) {
        trigger_error(
            "javascript_register hook: module registration error: " .
            "the \"module\" field was not set."
        );
        exit(1);
    }
    if (!isset($r['source'])) {
        trigger_error(
            "javascript_register hook: module registration error: " .
            "the \"source\" field was not set."
        );
        exit(1);
    }
    if (preg_match('/^(file|template|function)\((.+)\)$/', $r['source'], $m))
    {
        $module_registrations[$id]['type']   = $m[1];
        $module_registrations[$id]['source'] = $m[2];

        switch ($m[1])
        {
            case "file":

                if (!isset($r['cache_key'])) {
                    $mtime = @filemtime($m[2]);
                    $r['cache_key'] = $mtime;
                    $module_registrations[$id]['cache_key'] = $mtime;
                }
                break;

            case "template":

                // We load the parsed template into memory. This will refresh
                // the cached template file if required. This is the easiest
                // way to make this work correctly for nested template files.
                ob_start();
                include(phorum_get_template($m[2]));
                $module_registrations[$id]['content'] = ob_get_contents();
                ob_end_clean();

                // We use the mtime of the compiled template as the cache
                // key if no specific cache key was set.
                if (!isset($r['cache_key'])) {
                    list ($m[2], $php, $tpl) = phorum_get_template_file($m[2]);
                    $mtime = @filemtime($php);
                    $r['cache_key'] = $mtime;
                    $module_registrations[$id]['cache_key'] = $mtime;
                }
                break;

            case "function":

                if (!isset($r['cache_key'])) {
                    trigger_error(
                        "javascript_register hook: module registration " .
                        "error: \"cache_key\" field missing for source " .
                        "\"{$r['source']}\" in module \"{$r['module']}\"."
                    );
                    exit(1);
                }
                break;
        }
    } else {
        trigger_error(
            "javascript_register hook: module registration error: " .
            "illegal format for source definition \"{$r['source']}\" " .
            "in module \"{$r['module']}\"."
        );
        exit(1);
    }

    $cache_key .= '|' . $r['module'] . ':' . $r['cache_key'];
}

$cache_key = md5($cache_key);

$content = NULL;
$cache_time = 0;

if (!empty($PHORUM['cache_javascript']))
{
    $cache_data = phorum_cache_get('js', $cache_key);
    if ($cache_data !== null) {
        list($cache_time,$content) = $cache_data;
    }
}              
              
// Create the cache file if it does not exist or if caching is disabled.
if (isset($PHORUM['args']['refresh']) ||
    $content === null)
{
    $content =
        "// Phorum object. Other JavaScript code for Phorum can extend\n" .
        "// this one to implement functionality without risking\n" .
        "// name space collissions.\n" .
        "Phorum = {};\n\n";

    foreach ($module_registrations as $id => $r)
    {
        $content .= "/* Added by module \"{$r['module']}\", " .
                    "{$r['type']} \"{$r['source']}\" */\n";

        switch ($r['type'])
        {
            case "file":
                ob_start();
                // Make sure that relative paths start with "./",
                // just in case "." is not in the PHP include path.
                $path = $r['source'][0] == '/'
                      ? $r['source'] : './'.$r['source'];
                include($path);
                $content .= ob_get_contents();
                ob_end_clean();
                break;

            case "template":
                $content .= $r['content'];
                break;

            case "function":
                $content .= call_user_func($r['source']);
                break;
        }

        $content .= "\n\n";
    }

    /**
     * [hook]
     *     javascript_filter
     *
     * [description]
     *     This hook can be used to apply a filter to the Phorum JavaScript
     *     code. This can for example be used for compressing or cleaning
     *     up the JavaScript.
     *
     * [category]
     *     Templating
     *
     * [when]
     *     Right after the javascript.php script has generated a new
     *     JavaScript file and right before storing that file in the cache.
     *     The filter hook will not be run for every request to
     *     javascript.php, but only in case the JavaScript code has
     *     to be refreshed.
     *
     * [input]
     *     The generated JavaScript code.
     *
     * [output]
     *     The filtered JavaScript code.
     */
    if (isset($PHORUM['hooks']['javascript_filter'])) {
        $content = phorum_hook('javascript_filter', $content);
    }

    if (!empty($PHORUM['cache_javascript'])) {
    	$cache_time = time();
        phorum_cache_put('js',$cache_key,array($cache_time,$content),86400);
    }

    // Send the JavaScript to the browser.
    header("Content-Type: text/javascript");
    print "/* FRESH */";
    print $content;

    // Exit here explicitly for not giving back control to portable and
    // embedded Phorum setups.
    exit(0);
}

// Find the modification time for the cache file.
$last_modified = $cache_time;

// Check if a If-Modified-Since header is in the request. If yes, then
// check if the JavaScript code has changed, based on the filemtime() data from
// above. If nothing changed, then we can return a 304 header, to tell the
// browser to use the cached data.
if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $header = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
    $if_modified_since = strtotime($header);

    if ($if_modified_since == $last_modified) {
        header("HTTP/1.0 304 Not Modified");
        exit(0);
    }
}

// Send the JavaScript to the browser.
header("Content-Type: text/javascript");
header("Last-Modified: " . gmdate('D, d M Y H:i:s \G\M\T', $last_modified));


echo $content;

// Exit here explicitly for not giving back control to portable and
// embedded Phorum setups.
exit(0);

?>
