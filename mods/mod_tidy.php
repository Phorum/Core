<?php

/* phorum module info
hook:  common|mod_tidy_start
title: Tidy Output
desc:  This module removes unneeded white space from Phorum's output saving bandwidth.
*/


function mod_tidy_start(){
    ob_start("mod_tidy_end");
}

function mod_tidy_end($buffer){

    $preserved_tags = array();

    if(preg_match_all("!(<pre.*?>).+?(</pre.*?>)!ms", $buffer, $matches)){
        foreach($matches[0] as $match){
            $hash = md5($match);
            $preserved_tags[$hash] = $match;
            $buffer = str_replace($match, "<".$hash.">", $buffer);
        }
    }

    if(preg_match_all("!(<xmp.*?>).+?(</xmp.*?>)!ms", $buffer, $matches)){
        foreach($matches[0] as $match){
            $hash = md5($match);
            $preserved_tags[$hash] = $match;
            $buffer = str_replace($match, "<".$hash.">", $buffer);
        }
    }

    if($buffer){
        $buffer = preg_replace("!\n[ \t]+!", "\n", $buffer);
        $buffer = preg_replace("![ \t]+!", " ", $buffer);
        $buffer = preg_replace("!\n+!", "\n", $buffer);
        $buffer = preg_replace('!\s*(</?(div|td|tr|th|table|p|ul|li|body|head|html|script|meta|select|option|iframe|h\d|br /)[^>]*>)\s*!i', "$1", $buffer);
        $buffer = trim($buffer);
    }

    if(!empty($preserved_tags)){
        foreach($preserved_tags as $hash=>$tag){
            $buffer = str_replace("<".$hash.">", $tag, $buffer);
        }
    }

    return $buffer;
}

?>
