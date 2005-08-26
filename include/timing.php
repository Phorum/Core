<?php

    if(!defined("PHORUM")) return;

    function timing_start($key="default")
    {
        $GLOBALS["_TIMING"][$key]["start"]=microtime();
    }
    
    function timing_mark($mark, $key="default")
    {
        $GLOBALS["_TIMING"][$key][$mark]=microtime();
    }

    function timing_print($key="default")
    {
        echo '<table border="1" cellspacing="0" cellpadding="2">';
        echo "<tr><th>Mark</th><th>Time</th><th>Elapsed</th></tr>";
        foreach($GLOBALS["_TIMING"][$key] as $mark => $mt){
            $thistime=array_sum(explode(" ", $mt));
            if(isset($lasttime)){
                $elapsed=round($thistime-$start, 4);
                $curr=round($thistime-$lasttime, 4);
                echo "<tr><td>$mark</td><td>$curr sec.</td><td>$elapsed sec.</td></tr>"; 


            } else {
                $start=$thistime;
            }
            $lasttime=$thistime;
        }
        echo "</table>";
    }

?>