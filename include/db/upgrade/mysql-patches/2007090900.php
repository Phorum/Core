<?php

$upgrade_queries[]= "alter table {$PHORUM['message_table']} add `moved` tinyint(1) NOT NULL default '0'";

?>
