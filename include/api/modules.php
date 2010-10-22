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

/**
 * This script implements the Phorum module admin API.
 *
 * This API is used for managing Phorum modules. It can be used to retrieve
 * information about the available modules and takes care of activating
 * and deactivating them.
 *
 * @package    PhorumAPI
 * @subpackage ModulesAPI
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined("PHORUM")) return;

// {{{ Constant and variable definitions
/**
 * This array describes deprecated module hook names, which have been
 * replaced by new hook names. For backward compatibility, the mods
 * admin API will transparently rewrite old hook names to the new ones.
 */
$GLOBALS['PHORUM']['API']['mods_deprecated_hooks'] = array(
    'pre_post'         => 'before_post',
    'pre_edit'         => 'before_edit',
    'post_post'        => 'after_post',
    'user_check_login' => 'user_authenticate',
    'hide'             => 'hide_thread'
);

/**
 * This array describes modules that are no longer part of the core
 * Phorum distribution. If a module from this list is found, then
 * a check is done to see if its version is lower than the provided
 * theshold version. If yes, then the module is displayed as disabled
 * in the module information and the admin is told to download the
 * new version of the module from the Phorum.org website.
 */
$GLOBALS['PHORUM']['API']['mods_no_longer_bundled'] = array(
    'html' => array(
        'version' => '2.0.0',
        'url'     => 'http://www.phorum.org/phorum5/read.php?62,140066'
    )
);
// }}}

// {{{ Function: phorum_api_modules_list()
/**
 * Retrieve a list of all available modules.
 *
 * This function will scan the mods directory to find all available
 * modules. For each module, the module information (info.txt or
 * inline module info) is collected.
 *
 * @return array
 *     An array, containing the following fields:
 *     - modules:
 *       An array of available modules. The keys are module names and the
 *       values are arrays, containing detailed module information.
 *     - priorities:
 *       An array containing scheduling priority rules.
 *     - deprecated:
 *       An array of warnings about deprecated module hooks or an empty
 *       array if there are no deprecation warnings.
 *     - problems:
 *       An array of (HTML formatted) errors and warnings regarding module
 *       problems.
 */
