{IF CHARSET}
<?php header("Content-Type: text/html; charset=".htmlspecialchars($PHORUM['DATA']['CHARSET'])); ?>
<?php echo '<?' ?>xml version="1.0" encoding="{CHARSET}"<?php echo '?>' ?>
{ELSE}
<?php echo '<?' ?>xml version="1.0" <?php echo '?>' ?>
{/IF}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="{LOCALE}">
<head>

<title>{HTML_TITLE}</title>

{LANG_META}

{IF PRINTVIEW} 
    <link rel="stylesheet" type="text/css" href="templates/{TEMPLATE}/styles/print.css" media="screen,print" />
    <meta name="robots" content="NOINDEX,NOFOLLOW"> 
{ELSE}
    <link rel="stylesheet" type="text/css" href="templates/{TEMPLATE}/styles/main.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="templates/{TEMPLATE}/styles/print.css" media="print" />
{/IF}
{IF URL->FEED}
    <link rel="alternate" type="{FEED_CONTENT_TYPE}" title="{FEED}" href="{URL->FEED}" />
{/IF}
{IF URL->REDIRECT}
    <meta http-equiv="refresh" content="{IF REDIRECT_TIME}{REDIRECT_TIME}{ELSE}5{/IF}; url={URL->REDIRECT}" />
{/IF}

{IF DESCRIPTION}
    <meta name="description" content="{DESCRIPTION}">
{/IF}

{HEAD_TAGS}

</head>
<!--
Some Icons courtesy of:
    FAMFAMFAM - http://www.famfamfam.com/lab/icons/silk/
    Tango Project - http://tango-project.org/
-->
<body onload="{IF FOCUS_TO_ID}var focuselt=document.getElementById('{FOCUS_TO_ID}'); if (focuselt) focuselt.focus();{/IF}">

{! Please leave this div in your template   you can alter anything above this line }
<div id="phorum">

<div id="user-info">
{IF USER->user_id}

{LANG->Welcome}, {USER->username} <small>(<a href="{URL->LOGINOUT}">{LANG->LogOut}</a>)</small>
<a class="icon icon-user-edit" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>
{IF USER->new_private_messages}
<strong><a class="icon icon-user-comment" href="{URL->PM}">{LANG->NewPrivateMessages}</a></strong>
{ELSE}
<a class="icon icon-user-comment" href="{URL->PM}">{LANG->PrivateMessages}</a>
{/IF}
{ELSE}
{LANG->Welcome}!
<a class="icon icon-key-go" href="{URL->LOGINOUT}">{LANG->LogIn}</a>
<a class="icon icon-user-add" href="{URL->REGISTERPROFILE}">{LANG->Register}</a>
{/IF}
</div>

<div id="logo">

<a href="{URL->BASE_URL}"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/logo.png" width="111" height="25" alt="Phorum" border="0" /></a>
</div>

<div id="breadcrumb">

{IF PHORUM_PAGE "control"}
    {! This is the control center }
    <a href="{URL->INDEX}">{TITLE}</a> &gt; <a href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a> &gt;
{ELSEIF PHORUM_PAGE "pm"}
    {! This is the control center }
    <a href="{URL->INDEX}">{TITLE}</a> &gt; <a href="{URL->PM}">{LANG->PrivateMessages}</a> &gt;
{ELSEIF PHORUM_PAGE "search"}
    {! This is the control center }
    <a href="{URL->INDEX}">{TITLE}</a> &gt;
{ELSEIF NAME AND NOT PHORUM_PAGE "list"}
    {! This is a read page }
    <a href="{URL->INDEX}">{TITLE}</a> &gt; <a href="{URL->LIST}">{NAME}</a> &gt;
{ELSEIF NAME}
    {! This is a forum page other than a read page or a folder page }
    <a href="{URL->INDEX}">{TITLE}</a> &gt;
{ELSE}
    {! This is the index }
    &nbsp;
{/IF}

</div>

<div id="top-right">
    <div id="search-area" class="icon-zoom">
        <form id="header-search-form" action="{URL->SEARCH}" method="get">
            <input type="hidden" name="forum_id" value="{FORUM_ID}" />
            <input type="hidden" name="match_forum" value="ALL" />
            <input type="hidden" name="match_dates" value="365" />
            <input type="hidden" name="match_type" value="ALL" />
            <input type="text" name="search" size="20" value="" class="styled-text" /><input type="submit" value="{LANG->Search}" class="styled-button" /><br />
            <a href="{URL->SEARCH}">{LANG->Advanced}</a>
        </form>
    </div>
</div>

<div id="top">

{IF HEADING}
    {! This is custom set heading }
    <h1>{HEADING}</h1>
    {IF DESCRIPTION}
        <div id="description">{DESCRIPTION}</div>
    {/IF}
{ELSEIF MESSAGE->subject}
    {! This is a threaded read page }
    <h1>{MESSAGE->subject}</h1>
{ELSEIF TOPIC->subject}
    {! This is a read page }
    <h1>{TOPIC->subject}</h1>
    <div id="description">{LANG->Postedby} {TOPIC->linked_author}&nbsp;</div>
{ELSEIF NAME}
    {! This is a forum page other than a read page or a folder page }
    <h1>{NAME}</h1>{! replace with path see http://www.phorum.org/cgi-bin/trac.cgi/ticket/213 }
    <div id="description">{DESCRIPTION}&nbsp;</div>
{ELSE}
    {! This is the index }
    <h1>{TITLE}</h1>
    <div id="description">{DESCRIPTION}&nbsp;</div>
{/IF}



</div>
   
{IF USER->NOTICE->SHOW}
    <div class="attention">
        <h4>{LANG->NeedsAttention}</h4>
        {IF USER->NOTICE->MESSAGES}<a class="icon icon-table-add" href="{URL->NOTICE->MESSAGES}">{LANG->UnapprovedMessagesLong}</a>{/IF}
        {IF USER->NOTICE->USERS}<a class="icon icon-user-add" href="{URL->NOTICE->USERS}">{LANG->UnapprovedUsersLong}</a>{/IF}
        {IF USER->NOTICE->GROUPS}<a class="icon icon-group-add" href="{URL->NOTICE->GROUPS}">{LANG->UnapprovedGroupMembers}</a>{/IF}
    </div>
{/IF}

{IF NOT PHORUM_PAGE "search"}
    {INCLUDE "paging"}
{/IF}

