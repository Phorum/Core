<?php
if(!defined("PHORUM_ADMIN")) return;

// upgrade (and possibly fix) the custom profile field configuration.
Phorum::API()->custom_field->checkconfig();

?>
