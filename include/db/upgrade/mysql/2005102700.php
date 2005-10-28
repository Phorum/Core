<?php
if(!defined("PHORUM_ADMIN")) return;

$cid=phorum_db_mysql_connect();

// Add the new field and table index.
mysql_query(
    "ALTER TABLE {$PHORUM['search_table']} 
     ADD COLUMN forum_id int(10) UNSIGNED NOT NULL DEFAULT '0',
     ADD KEY forum_id (forum_id)"
);

// Fill the forum_id field for all messages. 
// Clean up messages which cannot be found in the messages table anymore.
$res=mysql_query(
    "SELECT message_id 
     FROM {$PHORUM['search_table']}"
);
while ($row=mysql_fetch_array($res)) 
{
    // Find the forum_id for the current message.
    $res2 = mysql_query(
        "SELECT forum_id 
         FROM {$PHORUM['message_table']} 
         WHERE message_id = {$row[0]}"
    );

    // Delete the message from the search table if no forum_id was found.
    if (mysql_num_rows($res2) == 0) {
        mysql_query(
            "DELETE FROM {$PHORUM['search_table']} 
             WHERE message_id = {$row[0]}"
        );
    }
    // Update the forum_id.
    else { 
        $row2 = mysql_fetch_array($res2);
        mysql_query(
            "UPDATE {$PHORUM['search_table']} 
             SET forum_id = {$row2[0]} 
             WHERE message_id = {$row[0]}"
        );
    }
}


?>
