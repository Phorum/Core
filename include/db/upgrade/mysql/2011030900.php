<?php
if (!defined("PHORUM_ADMIN")) return;

if (!empty($PHORUM['DBCONFIG']['charset'])) {
    $charset = " DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";
} else {
    $charset = "";
}

// Create new table for storing the custom field configuration
$upgrade_queries[] =
    "CREATE TABLE {$PHORUM['DB']->custom_fields_config_table} (
         id                       int unsigned   NOT NULL auto_increment,
         field_type               tinyint(1)     NOT NULL default 1,
         name                     varchar(50)    NOT NULL default '',
         length                   mediumint      NOT NULL default 255,
         html_disabled            tinyint(1)     NOT NULL default 1,
         show_in_admin            tinyint(1)     NOT NULL default 0,
         deleted                  tinyint(1)     NOT NULL default 0,

         PRIMARY KEY (id),
         UNIQUE KEY field_type_name (field_type, name)
     ) $charset";

?>
