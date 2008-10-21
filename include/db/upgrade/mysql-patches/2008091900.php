<?php

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']} drop key `user_id`";

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']} drop key `recent_threads`";

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']}
     add KEY `recent_threads` (`status`,`parent_id`,`message_id`,`forum_id`)";

?>
