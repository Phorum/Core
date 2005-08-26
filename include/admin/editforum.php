<?php

    if(!defined("PHORUM_ADMIN")) return;

    define("PHORUM_EDIT_FORUM", 1);

    if(empty($_REQUEST["forum_id"])){
        phorum_admin_error("forum_id not set");
    } else {
        include "./include/admin/newforum.php";
    }

?>