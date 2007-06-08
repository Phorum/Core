<?php

    if(!defined("PHORUM")) return;

    if (! isset($GLOBALS['PHORUM']["mod_username_restrictions"])) {
         $GLOBALS['PHORUM']["mod_username_restrictions"] = array();
    }
    
    $mod_username_restrictions_default = array(
        "min_length"     => 0,
        "max_length"     => 0,
        "valid_chars"    => "",
        "only_lowercase" => 0,
    );

    foreach ($mod_username_restrictions_default as $var => $default) {
        if (! isset($GLOBALS["PHORUM"]["mod_username_restrictions"][$var])) {
            $GLOBALS["PHORUM"]["mod_username_restrictions"][$var] = $default;
        }
    } 

?>
