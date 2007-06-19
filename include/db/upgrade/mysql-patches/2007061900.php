<?php
if(!defined("PHORUM_ADMIN")) return;

// upgrade (and possibly fix) the custom profile field configuration.
include_once('./include/api/custom_profile_fields.php');
phorum_api_custom_profile_field_checkconfig();

?>
