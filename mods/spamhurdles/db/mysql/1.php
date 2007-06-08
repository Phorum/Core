<?php
if (!defined("PHORUM")) return;

$sqlqueries[]= "
  CREATE TABLE ".SPAMHURDLES_TABLE." (
      id          char(32) not null default '',
      data        mediumtext NOT NULL default '',
      create_time int(10) unsigned NOT NULL default '0',
      expire_time int(10) unsigned NOT NULL default '0',
      PRIMARY KEY (id)
  )
";

?>
