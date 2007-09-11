<?php

$upgrade_queries[]= "alter table {$PHORUM['message_table']} add key new_count (forum_id, status, moved, message_id)";

?>
