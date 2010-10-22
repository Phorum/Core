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
////////////////////////////////////////////////////////////////////////////////

// This library contains some functions that can be used for
// code benchmarking. It is not actively in use in the Phorum
// core, but its functions are used by the developers once
// in a while.

    if(!defined("PHORUM")) return;

    function timing_start($key="default")
    {
        $GLOBALS["_TIMING"][$key]["start"]=microtime();
        $GLOBALS["_MEMORY"][$key]["start"]=memory_get_usage();
    }
    
    function timing_mark($mark, $key="default")
    {
        $GLOBALS["_TIMING"][$key][$mark]=microtime();
        $GLOBALS["_MEMORY"][$key][$mark]=memory_get_usage();
    }

    function timing_print($key="default")
    {
        echo '<table border="1" cellspacing="0" cellpadding="2">';
        echo "<tr><th>Mark</th><th>Time</th><th>Elapsed</th><th>Memory delta</th><th>Memory</th></tr>";
        foreach($GLOBALS["_TIMING"][$key] as $mark => $mt){
            $thismem=$GLOBALS["_MEMORY"][$key][$mark];
            $thistime=array_sum(explode(" ", $mt));
            if(isset($lasttime)){
                $elapsed=round($thistime-$start, 4);
                $curr=round($thistime-$lasttime, 4);
                $currmem=$thismem-$lastmem;
                $totalmem=$thismem-$startmem;
                echo "<tr><td>$mark</td><td>$curr sec.</td><td>$elapsed sec.</td>";
                echo "<td>$currmem</td><td>$totalmem</td></tr>"; 


            } else {
                $start=$thistime;
                $startmem=$thismem;
            }
            $lasttime=$thistime;
            $lastmem=$thismem;
        }
        echo "</table>";
    }

?>
