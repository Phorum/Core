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
 * This script implements timing and memory profiling tools. These are
 * not actively used in the Phorum core, but they are used by developers
 * for profiling the Phorum code.
 *
 * @package    PhorumAPI
 * @subpackage Development
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Function: phorum_api_profiler_start()
/**
 * Start a profiling run.
 *
 * @param string $key
 *     Optional: a key name for the profile run. Using this parameter,
 *     multiple profiling runs can coexist. The default key is "default".
 */
function phorum_api_profiler_start($key="default")
{
    global $PHORUM;
    $PHORUM['API']['profiler_t'][$key] = array(
        'BEGIN' => microtime(TRUE)
    );
    $PHORUM['API']['profiler_m'][$key] = array(
        'BEGIN' => memory_get_usage()
    );
}
// }}}

// {{{ Function: phorum_api_profiler_mark()
/**
 * Place a profiling marker.
 *
 * When doing this, the time and memory usage are recorded with an
 * arbitrary string as the description.
 * When no profiling run is started for the $key, then this function
 * will implicitly call phorum_api_profiler_start().
 *
 * @param string $mark
 *     A name for the profiling marker.
 *
 * @param string $key
 *     Optional: the key name for the profiler run.
 *     The default key is "default".
 */
function phorum_api_profiler_mark($mark, $key = 'default')
{
    global $PHORUM;
    if (empty($PHORUM['API']['profiler_t'][$key])) {
        phorum_api_profiler_start($key);
    }
    $PHORUM['API']['profiler_t'][$key][$mark] = microtime(TRUE);
    $PHORUM['API']['profiler_m'][$key][$mark] = memory_get_usage();
}
// }}}

// {{{ Function: phorum_api_profiler_print()
/**
 * Print out (html) information about a profiling run.
 *
 * @param string $key
 *     Optional: the key name for the profiler run.
 *     The default key is "default".
 */
function phorum_api_profiler_print($key = 'default')
{
    global $PHORUM;

    phorum_api_profiler_mark('END');

    print
       '<table border="1" cellspacing="0" cellpadding="2">
        <tr>
          <th>Mark</th>
          <th>Time</th>
          <th>Elapsed</th>
          <th>Memory delta</th>
          <th>Memory</th>
        </tr>';

    foreach ($PHORUM['API']['profiler_t'][$key] as $mark => $thistime)
    {
        $mark = htmlspecialchars($mark);
        $thismem = $PHORUM['API']['profiler_m'][$key][$mark];
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

?>
