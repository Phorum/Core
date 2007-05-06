<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['message_table']}
     CHANGE COLUMN author author varchar(255) NOT NULL default '',
     ADD COLUMN recent_message_id integer unsigned NOT NULL default '0',
     ADD COLUMN recent_user_id integer unsigned NOT NULL default '0',
     ADD COLUMN recent_author varchar(255) NOT NULL default ''"

?>
