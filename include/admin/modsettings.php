<?php

    if(!defined("PHORUM_ADMIN")) return;

    $mod=$_REQUEST["mod"];

    if(file_exists("./mods/$mod/settings.php")){

        include_once("./mods/$mod/settings.php");

    } else {

        echo "There are no settings for this module.";

    }


?>