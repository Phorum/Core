<?php

if (!defined("PHORUM_ADMIN")) return;

// For shorter writing.
$settings =& $GLOBALS["PHORUM"]["mod_event_logging"];
$eventtypes =& $GLOBALS["PHORUM"]["DATA"]["MOD_EVENT_LOGGING"]["EVENT_TYPES"];

if (count($_POST))
{
    $settings["resolve_hostnames"] = isset($_POST["resolve_hostnames"])?1:0;
    $settings["hide_passwords"]    = isset($_POST["hide_passwords"])?1:0;
    $settings["max_log_entries"]   = (int) $_POST["max_log_entries"];
    $settings["min_log_level"]     = (int) $_POST["min_log_level"];

    foreach ($eventtypes as $type => $desc) {
        $settings["do_log_$type"] = isset($_POST["do_log_$type"])?1:0;
    }

    phorum_db_update_settings(array("mod_event_logging" => $settings));
    phorum_admin_okmsg("The settings were successfully saved.");
}

// Create the settings form.
include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Save settings");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "event_logging"); 
$frm->hidden("el_action", "settings");

$frm->addbreak("General Settings");

$row = $frm->addrow("Minimum log level", $frm->select_tag("min_log_level", $GLOBALS["PHORUM"]["DATA"]["MOD_EVENT_LOGGING"]["LOGLEVELS"], $settings["min_log_level"]));
$frm->addhelp($row, "Minimum log level", "This option configures the minimum log level for which log messages are written to the event log. Events with a lower log level will not be written.<br/><br/>\"Debug\" is the lowest log level and \"Alert\" the highest, so to log all events, set this option to \"Debug\".");

$row = $frm->addrow("Maximum amount of stored event logs (0 = unlimited)", $frm->text_box("max_log_entries", $settings["max_log_entries"], 5));
$frm->addhelp($row, "Maximum amount of stored event logs", "This option configures the maximum amount of event logs that can be stored in the database at a given time. If the amount of event logs grows larger than this configured maximum, then old entries will be automatically cleaned up.<br/><br/>If you do not want a limit on the number of event logs, then set this option to 0 (zero).");

$row = $frm->addrow("Resolve IP addresses to host names when writing the event log", $frm->checkbox("resolve_hostnames", "1", "Yes", $settings["resolve_hostnames"]));
$frm->addhelp($row, "Resolve IP addresses", "If this option is enabled, the IP address of the visitor will immediately be resolved into its hostname. Enabling this option can result in delays for the user, in case hostname lookups are slow for some reason.<br/><br/><b>Because of the performance penalty, we do not recommend enabling this option, unless you really need it and know what you are doing.</b>");

$row = $frm->addrow("Hide passwords in log messages", $frm->checkbox("hide_passwords", "1", "Yes", $settings["hide_passwords"]));
$frm->addhelp($row, "Hide passwords in log messages", "If this option is enabled, then passwords and user registration codes in logged messages are replaced with \"XXXXXXXX\". In general, it is good practice to not log these because of security implications (the passwords are stored in plain text format in the database), but if you need access to the passwords, then you can disable this option.<br/><br/>Note: this option only affects new messages that are logged.");

$row = $frm->addbreak("Which events to log");
$frm->addhelp($row, "Which events to log", "Below, you see the events which the Event Logging module can log for you. Enable the checkbox for each event type that you wish to appear in the event log. You can use this to limit the amount of entries, in case you are not interested in some of them.<br/><br/>Note that other modules can also write entries to the event log. If you need to suppress logging for those, then please consult the settings and documentation of those modules.");

foreach ($eventtypes as $type => $desc) {
    if ($desc === NULL) {
        $frm->addsubbreak($type);
    } else {
        $frm->addrow($desc, $frm->checkbox("do_log_$type", "1", "Yes", $settings["do_log_$type"]));
    }
}

$frm->show();

?>
