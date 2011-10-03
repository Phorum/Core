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

    if(!defined("PHORUM_ADMIN")) return;

    // load the default Phorum language
    if(isset($PHORUM["default_forum_options"]["language"])){
        $lang = basename($PHORUM["default_forum_options"]["language"]);
        if (!file_exists("./include/lang/${lang}.php")) {
            $lang = PHORUM_DEFAULT_LANGUAGE;
        }
        include_once( "./include/lang/{$lang}.php" );
    }

    // HTTP Content-Type header with the charset from the default language
    if (isset($PHORUM["DATA"]['CHARSET'])) {
        header("Content-Type: text/html; " .
               "charset=".htmlspecialchars($PHORUM["DATA"]['CHARSET']));
    }

    // set the path to the CSS file to pull in
    $default_admin_css_file = 'default.css';
    $admin_css_path = dirname($PHORUM['admin_http_path']) .
                      '/include/admin/css/' . $default_admin_css_file;

    /**
     * [hook]
     *     admin_css_file
     *
     * [description]
     *     This hook can be used to pull in an alternate css file for the admin screens.
     *     That's pretty much all it's useful for. This hook is allowed to change the path to
     *     the admin css files because if we didn't allow it, someone would request it.
     *
     * [category]
     *     Admin interface
     *
     * [when]
     *     Just before output begins on the admin page.
     *
     * [input]
     *     A string containing the path to the css file which will be used for the admin page.
     *
     * [output]
     *     The path to the actual css file to use.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_admin_css_file($cssfile)
     *     {
     *         // Force admin screens to use the "bar.css" style sheet.
     *         $pieces = explode('/', $cssfile);
     *         $pieces[count($pieces)-1] = 'bar.css';
     *         $cssfile = implode('/', $pieces);
     *         return $cssfile;
     *     }
     *     </hookcode>
     */

    if (isset($PHORUM['hooks']['admin_css_file'])) {
        $admin_css_path = phorum_hook('admin_css_file', $admin_css_path);
    }

?>
<html>
<head>
<title>Phorum Admin</title>
<?php

// meta data with the charset from the default language
if (isset($PHORUM["DATA"]['CHARSET'])) {
    echo "<meta content=\"text/html; charset=".$PHORUM["DATA"]["CHARSET"]."\" http-equiv=\"Content-Type\">\n";
}

?>

<script src="<?php print htmlspecialchars(dirname($PHORUM['admin_http_path']) . "/javascript." . PHORUM_FILE_EXTENSION) ?>?admin=1" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($admin_css_path); ?>" />

<script>

function show_help(key)
{
    if (document.all) {
        topoffset=document.body.scrollTop;
        leftoffset=document.body.scrollLeft;
        WIDTH=document.body.clientWidth;
        HEIGHT=document.body.clientHeight;
    } else {
        topoffset=pageYOffset;
        leftoffset=pageXOffset;
        WIDTH=window.innerWidth;
        HEIGHT=window.innerHeight;
    }

    if(WIDTH%2!=0) WIDTH--;
    if(HEIGHT%2!=0) HEIGHT--;

    newtop=((HEIGHT-200)/2)+topoffset;

    // IE still puts selects on top of stuff so it has to be fixed to the left some
    if (document.all) {
        newleft=150;
    } else {
        newleft=((WIDTH-400)/2)+leftoffset;
    }

    document.getElementById('helpdiv').style.left=newleft;
    document.getElementById('helpdiv').style.top=newtop;

    document.getElementById('help-title').innerHTML = help[key][0];
    document.getElementById('help-text').innerHTML = help[key][1];

    document.getElementById('helpdiv').style.display = 'block';

}

function hide_help()
{
    document.getElementById('helpdiv').style.display = 'none';
    document.getElementById('help-title').innerHTML = "";
    document.getElementById('help-text').innerHTML = "";
}

</script>
</head>
<body>
<div id="helpdiv">
<div id="helpdiv-hide"><a href="javascript:hide_help();"><img border="0" src="<?php print $PHORUM['http_path'] ?>/images/close.gif" height="16" width="16" /></a></div>
<div id="helpdiv-title">&nbsp;Phorum Admin Help</div>
<div id="helpdiv-content">
<div id="help-title"></div>
<div id="help-text"></div>
</div>
</div>

