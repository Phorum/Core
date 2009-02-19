<?php

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']}
        ADD KEY forum_recent_messages (forum_id,status,datestamp),
        ADD KEY list_page_flat (forum_id,status,parent_id,datestamp),
        ADD KEY list_page_float (forum_id,status,parent_id,modifystamp),
        ADD KEY recent_messages (status,datestamp),
        ADD KEY recent_threads (status,parent_id,datestamp),
        ADD KEY thread_date (thread,datestamp)";

?>
