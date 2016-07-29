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
 * This script implements development tools. These are generally not actively
 * used in the Phorum core, but they are used by developers during
 * development.
 *
 * @package    PhorumAPI
 * @subpackage Development
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_dev_profiler_start()
/**
 * Start a profiling run.
 *
 * @param string $key
 *     Optional: a key name for the profile run. Using this parameter,
 *     multiple profiling runs can coexist. The default key is "default".
 */
function phorum_api_dev_profiler_start($key="default")
{
    global $PHORUM_PROFILER;
    $PHORUM_PROFILER['time'][$key] = array(
        'BEGIN' => microtime(TRUE)
    );
    $PHORUM_PROFILER['mem'][$key] = array(
        'BEGIN' => memory_get_usage()
    );
}
// }}}

// {{{ Function: phorum_api_dev_profiler_mark()
/**
 * Place a profiling marker.
 *
 * When doing this, the time and memory usage are recorded with an
 * arbitrary string as the description.
 * When no profiling run is started for the $key, then this function
 * will implicitly call phorum_api_dev_profiler_start().
 *
 * @param string $mark
 *     A name for the profiling marker.
 *
 * @param string $key
 *     Optional: the key name for the profiler run.
 *     The default key is "default".
 */
function phorum_api_dev_profiler_mark($mark, $key = 'default')
{
    global $PHORUM_PROFILER;
    if (empty($PHORUM_PROFILER['time'][$key])) {
        phorum_api_dev_profiler_start($key);
    }
    $PHORUM_PROFILER['time'][$key][$mark] = microtime(TRUE);
    $PHORUM_PROFILER['mem'][$key][$mark] = memory_get_usage();
}
// }}}

// {{{ Function: phorum_api_dev_profiler_print()
/**
 * Print out (html) information about a profiling run.
 *
 * @param string $key
 *     Optional: the key name for the profiler run.
 *     The default key is "default".
 */
function phorum_api_dev_profiler_print($key = 'default')
{
    global $PHORUM_PROFILER;

    phorum_api_dev_profiler_mark('END');

    print
       '<table border="1" cellspacing="0" cellpadding="2">
        <tr>
          <th>Mark</th>
          <th>Time</th>
          <th>Elapsed</th>
          <th>Memory delta</th>
          <th>Memory</th>
        </tr>';

    foreach ($PHORUM_PROFILER['time'][$key] as $mark => $thistime)
    {
        $mark = htmlspecialchars($mark);
        $thismem = $PHORUM_PROFILER['mem'][$key][$mark];
        if (isset($lasttime))
        {
            $elapsed  = round($thistime-$start, 4);
            $curr     = round($thistime-$lasttime, 4);
            $currmem  = $thismem - $lastmem;
            $totalmem = $thismem - $startmem;
        } else {
            $elapsed  = 0;
            $curr     = 0;
            $currmem  = 0;
            $totalmem = 0;
            $start    = $thistime;
            $startmem = $thismem;
        }

        print
            "<tr>
               <td>$mark</td>
               <td>$curr sec.</td>
               <td>$elapsed sec.</td>
               <td>$currmem</td>
               <td>$totalmem</td>
             </tr>";

        $lasttime = $thistime;
        $lastmem = $thismem;
    }
    print '</table>';
}
// }}}

// {{{ Function: phorum_api_dev_dump()
/**
 * Dump the contents of a variable on screen.
 * This is primarily a debugging tool.
 *
 * @param mixed $var
 *     The variable to dump on screen.
 *
 * @param boolean $admin_only
 *     If TRUE (default), the the dump is only done if the active Phorum
 *     user is an administrator. Otherwise, all users will see the dump.
 */
function phorum_api_dev_dump($var, $admin_only = TRUE)
{
    global $PHORUM;

    if ($admin_only && ! $PHORUM["user"]["admin"]) return;

    if (PHP_SAPI != "cli") print "<pre>";

    print "\n";
    print "type: " . gettype($var) . "\n";
    print "value: ";
    $val = print_r($var, TRUE);
    $formatted = trim(str_replace("\n", "\n       ", $val));

    if (PHP_SAPI == "cli") {
        print $formatted;
    } else {
        print htmlspecialchars($formatted);
    }

    if (PHP_SAPI != "cli") print "\n</pre>";

    print "\n";
}
// }}}

?>
