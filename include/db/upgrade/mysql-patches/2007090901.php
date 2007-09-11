<?php

$upgrade_queries[]= "update {$PHORUM['message_table']} set moved=1 where parent_id=0 and thread!=message_id";

?>
