<?php
if(!defined("PHORUM_ADMIN")) return;

if (!isset($PHORUM['cache_css'])) {
  $PHORUM['DB']->update_settings(array(
      "cache_css"        => 1,
      "cache_javascript" => 1
  ));
}

?>
