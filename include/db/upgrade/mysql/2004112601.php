<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]="
    CREATE TABLE {$PHORUM['DBCONFIG']['table_prefix']}_user_custom_fields (
        user_id INT DEFAULT '0' NOT NULL ,
        type INT DEFAULT '0' NOT NULL ,
        data TEXT NOT NULL ,
        PRIMARY KEY ( user_id , type )
    )";
?>
