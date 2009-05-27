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

?>
