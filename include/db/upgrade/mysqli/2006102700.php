<?php
if(!defined("PHORUM_ADMIN")) return;

$upgrade_queries[]= "delete from {$PHORUM["user_newflags_table"]} using {$PHORUM["user_newflags_table"]}, {$PHORUM["message_table"]} where {$PHORUM["user_newflags_table"]}.message_id={$PHORUM["message_table"]}.message_id and {$PHORUM["user_newflags_table"]}.forum_id<>{$PHORUM["message_table"]}.forum_id";
$upgrade_queries[]= "UPDATE IGNORE {$PHORUM["message_table"]} as msg, {$PHORUM["message_table"]} as thd SET msg.forum_id=thd.forum_id where msg.thread=thd.message_id and msg.forum_id<>thd.forum_id";

?>
