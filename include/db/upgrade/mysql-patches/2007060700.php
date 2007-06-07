<?php 

$upgrade_queries[]= 
    "ALTER TABLE {$PHORUM['user_table']}
     CHANGE COLUMN cookie_sessid_lt sessid_lt varchar(50) NOT NULL default ''";

?>
