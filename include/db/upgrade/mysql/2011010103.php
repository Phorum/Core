<?php

// Upgrade the "use_new_folder_style" configuration option
// to the new "index_style" option.

if (!defined('PHORUM_ADMIN')) return;

if (empty($PHORUM["use_new_folder_style"])) {
    $index_style = PHORUM_INDEX_DIRECTORY;
} else {
    $index_style = PHORUM_INDEX_FLAT;
}

// Add the new setting to the database.
$PHORUM['DB']->update_settings(array('index_style' => $index_style));

// Delete the old setting from the database.
$upgrade_queries[] =
   "DELETE FROM {$PHORUM["settings_table"]}
    WHERE name = 'use_new_folder_style'";

?>