function phorum_api_modules_list()
{
    global $PHORUM;

    $modules    = array();
    $priorities = array();
    $deprecated = array();
    $problems   = array();

    include_once('./include/version_functions.php');

    $dh = opendir("./mods");
    if (! $dh) trigger_error(
        "Unable to create a list of available modules: " .
        "opendir of directory \"./mods\" failed.",
        E_USER_ERROR
    );

    while ($entry = readdir($dh))
    {
        // Some entries which we skip by default.
        // ATTIC    : a directory that I (maurice) sometimes use for storing
        //            deprecated modules or for moving stuff temporarily out
        //            of the way.
        // _vti_cnf : a directory that is created by Microsoft Frontpage for
        //            storing settings (blame Azumandias for this one).
        if ($entry == '.'     || $entry == '..'        ||
            $entry == '.svn'  || $entry == '.htaccess' ||
            $entry == 'ATTIC' || $entry == '_vti_cnf' ) continue;

        // Read in the module information.
        $lines = array();
        if (file_exists("./mods/$entry/info.txt")) {
            $lines = file("./mods/$entry/info.txt");
        } elseif (is_file("./mods/$entry") && substr($entry, -4)==".php") {
            $entry = str_replace(".php", "", $entry);
            $data = file_get_contents("./mods/$entry.php");
            if($data = stristr($data, "/* phorum module info")){
                $data = substr($data, 0, strpos($data, "*/"));
                $lines = preg_split('!(\r|\n|\r\n)!', $data);
            }
        }

        // Check if we found module information.
        if (!count($lines)) {
            $problems[] =
                "Warning: possible module " .
                "\"" . htmlspecialchars($entry) . "\" found, but no " .
                "module information is available for that module.";
            continue;
        }

        // Parse the module information.
        $info = array();
        $info['version_disabled'] = false;
        foreach ($lines as $line) {

            if (strstr($line, ":")) {
                $parts = explode(":", trim($line), 2);
                if ($parts[0]=="hook"){
                    list ($hook,$function) = explode('|', trim($parts[1]));
                    if (isset($PHORUM['API']['mods_deprecated_hooks'][$hook])) {
                        $deprecated[] = "Mod " . htmlspecialchars($entry) . ": rename \"" . htmlspecialchars($hook) . "\"; to \"" . htmlspecialchars($PHORUM['API']['mods_deprecated_hooks'][$hook]) . "\"";
                        $hook = $PHORUM['API']['mods_deprecated_hooks'][$hook];
                        $parts[1] = "$hook|$function";
                    }
                    $info["hooks"][]=trim($parts[1]);
                } elseif ($parts[0]=="priority"){
                    $prio = trim($parts[1]);
                    if (preg_match('/^run\s+hook\s+(.+)\s+(before|after)\s(.+)$/i', $prio, $m)) {
                        $priorities['hook'][$m[1]][$entry][] = $m;
                    } elseif (preg_match('/^run\s+module\s+(before|after)\s(.+)$/i', $prio, $m)) {
                        $priorities['module'][$entry][] = $m;
                    } else {
                        $problems[] =
                            "Priority configuration error for module " .
                            htmlspecialchars($entry) . "<br/>" .
                            "Cannot parse priority " .
                            "\"" . htmlspecialchars($prio) . "\"<br/>";
                    }
                } elseif ($parts[0]=="required_version" ||
                          $parts[0]=="require_version") {
                    $required_ver = trim($parts[1]);
                    $phorum_ver = PHORUM;

                    $info['required_version'] = $required_ver;
                    $cur = phorum_parse_version($phorum_ver);
                    $req = phorum_parse_version($required_ver);

                    // If an admin is using a development or snapshot release,
                    // the we asume that he knows what he's doing.
                    if ($cur[0] == 'snapshot' ||
                        $cur[0] == 'development') {
                        // noop
                    }
                    // Otherwise, do a real version comparison.
                    elseif (phorum_compare_version($cur, $req) == -1) {
                        $info['version_disabled'] = true;
                    }

                } else {
                    $info[$parts[0]] = trim($parts[1]);
                }
            }
        }

        if (isset($PHORUM["mods"][$entry])) {
            $info['enabled'] = $PHORUM["mods"][$entry];
        } else {
            $info['enabled'] = 0;
        }

        if (file_exists("./mods/$entry/settings.php")){
            $info["settings"]=true;
        } else {
            $info["settings"]=false;
        }

        $modules[$entry] = $info;
    }
    closedir($dh);

    // Check if there are modules available, which are no longer
    // included in the Phorum core distribution. If yes, then check
    // if the version of those modules indicates an old bundled version
    // of the module. In that case, the module is disabled and the admin
    // is told where to get an up-to-date version of the module.
    foreach ($PHORUM['API']['mods_no_longer_bundled'] as $module => $info)
    {
        if (isset($modules[$module]) && !empty($modules[$module]['enabled']))
        {
            $modinfo = $modules[$module];
            if (empty($modinfo['version']) ||
                phorum_compare_version(
                    $modinfo['version'], // installed module's version
                    $info['version']     // required module's version
                ) == -1) {
                $modules[$module]['url'] = $info['url'];
                $problems[] = "The module \"{$modinfo['title']}\" is no longer included in the core Phorum distribution. A more recent version of this module (version {$info['version']} or higher) is available at the phorum.org website. Please download and install that version. For more information, visit <a href=\"{$info['url']}\" target=\"_new\">the module's page at phorum.org</a>.";
            } 
        }
    }

    // Sort the modules by their title, so they show up in an easy
    // to use way for the user in the admin interface.
    uasort($modules, "module_sort");

    // Store the data for other functions in this API.
    $PHORUM["API"]["mods_modules"]    = $modules;
    $PHORUM["API"]["mods_priorities"] = $priorities;

    return array(
      'modules'    => $modules,
      'priorities' => $priorities,
      'deprecated' => $deprecated,
      'problems'   => $problems
    );
}
// }}}

// {{{ Function: phorum_api_modules_enable()
/**
 * Flag a module as enabled.
 *
 * This will only flag the module as enabled. After calling this function,
 * {@link phorum_api_modules_save()} has to be called to store the
 * new module settings in the database.
 *
 * @param string $module
 *     The name of the module to enable.
 */
function phorum_api_modules_enable($module)
{
    global $PHORUM;

    // Load the module info if this was not done yet.
    if (!isset($PHORUM["API"]["mods_modules"])) {
        phorum_api_modules_list();
    }

    // Check if the module is valid.
    if (!isset($PHORUM["API"]["mods_modules"][$module])) trigger_error(
        "Unable to enable module \"$module\": no such module available.",
        E_USER_ERROR
    );

    $PHORUM["mods"][$module] = 1;
}
// }}}

