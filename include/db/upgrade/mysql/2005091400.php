<?php
if(!defined("PHORUM_ADMIN")) return;

// Create tables for the new PM system.

$upgrade_queries[]= "CREATE TABLE {$PHORUM["pm_messages_table"]} (
    pm_message_id int(10) unsigned NOT NULL auto_increment,
    from_user_id int(10) unsigned NOT NULL default '0',
    from_username varchar(50) NOT NULL default '',
    subject varchar(100) NOT NULL default '',
    message text NOT NULL,
    datestamp int(10) unsigned NOT NULL default '0',
    meta mediumtext NOT NULL,
    PRIMARY KEY (pm_message_id)
)";

$upgrade_queries[] = "CREATE TABLE {$PHORUM["pm_folders_table"]} (
    pm_folder_id int(10) unsigned NOT NULL auto_increment,
    user_id int(10) unsigned NOT NULL default '0',
    foldername varchar(20) NOT NULL default '',
    KEY user_id (user_id),
    PRIMARY KEY (pm_folder_id)
)";

$upgrade_queries[] = "CREATE TABLE {$PHORUM["pm_xref_table"]} (
    pm_xref_id int(10) unsigned NOT NULL auto_increment,
    user_id int(10) unsigned NOT NULL default '0',
    pm_folder_id int(10) unsigned NOT NULL default '0',
    special_folder varchar(10),
    pm_message_id int(10) unsigned NOT NULL default '0',
    read_flag tinyint(1) NOT NULL default '0',
    reply_flag tinyint(1) NOT NULL default '0',
    PRIMARY KEY (pm_xref_id),
    KEY xref (user_id,pm_folder_id,pm_message_id),
    KEY read_flag (read_flag)
)";

// converting the old PM system to the new one.
$old_table = "{$PHORUM['DBCONFIG']['table_prefix']}_private_messages";
$res = phorum_db_interact(
    DB_RETURN_RES,
    "SELECT *
     FROM   $old_table",
    NULL, DB_MASTERQUERY
);
while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC))
{
    // Put the message in the message table.
    $meta = serialize(array(
        'recipients' => array(
            $row["to_user_id"] => array (
                'user_id' => $row["to_user_id"],
                'username' => $row["to_username"],
                'read_flag' => $row["read_flag"],
            )
        )
    ));
    $sql = "INSERT INTO {$PHORUM["pm_messages_table"]} SET " .
           "pm_message_id = {$row["private_message_id"]}, " .
           "from_user_id = {$row["from_user_id"]}, " .
           "from_username = '" . addslashes($row["from_username"]) . "', " .
           "subject = '" . addslashes($row['subject']) . "', " .
           "message = '" . addslashes($row['message']) . "', " .
           "datestamp = {$row["datestamp"]}, " .
           "meta = '" . addslashes($meta) . "'";
    $upgrade_queries[] = $sql;

    // Link message to recipient inbox.
    if (! $row["to_del_flag"]) {
        $sql = "INSERT INTO {$PHORUM["pm_xref_table"]} SET " .
               "user_id = {$row["to_user_id"]}, " .
               "pm_folder_id = 0, " .
               "special_folder = '" . PHORUM_PM_INBOX . "', " .
               "pm_message_id = {$row["private_message_id"]}, " .
               "read_flag = {$row["read_flag"]}, " .
               "reply_flag = {$row["reply_flag"]}";
        $upgrade_queries[] = $sql;
    }

    // Link message to sender outbox.
    if (! $row["from_del_flag"]) {
        $sql = "INSERT INTO {$PHORUM["pm_xref_table"]} SET " .
               "user_id = {$row["from_user_id"]}, " .
               "pm_folder_id = 0, " .
               "special_folder = '" . PHORUM_PM_OUTBOX . "', " .
               "pm_message_id = {$row["private_message_id"]}, " .
               "read_flag = 1, " .
               "reply_flag = 0";
        $upgrade_queries[] = $sql;
    }
}
?>
