<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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

/**
 * This script implements the Phorum template handling API.
 *
 * @package    PhorumAPI
 * @subpackage Template
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_template_list()
/**
 * Retrieve a list of all available templates.
 *
 * This function will scan the templates directory to find all available
 * templates.
 *
 * @param bool $include_hidden
 *     Whether or not to include hidden templates in the template list.
 *     Templates can be hidden by setting the variable $template_hide
 *     to a true value in the temlate's info.php file.
 *
 * @return array
 *     An array of templates. The keys in the array are the template
 *     id's by which they are referenced internally. The values contain
 *     the names + versions of the templates.
 */
function phorum_api_template_list($include_hidden = FALSE)
{
    global $PHORUM;

    $templates = array();

    $dh = opendir($PHORUM['template_path']);
    while ($entry = readdir($dh))
    {
        if ($entry[0] !== '.' &&
            file_exists($PHORUM['template_path'].'/'.$entry.'/info.php')) {

            $template_hide = FALSE;
            $version = '(version unknown)';
            $name = $entry;

            include $PHORUM['template_path'].'/'.$entry.'/info.php';
            if ($include_hidden || empty($template_hide)) {
                $templates[$entry] = "$name $version";
            }
        }
    }
    closedir($dh);

    return $templates;
}
// }}}

// {{{ Function: phorum_api_template_set()
/**
 * Set the active template.
 *
 * This function can be used to setup the data that is needed for activating
 * a different template or template storage path. This can be especially
 * useful for modules that can use this function to switch Phorum to a
 * template that is stored inside the module's directory (so no file copying
 * required to get the module's template tree into place). If for example
 * module "Foo" has a template directory "./mods/foo/templates/bar", then
 * the module could use this code to make sure that this template is used.
 * <code>
 *   phorum_api_template_set(
 *       "bar",
 *       PHORUM_PATH."/mods/foo/templates",
 *       $PHORUM['http_path']."/mods/foo/templates"
 *   );
 * </code>
 *
 * Beware that after doing this, the module's template directory is expected
 * to carry a full standard Phorum template and not only templates that are
 * required by the module for access through the "foo::templatename"
 * construction. Therefore, this template needs to have an info.php that
 * describes the template and a copy of all other template files that
 * Phorum normally uses.
 *
 * @param string $template
 *     The name of the template to active (e.g. "emerald", "lightweight", etc.)
 *     If this parameter is NULL, then no change will be done to the
 *     currently activated template.
 *
 * @param string $template_path
 *     The path to the base of the template directory. By default,
 *     this is "./templates". If this parameter is NULL, then
 *     no change will be done to the currenctly configured path.
 *
 * @param string $template_http_path
 *     The URL to the base of the template directory. By default,
 *     this is "<http_path>/templates". If this parameter is NULL, then
 *     no change will be done to the currenctly configured http path.
 *
 */
function phorum_api_template_set($template = NULL, $template_path = NULL, $template_http_path = NULL)
{
    global $PHORUM;

    if ($template !== NULL) {
        $PHORUM['template'] = basename($template);
    }
    if ($template_path !== NULL) {
        $PHORUM['template_path'] = $template_path;
    }
    if ($template_http_path !== NULL) {
        $PHORUM['template_http_path'] = $template_http_path;
    }

    // Apply defaults when needed.
    if (empty($PHORUM['template_path'])) {
        $PHORUM['template_path'] = PHORUM_PATH.'/templates';
    }
    if (empty($PHORUM['template_http_path'])) {
        $PHORUM['template_path'] = $PHORUM['http_path'].'/templates';
    }

    // Setup related template data.
    $PHORUM["DATA"]["TEMPLATE"] = htmlspecialchars($PHORUM['template']);
    $PHORUM["DATA"]["URL"]["TEMPLATE"] =
        htmlspecialchars("$PHORUM[template_http_path]/$PHORUM[template]");

    // Load the settings file for the configured template.
    ob_start();
    include phorum_api_template('settings');
    ob_end_clean();
}
// }}}

