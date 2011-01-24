<?php
if(!defined("PHORUM_ADMIN")) return;

// initialize the "allow_pm_email_notify" setting
if (!isset($PHORUM["allow_pm_email_notify"])) {
    $PHORUM['DB']->update_settings(array(
        "allow_pm_email_notify" => 1
    ));
}

?>
