<?php

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['user_table']} 
        DROP INDEX active, 
        ADD INDEX active(active, admin);";

?>
