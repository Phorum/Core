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
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

define('phorum_page','script');
define('PHORUM_SCRIPT', 1);

chdir(dirname(__FILE__));
include_once("./common.php");

// if we are running in the webserver, bail out
if ('cli' != php_sapi_name()) {
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
/*
 * [hook]
 *     external
 *
 * [description]
 *     The external hook functions are never called from any of the standard
 *     Phorum pages. These functions are called by invoking 
 *     <filename>script.php</filename> on the command line with the 
 *     <literal>--module</literal> parameter. This can be used to pipe output
 *     from some arbitrary command to a specific module, which can do something
 *     with that input. If your module does not need any command line input and
 *     is meant to be run on a regular basis, you should consider using the
 *     <hook>scheduled</hook> hook.<sbr/>
 *     <sbr/>
 *     Mind that for using an <hook>external</hook> hook, the module in which it
 *     is handled must be enabled in your admin interface. So if an 
 *     <hook>external</hook> hook is not running, the containing module might be
 *     disabled.<sbr/>
 *     <sbr/>
 *     To run this hook from the command line, you have to be in the Phorum
 *     installation directory. So running the <hook>external</hook> hook of
 *     a module named <literal>external_foo</literal> would be done like this on
 *     a UNIX system prompt:
 *     <hookcode>
 *         # cd /your/phorum/dir
 *         # php ./script.php --module=external_foo
 *     </hookcode>
 *     For easy use, you can of course put these commands in a script file.
 *
 * [category]
 *     Miscellaneous
 *
 * [when]
 *     In the <filename>script.php</filename> when called from the command
 *     prompt or a script file.
 *
 * [input]
 *     Any array of arguments. (Optional)
 *
 * [output]
 *     Same as input.
 *
 */
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
/*
 * [hook]
 *     scheduled
 *
 * [description]
 *     <hook>scheduled</hook> hook functions are similar to
 *     <hook>external</hook> ones, except these functions do not require any
 *     input from the command line. The modules containing this hook are invoked
 *     by running <filename>script.php</filename> with the
 *     <literal>--scheduled</literal> argument (no module name is taken; this
 *     argument will run all scheduled hooks for all available modules).<sbr/>
 *     <sbr/>
 *     Like the name of the hook already suggests, this hook can be used for
 *     creating tasks which have to be executed on a regular basis. To archieve
 *     this, you can let <filename>script.php</filename> run from a scheduling
 *     service (like a cron job on a UNIX system).<sbr/>
 *     <sbr/>
 *     In general, <hook>scheduled</hook> hooks are used for automating tasks
 *     you want to execute without having to perform any manual action.
 *     Practical uses for a scheduled hook could be:
 *     <ul>
 *     <li>housekeeping (cleanup of stale/old data)</li>
 *     <li>daily content generation (like sending daily digests containing all
 *     posted messages for that day)</li>
 *     <li>forum statistics generation</li>
 *     </ul>
 *     Keep in mind that for using this hook, the module in which it is handled
 *     must be enabled in your admin interface. So if this hook is not running, 
 *     the containing module might be disabled.<sbr/>
 *     <sbr/>
 *     To run this hook from the command line or from a scheduling service, you
 *     have to be in the Phorum installation directory. So running this hook for
 *     your Phorum installation would be done like this on a UNIX system prompt:
 *     <hookcode>
 *     # cd /your/phorum/dir
 *     # php ./script.php --scheduled
 *     </hookcode>
 *     When creating a scheduling service entry for running this automatically,
 *     remember to change the directory as well. You might also have to use the
 *     full path to your PHP binary (<filename>/usr/bin/php</filename> or
 *     whatever it is on your system), because the scheduling service might not
 *     know the path to it. An entry for the cron system on UNIX could look like
 *     this:
 *     <hookcode>
 *     0 0 * * * cd /your/phorum/dir && /usr/bin/php ./script.php --scheduled
 *     </hookcode>
 *     Please refer to your system's documentation to see how to use your
 *     system's scheduling service.
 *
 * [category]
 *     Miscellaneous
 *
 * [when]
 *     In the <filename>script.php</filename> when called from the command
 *     prompt or a script file with the <literal>--scheduled</literal> argument.
 *
 * [input]
 *     None
 *
 * [output]
 *     None
 *
 */
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
