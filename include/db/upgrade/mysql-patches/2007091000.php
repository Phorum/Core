<?php

$upgrade_queries[]= "alter table {$PHORUM['message_table']} drop key post_count, add key new_threads (forum_id, status, parent_id, moved, message_id)";

?>
