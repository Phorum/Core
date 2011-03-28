<?php
if(!defined("PHORUM_ADMIN")) return;

// upgrade (and possibly fix) the custom profile field configuration.
require_once PHORUM_PATH.'/include/api/custom_field.php';
phorum_api_custom_field_checkconfig();

?>
