<?php

if (! isset($GLOBALS["PHORUM"]["mod_event_logging"])) {
    $GLOBALS["PHORUM"]["mod_event_logging"] = array();
}

if (! isset($GLOBALS["PHORUM"]["mod_event_logging"]["resolve_hostnames"])) {
    $GLOBALS["PHORUM"]["mod_event_logging"]["resolve_hostnames"] = 0;
}

if (! isset($GLOBALS["PHORUM"]["mod_event_logging"]["hide_passwords"])) {
    $GLOBALS["PHORUM"]["mod_event_logging"]["hide_passwords"] = 1;
}

if (! isset($GLOBALS["PHORUM"]["mod_event_logging"]["max_log_entries"])) {
    $GLOBALS["PHORUM"]["mod_event_logging"]["max_log_entries"] = 500;
}

if (! isset($GLOBALS["PHORUM"]["mod_event_logging"]["min_log_level"])) {
    $GLOBALS["PHORUM"]["mod_event_logging"]["min_log_level"] = 0;
}

$eventtypes =& $GLOBALS["PHORUM"]["DATA"]["MOD_EVENT_LOGGING"]["EVENT_TYPES"];
foreach ($eventtypes as $type => $desc)
{
    // These are used as grouping headers in the settings interface.
    if ($desc === NULL) continue;

    if (!isset($GLOBALS["PHORUM"]["mod_event_logging"]["do_log_$type"])) {
        $GLOBALS["PHORUM"]["mod_event_logging"]["do_log_$type"] = 1;
    }
}

?>