// {{{ Function: phorum_api_modules_disable()
/**
 * Flag a module as disabled.
 *
 * This will only flag the module as disabled. After calling this function,
 * {@link phorum_api_modules_save()} has to be called to store the
 * new module settings in the database.
 *
 * @param string $module
 *     The name of the module to disabled.
 */
function phorum_api_modules_disable($module)
{
    global $PHORUM;

    if (isset($PHORUM["mods"][$module])) {
        $PHORUM["mods"][$module] = 0;
    }
}
// }}}

// {{{ Function: phorum_api_modules_save()
/**
 * Store the module information in the database.
 *
 * This function will sort out all module and hook priorities for the
 * enabled modules and write the result data ($PHORUM["mods"] and
 * $PHORUM["hooks"]) to the database.
 */
function phorum_api_modules_save()
{
    global $PHORUM;

    // Load the module info if this was not done yet.
    if (!isset($PHORUM["API"]["mods_modules"])) {
        phorum_api_modules_list();
    }

    // For easy access.
    $modules    = $PHORUM["API"]["mods_modules"];
    $priorities = $PHORUM["API"]["mods_priorities"];

    // Create a list of enabled modules.
    $active_mods = array();
    foreach ($PHORUM["mods"] as $mod => $enabled)
    {
        // Only add modules that were found.
        // Disable modules that do not / no longer exist.
        if (isset($modules[$mod])) {
            if ($enabled) $active_mods[] = $mod;
        } else {
            $PHORUM["mods"][$mod] = 0;
        }
    }

    // First priority ordering pass:
    // run module before|after *
    $active_mods_copy = $active_mods;
    foreach ($active_mods as $mod)
    {
        if (!isset($priorities["module"][$mod])) continue;
        $mod_priorities = $priorities["module"][$mod];

        foreach ($mod_priorities as $priority)
        {
            if ($priority[2] == "*")
            {
                // Remove the module from the list.
                $idx = array_search($mod, $active_mods_copy);
                unset($active_mods_copy[$idx]);

                // Add it do the end of start of the list.
                if ($priority[1] == "after") {
                    array_push($active_mods_copy, $mod);
                } else {
                    array_unshift($active_mods_copy, $mod);
                }
            }
        }
    }
    $active_mods = $active_mods_copy;

    // Second priority ordering pass:
    // run module before|after <othermodule>
    $active_mods_copy = array_values($active_mods);
    foreach ($active_mods as $mod)
    {
        if (!isset($priorities["module"][$mod])) continue;
        $mod_priorities = $priorities["module"][$mod];
        foreach ($mod_priorities as $priority)
        {
            if ($priority[2] == '*') continue;

            // Find the current position of the modules.
            $idx1 = array_search($mod, $active_mods_copy);
            $idx2 = array_search($priority[2], $active_mods_copy);
            if ($idx2 === false || $idx2 === NULL) continue; //NULL = pre 4.2.0

            // Move module up in the list.
            if ($idx1 > $idx2 && $priority[1] == "before") {
                unset($active_mods_copy[$idx1]);
                array_splice($active_mods_copy, $idx2, 1, array($mod, $priority[2]));
                $active_mods_copy = array_values($active_mods_copy);
            }

            // Move module down in the list.
            if ($idx1 < $idx2 && $priority[1] == "after") {
                array_splice($active_mods_copy, $idx2, 1, array($priority[2], $mod));
                unset($active_mods_copy[$idx1]);
                $active_mods_copy = array_values($active_mods_copy);
            }
        }
    }
    $active_mods = $active_mods_copy;

    # Determine what hooks to run for the activated modules.
    $modules_by_hook = array();
    $functions_by_module = array();
    foreach ($active_mods as $mod)
    {
        if (! isset($modules[$mod]["hooks"])) continue;
        foreach ($modules[$mod]["hooks"] as $hookinfo) {
            list ($hook,$func) = explode("|", $hookinfo);
            $modules_by_hook[$hook][] = $mod;
            $functions_by_module[$mod][$hook] = $func;
        }
    }

    // Third priority ordering pass:
    // run hook <hook> before|after *
    foreach ($modules_by_hook as $hook => $mods)
    {
        if (!isset($priorities["hook"][$hook])) continue;

        foreach ($active_mods as $mod)
        {
            if (!isset($priorities["hook"][$hook][$mod])) continue;

            $hook_priorities = $priorities["hook"][$hook][$mod];
            foreach ($hook_priorities as $priority)
            {
                if ($priority[3] == "*")
                {
                    // Remove the module from the list.
                    $idx = array_search($mod, $mods);
                    unset($mods[$idx]);

                    // Add it do the end of start of the list.
                    if ($priority[2] == "after") {
                        array_push($mods, $mod);
                    } else {
                        array_unshift($mods, $mod);
                    }
                }
            }
        }
        $mods = array_values($mods); // array_values reindexes
        $modules_by_hook[$hook] = $mods;
    }

    // Fourth priority ordering pass:
    // run hook <hook> before|after <othermodule>
    foreach ($modules_by_hook as $hook => $mods)
    {
        if (!isset($priorities["hook"][$hook])) continue;

        foreach ($active_mods as $mod)
        {
            if (!isset($priorities["hook"][$hook][$mod])) continue;

            $hook_priorities = $priorities["hook"][$hook][$mod];
            foreach ($hook_priorities as $priority)
            {
                if ($priority[3] == '*') continue;

                // Find the current position of the modules.
                $idx1 = array_search($mod, $mods);
                $idx2 = array_search($priority[3], $mods);
                //NULL = pre 4.2.0
                if ($idx2 === false || $idx2 === NULL) continue;

                // Move module up in the list.
                if ($idx1 > $idx2 && $priority[2] == "before") {
                    unset($mods[$idx1]);
                    array_splice($mods, $idx2, 1, array($mod, $priority[3]));
                    $mods = array_values($mods);
                }

                // Move module down in the list.
                if ($idx1 < $idx2 && $priority[2] == "after") {
                    array_splice($mods, $idx2, 1, array($priority[3], $mod));
                    unset($mods[$idx1]);
                    $mods = array_values($mods);
                }
            }
        }
        $mods = array_values($mods); // array_values reindexes
        $modules_by_hook[$hook] = array_values($mods);
    }

    // Create the hooks configuration.
    $hooks = array();
    foreach ($modules_by_hook as $hook => $mods)
    {
        $hooks[$hook] = array();
        foreach ($mods as $mod) {
            $hooks[$hook]["mods"][] = $mod;
            $hooks[$hook]["funcs"][] = $functions_by_module[$mod][$hook];
        }
    }
    $PHORUM["hooks"] = $hooks;

    // Store the settings in the database.
    phorum_db_update_settings(array(
        "hooks" => $PHORUM["hooks"],
        "mods"  => $PHORUM["mods"]
    ));

    // Reset the module information update checking data.
    phorum_api_modules_check_updated_info(TRUE);
}
// }}}

