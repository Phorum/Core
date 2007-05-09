<?php 

$upgrade_queries[]= 
    "ALTER TABLE {$PHORUM['pm_messages_table']}
     CHANGE COLUMN from_user_id user_id int unsigned NOT NULL default '0',
     CHANGE COLUMN from_username author varchar(255) NOT NULL default ''";

?>
