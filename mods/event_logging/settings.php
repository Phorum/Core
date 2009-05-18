<?php

// Check if we are loaded from the Phorum admin code.
// Direct access to this file is not allowed.
if (! defined("PHORUM_ADMIN")) return;

// Load the constants and defaults that we use.
require_once("./mods/event_logging/constants.php");
require_once("./mods/event_logging/defaults.php");

print '<h1>Phorum Event Logging</h1>';

// This admin interface contains multiple screens. Determine which one
// we have to load.
$action = "settings";
if (isset($_REQUEST["el_action"])) {
    $action = basename($_REQUEST["el_action"]);
}

// Create a page switching menu at the start of the page.

print "<div style=\"border:2px solid #000040; padding:6px; margin-bottom: 10px\">";

// An array, containing the pages to show.
$menu = array(
    "settings"  => "Module settings",
);

// Only display log viewing pages if the module is enabled.
if (!empty($PHORUM['mods']['event_logging'])) {
    $menu["logviewer"] = "View logged events";
    $menu["filter"]    = "Filter logged events";
}

foreach ($menu as $act => $itm) {
    if ($act == $action) {
        print "<span style=\"margin-right: 5px; padding: 3px 10px\" class=\"input-form-td-break\">$itm</span>";
    } else {
        $link = phorum_admin_build_url(array(
            'module=modsettings',
            'mod=event_logging',
            'el_action='.$act
        ));
        print "<span style=\"margin-right: 5px; padding: 3px 10px\" class=\"input-form-th\"><a href=\"$link\">$itm</a></span>";
   }
}

print "</div>";

// Load the settings screen. For security,
// we follow a strict naming scheme here.
$settings_file = "./mods/event_logging/settings/{$action}.php";
if (file_exists($settings_file)) {
    include($settings_file);
} else {
    trigger_error("Illegal settings action requested.", E_USER_ERROR);
}

?>
