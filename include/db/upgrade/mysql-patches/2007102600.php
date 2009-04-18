<?php

// Find orphaned messages in newflags
$res = phorum_db_interact(
    DB_RETURN_RES,
    "SELECT    distinct {$PHORUM['user_newflags_table']}.message_id
     FROM      {$PHORUM['user_newflags_table']}
     LEFT JOIN {$PHORUM['message_table']} using (message_id)
     WHERE     {$PHORUM['message_table']}.message_id is null"
);

$ids = array();

// delete rows from newflags table that have not matching messages row
$done = false;
while (!$done) {

    if($row = phorum_db_fetch_row($res, DB_RETURN_ROW)){
        $ids[] = $row[0];
    } else {
        $done = true;
    }

    if(count($ids) > 1000 || ($done && count($ids) > 0)){
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE
             FROM      {$PHORUM['user_newflags_table']}
             WHERE     message_id in (".implode(",", $ids).")", 
            NULL, 
            DB_MASTERQUERY 
        );
        $ids = array();
    }

}

// fix messages in newflags table with the wrong forum_id
$upgrade_queries[]= "delete
                     from {$PHORUM["user_newflags_table"]}
                     using {$PHORUM["user_newflags_table"]},
                           {$PHORUM["message_table"]}
                     where
                        {$PHORUM["user_newflags_table"]}.message_id={$PHORUM["message_table"]}.message_id
                        and {$PHORUM["user_newflags_table"]}.forum_id<>{$PHORUM["message_table"]}.forum_id";

// fix messages in the messages table with the wrong
// forum_id except move notices
$upgrade_queries[]= "UPDATE IGNORE
                     {$PHORUM["message_table"]} as msg,
                     {$PHORUM["message_table"]} as thd
                     SET msg.forum_id=thd.forum_id
                     where msg.thread=thd.message_id and
                           msg.forum_id<>thd.forum_id
                           and msg.moved=0";



?>
