<?php

    if(!defined("PHORUM_ADMIN")) return;

    phorum_user_clear_session("phorum_admin_session");
    phorum_redirect_by_url($_SERVER['PHP_SELF']);
    exit();

?>
