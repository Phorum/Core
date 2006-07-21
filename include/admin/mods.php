<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2006  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

    if (!defined("PHORUM_ADMIN")) return;

    // ----------------------------------------------------------------------
    // Read in the module info for all available modules
    // ----------------------------------------------------------------------

    $modules_info = array();
    $priorities = array();

    $d = dir("./mods");
    while (false !== ($entry = $d->read()))
    {
        // Some entries which we skip by default.
        if ($entry == '.' || $entry == '..' ||
            $entry == '.svn' || $entry == 'ATTIC' ||
            $entry == '.htaccess') continue;

        // Read in the module information. 
        $lines = array();
        if (file_exists("./mods/$entry/info.txt")) {
            $lines = file("./mods/$entry/info.txt");
        } elseif (is_file("./mods/$entry") && substr($entry, -4)==".php") {
            $entry = str_replace(".php", "", $entry);
            $data = file_get_contents("./mods/$entry.php");
            if($data = stristr($data, "/* phorum module info")){
                $data = substr($data, 0, strpos($data, "*/"));
                $lines = preg_split('!(\r|\n|\r\n)!', $data);
            }
        }
        
        // Check if we found module information.
        if (!count($lines)) {
            phorum_admin_error(
                "Warning: possible module " .
                "\"" . htmlspecialchars($entry) . "\" found, but no " .
                "module information is available for that module.");
            continue;
        }

        // Parse the module information.
        $info = array();
        foreach ($lines as $line) {
            if (strstr($line, ":")) {
                $parts = explode(":", trim($line), 2);
                if($parts[0]=="hook"){
                    $info["hooks"][]=trim($parts[1]);
                } elseif($parts[0]=="priority"){
                    $prio = trim($parts[1]);
                    if (preg_match('/^run\s+hook\s+(.+)\s+(before|after)\s(.+)$/i', $prio, $m)) {
                        $priorities['hook'][$m[1]][$entry][] = $m;
                    } elseif (preg_match('/^run\s+module\s+(before|after)\s(.+)$/i', $prio, $m)) {
                        $priorities['module'][$entry][] = $m;
                    } else {
                        phorum_admin_error(
                            "Priority configuration error for module " . 
                            htmlspecialchars($entry) . "<br/>" .
                            "Cannot parse priority " .
                            "\"" . htmlspecialchars($prio) . "\"<br/>");
                    }
                } else {
                    $info[$parts[0]]=trim($parts[1]);
                }
            }
        }

        if(file_exists("./mods/$entry/settings.php")){
            $info["settings"]=true;
        } else {
            $info["settings"]=false;
        }

        $modules_info[$entry] = $info;
    }
    $d->close();

    // Sort the modules by their title, so they show up in an easy
    // to use way for the user in the admin interface.
    function module_sort($a, $b) { return strcmp($a["title"], $b["title"]); }
    uasort($modules_info, "module_sort");

    // ----------------------------------------------------------------------
    // Process posted form data
    // ----------------------------------------------------------------------

    if(count($_POST))
    {
        // Determine the module status (enabled/disabled).
        $mods = array();
        $active_mods = array(); 
        foreach ($_POST as $key => $value) {
            $key = base64_decode($key);
            if(substr($key, 0, 5) == "mods_") {
                $mod = substr($key, 5);
                $mods[$mod] = $value ? 1 : 0;
                if ($value) $active_mods[] = $mod;
            }
        }
        $PHORUM["mods"] = $mods;

        // First priority ordering pass:
        // run module before|after * 
        $active_mods_copy = array_values($active_mods); // array_values reindexes
        foreach ($active_mods as $mod)
        {
            if (!isset($priorities["module"][$mod])) continue;
            $mod_priorities = $priorities["module"][$mod];

            foreach ($mod_priorities as $priority)
            {
                if ($priority[2] == "*")
                {
                    // Remove the module from the list.
                    $idx = array_search($mod, $active_mods_copy);
                    unset($active_mods_copy[$idx]);

                    // Add it do the end of start of the list.
                    if ($priority[1] == "after") {
                       array_push($active_mods_copy, $mod);
                    } else {
                       array_unshift($active_mods_copy, $mod);
                    }
                }
            }
        }
        $active_mods = $active_mods_copy;

        // Second priority ordering pass: 
        // run module before|after <othermodule> 
        $active_mods_copy = array_values($active_mods);
        foreach ($active_mods as $mod)
        {
            if (!isset($priorities["module"][$mod])) continue;
            $mod_priorities = $priorities["module"][$mod];
            foreach ($mod_priorities as $priority)
            {
                if ($priority[2] == '*') continue;

                // Find the current position of the modules.
                $idx1 = array_search($mod, $active_mods_copy);
                $idx2 = array_search($priority[2], $active_mods_copy);
                if ($idx2 === false || $idx2 === NULL) continue; //NULL = pre 4.2.0

                // Move module up in the list.
                if ($idx1 > $idx2 && $priority[1] == "before") {
                    unset($active_mods_copy[$idx1]);
                    array_splice($active_mods_copy, $idx2, 1, array($mod, $priority[2]));
                    $active_mods_copy = array_values($active_mods_copy);
                }

                // Move module down in the list.
                if ($idx1 < $idx2 && $priority[1] == "after") {
                    array_splice($active_mods_copy, $idx2, 1, array($priority[2], $mod));
                    unset($active_mods_copy[$idx1]);
                    $active_mods_copy = array_values($active_mods_copy);
                }
            }
        }
        $active_mods = $active_mods_copy;

        # Determine what hooks to run for the activated modules.
        $modules_by_hook = array();
        $functions_by_module = array();
        foreach ($active_mods as $mod) 
        {
            if (! isset($modules_info[$mod]["hooks"])) continue;
            foreach ($modules_info[$mod]["hooks"] as $hookinfo) {
                list ($hook,$func) = explode("|", $hookinfo); 
                $modules_by_hook[$hook][] = $mod;
                $functions_by_module[$mod][$hook] = $func;
            }
        }

        // Third priority ordering pass:
        // run hook <hook> before|after * 
        foreach ($modules_by_hook as $hook => $mods)
        {
            if (!isset($priorities["hook"][$hook])) continue;

            foreach ($active_mods as $mod)
            {
                if (!isset($priorities["hook"][$hook][$mod])) continue;

                $hook_priorities = $priorities["hook"][$hook][$mod];
                foreach ($hook_priorities as $priority)
                {
                    if ($priority[3] == "*")
                    {
                        // Remove the module from the list.
                        $idx = array_search($mod, $mods);
                        unset($mods[$idx]);

                        // Add it do the end of start of the list.
                        if ($priority[2] == "after") {
                           array_push($mods, $mod);
                        } else {
                           array_unshift($mods, $mod);
                        }
                    }
                }
            }
            $modules_by_hook[$hook] = $mods;
        }

        // Fourth priority ordering pass:
        // run hook <hook> before|after <othermodule> 
        foreach ($modules_by_hook as $hook => $mods)
        {
            if (!isset($priorities["hook"][$hook])) continue;

            foreach ($active_mods as $mod)
            {
                if (!isset($priorities["hook"][$hook][$mod])) continue;

                $hook_priorities = $priorities["hook"][$hook][$mod];
                foreach ($hook_priorities as $priority)
                {
                    if ($priority[3] == '*') continue;

                    // Find the current position of the modules.
                    $idx1 = array_search($mod, $mods);
                    $idx2 = array_search($priority[3], $mods);
                    if ($idx2 === false || $idx2 === NULL) continue; //NULL = pre 4.2.0

                    // Move module up in the list.
                    if ($idx1 > $idx2 && $priority[2] == "before") {
                        unset($mods[$idx1]);
                        array_splice($mods, $idx2, 1, array($mod, $priority[3]));
                        $mods = array_values($mods);
                    }

                    // Move module down in the list.
                    if ($idx1 < $idx2 && $priority[2] == "after") {
                        array_splice($mods, $idx2, 1, array($priority[3], $mod));
                        unset($mods[$idx1]);
                        $mods = array_values($mods);
                    }
                }
            }
            $modules_by_hook[$hook] = array_values($mods);
        }

        // Create the hooks configuration.
        $hooks = array();
        foreach ($modules_by_hook as $hook => $mods) 
        {
            $hooks[$hook] = array();
            foreach ($mods as $mod) {
                $hooks[$hook]["mods"][] = $mod;
                $hooks[$hook]["funcs"][] = $functions_by_module[$mod][$hook];
            }
        }
        $PHORUM["hooks"] = $hooks;

        // Store the settings in the database.
        $data = array(
            "hooks" => $PHORUM["hooks"],
            "mods"  => $PHORUM["mods"]
        );
        if (phorum_db_update_settings($data)) {
            phorum_admin_okmsg("The module settings were successfully updated."); 
        } else {
            phorum_admin_error("Database error while updating settings.");
        }
    }

    // ----------------------------------------------------------------------
    // Build form
    // ----------------------------------------------------------------------

    include_once "./include/admin/PhorumInputForm.php";

    $frm = new PhorumInputForm ("", "post");

    $frm->addbreak("Phorum Module Settings");

    $frm->hidden("module", "mods");

    foreach ($modules_info as $name => $info)
    {
        if (isset($PHORUM["mods"]["$name"])) {
            $enabled = $PHORUM["mods"]["$name"];
        } else {
            $enabled = 0;
        }

        if ($info["settings"])
        {
            if ($enabled==0) {
                $settings_link="<br /><a href=\"javascript:alert('You can not edit settings for a module unless it is turned On.');\">Settings</a>";
            } else {
                $settings_link="<br /><a href=\"$_SERVER[PHP_SELF]?module=modsettings&mod=$name\">Settings</a>";
            }
        } else {
            $settings_link="";
        }

        $frm->addrow("$info[title]<div class=\"small\">".wordwrap($info["desc"], 90, "<br />")."</div>", $frm->select_tag(base64_encode("mods_$name"), array("Off", "On"), $enabled).$settings_link);

    }

    $frm->show();

?>
