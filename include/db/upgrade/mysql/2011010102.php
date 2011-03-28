<?php
if(!defined("PHORUM_ADMIN")) return;


$upgrade_queries[]="DROP TABLE {$PHORUM['DBCONFIG']['table_prefix']}_user_custom_fields";


?>
