<?php

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']}
        DROP KEY forum_max_message,
        DROP KEY list_page_flat,
        DROP KEY list_page_float,
        DROP KEY new_count,
        DROP KEY new_threads,
        DROP KEY next_prev_thread,
        DROP KEY recent_threads,
        DROP KEY status_forum,
        DROP KEY thread_forum,
        DROP KEY thread_message,
        ADD KEY forum_recent_messages (forum_id,status,datestamp),
        ADD KEY list_page_flat (forum_id,status,parent_id,datestamp),
        ADD KEY list_page_float (forum_id,status,parent_id,modifystamp),
        ADD KEY recent_messages (status,datestamp),
        ADD KEY recent_threads (status,parent_id,datestamp),
        ADD KEY thread_date (thread,datestamp)";

?>
