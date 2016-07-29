<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
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
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Style code. Needs to move to the main stylesheet eventually.
?>
<style type="text/css">
.module_block {
    padding: 1em 0.5em;
    margin: 0.3em 0em;
    border: 1px solid #ddd;
}
.module_block.enabled {
    background-color: #ded;
}
.module_block.disabled {
    background-color: #f0f0f0;
}
.module_title {
    font-weight: bold;
}
.module_block.enabled .module_title {
    color: #030;
}
.module_block.disabled .module_title {
    color: #444;
}

.module_data {
    margin: 0.4em 0.4em 0.4em 1.7em;
    font-size: 80%;
}
.module_description {
}
.module_author,
.module_release_date {
    font-weight: bold;
}
.module_links {
    margin-top: 0.5em;
}
.module_error {
    font-weight: bold;
    color: red;
    margin-top: 0.5em;
}

.module_block.version_disabled {
    background-color: #ffeedd;
}
.module_block.version_disabled .module_title,
.module_block.version_disabled .module_links,
.module_block.version_disabled .module_author,
.module_block.version_disabled .module_release_date,
.module_block.version_disabled .module_description {
    color: #bbb;
}

.modules_filter {
    margin: 0.4em 0;
}
</style>

<script type="text/javascript">
//<![CDATA[
function toggle_module_status(checkbox)
{
    var newclass = checkbox.checked
                 ? 'module_block enabled'
                 : 'module_block disabled';
    checkbox.parentNode.className = newclass;
}

function filter_modules(form)
{
    var status = 0;
    var i = form.filter_status.selectedIndex;
    if (i > -1) {
        status = form.filter_status.options[i].value;
    }

    var hide_description = form.hide_description.checked;

    var match = form.filter_text.value.toLowerCase();

    var div_elts = document.getElementsByTagName('div');

    for (var i = 0; i < div_elts.length; i++)
    {
        var elt = div_elts[i];

        // Handle description visibility.
        if (elt.className == 'module_description' ||
            elt.className == 'module_author') {
            if (hide_description) {
                elt.style.display = 'none';
            } else {
                elt.style.display = 'block';
            }
            continue;
        }

        if (elt.className.substr(0, 12) == 'module_block')
        {
            // Handle visibility, based on the status filter.
            if (elt.className == 'module_block enabled') {
                if (status == 0 || status == 1) {
                    elt.style.display = 'block';
                } else {
                    elt.style.display = 'none';
                    continue;
                }
            } else {
                if (status == 0 || status == 2) {
                    elt.style.display = 'block';
                } else {
                    elt.style.display = 'none';
                    continue;
                }
            }

            // Handle visibility, based on the filter string.
            var title = document.getElementById('title_' + elt.id);
            if (title) {
                if (title.innerHTML.toLowerCase().indexOf(match) != -1) {
                    elt.style.display = 'block';
                } else {
                    elt.style.display = 'none';
                }
            }
        }
    }
}

//]]>
</script>
<?php

if (!defined("PHORUM_ADMIN")) return;

require_once './include/api/modules.php';

// Retrieve a list of available modules.
$list = phorum_api_modules_list();

// ----------------------------------------------------------------------
// Process posted form data
// ----------------------------------------------------------------------

// Request for module information file?
if (isset($_GET['info']) && isset($_GET['mod']))
{
    $mod  = basename($_GET['mod']);
    $file = basename($_GET['info']);
    if (($file == 'README' || $file == 'INSTALL' || $file == 'Changelog') &&
        file_exists("./mods/$mod/$file")) {
        include_once "./include/admin/PhorumInputForm.php";
        $frm = new PhorumInputForm ("", "post", "Back to Modules admin");
        $frm->hidden('module', 'mods');
        $frm->addbreak("$file for module $mod:");
        $info = file_get_contents("./mods/$mod/$file");
        $frm->addmessage("<pre>" . htmlspecialchars($info) . "</pre>");
        $frm->show();
    } else {
        phorum_admin_error(
            "Denied access to illegal module information file request!"
        );
    }
    return;
}


if(!empty($_POST['do_module_updates']))
{
    foreach ($list['modules'] as $mod => $info)
    {
        $key = base64_encode('mods_'.$mod);
        if (isset($_POST[$key])) {
            phorum_api_modules_enable($mod);
        } else {
            phorum_api_modules_disable($mod);
        }
    }

    phorum_api_modules_save();

    phorum_admin_okmsg("The module settings were successfully updated.");

    // Retrieve a fresh module list.
    $list = phorum_api_modules_list();
}

// ----------------------------------------------------------------------
// Display status information about possible module problems
// ----------------------------------------------------------------------

// Show module problems to the admin.
if (count($list['problems'])) {
    foreach ($list['problems'] as $problem) {
        phorum_admin_error($problem);
    }
}

// ----------------------------------------------------------------------
// Build the form
// ----------------------------------------------------------------------

