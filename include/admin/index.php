<?php

    if(!defined("PHORUM_ADMIN")) return;
    
    phorum_redirect_by_url(phorum_get_url(PHORUM_INDEX_URL));
    exit();

?>