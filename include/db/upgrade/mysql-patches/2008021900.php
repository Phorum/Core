<?php

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['banlist_table']}
     ADD COLUMN comments TEXT NOT NULL";

?>
