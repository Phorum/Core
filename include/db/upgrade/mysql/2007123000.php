<?php
if(!defined("PHORUM_ADMIN")) return;

// upgrade (and possibly fix) the custom profile field configuration.
require_once('./include/api/custom_fields.php');
phorum_api_custom_field_checkconfig();

?>
