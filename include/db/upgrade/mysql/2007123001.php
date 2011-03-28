<?php
if(!defined("PHORUM_ADMIN")) return;

if(!empty($PHORUM['DBCONFIG']['charset'])) {
    $charset = " DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";
} else {
    $charset = "";
}

$upgrade_queries[]="
      CREATE TABLE {$PHORUM['custom_fields_table']} (
           relation_id              int unsigned   NOT NULL default '0',
           field_type               tinyint(1)     NOT NULL default '1',
           type                     int unsigned   NOT NULL default '0',
           data                     text           NOT NULL,

           PRIMARY KEY (relation_id, field_type, type)
       ) $charset
       SELECT user_id as relation_id,
              ".PHORUM_CUSTOM_FIELD_USER." as field_type,
              type,
              data
       FROM   {$PHORUM['DBCONFIG']['table_prefix']}_user_custom_fields";


?>
