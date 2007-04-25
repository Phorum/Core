<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]="alter ignore table {$PHORUM['user_table']} drop user_data";

?>
