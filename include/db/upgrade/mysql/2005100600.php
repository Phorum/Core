<?php

if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]= "alter table {$PHORUM["users_table"]} add sessid_st_timeout int unsigned not null default 0";

$upgrade_queries[]= "insert into {$PHORUM["settings_table"]} set name='short_session_timeout', type='V', '60'";

$upgrade_queries[]= "insert into {$PHORUM["settings_table"]} set name='tight_security', type='V', '0'";

$upgrade_queries[]= "insert into {$PHORUM["settings_table"]} set name='admin_session_salt', type='V', '".microtime()."'";


?>
