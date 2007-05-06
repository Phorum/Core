<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[] =
    "CREATE INDEX recent_user_id 
     ON {$PHORUM['message_table']} (recent_user_id)";

?>
