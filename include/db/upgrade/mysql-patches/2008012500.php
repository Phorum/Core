<?php

// Initialize some default setting values.

if (!defined('PHORUM_ADMIN')) return;

$update_settings = array();

if (!isset($PHORUM['default_feed'])) {
    $update_settings['default_feed'] = 'rss';
}

if (!isset($PHORUM['cache_newflags'])) {
    $update_settings['cache_newflags'] = 0;
}

if (!isset($PHORUM['cache_messages'])) {
    $update_settings['cache_messages'] = 0;
}

if (!isset($PHORUM['track_edits'])) {
    $update_settings['track_edits'] = 0;
}

if (!empty($update_settings)) {
    phorum_db_update_settings($update_settings);
}

?>
