<?php
if(!defined("PHORUM_ADMIN")) return;

// This key needs to be rebuilt after we changed the author field
// length because of UTF-8 issues.
$upgrade_queries[] =
    "ALTER IGNORE TABLE {$PHORUM['message_table']}
     DROP KEY dup_check";

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['message_table']}
     CHANGE COLUMN author author varchar(255) NOT NULL default '',
     ADD COLUMN recent_message_id integer unsigned NOT NULL default '0',
     ADD COLUMN recent_user_id integer unsigned NOT NULL default '0',
     ADD COLUMN recent_author varchar(255) NOT NULL default ''";

$upgrade_queries[] =
    "ALTER TABLE {$PHORUM['message_table']}
     ADD KEY `dup_check` (`forum_id`, author(50), `subject`,`datestamp`)";

?>
