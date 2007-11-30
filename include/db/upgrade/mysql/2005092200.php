<?php
if(!defined("PHORUM_ADMIN")) return;

// Check if all the tables from version 2005091400 were created,
// before dropping the old private messages table.

$old_table = "{$PHORUM['DBCONFIG']['table_prefix']}_private_messages";

$check_tables = array(
   $PHORUM["pm_messages_table"] => 1,
   $PHORUM["pm_folders_table"]  => 1,
   $PHORUM["pm_xref_table"]     => 1,
);

$rows = phorum_db_interact(
    DB_RETURN_ROWS,
    "SHOW TABLES",
    NULL, DB_MASTERQUERY
);
foreach ($rows as $row) {
    if (isset($check_tables[$row[0]])) {
        unset($check_tables[$row[0]]);
    }
}

if (count($check_tables)) { ?>
    <br/>
    <b>Warning: database upgrade 2005091400 does not seem to have
    completed successfully. The old style private messages table
    <?php print $old_table ?> will be kept for backup. <?php
} else {
    $upgrade_queries[] = "DROP TABLE $old_table";
}

?>
