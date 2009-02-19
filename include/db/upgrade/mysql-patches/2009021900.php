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
        DROP KEY thread_message";

?>
