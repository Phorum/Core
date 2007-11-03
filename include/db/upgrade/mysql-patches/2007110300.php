<?php
if(!defined("PHORUM_ADMIN")) return;

if (!isset($PHORUM['cache_css'])) {
  phorum_db_update_settings(array(
      "cache_css"        => 1,
      "cache_javascript" => 1
  ));
}

?>
