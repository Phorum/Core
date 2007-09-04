<?php 

$upgrade_queries[]= 
    "CREATE INDEX user_id ON {$PHORUM['pm_messages_table']} (user_id)";

?>
