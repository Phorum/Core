{! The doctype declaration, which tells the browser what version of markup }
{! is used in the document and what character set to use. Leave this }
{! untouched, unless you know what you are doing. The default doctype }
{! is targeted at "Standards Mode" in XHTML 1.0 Transitional. For more }
{! info on this subject, see http://hsivonen.iki.fi/doctype/ }
<?php
if ($PHORUM['DATA']['CHARSET']) {
    header("Content-Type: text/html; charset=".htmlspecialchars($PHORUM['DATA']['CHARSET']));
    echo '<?xml version="1.0" encoding="'.$PHORUM['DATA']['CHARSET'].'"?>';
} else {
    echo '<?xml version="1.0" ?>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!-- START TEMPLATE header.tpl -->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{LOCALE}" lang="{LOCALE}">

<head>

<title>{HTML_TITLE}</title>

{! Language meta data from the language file ($PHORUM['DATA']['LANG_META']). }
{IF LANG_META}{LANG_META}{/IF}

{! Load CSS code. This code origins from css.tpl, css_print.tpl. }
{! Additionally, modules can add their own CSS code to these, using the }
{! "css_register" module hook. }
{IF PRINTVIEW}
  <meta name="robots" content="NOINDEX,NOFOLLOW">
  <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="screen,print" />
{ELSE}
  <link rel="stylesheet" type="text/css" href="{URL->CSS}" media="screen" />
  <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="print" />
{/IF}

{! Load Javascript code. This code origins from core Phorum javascript }
{! code, template javascript code (templates/.../javascript.tpl) and }
{! modules that add their code using the "javascript_register" module hook. }
<script type="text/javascript" src="{URL->JAVASCRIPT}"></script>

{! Add links to the available RSS feeds. }
{IF FEEDS}
  {LOOP FEEDS}
  <link rel="alternate" type="{FEED_CONTENT_TYPE}" title="{FEEDS->TITLE}" href="{FEEDS->URL}" />
  {/LOOP FEEDS}
{/IF}

{! Sometimes, a page redirect is needed. This code is used to redirect the }
{! browser to a different page, if a URL->REDIRECT is set from Phorum. }
{IF URL->REDIRECT}
  <meta http-equiv="refresh" content="{IF REDIRECT_TIME}{REDIRECT_TIME}{ELSE}5{/IF}; url={URL->REDIRECT}" />
{/IF}

{! The meta description for the page. This is initially filled from the }
{! option "Phorum Description" under "General Settings" in the Phorum }
{! admin interface. Modules can override this description by overriding }
{! the template variable $PHORUM['DATA']['DESCRIPTION']. }
{IF DESCRIPTION}
  <meta name="description" content="{DESCRIPTION}" />
{/IF}

{! Additional tags for the <head> section of the page. This is initially }
{! filled from the option "Phorum Head Tags" under "General Settings" in }
{! the Phorum admin interface. Modules that need to add data to the <head> }
{! section dynamically can do so by adding that data to the template }
{! variable $PHORUM['DATA']['HEAD_TAGS']. }
{HEAD_TAGS}

{! A special hack for being able to set the max width for the #phorum }
{! container in MSIE6 and before. This uses the width that is set from }
{! settings.tpl in the max_width_ie variable. If you want to disable }
{! this hack, then you can delete this code or set max_width_id to zero }
{IF max_width_ie}
  <!--[if lte IE 6]>
  <style type="text/css">
  #phorum {
  width:       expression(document.body.clientWidth > {max_width_ie}
               ? '{max_width_ie}px': 'auto' );
  margin-left: expression(document.body.clientWidth > {max_width_ie}
               ? parseInt((document.body.clientWidth-{max_width_ie})/2) : 0 );
  }
  </style>
  <![endif]-->
{/IF}

<!--
Some Icons courtesy of:
  FAMFAMFAM - http://www.famfamfam.com/lab/icons/silk/
  Tango Project - http://tango-project.org/
-->
</head>

{! Start of the page body. }
{! The default onload code for the <body> uses the FOCUS_TO_ID template }
{! variable to specify what page element should get the focus. }
<body onload="{IF FOCUS_TO_ID}var focuselt=document.getElementById('{FOCUS_TO_ID}'); if (focuselt) focuselt.focus();{/IF}">

  {! Please, always keep this <div> in your template and do not change its id }
  {! It acts as the main Phorum content container, which will be used for }
  {! styling the pages using CSS and possibly for finding the Phorum content }
  {! through JavaScript. If you are creating your own template, we advice you }
  {! to also keep all other id=".." and class=".." properties from the }
  {! template files in your code, unless you know what you are doing by }
  {! changing them in relation to CSS and JavaScript. }
  <div id="phorum">

  {IF NOT PRINTVIEW}

    {! This <div> shows code that relates to the currently active Phorum user }
    {! or shows code for logging in or creating a new profile if there is no }
    {! user logged in. }
    <div id="user-info" class="{IF LOGGEDIN}logged-in{ELSE}logged-out{/IF}">

      {! Code for logged in users }
      {IF LOGGEDIN}
        <span class="welcome">{LANG->Welcome}, {USER->username}</span>
        <a class="icon icon-key-delete" href="{URL->LOGINOUT}">{LANG->LogOut}</a>
        <a class="icon icon-user-edit" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>
        {IF ENABLE_PM}
          <a class="icon icon-user-comment" href="{URL->PM}">
            {IF USER->new_private_messages}
              <strong>{LANG->NewPrivateMessages}</strong>
            {ELSE}
              {LANG->PrivateMessages}
            {/IF}
          </a>
        {/IF}

      {! Code for anonymous users }
      {ELSE}
        <span class="welcome">{LANG->Welcome}!</span>
        <a class="icon icon-key-go" href="{URL->LOGINOUT}">{LANG->LogIn}</a>
        <a class="icon icon-user-add" href="{URL->REGISTERPROFILE}">{LANG->Register}</a>
      {/IF}

    </div> <!-- end of div id=user-info -->

    {! This <div> holds the site logo. If you provide a different logo in }
    {! images/logo.png, then change logo_width and logo_height in the }
    {! settings.tpl file to match the size of your logo image. }
    <div id="logo">
      <a href="{URL->BASE}">
        <img src="{URL->TEMPLATE}/images/logo.png"
             width="{logo_width}" height="{logo_height}"
             alt="Phorum" border="0" />
      </a>
    </div> <!-- end of div id=logo -->

    {! This <div> holds the breadcrumb navigation code. This breadcrumb }
    {! navigation shows the user where he is on the site, relative to }
    {! the Phorum start location (leaving a "breadcrumb" at every step }
    {! deeper into the site structure.) }
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
    </div> <!-- end of div id=breadcrumb -->

    {! This div holds the search form }
    <div id="search-area" class="icon-zoom">
      <form id="header-search-form" action="{URL->SEARCH}" method="get">
        {POST_VARS}
        <input type="hidden" name="phorum_page" value="search" />
        <input type="hidden" name="match_forum" value="ALL" />
        <input type="hidden" name="match_dates" value="365" />
        <input type="hidden" name="match_threads" value="0" />
        <input type="hidden" name="match_type" value="ALL" />
        <input type="text" name="search" size="20" value="" class="styled-text" /><input type="submit" value="{LANG->Search}" class="styled-button" /><br />
        <a href="{URL->SEARCH}">{LANG->Advanced}</a>
      </form>
    </div> <!-- end of div id=search-area -->

    {! This <div> holds info about the active page (heading and description) }
    <div id="page-info">

      {IF HEADING}
        {! This is custom set heading }
          <h1 class="heading">{HEADING}</h1>
        {IF HTML_DESCRIPTION}
          <div class="description">{HTML_DESCRIPTION}</div>
        {/IF}
      {ELSEIF MESSAGE->subject}
        {! This is a threaded read page }
        <h1 class="heading">{MESSAGE->subject}</h1>
      {ELSEIF TOPIC->subject}
        {! This is a read page }
        <h1 class="heading">{TOPIC->subject}</h1>
        <div class="description">{LANG->Postedby} {IF TOPIC->URL->PROFILE}<a href="{TOPIC->URL->PROFILE}">{/IF}{TOPIC->author}{IF TOPIC->URL->PROFILE}</a>{/IF}&nbsp;</div>
      {ELSEIF NAME}
        {! This is a forum page other than a read page or a folder page }
        <h1 class="heading">{NAME}</h1>{! replace with path see http://www.phorum.org/cgi-bin/trac.cgi/ticket/213 }
        {IF HTML_DESCRIPTION}
          <div class="description">{HTML_DESCRIPTION}&nbsp;</div>
        {/IF}
      {ELSE}
        {! This is the index }
        <h1 class="heading">{TITLE}</h1>
        {IF HTML_DESCRIPTION}
          <div class="description">{HTML_DESCRIPTION}&nbsp;</div>
        {/IF}
      {/IF}

    </div> <!-- end of div id=page-info -->

    {! The template variable GLOBAL_ERROR can be used to show an error }
    {! message at the start of the page. }
    {IF GLOBAL_ERROR}
      <div id="global-error" class="attention">
        {GLOBAL_ERROR}
      </div>
    {/IF}

    {! Various notices for situations that require the user's attention. }
    {IF USER->NOTICE->SHOW}
      <div id="notices" class="attention">
        <h4 class="heading">{LANG->NeedsAttention}</h4>
        {IF USER->NOTICE->MESSAGES}<a class="icon icon-table-add" href="{URL->NOTICE->MESSAGES}">{LANG->UnapprovedMessagesLong}</a>{/IF}
        {IF USER->NOTICE->USERS}<a class="icon icon-user-add" href="{URL->NOTICE->USERS}">{LANG->UnapprovedUsersLong}</a>{/IF}
        {IF USER->NOTICE->GROUPS}<a class="icon icon-group-add" href="{URL->NOTICE->GROUPS}">{LANG->UnapprovedGroupMembers}</a>{/IF}
      </div> <!-- end of div id=notices -->
    {/IF}

  {/IF} {! end of NOT PRINTVIEW }
<!-- END TEMPLATE header.tpl -->
