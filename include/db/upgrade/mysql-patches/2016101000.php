<?php
if (!defined('PHORUM_ADMIN')) return;

$upgrade_queries[]="alter table {$PHORUM['user_table']} add column force_password_change tinyint(1) not null default '0';";

?>
