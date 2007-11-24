<?php

$upgrade_queries[]=
    "CREATE INDEX user_messages ON {$PHORUM['message_table']} (user_id, message_id)";

?>
