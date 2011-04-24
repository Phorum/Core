<?php
if (!defined("PHORUM_ADMIN")) return;

// Initialize the new user_language setting to TRUE.
if (!isset($PHORUM['user_language'])) {
    $PHORUM['DB']->update_settings(array("user_language" => TRUE));
}

