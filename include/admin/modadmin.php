<?php

    if(!defined("PHORUM_ADMIN")) return;

    if(file_exists()){

        $mod=$_REQUEST["mod"];

        include_once("./mods/$mod/admin.php");

    } else {

        exit();

    }


?>