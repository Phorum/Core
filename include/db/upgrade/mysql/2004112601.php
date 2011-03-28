<?php
if(!defined("PHORUM_ADMIN")) return;

if(!empty($PHORUM['DBCONFIG']['charset'])) {
    $charset = " DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";
} else {
    $charset = "";
}

$upgrade_queries[]="
    CREATE TABLE {$PHORUM['DBCONFIG']['table_prefix']}_user_custom_fields (
        user_id INT DEFAULT '0' NOT NULL ,
        type INT DEFAULT '0' NOT NULL ,
        data TEXT NOT NULL ,
        PRIMARY KEY ( user_id , type )
    ) $charset";
?>
