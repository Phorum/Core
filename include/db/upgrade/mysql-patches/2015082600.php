<?php

// Initialize some default setting values.

if (!defined('PHORUM_ADMIN')) return;

$update_settings = array();

if (!isset($PHORUM['strip_quote_posting_form'])) {
    $update_settings['strip_quote_posting_form'] = 0;
}

if (!isset($PHORUM['strip_quote_mail'])) {
    $update_settings['strip_quote_mail'] = 0;
}

if (!empty($update_settings)) {
    phorum_db_update_settings($update_settings);
}

?>
