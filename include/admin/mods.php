<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2007  Phorum Development Team                              //
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

// This array describes deprecated module hook names, which have been
// replaced by new hook names. For backward compatibility, the
// module admin will transparently rewrite old hook names to the
// new ones.
$deprecated_module_hooks = array(
"pre_post"  => "before_post",
"pre_edit"  => "before_edit",
"post_post" => "after_post",
);

// ----------------------------------------------------------------------
// Read in the module info for all available modules
// ----------------------------------------------------------------------

$modules_info = array();
$priorities = array();

$deprecate_warn = '';

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

    include_once('./include/version_functions.php');

    // Parse the module information.
    $info = array();
    $info['version_disabled'] = false;
    foreach ($lines as $line) {

        if (strstr($line, ":")) {
            $parts = explode(":", trim($line), 2);
            if($parts[0]=="hook"){
                list ($hook,$function) = explode('|', trim($parts[1]));
                if (isset($deprecated_module_hooks[$hook])) {
                    $deprecate_warn .= "<li> Mod " . htmlspecialchars($entry) . ": rename \"" . htmlspecialchars($hook) . "\"; to \"" . htmlspecialchars($deprecated_module_hooks[$hook]) . "\"</li>\n";
                    $hook = $deprecated_module_hooks[$hook];
                    $parts[1] = "$hook|$function";
                }
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
            } elseif($parts[0]=="required_version") {
                $required_ver = trim($parts[1]);
                $phorum_ver = PHORUM;

                list ($release, $cur) = phorum_parse_version($phorum_ver);
                list ($release, $req) = phorum_parse_version($required_ver);

                if (phorum_compare_version($cur, $req) == -1) {
                    phorum_admin_error(
                        "Minimum Phorum-Version requirement not met for " .
                        "module \"" . htmlspecialchars($entry) . "\"<br/>" .
                        "It requires at least version " .
                        "\"" . htmlspecialchars($required_ver) . "\", " .
                        "but the current version is \"" . PHORUM . "\".<br />".
                        "The module was disabled to avoid malfunction of " .
                        "your Phorum because of that requirement.<br/>"
                    );
                    $info['version_disabled'] = true;
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

// Show module warnings to the admin in a non intrusive way. In a lot
// of cases, the admin won't be the one to fix the problems, therefore
// we do not want to display too much noise about warnings here.
if ($deprecate_warn != '') {
    $warn = "One or more deprecated hook names were detected in the " .
    "installed modules. Although the modules will still work, " .
    "we advice you to update the hook names in the module " .
    "info and/or contact the module author.<br/>" .
    "Deprecated hook(s):" .
    "<ul>" . $deprecate_warn . "</ul>"; ?>

    <div id="showmodwarnings" class="PhorumAdminError">
    One or more module warnings found.
    <script type="text/javascript">
    function toggle_module_warnings() {
        document.getElementById('modwarnings').style.display='block';
        document.getElementById('showmodwarnings').style.display='none'
        return false;
    }
    </script>
    <a href="" onclick="return toggle_module_warnings()">Click here to see them</a>
    </div>
    <div id="modwarnings" class="PhorumAdminError" style="display:none">
    <?php print $warn ?>
    </div>
    <?php
}

// Sort the modules by their title, so they show up in an easy
// to use way for the user in the admin interface.
function module_sort($a, $b) { return strcmp($a["title"], $b["title"]); }
uasort($modules_info, "module_sort");

// ----------------------------------------------------------------------
// Process posted form data
// ----------------------------------------------------------------------

if(count($_POST) && (!defined("PHORUM_INSTALL") || isset($_POST["do_modules_update"])))
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
        $mods = array_values($mods); // array_values reindexes
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
        $mods = array_values($mods); // array_values reindexes
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
        if(defined("PHORUM_INSTALL")){
            $step = "done";
            return;
        } else {
            phorum_admin_okmsg("The module settings were successfully updated.");
        }
    } else {
        phorum_admin_error("Database error while updating settings.");
    }
}

// ----------------------------------------------------------------------
// Build form
// ----------------------------------------------------------------------

include_once "./include/admin/PhorumInputForm.php";



if(defined("PHORUM_INSTALL")){
    $frm = new PhorumInputForm ("", "post", "Continue ->");
    $frm->addbreak("Optional modules");
    $frm->hidden("module", "install");
    $frm->hidden("sanity_checks_done", "1");
    $frm->hidden("step", "modules");
    $frm->hidden("do_modules_update", "1");
    $frm->addmessage("Phorum has a very robust module system.  The following modules are included with the distribution.  You can find more modules at the Phorum web site.  Some modules may have additional configuration options, which are not available during install.  To configure the modules, click the Modules menu item after installation is done.");
} else {
    $frm = new PhorumInputForm ("", "post");
    $frm->addbreak("Phorum Module Settings");
    $frm->hidden("module", "mods");
}

$module_changes = false;

foreach ($modules_info as $name => $info)
{
    if (isset($PHORUM["mods"]["$name"])) {
        $enabled = $PHORUM["mods"]["$name"];
    } else {
        $enabled = 0;
    }

    if ($info["settings"] && !defined("PHORUM_INSTALL"))
    {
        /*if ($enabled==0) {
            $settings_link="<br /><a href=\"javascript:alert('You can not edit settings for a module unless it is turned On.');\">Settings</a>";
        } else {*/
            $settings_link="<br /><a href=\"{$PHORUM["admin_http_path"]}?module=modsettings&mod=$name\">Settings</a>";
        /* } */
    } else {
        $settings_link="";
    }

    $text = $info["title"];
    if(isset($info["version"])){
        $text.=" (version ".$info["version"].")";
    }
    if(isset($info["desc"])){
        $text.="<div class=\"small\">".wordwrap($info["desc"], 90, "<br />")."</div>";
    }
    if(isset($info["author"])){
        $text.="<div class=\"small\">Created by ".$info["author"]."</div>";
    }
    if(isset($info["release_date"])){
        $text.="<div class=\"small\">Released ".$info["release_date"]."</div>";
    }

    $moreinfo = array();
    if(isset($info["url"])){
        $moreinfo[] = "<a href=\"".$info["url"]."\">web site</a>";
    }
    foreach(array('README','INSTALL','Changelog') as $file) {
        if(file_exists("./mods/$name/$file")){
            $moreinfo[] = "<a target=\"_blank\" href=\"".$PHORUM["http_path"]."/mods/$name/$file\">$file</a>";
        }
    }
    if (!empty($moreinfo)) {
        $text.="More info: <small>" . implode(" &bull; ", $moreinfo) . "</small>";
    }

    if(!$info['version_disabled']) {
        $frm->addrow($text, $frm->select_tag(base64_encode("mods_$name"), array("Off", "On"), $enabled).$settings_link);
    } else {
        $frm->addrow($text, $frm->select_tag(base64_encode("mods_$name"), array("Off"),0,'disabled=\'disabled\'').$settings_link);
        if($enabled) {
            foreach($info['hooks'] as $hookdata) {
                list($hook,$hookfunction)=explode('|',$hookdata);
                foreach($PHORUM['hooks'][$hook]['funcs'] as $hid => $functionname) {
                    if($functionname == $hookfunction) {
                        unset($PHORUM['hooks'][$hook]['funcs'][$hid]);
                        unset($PHORUM['hooks'][$hook]['mods'][$hid]);
                    }
                }
            }
            unset($PHORUM["mods"]["$name"]);
            $module_changes = true;
        }
    }
}

if($module_changes) {
    // Store the settings in the database.
    // changes could occur with a disabled module
    $data = array(
    "hooks" => $PHORUM["hooks"],
    "mods"  => $PHORUM["mods"]
    );
    phorum_db_update_settings($data);
}

$frm->show();

?>
