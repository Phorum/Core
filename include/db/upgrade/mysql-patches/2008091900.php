<?php

$upgrade_queries[]=
    "ALTER IGNORE TABLE {$PHORUM['message_table']}
     DROP KEY `user_id`,
     DROP KEY `recent_threads`";

$upgrade_queries[]=
    "ALTER TABLE {$PHORUM['message_table']}
     ADD KEY `recent_threads` (`status`,`parent_id`,`message_id`,`forum_id`)";

?>