// {{{ Function: phorum_api_template()
/**
 * Get the name of the PHP file to include for rendering a given template.
 *
 * If the format for $template is <module>::<template>, then
 * the template is loaded from the module's template directory. The
 * directory structure for storing module templates is the same as for the
 * main templates directory, only it is stored within a module's
 * directory:
 *
 *   <phorum_dir>/mods/templates/<template name>/<page>.tpl
 *
 * @param string $page
 *     The name of the temlate to compile (e.g. "header", "css",
 *     "somemod::template", etc.).
 *
 * @return string
 *     The name of the PHP file to include for rendering the template.
 */
function phorum_api_template($page)
{
    // This might for example happen if a template contains code like
    // {INCLUDE template} instead of {INCLUDE "template"}.
    if ($page === NULL || $page == "") {
        require_once PHORUM_PATH.'/include/api/error/backtrace.php';
        print "<html><head><title>Phorum Template Error</title><body>";
        print "<h1>Phorum Template Error</h1>";
        print "phorum_api_template() was called with an empty page name.<br/>";
        print "This might indicate a template problem.<br/>";
        if (function_exists('debug_print_backtrace')) {
            print "Here's a backtrace that might help finding the error:";
            print "<pre>";
            print phorum_api_error_backtrace();
            print "</pre>";
        }
        print "</body></html>";
        exit(1);
    }

    list ($page, $phpfile, $tplfile) = phorum_api_template_resolve($page);

    // No template to process. This will happen in case a .php file
    // is used for defining the template instead of a .tpl file.
    if (empty($tplfile)) return $phpfile;

    // Compile the template if the output PHP file is not available.
    if (!file_exists($phpfile)) {
        require_once PHORUM_PATH.'/include/api/template/compile.php';
        phorum_api_template_compile($page, $tplfile, $phpfile);
    }

    return $phpfile;
}
// }}}

// {{{ Function: phorum_api_template_resolve()
/**
 * Resolve the input and output files to use for a given template.
 *
 * @param string $page
 *     The template name (e.g. "header", "css", "foobar::frontpage", etc.).
 *
 * @return array
 *     This function returns an array, containing three elements:
 *     - The template name, which could be different from the input template
 *       name, because the "get_template_file" hook can override it.
 *     - The PHP file to include for rendering the template.
 *     - The file to use as the template source. When there is no
 *       pre-processing required for compiling the template source
 *       into the rendering PHP include file, then this value will
 *       be NULL.
 */