<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
    <td class="statusbar_edge">Phorum Admin<small><br />version <?php echo PHORUM; ?></small></td>
<?php if(empty($module)){ // only show the versioncheck if you are on the front page of the admin ?>
    <td class="statusbar_edge" align="center" valign="middle">
      <iframe scrolling="no" frameborder="0" align="top" width="400" height="35" src="versioncheck.php"></iframe>
    </td>
<?php } else {
    // Reset the cookie that is used for the version check.
    setcookie("phorum_upgrade_available", '', time()-86400,
              $PHORUM["session_path"], $PHORUM["session_domain"]);
} ?>
    <td class="statusbar_edge" align="center" valign="middle">
<?php
    require_once('./include/api/modules.php');
    $updates = phorum_api_modules_check_updated_info();
    if (!empty($updates)) {
        phorum_api_modules_save();
        print "<div style=\"padding:5px;background-color:#fffff0;".
              "border:2px solid orange; text-align:left\">" .
              "<strong>Notification:</strong> " .
              "Updated module info for module".(count($updates)==1?"":"s") .
              (count($updates)>10 ? "" : ":" . implode(", ", $updates)) .
              "</div>";
    }
?>
    </td>
    <td class="statusbar_edge" align="right">

    <div id="phorum-status">
<?php if($module!="login" && $module!="install" && $module!="upgrade"){ ?>
<form id="status-form" action="<?php echo phorum_admin_build_url('base'); ?>" method="post">
<input type="hidden" name="phorum_admin_token" value="<?php echo $PHORUM['admin_token'];?>" />
<input type="hidden" name="module" value="status" />
Phorum Status:
<select name="status" onChange="this.form.submit();">
<option value="normal" <?php if($PHORUM["status"]=="normal") echo "selected"; ?>>Normal</option>
<option value="read-only"<?php if($PHORUM["status"]=="read-only") echo "selected"; ?>>Read Only</option>
<option value="admin-only"<?php if($PHORUM["status"]=="admin-only") echo "selected"; ?>>Admin Only</option>
<option value="disabled"<?php if($PHORUM["status"]=="disabled" || !phorum_db_check_connection()) echo "selected"; ?>>Disabled</option>
</select>
</form>
<?php } ?>
</div>
<?php if(isset($PHORUM['user'])) { ?>
<small>Logged In As <?php echo $PHORUM["user"]["username"]; ?></small>
<?php } ?>
</td>
</tr>
</table><br />
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<?php

    if($module!="login" && $module!="install" 
    && $module!="upgrade" && $module !="tokenmissing" ){
?>
<tr>
    <td valign="top">
<?php
        include_once "./include/admin/PhorumAdminMenu.php";
        include_once "./include/admin/PhorumAdminMenuHookPosition.php";

        /*
         * [hook]
         *     admin_menu
         *
         * [availability]
         *     Phorum 5.2.16
         *
         * [description]
         *     This hook allows to inject custom HTML into the Phorum admin
         *     menu. The hook will receive an instance of
         *     PhorumAdminMenuHookPosition which is required to determine at
         *     which position in the Phorum admin menu the module author wishes
         *     to place his custom menu. Although any HTML can be injected, it
         *     is advised to use the PhorumAdminMenu class.
         *
         *     Use the methods appendAt(position, html) and appendLast(html) to
         *     tell where you want them to appear.
         *
         * [category]
         *     Admin interface
         *
         * [when]
         *     Admin header
         *
         * [input]
         *     Object of PhorumAdminMenuHookPosition
         *
         * [output]
         *     Return the object
         *
         * [example]
         *     <hookcode>
         *     function phorum_mod_foo_admin_menu($pos)
         *     {
         *         $menu = new PhorumAdminMenu("MyImportantLinks");
         *         $menu->addCustom(
         *             "Event log",
         *             phorum_admin_build_url(array(
         *                 'module=modsettings',
         *                 'mod=event_logging',
         *                 'el_action=logviewer'
         *             ))
         *         );
         *         $menu->addCustom(
         *             "My module subpage",
         *             phorum_admin_build_url(array(
         *                 'module=modsettings',
         *                 'mod=foo',
         *                 'action=subpage'
         *             ))
         *         );
         *
         *         $pos->appendLast($menu->getHtml());
         *
         *         $menu = new PhorumAdminMenu("Who rocks?");
         *         $menu->addCustom(
         *             "Guess!",
         *             "http://phorum.org/",
         *             "Phorum rocks!",
         *             "_blank"
         *         );
         *
         *         $pos->appendAt(0, $menu->getHtml());
         *
         *         return $pos;
         *     }
         *     </hookcode>
         */
        $layout = new PhorumAdminMenuHookPosition();
        # It's an object, not necessary to return/re-assign it. And in case
        # some hook forgets to return, saves us troubles.
        phorum_hook('admin_menu', $layout);
        $layout->reorderPositions();

        echo $layout->fetchAndRemoveNext();

        $menu = new PhorumAdminMenu("Main Menu");

        $menu->add("Admin Home", "", "Takes you to the default Admin page.");
        $menu->add("Phorum Index", "index", "Takes you to the front page of the Phorum.");
        $menu->add("Log Out", "logout", "Logs you out of the admin.");

        $menu->show();

        echo $layout->fetchAndRemoveNext();

        $menu = new PhorumAdminMenu("Global Settings");

        $menu->add("General Settings", "settings", "Edit the global settings which affect the enter installation.");
        $menu->add("Cache Settings", "cache", "Edit the cache settings, like which cache layer to use and what to cache.");
        $menu->add("Ban Lists", "banlist", "Edits the list of banned names, email addresses and IP addresses.");
        $menu->add("Censor List", "badwords", "Edit the list of words that are censored in posts.");
        $menu->add("Modules", "mods", "Administer the Phorum Modules that are installed.");

        $menu->show();

        echo $layout->fetchAndRemoveNext();

        $menu = new PhorumAdminMenu("Forums");

        $menu->add("Manage Forums", "", "Takes you to the default Admin page.");
        $menu->add("Default Settings", "forum_defaults", "Allows you to set defaults settings that can be inherited by forums.");
        $menu->add("Create Forum", "newforum", "Creates a new area for your users to post messages.");
        $menu->add("Create Folder", "newfolder", "Creates a folder which can contain other folders of forums.");

        $menu->show();

        echo $layout->fetchAndRemoveNext();

        $menu = new PhorumAdminMenu("Users/Groups");

        $menu->add("Edit Users", "users", "Allows administrator to edit users including deactivating them.");
        $menu->add("Edit Groups", "groups", "Allows administrator to edit groups and their forum permissions.");
        $menu->add("Custom Profiles", "customprofile", "Allows administrator to add fields to Phorum profile.");

        $menu->show();

        echo $layout->fetchAndRemoveNext();

        $menu = new PhorumAdminMenu("Maintenance");

        $menu->add("Check For New Version", "version", "Check for new releases.");
        $menu->add("Database Integrity", "rebuild", "Database Integrity Actions");
        $menu->add("Prune Messages", "message_prune", "Pruning old messages.");
        $menu->add("Purge Stale Files", "file_purge", "Purging stale files from the database.");
        $menu->add("Purge cache", "cache_purge", "Purging the Phorum cache.");
        $menu->add("System Sanity Checks", "sanity_checks", "Perform a number of sanity checks on the system to identify possible problems.");
        $menu->add("Manage Language Files", "manage_languages", "Allows administrator to create new or updated versions of language files.");

        $menu->show();

        echo $layout->fetchAndRemoveRemaining();

?>
<img src="<?php echo "$PHORUM[http_path]/images/trans.gif"; ?>" alt="" border="0" width="150" height="1" />
    </td>
    <td valign="top"><img src="<?php echo "$PHORUM[http_path]/images/trans.gif"; ?>" alt="" border="0" width="15" height="15" /></td>
<?php
    }
?>
    <td valign="top" width="100%">
