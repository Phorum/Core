<?php
if (!defined("PHORUM")) return;

$sqlqueries[]= "
  CREATE TABLE ".SPAMHURDLES_TABLE." (
      id          varchar(32) not null default '',
      data        text NOT NULL default '',
      create_time integer NOT NULL default '0',
      expire_time integer NOT NULL default '0',
      PRIMARY KEY (id)
  )
";

?>
