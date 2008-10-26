<?php

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']} drop key `list_page_flat`";

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']}
     add KEY list_page_flat (forum_id, parent_id, datestamp)";

?>
