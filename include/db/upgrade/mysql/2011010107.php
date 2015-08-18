<?php

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['forums_table']}
     ADD INDEX vroot_list ( active , vroot , display_order , name );";

?>
