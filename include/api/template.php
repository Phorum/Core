<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

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
        if ($entry != '.' && $entry != '..' &&
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

    $PHORUM["DATA"]["TEMPLATE"] = htmlspecialchars($PHORUM['template']);
    $PHORUM["DATA"]["URL"]["TEMPLATE"] =
        htmlspecialchars("$PHORUM[template_http_path]/$PHORUM[template]");

    ob_start();
    include phorum_get_template('settings');
    ob_end_clean();
}
// }}}

?>
