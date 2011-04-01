<?php
if (!defined("PHORUM_ADMIN")) return;

// Initialize the new user_language setting to TRUE.
if (!isset($PHORUM['user_language'])) {
    phorum_db_update_settings(array("user_language" => TRUE));
}

