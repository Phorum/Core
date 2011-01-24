<?php

require_once PHORUM_PATH.'/include/api/thread.php';

if (! ini_get('safe_mode')) {
    set_time_limit(0);
    ini_set("memory_limit","64M");
}

// Find all threads.
$res = $PHORUM['DB']->interact(
    DB_RETURN_RES,
    "SELECT message_id, forum_id
     FROM   {$PHORUM["message_table"]}
     WHERE  parent_id = 0 AND
            message_id = thread"
);

// Update the thread info for each thread.
while ($row = $PHORUM['DB']->fetch_row($res, DB_RETURN_ROW)) {
    $GLOBALS["PHORUM"]["forum_id"] = $row[1];
    phorum_api_thread_update_metadata($row[0]);
}

?>
