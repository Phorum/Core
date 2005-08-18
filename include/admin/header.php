<?php



    if(!defined("PHORUM_ADMIN")) return;

    if(empty($PHORUM["http_path"])){
        $PHORUM["http_path"]=dirname($_SERVER["PHP_SELF"]);
    }

?>
<html>
<head>
<title>Phorum Admin</title>
<?php

// load the charset from the default Phorum language if there is one
if(isset($PHORUM["default_language"])){
    include_once( "./include/lang/$PHORUM[default_language].php" );
    echo "<meta content=\"text/html; charset=".$PHORUM["DATA"]["CHARSET"]."\" http-equiv=\"Content-Type\">\n";
}

?>
<style>

body
{
    font-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;
    font-size: 13px;
}

input, textarea, select, td
{
    font-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;
    font-size: 13px;
    border-color: #EEEEEE;
}

.input-form-th
{
    font-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;
    font-size: 13px;
    padding: 3px;
    background-color: #DDDDEA;
}

.input-form-td
{
    font-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;
    font-size: 13px;
    padding: 3px;
    background-color: #EEEEFA;
}

.input-form-td-break, .PhorumAdminTitle
{
    font-family: "Trebuchet MS",Verdana, Arial, Helvetica, sans-serif;
    font-size: 16px;
    font-weight: bold;
    padding: 3px;
    background-color: Navy;
    color: White;
}

.input-form-td-message
{
    font-family: "Trebuchet MS",Verdana, Arial, Helvetica, sans-serif;
    font-size: 13px;
    padding: 10px;
    background-color: White;
    color: Black;
}

.PhorumAdminMenu
{
    width: 150px;
    border: 1px solid Navy;
    font-size: 13px;
    margin-bottom: 3px;
    line-height: 18px;
    padding: 3px;
}

.PhorumAdminMenuTitle
{
    width: 150px;
    border: 1px solid Navy;
    background-color: Navy;
    color:  white;
    font-size: 14px;
    font-weight: bold;
    padding: 3px;
}

.PhorumAdminTableRow
{
    background-color: #EEEEFA;
    color: Navy;
    padding: 3px;
    font-size: 13px;
}

.PhorumAdminTableHead
{
    background-color: Navy;
    color: White;
    padding: 3px;
    font-weight: bold;
    font-size: 13px;
}

.PhorumInfoMessage
{
    font-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;
    font-size: 13px;
    padding: 3px;
    background-color: #EEEEFA;
    width: 300px;
    align: center;
    text-align: left;
}

.PhorumAdminError
{
    font-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;
    font-size: 13px;
    font-weight: bold;
    padding: 3px;
    color: #000000;
    border: 1px solid red;
}

.small
{
    font-size: 10px;
}

.help-td, .help-td a
{
    color: White;
    padding-bottom: 2px;
    text-decoration: none;
}

#phorum-status
{
    vertical-align: middle;
}

#status-form
{
    display: inline;
}

</style>
<script>

function show_help(help_text)
{
    if (document.all) {
        topoffset=document.body.scrollTop;
        leftoffset=document.body.scrollLeft;
    } else {
        topoffset=pageYOffset;
        leftoffset=pageXOffset;
    }

    newtop=((getClientHeight()-200)/2)+topoffset;
    newleft=((getClientWidth()-400)/2)+leftoffset;

    move_div('helpdiv', newtop, newleft)

    show_dhtml_popup('help', '<?php echo $_SERVER['PHP_SELF']; ?>?module=help&text=' + help_text);
}

function hide_help()
{
    if (!document.layers) {
      set_dhtml_frame_url('help', '<?php echo $_SERVER['PHP_SELF']; ?>?module=help');
    }
    hide_dhtml_popup('help');
}

<?php include "./include/dhtml_popup.js"; ?>

</script>
</head>
<body>
<?php if($module!="login" && $module!="install" && $module!="upgrade"){ ?>
<div id="helpdiv" style="position: absolute; visibility: hidden; width: 400px; height: 200px; border-style: solid; border-width: 2px; border-color: Navy;">
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
    <td bgcolor="Navy" class="help-td">&nbsp;Phorum Admin Help</td>
    <td bgcolor="Navy" align="right" class="help-td"><a href="javascript:hide_help();"><img border="0" src="images/close.gif" height="16" width="16" /></a></td>
</tr>
<tr>
    <td colspan="2">
        <iframe src="<?php echo $_SERVER['PHP_SELF']; ?>?module=help" style="position: relative; background-color: White;" frameborder="0" id="helpframe" width="100%" height="177"></iframe>
    </td>
</tr>
</table>
</div>
<?php } ?>

<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
    <td style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: Navy;">Phorum Admin<small><br />version <?php echo PHORUM; ?></small></td>
    <td style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: Navy;" align="right">
<div id="phorum-status">
<?php if($module!="login" && $module!="install" && $module!="upgrade"){ ?>
<form id="status-form" action="<?=$_SERVER["PHP_SELF"]?>" method="post">
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

    if($module!="login" && $module!="install" && $module!="upgrade"){
?>
<tr>
    <td valign="top">
<?php
        include_once "./include/admin/PhorumAdminMenu.php";

        $menu =& new PhorumAdminMenu("Main Menu");

        $menu->add("Admin Home", "", "Takes you to the default Admin page.");
        $menu->add("Phorum Index", "index", "Takes you to the front page of the Phorum.");
        $menu->add("Log Out", "logout", "Logs you out of the admin.");

        $menu->show();

        $menu =& new PhorumAdminMenu("Global Settings");

        $menu->add("General Settings", "settings", "Edit the global settings which affect all forums.");
        $menu->add("Ban Lists", "banlist", "Edits the list of banned names, email addresses and IP addresses.");
        $menu->add("Censor List", "badwords", "Edit the list of words that are censored in posts.");
        $menu->add("Modules", "mods", "Administer the Phorum Modules that are installed.");

        $menu->show();

        $menu =& new PhorumAdminMenu("Forums");

        $menu->add("Manage Forums", "", "Takes you to the default Admin page.");
        $menu->add("Create Forum", "newforum", "Creates a new area for your users to post messages.");
        $menu->add("Create Folder", "newfolder", "Creates a folder which can contain other folders of forums.");

        $menu->show();

        $menu =& new PhorumAdminMenu("Users/Groups");

        $menu->add("Edit Users", "users", "Allows administrator to edit users including deactivating them.");
        $menu->add("Edit Groups", "groups", "Allows administrator to edit groups and their forum permissions.");
        $menu->add("Custom Profiles", "customprofile", "Allows administrator to add fields to Phorum profile.");

        $menu->show();
        $menu =& new PhorumAdminMenu("Maintenance");

        $menu->add("Prune Messages", "message_prune", "Pruning old messages.");

        $menu->show();

?>
<img src="<?php echo "$PHORUM[http_path]/images/trans.gif"; ?>" alt="" border="0" width="150" height="1" />
    </td>
    <td valign="top"><img src="<?php echo "$PHORUM[http_path]/images/trans.gif"; ?>" alt="" border="0" width="15" height="15" /></td>
<?php
    }
?>
    <td valign="top" width="100%">
