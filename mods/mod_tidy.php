<?php

/* phorum module info
hook:  start_output|mod_tidy_start
hook:  end_output|mod_tidy_end
title: Tidy Output
desc:  This module removes unneeded white space from Phorum's output saving bandwidth.
*/


function mod_tidy_start(){
    ob_start();
}

function mod_tidy_end(){

    $buffer = ob_get_contents();
    ob_end_clean();

    if($buffer){
        $buffer = preg_replace("!\n[ \t]+!", "\n", $buffer);
        $buffer = preg_replace("![ \t]+!", " ", $buffer);
        $buffer = preg_replace("!\n+!", "\n", $buffer);
        $buffer = preg_replace('!\s*(</?(div|td|tr|th|table|p|ul|li|body|head|html|script|meta|select|option|iframe|h\d|br /)[^>]*>)\s*!i', "$1", $buffer);
        $buffer = trim($buffer);
    }

    echo $buffer;
}

?>
