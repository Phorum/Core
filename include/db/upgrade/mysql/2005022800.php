<?php
if(!defined("PHORUM_ADMIN")) return;

if(!empty($PHORUM['DBCONFIG']['charset'])) {
    $charset = " DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";
} else {
    $charset = "";
}

$upgrade_queries[]="CREATE TABLE {$PHORUM['search_table']} ( message_id int(10) unsigned NOT NULL default '0', search_text mediumtext NOT NULL, PRIMARY KEY  (message_id), FULLTEXT KEY search_text (search_text) ) TYPE=MyISAM $charset";
$upgrade_queries[]="insert into {$PHORUM['search_table']} select message_id, concat(author, ' | ', subject, ' | ', body) from {$PHORUM['message_table']}";

?>
