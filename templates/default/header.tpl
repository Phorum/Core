<?php echo '<?xml version="1.0" encoding="iso-8859-1"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "DTD/xhtml1-transitional.dtd">
<html lang="<?php echo $PHORUM['locale']; ?>">
<head>
<title>{HTML_TITLE}</title>
<style type="text/css">
{include css}
</style>
{if URL->REDIRECT}
<meta http-equiv="refresh" content="5; url={URL->REDIRECT}" />
{/if}
{LANG_META}
{HEAD_TAGS}
</head>
<body>
<div align="{forumalign}">
<div class="PDDiv">
{IF notice_all}
<div class="PhorumNotificationArea PhorumNavBlock">
{IF PRIVATE_MESSAGES->new}<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->NewPrivateMessages}</a><br />{/IF}
{IF notice_messages}<a class="PhorumNavLink" href="{notice_messages_url}">{LANG->UnapprovedMessagesLong}</a><br />{/IF}
{IF notice_users}<a class="PhorumNavLink" href="{notice_users_url}">{LANG->UnapprovedUsersLong}</a><br />{/IF}
{IF notice_groups}<a class="PhorumNavLink" href="{notice_groups_url}">{LANG->UnapprovedGroupMembers}</a><br />{/IF}
</div>
{/IF}
<span class="PhorumTitleText PhorumLargeFont">
{IF NAME}<a href="{URL->TOP}">{NAME}</a>&nbsp;:&nbsp;{/IF}{TITLE}</span>
{IF DESCRIPTION}<div class="PhorumFloatingText">{DESCRIPTION}</div>{/IF}
<img src="templates/default/images/logo.png" alt="The fastest message board....ever." width="170" height="42" />
