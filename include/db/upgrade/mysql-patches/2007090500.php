<?php 

$upgrade_queries[]= 
    "CREATE INDEX user_id_link ON {$PHORUM['files_table']} (user_id, link)";

?>
