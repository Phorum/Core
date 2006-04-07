{IF CHARSET}
<?php header("Content-Type: text/html; charset=".htmlspecialchars($PHORUM['DATA']['CHARSET'])); ?>
{/IF}
<?php echo '<?' ?>xml version="1.0" encoding="{CHARSET}"<?php echo '?>' ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="{LOCALE}">
<head>
<link rel="stylesheet" type="text/css" href="templates/{TEMPLATE}/styles/main.css" media="screen,print" />
{IF URL->RSS}
<link rel="alternate" type="application/rss+xml" title="RSS-Feed" href="{URL->RSS}" />
{/IF}
{IF URL->REDIRECT}
<meta http-equiv="refresh" content="{IF REDIRECT_TIME}{REDIRECT_TIME}{ELSE}5{/IF}; url={URL->REDIRECT}" />
{/IF}
{LANG_META}
<title>{HTML_TITLE}</title>
{HEAD_TAGS}
</head>
<body>

{! Please leave this div in your template   you can alter anything above this line }
<div id="phorum">

<div id="top">
<div id="user-info">
{IF USER->user_id}
{LANG->Welcome}, {USER->username} <small>(<a href="{URL->LOGINOUT}">{LANG->LogOut}</a>)</small>
<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/user_edit.png');" href="URL->REGISTERPROFILE">{LANG->MyProfile}</a>
{IF USER->new_private_messages}
<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/user_comment.png');" href="{URL->PM}">{LANG->NewPrivateMessages}</a>
{ELSE}
<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/user_comment.png');" href="{URL->PM}">{LANG->PrivateMessages}</a>
{/IF}
{IF notice_all}
{IF USER->notice_messages}<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/table_add.png');" href="{URL->notice_messages}">{LANG->UnapprovedMessagesLong}</a>{/IF}
{IF USER->notice_users}<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/user_add.png');" href="{URL->notice_users}">{LANG->UnapprovedUsersLong}</a>{/IF}
{IF USER->notice_groups}<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/group_add.png');" href="{URL->notice_groups}">{LANG->UnapprovedGroupMembers}</a>{/IF}
{/IF}
{ELSE}
{LANG->Welcome}!
<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/key_go.png');" href="{URL->LOGINOUT}">{LANG->LogIn}</a>
<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/user_add.png');" href="{URL->REGISTERPROFILE}">{LANG->Register}</a>
{/IF}
</div>

{IF NAME}
<h2><a href="{URL->INDEX}">{TITLE}</a> > </h2>
<h1>{NAME}</h1>{! replace with path see http://www.phorum.org/cgi-bin/trac.cgi/ticket/213 }
{ELSE}
<h1>{TITLE}</h1>
{/IF}
<div id="description">{DESCRIPTION}&nbsp;</div>

</div>

<div id="search-area">
<form id="header-search-form" action="{URL->SEARCH}" method="get">
<input type="hidden" name="forum_id" value="{FORUM_ID}" />
<input type="hidden" name="match_forum" value="ALL" />
<input type="hidden" name="match_dates" value="365" />
<input type="hidden" name="match_type" value="ALL" />
{LANG->Search}: <input type="text" name="search" size="20" value="" /><input type="submit" value="{LANG->Search}" /> <a href="{URL->SEARCH}">{LANG->Advanced}</a>
</form>
</div>

