<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]="insert into {$PHORUM['settings_table']} set name='reply_on_read_page', type='V', data='1'";

?>