// Just used for building form elements.
include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "");
$frm_url = phorum_admin_build_url();
$html = "<form id=\"modules_form\" " .
        "action=\"$frm_url\" method=\"post\">" .
        "<input type=\"hidden\" name=\"phorum_admin_token\"
                value=\"{$PHORUM['admin_token']}\" />".
        // Prevent the modules form from submitting when pressing enter
        // in the filter text box.
        "<input style=\"display:none\" type=\"submit\" " .
        "value=\"catch [enter] key\" onclick=\"return false\"/>" .
        "<input type=\"hidden\" name=\"module\" value=\"mods\" />" .
        "<input type=\"hidden\" name=\"do_module_updates\" value=\"1\" />" .
        "<div class=\"PhorumAdminTitle\">Phorum module settings</div>" .
        "<div class=\"modules_filter\">" .
          "<strong>Filter:</strong> " .
          "show " .
          $frm->select_tag(
              'filter_status',
              array(
                  0 => 'all',
                  1 => 'enabled',
                  2 => 'disabled'
              ),
              isset($_POST['filter_status']) ? $_POST['filter_status'] : 0,
              'onchange="filter_modules(this.form)"'
          ) .
          " modules, matching " .
          $frm->text_box(
              'filter_text',
              isset($_POST['filter_text']) ? $_POST['filter_text'] : '',
              30, NULL, FALSE,
              'onkeyup="filter_modules(this.form)" id="filter_text"'
          ) .
          $frm->checkbox(
              'hide_description', 1, 'hide descriptions',
              isset($_POST['hide_description']) ? 1 : 0,
              'style="margin-left:1em" ' .
              'onchange="filter_modules(this.form)" ' .
              'id="hide_descriptions"'
          ) .
        "</div>";

foreach ($list['modules'] as $name => $info)
{
    // Disable a module if it's enabled, but should be disabled based
    // on the Phorum version.
    if ($info['version_disabled'] && $info['enabled'])
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

        $info['version_disabled'] = TRUE;
    }

    $id = base64_encode("mods_$name");

    $title = $info["title"];
    if(isset($info["version"])){
        $title.=" (version ".$info["version"].")";
    }

    // Compatibility modules are handles in a special way. These are
    // not enabled from the admin panel. Instead, they are automatically
    // loaded when required from the start of common.php.
    $is_compat = FALSE;
    if (!empty($info['compat']))
    {
        foreach($info['compat'] as $function => $extension) {
            if (!function_exists($function)) {
                $is_compat = "function $function() " .
                             "from PHP extension \"$extension\"";
                break;
            }
        }

        // If the compatibility module would not be loaded, then
        // we won't bother showing it in the interface. That would
        // probably only confuse the admins.
        if (!$is_compat) continue;
    }

    $class = $info['version_disabled']
           ? 'module_block disabled version_disabled'
           : ($info['enabled'] || $is_compat
              ? 'module_block enabled'
              : 'module_block disabled');
    $disabled = $info['version_disabled'] || $is_compat
              ? ' disabled="disabled"' : '';
    $html .= "<div class=\"$class\" id=\"$id\">" .
             "<input type=\"checkbox\" name=\"$id\" id=\"cb_$id\" " .
             "onchange=\"toggle_module_status(this)\" " .
             ($is_compat || $info['enabled']
              ? 'checked="checked"' : '') .
             $disabled . '/>' .
             "<label id=\"title_$id\" for=\"cb_$id\" class=\"module_title\">$title</label>" .
             "<div class=\"module_data\">";

    if (isset($info['desc']) || $is_compat) {
        $html .= '<div class="module_description">' .
                 ($is_compat
                  ? '<b>Automatically enabled, because ' .
                    "$is_compat was not found on your system.</b>" : '') .
                 ($info['desc'] ? $info['desc'] : '') .
                 '</div>';
    }

    if(isset($info["author"])){
        $html.="<div class=\"module_author\">Created by ".$info["author"]."</div>";
    }
    if(isset($info["release_date"])){
        $text.="<div class=\"module_release_date\">Released ".$info["release_date"]."</div>";
    }

    if ($info['version_disabled'])
    {
        $html .= "<div class=\"module_error\">" .
                 "Module disabled. Phorum version " .
                 "{$info['required_version']} or higher is required " .
                 "for running this module." .
                 "</div>";
    }
    else
    {
        $links = array();
        if ($info["settings"]) {
            $setting_url = phorum_admin_build_url(array('module=modsettings','mod='.$name));
            $links[] = "<a name=\"link-settings-$name\" href=\"$setting_url\">" .
                      "Edit module settings</a>";
        }
        if(isset($info["url"])){
            $links[] = "<a target=\"_blank\" href=\"".$info["url"]."\">Visit web site</a>";
        }

        foreach(array('README','INSTALL','Changelog') as $file) {
            if(file_exists("./mods/$name/$file")){
                $add_url = phorum_admin_build_url(array('module=mods','mod='.$name,'info='.$file));
                $links[] = "<a href=\"$add_url\">View $file</a>";
            }
        }

        if (!empty($links)) {
            $html .= "<div class=\"module_links\">";
            $html .= implode(' | ', $links);
            $html .= "</div>";
        }
    }

    $html .= '</div></div>';
}

$html .= "<div style=\"text-align: center\" class=\"PhorumAdminTitle\">" .
         "<input type=\"submit\" value=\"Save settings\">" .
         "</div>" .
         "</form>";

print $html;

?>

<script type="text/javascript">
filter_modules(document.getElementById('modules_form'));
document.getElementById('filter_text').focus();
</script>