function phorum_api_template_resolve($page)
{
    global $PHORUM;

    $page = basename($page);

    /*
     * [hook]
     *     get_template_file
     *
     * [availability]
     *     Phorum 5 >= 5.2.11
     *
     * [description]
     *     Allow modules to have influence on the results of the
     *     phorum_api_template_resolve() function. This function translates
     *     a page name (e.g. <literal>list</literal>) into a filename
     *     to use as the template source for that page (e.g.
     *      <filename>/path/to/phorum/templates/emerald/list.tpl</filename>).
     *
     * [category]
     *     Page output
     *
     * [when]
     *     At the start of the api_template_resolve() function
     *     from <filename>include/api/template.php</filename>.
     *
     * [input]
     *     An array containing two elements:
     *     <ul>
     *       <li>page:
     *           The page that was requested.</li>
     *       <li>source:
     *           The file that has to be used as the source for the page.
     *           This one is initialized as NULL.</li>
     *     </ul>
     *
     * [output]
     *     Same as input. Modules can override either or both of the array
     *     elements. When the "source" element is set after running the
     *     hook, then the file named in this element is directly used as
     *     the template source. It must end in either ".php" or ".tpl" to
     *     be accepted as a template source. Phorum does not do any additional
     *     checking on this source file name. It is the module's duty to
     *     provide a correct source file name.<sbr/>
     *     Otherwise, the template source file is determined based on
     *     the value of the "page" element, following the standard
     *     Phorum template resolving rules.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_get_template_file($data)
     *     {
     *         // Override the "index_new" template with a custom
     *         // template from the "foo" module.
     *         if ($data['page'] == 'index_new') {
     *             $data['page'] = 'foo::index_new';
     *         }
     *
     *         // Point the "pm" template directly at a custom PHP script.
     *         if ($data['page'] == 'pm') {
     *             $data['source'] = './mods/foo/pm_output_handler.php';
     *         }
     *
     *         return $data;
     *     }
     *     </hookcode>
     */
    $tplbase = NULL;
    $template = NULL;
    if (isset($GLOBALS["PHORUM"]["hooks"]["get_template_file"]))
    {
        $res = phorum_api_hook("get_template_file", array(
            'page'   => $page,
            'source' => NULL
        ));

        $page = basename($res['page']);

        if ($res['source'] !== NULL && strlen($res['source']) > 4)
        {
            // PHP source can be returned right away. These will be included
            // directly by the template handling code.
            if (substr($res['source'], -4, 4) == '.php') {
                return array($page, $res['source'], NULL);
            }
            // For .tpl files, we continue running this function, because
            // a cache file name has to be compiled for storing the
            // compiled template data.
            if (substr($res['source'], -4, 4) == '.tpl') {
                $tplbase = substr($res['source'], 0, -4);
            }
        }

        $template = 'set_from_module';
    }

    // No template source set by a module? Then continue by finding
    // a template based on the provided template page name.
    if ($tplbase === NULL)
    {
        // Check for a module reference in the page name.
        $fullpage = $page;
        $module = NULL;
        if (($pos = strpos($fullpage, "::", 1)) !== FALSE) {
            $module = substr($fullpage, 0, $pos);
            $page = substr($fullpage, $pos+2);
        }

        if ($module === NULL) {
            $prefix = $PHORUM['template_path'];
            // The postfix is used for checking if the template directory
            // contains at least the mandatory info.php file. Otherwise, it
            // could be an incomplete or empty template.
            $postfix = '/info.php';
        } else {
            $prefix = PHORUM_PATH.'/mods/'.basename($module).'/templates';
            $postfix = '';
        }

        // If no user template is set or if the template cannot be found,
        // fallback to the configured default template. If that one can also
        // not be found, then fallback to the hard-coded default template.
        if (empty($PHORUM["template"]) ||
            !file_exists("$prefix/{$PHORUM['template']}$postfix"))
        {
            $template = $PHORUM["default_forum_options"]["template"];
            if ($template != PHORUM_DEFAULT_TEMPLATE &&
                !file_exists("$prefix/$template$postfix")) {
                $template = PHORUM_DEFAULT_TEMPLATE;
            }

            // If we're not handling a module template, then we can change the
            // global template to remember the fallback template and to make
            // sure that {URL->TEMPLATE} and {TEMPLATE} aren't pointing to a
            // non-existent template in the end..
            if ($module === NULL) { $PHORUM["template"] = $template; }
        } else {
            $template = $PHORUM['template'];
        }

        $tplbase = "$prefix/$template/$page";

        // check for straight PHP file
        if (file_exists("$tplbase.php")) {
            return array($page, "$tplbase.php", NULL);
        }
    }

    // Build the compiled template and template input file names.
    $tplfile = "$tplbase.tpl";
    $safetemplate = str_replace(array("-",":"), array("_","_"), $template);
    if (isset($module)) $page = "$module::$page";
    $safepage = str_replace(array("-",":"), array("_","_"), $page);
    $phpfile = "{$PHORUM['CACHECONFIG']['directory']}/tpl-$safetemplate-$safepage-" .
           md5(dirname(__FILE__) . $tplfile) . ".php";

    return array($page, $phpfile, $tplfile);
}
// }}}

?>
