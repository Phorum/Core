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
 * This script implements the Phorum hook system.
 *
 * This API is used for calling Phorum hooks.
 *
 * @package    PhorumAPI
 * @subpackage Modules
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_hook()
/**
 * Call a hook in the Phorum modules.
 *
 * This function will check what modules do implement the requested
 * hook. Those modules are loaded and the module hook functions are called.
 *
 * @param string $hook
 *     The name of the hook.
 *
 * @param mixed
 *     Extra arguments that will be passed on to the hook functions.
 */
function phorum_api_hook($hook)
{
    global $PHORUM;

    // Keep track of modules that we have already loaded at
    // earlier calls to the phorum_api_hook() function.
    static $load_cache = array();

    // Retrieve the arguments that were passed to the function.
    $args = func_get_args();

    // Shift off the hook name.
    array_shift($args);

    if (!empty($PHORUM['hooks'][$hook]))
    {
        // Load the modules for this hook.
        foreach ($PHORUM['hooks'][$hook]['mods'] as $mod)
        {
            $mod = basename($mod);

            // Check if the module file is not yet loaded.
            if (isset($load_cache[$mod])) continue;
            $load_cache[$mod] = 1;

            // Load the module file.
            if (file_exists(PHORUM_PATH."/mods/$mod/$mod.php")) {
                require_once PHORUM_PATH."/mods/$mod/$mod.php";
            } elseif (file_exists(PHORUM_PATH."/mods/$mod.php")) {
                require_once PHORUM_PATH."/mods/$mod.php";
            }

            // Load the module database layer file.
            if (!empty($PHORUM['moddblayers'][$mod])) {
                $type = $PHORUM['DBCONFIG']['type'];
                $file = PHORUM_PATH."/mods/$mod/db/$type.php";
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        }

        $called = array();
        foreach ($PHORUM["hooks"][$hook]["funcs"] as $func)
        {
            // Do not call a function twice (in case it is configured twice
            // for the same hook in the module info).
            if (isset($called[$func])) continue;
            $called[$func] = TRUE;

            // Call the hook functions for this hook.
            if (function_exists($func)) {
                if (count($args)) {
                    $args[0] = call_user_func_array($func, $args);
                } else {
                    call_user_func($func);
                }
            }
        }
    }

    if (isset($args[0])) {
        return $args[0];
    }
}
// }}}

?>
