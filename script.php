<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

define('phorum_page','script');
define('PHORUM_SCRIPT', 1);

chdir(dirname(__FILE__));
include_once("./common.php");

// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
    echo $PHORUM["DATA"]["LANG"]["CannotBeRunFromBrowser"];
    return;
}

// ----------------------------------------------------------------------
// Parse the command line arguments.
// ----------------------------------------------------------------------

$modules   = array();
$callhook  = NULL;
$callargs  = array();

$args = $_SERVER["argv"];
array_shift($args);

while (count($args))
{
    $arg = array_shift($args);

    if (preg_match('/^--module=(.+)$/', $arg, $m)) {
        if ($callhook === NULL) $callhook = 'external';
        $modules[$m[1]] = $m[1];
        continue;
    }

    if ($arg == '-m') {
        if (count($arg)) {
             if ($callhook === NULL) $callhook = 'external';
             $mod = array_shift($args);
             $modules[$mod] = $mod;
             continue;
        } else trigger_error(
            "Missing argument for the -m option.\n"
        );
    }

    if ($arg == '--scheduled' || $arg == '-s') {
        $callhook = 'scheduled';
        continue;
    }

    $callargs[] = $arg;
}

// At least one of --module or --scheduled is required.
// Additionally, exactly one module name is required for "external" mode.
if ($callhook === NULL || ($callhook == 'external' and count($modules) != 1)) {
    echo $GLOBALS["PHORUM"]["DATA"]["LANG"]["ScriptUsage"];
    exit(1);
}

// ----------------------------------------------------------------------
// Filter hooks to only keep the requested external or scheduled hook(s).
// ----------------------------------------------------------------------

if (count($modules))
{
    $process  = $modules;
    $filtered = NULL;

    foreach ($PHORUM['hooks'][$callhook]['mods'] as $id => $mod) {
        if (!empty($process[$mod])) {
            $filtered = array(
                'mods'  => array( $mod ),
                'funcs' => array( $PHORUM['hooks'][$callhook]['funcs'][$id] )
            );
            unset($process[$mod]);
            break;
        }
    }

    $PHORUM['hooks'][$callhook] = $filtered;

    // If there are modules left in the list, it means that we could not
    // find a registered external/scheduled hook for them.
    if (count($process)) {
        $mod = array_shift($process);
        if (empty($PHORUM['mods'][$mod])) trigger_error(
            "Requested module \"$mod\" does not exist or is not enabled.",
            E_USER_ERROR
        );
        trigger_error(
            "Requested module \"$mod\" does not implement hook \"$callhook\".",
            E_USER_ERROR
        );
    }
}

// ----------------------------------------------------------------------
// Run the "external" hook for a module.
// ----------------------------------------------------------------------

if ($callhook == 'external')
{
    $module = array_shift($modules);

    // The first argument in $callargs is set to the name of the
    // called module. This module name is not really needed, but it
    // in there for backward compatibility (in older code, all "external"
    // hooks were called and the external hook implementation had to check
    // the module name to see if it had to be run or not).
    array_unshift($callargs, $module);
    $callargs = array_values($callargs); // reindex (0, 1, 2, ...) array keys.

    // Call the external hook.
    phorum_hook("external", $callargs);
}

// ----------------------------------------------------------------------
// Run the "scheduled" hook for all modules.
// ----------------------------------------------------------------------

elseif ($callhook == 'scheduled')
{
    phorum_hook('scheduled');
}

// ----------------------------------------------------------------------
// The command is not recognized. Show the usage message.
// ----------------------------------------------------------------------

else {
    echo $GLOBALS["PHORUM"]["DATA"]["LANG"]["ScriptUsage"];
    exit(1);
}

?>
