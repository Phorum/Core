<?php
if (!defined("PHORUM")) return;

$sqlqueries[]= "
  CREATE TABLE {$GLOBALS['PHORUM']['spamhurdles_table']} (
      id          char(32) not null default '',
      data        mediumtext NULL,
      create_time int(10) unsigned NOT NULL default '0',
      expire_time int(10) unsigned NOT NULL default '0',
      PRIMARY KEY (id)
  )
";

?>
