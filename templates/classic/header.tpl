<?php
if ($PHORUM['DATA']['CHARSET']) {
    header("Content-Type: text/html; charset=".htmlspecialchars($PHORUM['DATA']['CHARSET']));
    echo '<?xml version="1.0" encoding="'.$PHORUM['DATA']['CHARSET'].'"?>';
} else {
    echo '<?xml version="1.0" ?>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="<?php echo $PHORUM['locale']; ?>">
  <head>
    {IF PRINTVIEW}
      <meta name="robots" content="NOINDEX,NOFOLLOW">
      <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="screen,print" />
    {ELSE}
      <link rel="stylesheet" type="text/css" href="{URL->CSS}" media="screen" />
      <link rel="stylesheet" type="text/css" href="{URL->CSS_PRINT}" media="print" />
    {/IF}

    {! Add links to the available RSS feeds. }
    {IF FEEDS}
      {LOOP FEEDS}
      <link rel="alternate" type="{FEED_CONTENT_TYPE}" title="{FEEDS->TITLE}" href="{FEEDS->URL}" />
      {/LOOP FEEDS}
    {/IF}

    {IF URL->JAVASCRIPT}
        <script type="text/javascript" src="{URL->JAVASCRIPT}"></script>
    {/IF}
    {IF URL->REDIRECT}
      <meta http-equiv="refresh" content="{IF REDIRECT_TIME}{REDIRECT_TIME}{ELSE}5{/IF}; url={URL->REDIRECT}" />
    {/IF}
    {LANG_META}
    <title>{HTML_TITLE}</title>
    {HEAD_TAGS}
    {IF PRINTVIEW}
      <meta name="robots" content="NOINDEX,NOFOLLOW">
    {/IF}
  </head>
  <body onload="{IF FOCUS_TO_ID}var focuselt=document.getElementById('{FOCUS_TO_ID}'); if (focuselt) focuselt.focus();{/IF}">
    <div align="{forumalign}">
      <div class="PDDiv">
      {IF NOT PRINTVIEW}
	    {! The template variable GLOBAL_ERROR can be used to show an error }
	    {! message at the start of the page. }
	    {IF GLOBAL_ERROR}
	      <div id="global-error" class="PhorumUserError">
	        {GLOBAL_ERROR}
	      </div>
	    {/IF}
          
        {IF USER->NOTICE->SHOW OR USER->new_private_messages}
          <div class="PhorumNotificationArea PhorumNavBlock">
            {IF USER->new_private_messages}<a class="PhorumNavLink" href="{URL->PM}">{LANG->NewPrivateMessages}</a><br />{/IF}
            {IF USER->NOTICE->MESSAGES}<a class="PhorumNavLink" href="{URL->NOTICE->MESSAGES}">{LANG->UnapprovedMessagesLong}</a><br />{/IF}
            {IF USER->NOTICE->USERS}<a class="PhorumNavLink" href="{URL->NOTICE->USERS}">{LANG->UnapprovedUsersLong}</a><br />{/IF}
            {IF USER->NOTICE->GROUPS}<a class="PhorumNavLink" href="{URL->NOTICE->GROUPS}">{LANG->UnapprovedGroupMembers}</a><br />{/IF}
          </div>
        {/IF}
        <span class="PhorumTitleText PhorumLargeFont">
          {IF NAME}<a href="{URL->LIST}">{NAME}</a>&nbsp;:&nbsp;{/IF}
          {TITLE}
        </span>
        {IF URL->INDEX}<a href="{URL->INDEX}">{/IF}<img src="templates/classic/images/logo.png" alt="The fastest message board... ever. " title="The fastest message board... ever. " width="170" height="42" border="0" />{IF URL->INDEX}</a>{/IF}
        <div class="PhorumFloatingText">{HTML_DESCRIPTION}&nbsp;</div>
        {/IF}
