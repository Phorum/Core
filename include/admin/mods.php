<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

require_once('./include/api/modules.php');

// ----------------------------------------------------------------------
// Process posted form data
// ----------------------------------------------------------------------

// Retrieve a list of available modules.
$list = phorum_api_modules_list();

if(count($_POST) && (!defined("PHORUM_INSTALL") || isset($_POST["do_modules_update"])))
{
    foreach ($_POST as $key => $value) {
        $key = base64_decode($key);
        if(substr($key, 0, 5) == "mods_") {
            $mod = substr($key, 5);
            if ($value) {
                phorum_api_modules_enable($mod);
            } else {
                phorum_api_modules_disable($mod);
            }
        }
    }

    phorum_api_modules_save();

    if (defined("PHORUM_INSTALL")) {
        $step = "done";
        return;
    } else {
        phorum_admin_okmsg("The module settings were successfully updated.");
    }
}

// ----------------------------------------------------------------------
// Build form
// ----------------------------------------------------------------------

// Retrieve a list of available modules.
$list = phorum_api_modules_list();

// Show module problems to the admin.
if (count($list['problems'])) {
    foreach ($list['problems'] as $problem) {
        phorum_admin_error($problem);
    }
}

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

foreach ($list['modules'] as $name => $info)
{
    if ($info["settings"] && !defined("PHORUM_INSTALL")) {
        $settings_url = phorum_admin_build_url(array('module=modsettings',"mod=$name"));
        $settings_link="<br /><a name=\"link-settings-$name\" href=\"$settings_url\">Settings</a>";
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
        $frm->addrow($text, $frm->select_tag(base64_encode("mods_$name"), array("Off", "On"), $info['enabled']).$settings_link);
    } else {
        $frm->addrow($text, $frm->select_tag(base64_encode("mods_$name"), array("Off"),0,'disabled="disabled"').$settings_link);

        // Disable a module if it's enabled, but should be disabled based
        // on the Phorum version.
        if ($info['enabled'])
        {
            phorum_admin_error(
                "Minimum Phorum-Version requirement not met for " .
                "module \"" . htmlspecialchars($name) . "\"<br/>" .
                "It requires at least version " .
                "\"" . htmlspecialchars($info['required_version']) . "\", " .
                "but the current version is \"" . PHORUM . "\".<br />".
                "The module was disabled to avoid malfunction of " .
                "your Phorum because of that requirement.<br/>"
            );

            phorum_api_modules_disable($name);
            phorum_api_modules_save();
        }
    }
}

$frm->show();

?>
