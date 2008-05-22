<?php
if(!defined("PHORUM_ADMIN")) return;

// Add the new "open_id" setting to the database.
phorum_db_update_settings(array('open_id' => 0));

?>
