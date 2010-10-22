<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2010  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
///////////////////////////////////////////////////////////////////////////////

// This script can be used for implementing addon scripts, using the
// Phorum module system. This allows for full featured scripts, that
// run on their own (outside the hooks in the Phorum code), but which
// do not need to be copied to the Phorum main directory to be run.
// By containing addon scripts in the modules this way, installation and
// maintaining them is easier for the users.
//
//
// IMPLEMENTING AN ADDON SCRIPT:
// -----------------------------
//
// To implement an addon script, the following needs to be done:
//
// 1) Create a module, which contains a function that has to be 
//    called for running the addon code. For example:
//    
//    function phorum_mod_foo_youraddonfunction() {
//      # Code for implementing the addon goes here.
//      # This can of course also be an include of a script
//      # to run, using include("./mods/foo/yourscript.php").
//      # ...
//    }
// 
// 2) In the module information, register an addon hook for the function:
//
//    hook: addon|phorum_mod_foo_youraddonfunction
//
// 3) Call the addon code through the addon.php script (where 1 in the
//    URL indicates the current forum id):
//
//    http://your.phorum.site/addon.php?1,module=foo
//
//
// LINKING TO AN ADDON SCRIPT:
// ---------------------------
//
// If you want to link to the addon script, then always use the 
// phorum_get_url() function for generating the URL to link to.
//
//   $url = phorum_get_url(PHORUM_ADDON_URL, "module=foo");
//
//
// IMPLEMENTING MULTIPLE ADDON ACTIONS:
// ------------------------------------
// 
// Only one addon hook is allowed per module. If your module needs to 
// implement multiple addon script actions, then handle this by means
// of extra custom parameters for the addon.php URL, for example:
//
//    http://your.phorum.site/addon.php?1,module=foo,action=bar
//
// Using this, your addon function can check $PHORUM["args"]["action"]
// to see what action to perform. Generating an URL for this example
// would look like this:
//
//   $url = phorum_get_url(PHORUM_ADDON_URL, "module=foo", "action=bar");
// 

define('phorum_page','addon');

include_once( "./common.php" );

// Bail out early if there are no modules enabled that implement
// the addon hook.
if (! isset($PHORUM["hooks"]["addon"])) trigger_error(
    '<h1>Modscript Error</h1><br/>' .
    'There are no addon hook enabled modules active.',
    E_USER_ERROR
);

// Find the module argument. This one can be in the Phorum args,
// $_POST or $_GET (in that order).
$module = NULL;
if (isset($PHORUM['args']['module'])) {
    $module = $PHORUM['args']['module'];
} elseif (isset($_POST['module'])) {
    $module = $_POST['module'];
} elseif (isset($_GET['module'])) {
    $module = $_GET['module'];
}

if ($module === NULL) trigger_error(
    '<h1>Modscript Error</h1><br/>' .
    'Missing "module" argument.',
    E_USER_ERROR
);

$module = basename($module);

// Check if the mod is enabled and does implement the addon hook.
// Filter the list of hooks, so we only keep the one for the
// requested module.
$avail_hooks = $PHORUM["hooks"]["addon"];
$filtered_hooks = array("mods" => array(), "funcs" => array());
foreach ($avail_hooks["mods"] as $id => $checkmodule) {
    if ($module == $checkmodule) {
        $filtered_hooks["mods"][] = $module;
        $filtered_hooks["funcs"][] = $avail_hooks["funcs"][$id];
    }
}

if (count($filtered_hooks["mods"]) == 0) trigger_error(
    '<h1>Modscript Error</h1>' .
    'No addon hook enabled for module "'. htmlspecialchars($module, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) .'"',
    E_USER_ERROR
);

if (count($filtered_hooks["mods"]) > 1) trigger_error(
    '<h1>Modsript Error</h1>' .
    'More than one addon hook was registered ' .
    'in the info for module "' . htmlspecialchars($module, ENT_COMPAT, $PHORUM["DATA"]["HCHARSET"]) . '".<br/>Only ' .
    'one addon hook is allowed per module.',
    E_USER_ERROR
);

// Run the hook function.
$PHORUM["hooks"]["addon"] = $filtered_hooks;
phorum_hook("addon");

?>
