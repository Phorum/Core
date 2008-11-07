<?php 

// Find all private messages.
$res = phorum_db_interact(
    DB_RETURN_RES,
    "SELECT pm_message_id, user_id, meta
     FROM   {$PHORUM["pm_messages_table"]}"
);

// Update the meta author + rcpt info for each private message.
while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC))
{
    // Update the PM author.
    $m = $row["pm_message_id"]; 
    $u = $row["user_id"];
    $user = phorum_db_user_get($u);
    if ($user) {
        $author = phorum_db_interact(DB_RETURN_QUOTED, $user["display_name"]);
        phorum_db_interact(
           DB_RETURN_RES,
           "UPDATE {$PHORUM["pm_messages_table"]}
            SET    author = '$author'
            WHERE  user_id = $u", 
           NULL, 
           DB_MASTERQUERY 
        );
    }

    // Update the PM recipients.
    $meta = unserialize($row["meta"]);
    $userids = array_keys($meta["recipients"]);
    $users = phorum_db_user_get($userids);
    foreach ($users as $user) {
        unset($meta["recipients"][$user["user_id"]]["username"]);
        $meta["recipients"][$user["user_id"]]["display_name"] =
            $user["display_name"];
    }
    $meta = phorum_db_interact(DB_RETURN_QUOTED, serialize($meta));
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM["pm_messages_table"]}
         SET    meta = '$meta'
         WHERE  pm_message_id = $m", 
        NULL, 
        DB_MASTERQUERY 
    );
}

?>
