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
 * This script implements the Phorum language handling API.
 *
 * @package    PhorumAPI
 * @subpackage Language
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_lang_list()
/**
 * Retrieve a list of all available languages.
 *
 * This function will scan the include/lang directory to find all available
 * languages.
 *
 * @param bool $include_hidden
 *     Whether or not to include hidden languages in the language list.
 *     Languages can be hidden by setting the variable $language_hide
 *     to a true value in the language PHP file.
 *
 * @return array
 *     An array of languages. The keys in the array are the language
 *     id's by which they are referenced internally. The values contain
 *     the names of the languages.
 */
function phorum_api_lang_list($include_hidden = FALSE)
{
    // To make some language-files happy which are using $PHORUM-variables.
    // We don't make this really global, otherwise the included language
    // file would override the active Phorum language.
    $PHORUM = $GLOBALS['PHORUM'];

    $languages = array();

    $dh = opendir(PHORUM_PATH.'/include/lang');
    while ($entry = readdir($dh))
    {
        $file = PHORUM_PATH.'/include/lang/'.$entry;
        if (is_file($file) && substr($file, -4) == ".php")
        {
            $language_hide = FALSE;
            $language = '';

            // Surround including the language file by output buffering,
            // to eat possible extra output like UTF-8 BOM and whitespace
            // outside PHP tags.
            ob_start();
            include $file;
            ob_end_clean();

            if (!$language) trigger_error(
                "phorum_api_lang_list(): Language file include/lang/" .
                htmlspecialchars($entry) . " does not set the \$language " .
                "variable.",
                E_USER_ERROR
            );

            if ($include_hidden || empty($language_hide)) {
                $lang_id = substr($entry, 0, -4);
                $languages[$lang_id] = $language;
            }
        }
    }
    closedir($dh);

    asort($languages, SORT_STRING);

    return $languages;
}
// }}}

?>
