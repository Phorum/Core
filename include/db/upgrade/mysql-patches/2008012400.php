<?php 

$upgrade_queries[]= 
    "CREATE INDEX recent_threads 
     ON {$PHORUM['message_table']} (status, parent_id, message_id)";

?>
