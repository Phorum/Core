<?php



    if(!defined("PHORUM_ADMIN")) return;

    if(empty($PHORUM["http_path"])){
        $PHORUM["http_path"]=dirname($_SERVER["PHP_SELF"]);
    }

?>
<html>
<head>
<title>Phorum Admin</title>
<style>

    BODY
    {
        font-family: Lucida Sans Unicode, Lucida Grand, Verdana, Arial, Helvetica;
        font-size: 13px;
    }

    INPUT, TEXTAREA, SELECT, TD
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
        border-width: 1px;
        border-style: solid;
        border-color: Navy;
        font-size: 13px;
        margin-bottom: 3px;
    }

    .PhorumAdminMenuTitle
    {
        background-color: Navy;
        color:  white;
        font-size: 14px;
        font-weight: bold;
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
<?php if($module!="login" && $module!="install"){ ?>
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
    <td style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: Navy;">Phorum Admin<span class="small"><br />version <?php echo PHORUM; ?></span></td>
    <td style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: Navy;" align="right">Database <?php echo (phorum_db_check_connection()) ? "Connected" : "Not Connected"; ?><span class="small"><br />Logged In As <?php echo $PHORUM["user"]["username"]; ?></span></td>
</tr>
</table><br />
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<?php

    if($module!="login" && $module!="install"){
?>
<tr>
    <td valign="top">
<?php
        include_once "./include/admin/PhorumAdminMenu.php";

        $menu =& new PhorumAdminMenu("Main Menu", 150);

        $menu->add("Admin Home", "", "Takes you to the default Admin page.");
        $menu->add("Phorum Index", "index", "Takes you to the front page of the Phorum.");
        $menu->add("Log Out", "logout", "Logs you out of the admin.");

        $menu->show();

        $menu =& new PhorumAdminMenu("Global Settings", 150);

        $menu->add("General Settings", "settings", "Edit the global settings which affect all forums.");
        $menu->add("Ban Lists", "banlist", "Edits the list of banned names, email addresses and IP addresses.");
        $menu->add("Censor List", "badwords", "Edit the list of words that are censored in posts.");
        $menu->add("Modules", "mods", "Administer the Phorum Modules that are installed.");

        $menu->show();

        $menu =& new PhorumAdminMenu("Forums", 150);

        $menu->add("Manage Forums", "", "Takes you to the default Admin page.");
        $menu->add("Create Forum", "newforum", "Creates a new area for your users to post messages.");
        $menu->add("Create Folder", "newfolder", "Creates a folder which can contain other folders of forums.");

        $menu->show();

        $menu =& new PhorumAdminMenu("Users/Groups", 150);

        $menu->add("Edit Users", "users", "Allows administrator to edit users including deactivating them.");
        $menu->add("Edit Groups", "groups", "Allows administrator to edit groups and their forum permissions.");
        $menu->add("Custom Profiles", "customprofile", "Allows administrator to add fields to Phorum profile.");

        $menu->show();
        $menu =& new PhorumAdminMenu("Maintenance", 150);        

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
