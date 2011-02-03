<?php

$upgrade_queries[]=
    " ALTER TABLE {$PHORUM['user_table']} CHANGE `tz_offset`
     `tz_offset` FLOAT( 4, 2 ) NOT NULL DEFAULT '-99.00' ";

?>