// {{{ Function: phorum_api_modules_check_updated_info()
/**
 * Check if there are modules for which the module information is updated.
 *
 * @param boolean $do_reset
 *     If this parameter has a true value, then the active status for
 *     the module information is stored in the database.
 *
 * @return array
 *     An array of module names for which the module information was updated.
 */
function phorum_api_modules_check_updated_info($do_reset = FALSE)
{
    global $PHORUM;

    $existing = empty($PHORUM['mod_info_timestamps'])
              ? array()
              : $PHORUM['mod_info_timestamps'];

    $new = array();
    $need_update = array();

    if (!empty($PHORUM['mods']))
    {
        foreach ($PHORUM['mods'] as $mod => $active)
        {
            if (!$active) continue;
            $info = "./mods/$mod/info.txt";
            $filemod = "./mods/$mod.php";
            if (file_exists($info)) {
                $time = @filemtime($info);
            } elseif (file_exists($filemod)) {
                $time = @filemtime($filemod);
            } else {
                continue;
            }

            if (!isset($existing[$mod]) ||
                $existing[$mod] != $time) {
                $need_update[] = $mod;
            }

            $new[$mod] = $time;
        }
    }

    $PHORUM['mod_info_timestamps'] = $new;

    // Store the settings in the database if a reset is requested.
    if ($do_reset) {
        phorum_db_update_settings(array(
            "mod_info_timestamps" => $new
        ));
    }

    return $need_update;
}
// }}}

// {{{ Function: module_sort()
/**
 * A small utility function which can be used to sort modules by name
 * using the uasort() command.
 */
function module_sort($a, $b) { return strcasecmp($a["title"], $b["title"]); }
// }}}

?>
