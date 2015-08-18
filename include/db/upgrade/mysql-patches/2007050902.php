<?php

// Find all private messages.
$res = $PHORUM['DB']->interact(
    DB_RETURN_RES,
    "SELECT pm_message_id, user_id, meta
     FROM   {$PHORUM["pm_messages_table"]}"
);

// Update the meta author + rcpt info for each private message.
while ($row = $PHORUM['DB']->fetch_row($res, DB_RETURN_ASSOC))
{
    // Update the PM author.
    $m = $row["pm_message_id"];
    $u = $row["user_id"];
    $user = $PHORUM['DB']->user_get($u);
    if ($user) {
        $author = $PHORUM['DB']->interact(DB_RETURN_QUOTED, $user["display_name"]);
        $PHORUM['DB']->interact(
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
    $users = $PHORUM['DB']->user_get($userids);
    foreach ($users as $user) {
        unset($meta["recipients"][$user["user_id"]]["username"]);
        $meta["recipients"][$user["user_id"]]["display_name"] =
            $user["display_name"];
    }
    $meta = $PHORUM['DB']->interact(DB_RETURN_QUOTED, serialize($meta));
    $PHORUM['DB']->interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM["pm_messages_table"]}
         SET    meta = '$meta'
         WHERE  pm_message_id = $m",
        NULL,
        DB_MASTERQUERY
    );
}

?>
