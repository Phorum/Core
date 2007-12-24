<?php
if ($PHORUM['DATA']['CHARSET']) {
    header("Content-Type: text/html; charset=".htmlspecialchars($PHORUM['DATA']['CHARSET']));
    echo '<?xml version="1.0" encoding="'.$PHORUM['DATA']['CHARSET'].'"?>';
} else {
    echo '<?xml version="1.0" ?>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{LOCALE}" lang="{LOCALE}">
<head>

<title>{HTML_TITLE}</title>

{LANG_META}

{IF PRINTVIEW}
    <meta name="robots" content="NOINDEX,NOFOLLOW">
    <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="screen,print" />
{ELSE}
    <link rel="stylesheet" type="text/css" href="{URL->CSS}" media="screen" />
    <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="print" />
{/IF}
{IF URL->JAVASCRIPT}
    <script type="text/javascript" src="{URL->JAVASCRIPT}"></script>
{/IF}
{IF URL->FEED}
    <link rel="alternate" type="{FEED_CONTENT_TYPE}" title="{FEED}" href="{URL->FEED}" />
{/IF}
{IF URL->REDIRECT}
    <meta http-equiv="refresh" content="{IF REDIRECT_TIME}{REDIRECT_TIME}{ELSE}5{/IF}; url={URL->REDIRECT}" />
{/IF}

{IF DESCRIPTION}
    <meta name="description" content="{DESCRIPTION}" />
{/IF}

{HEAD_TAGS}

</head>
<body onload="{IF FOCUS_TO_ID}var focuselt=document.getElementById('{FOCUS_TO_ID}'); if (focuselt) focuselt.focus();{/IF}">

{! Please leave this div in your template   you can alter anything above this line }
<div id="phorum">
{IF NOT PRINTVIEW}
    <div id="user-info">
        {IF USER->user_id}
            {LANG->Welcome}, {USER->username} <small>(<a href="{URL->LOGINOUT}">{LANG->LogOut}</a>)</small>&nbsp;&nbsp;
            &raquo; <a class="icon" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>&nbsp;
            {IF ENABLE_PM}
            {IF USER->new_private_messages}
                &raquo; <strong><a class="icon" href="{URL->PM}">{LANG->NewPrivateMessages}</a></strong>
            {ELSE}
                &raquo; <a class="icon" href="{URL->PM}">{LANG->PrivateMessages}</a>
            {/IF}
            {/IF}
        {ELSE}
            {LANG->Welcome}!
            &raquo; <a class="icon" href="{URL->LOGINOUT}">{LANG->LogIn}</a>
            &raquo; <a class="icon" href="{URL->REGISTERPROFILE}">{LANG->Register}</a>
        {/IF}
    </div>

<div id="logo">

<a href="{URL->BASE}"><img src="{URL->TEMPLATE}/images/logo.png" width="111" height="25" alt="Phorum" border="0" /></a>
</div>

<div id="breadcrumb">
  {VAR FIRST TRUE}
  {LOOP BREADCRUMBS}
    {IF NOT FIRST} &gt;{/IF}
    {IF BREADCRUMBS->URL}
      <a {IF BREADCRUMBS->ID AND BREADCRUMBS->TYPE}rel="breadcrumb-{BREADCRUMBS->TYPE}[{BREADCRUMBS->ID}]"{/IF} href="{BREADCRUMBS->URL}">{BREADCRUMBS->TEXT}</a>
    {ELSE}
      {BREADCRUMBS->TEXT}
    {/IF}
    {VAR FIRST FALSE}
  {/LOOP BREADCRUMBS}
</div>

<div id="top-right">
    <div id="search-area" class="icon-zoom">
        <form id="header-search-form" action="{URL->SEARCH}" method="get">
            {POST_VARS}
            <input type="hidden" name="phorum_page" value="search" />
            <input type="hidden" name="match_forum" value="ALL" />
            <input type="hidden" name="match_dates" value="365" />
            <input type="hidden" name="match_threads" value="1" />
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
    {IF HTML_DESCRIPTION}
        <div id="description">{HTML_DESCRIPTION}</div>
    {/IF}
{ELSEIF MESSAGE->subject}
    {! This is a threaded read page }
    <h1>{MESSAGE->subject}</h1>
{ELSEIF TOPIC->subject}
    {! This is a read page }
    <h1>{TOPIC->subject}</h1>
    <div id="description">{LANG->Postedby} {IF TOPIC->URL->PROFILE}<a href="{TOPIC->URL->PROFILE}">{/IF}{TOPIC->author}{IF TOPIC->URL->PROFILE}</a>{/IF}&nbsp;</div>
{ELSEIF NAME}
    {! This is a forum page other than a read page or a folder page }
    <h1>{NAME}</h1>{! replace with path see http://www.phorum.org/cgi-bin/trac.cgi/ticket/213 }
    {IF HTML_DESCRIPTION}
      <div id="description">{HTML_DESCRIPTION}&nbsp;</div>
    {/IF}
{ELSE}
    {! This is the index }
    <h1>{TITLE}</h1>
    {IF HTML_DESCRIPTION}
      <div id="description">{HTML_DESCRIPTION}&nbsp;</div>
    {/IF}
{/IF}



</div>

{IF GLOBAL_ERROR}<div class="attention">{GLOBAL_ERROR}</div>{/IF}

{IF USER->NOTICE->SHOW}
    <div class="attention">
        <h4>{LANG->NeedsAttention}</h4>
        {IF USER->NOTICE->MESSAGES}<a class="icon icon-table-add" href="{URL->NOTICE->MESSAGES}">{LANG->UnapprovedMessagesLong}</a>{/IF}
        {IF USER->NOTICE->USERS}<a class="icon icon-user-add" href="{URL->NOTICE->USERS}">{LANG->UnapprovedUsersLong}</a>{/IF}
        {IF USER->NOTICE->GROUPS}<a class="icon icon-group-add" href="{URL->NOTICE->GROUPS}">{LANG->UnapprovedGroupMembers}</a>{/IF}
    </div>
{/IF}

{/IF}
