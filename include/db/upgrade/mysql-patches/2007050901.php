<?php 

// Find all private messages.
print "Start select of private messages\n";
$res = phorum_db_interact(
    DB_RETURN_RES,
    "SELECT pm_message_id, user_id, meta
     FROM   {$PHORUM["pm_messages_table"]}"
);

// Update the meta author + rcpt info for each private message.
print "Start loop\n";
while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC))
{
    // Update the PM author.
    $m = $row["pm_message_id"]; 
    $u = $row["user_id"];
    print "Handle message $m for author id $u\n";
    $user = phorum_db_user_get($u);
    print "> update message\n";
    if ($user) {
        $author = phorum_db_interact(DB_RETURN_QUOTED, $user["display_name"]);
        phorum_db_interact(
           DB_RETURN_RES,
           "UPDATE {$PHORUM["pm_messages_table"]}
            SET    author = '$author'
            WHERE  user_id = $u"
        );
    }

    // Update the PM recipients.
    print "> update PM rcpts\n";
    $meta = unserialize($row["meta"]);
    $userids = array_keys($meta["recipients"]);
    $users = phorum_db_user_get($userids);
    foreach ($users as $user) {
        unset($meta["recipients"][$user["user_id"]]["username"]);
        $meta["recipients"][$user["user_id"]]["display_name"] =
            $user["display_name"];
    }
    $meta = phorum_db_interact(DB_RETURN_QUOTED, serialize($meta));
    print "> store new data\n";
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM["pm_messages_table"]}
         SET    meta = '$meta'
         WHERE  pm_message_id = $m"
    );
}

?>
