<?php
if(!defined("PHORUM_ADMIN")) return;

$update_settings = array();

// initialize the banlist-caching
if($PHORUM['cache_users']) {
	$update_settings['cache_banlists'] = 1;
} else {
	$update_settings['cache_banlists'] = 0;
}

// initialize the banlist version
if(!isset($PHORUM['banlist_version'])) {
	$update_settings['banlist_version']=0;
}

phorum_db_update_settings($update_settings);
?>
