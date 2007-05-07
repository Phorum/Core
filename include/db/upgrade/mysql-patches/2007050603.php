<?php 

$upgrade_queries[]= 
    "INSERT INTO {$PHORUM["settings_table"]}
     SET name = 'display_name_source',
         type = 'V',
         data = 'username'";

?>
