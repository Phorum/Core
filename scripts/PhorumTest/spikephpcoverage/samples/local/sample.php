<?php
/*
 *  $Id: sample.php 16428 2005-04-15 16:37:04Z npac $
 *  
 *  Copyright(c) 2004-2005, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Source License version 2.1
 *  (See http://www.spikesource.com/license.html)
 */
?>
<?php

    function a($a) {
        echo $a * 2.5;
    }

    function b($count) {
        for ($i = 0; $i < $count; $i++) {
            a($i + 0.17);
        }
    }

    b(6);
    b(10);

?>
