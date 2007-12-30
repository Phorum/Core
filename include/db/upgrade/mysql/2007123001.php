<?php
if(!defined("PHORUM_ADMIN")) return;

require_once('./include/api/custom_fields.php');

if(!empty($PHORUM['DBCONFIG']['charset'])) {
    $charset_str = " DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";
} else {
    $charset_str = "";
}

$upgrade_queries[]="
      CREATE TABLE {$PHORUM['custom_fields_table']} (
           relation_id              int unsigned   NOT NULL default '0',
           field_type               tinyint(1)     NOT NULL default '1',
           type                     int unsigned   NOT NULL default '0',
           data                     text           NOT NULL,

           PRIMARY KEY (relation_id, field_type, type)
       )$charset_str
       SELECT user_id as relation_id,".PHORUM_CUSTOM_FIELD_USER.",type,data
       FROM {$PHORUM['DBCONFIG']['table_prefix']}_user_custom_fields";


?>
