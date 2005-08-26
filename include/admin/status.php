<?php

    if(!defined("PHORUM_ADMIN")) return;
    
    phorum_db_update_settings( array("status"=>$_POST["status"]) );
        
    header("Location: $_SERVER[PHP_SELF]");
    exit();
    
?>