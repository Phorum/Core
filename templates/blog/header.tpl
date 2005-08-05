<?php echo '<?xml version="1.0" encoding="iso-8859-1"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "DTD/xhtml1-transitional.dtd">
<html lang="<?php echo $PHORUM['locale']; ?>">
<head>
<title>{HTML_TITLE}</title>
<style type="text/css">
{include css}
</style>
<link rel="alternate" type="application/rss+xml" title="RSS-File" href="http://blogs.phorum.org/rss.php?<?=$PHORUM["forum_id"]?>" />
{if URL->REDIRECT}
<meta http-equiv="refresh" content="5; url={URL->REDIRECT}" />
{/if}
{LANG_META}
{HEAD_TAGS}
</head>
<body>

<div id="right-column">

<h1>The Phorum Blog Template</h1>

Search:<br />
<form id="search-form" action="<?php echo phorum_get_url(PHORUM_SEARCH_ACTION_URL); ?>" method="get" >
<input type="hidden" name="forum_id" value="0" />
<input type="hidden" name="match_type" value="ALL" />
<input type="hidden" name="match_dates" value="30" />
<input type="hidden" name="match_forum" value="ALL" />
<input type="hidden" name="body" value="1" />
<input type="hidden" name="author" value="0" />
<input type="hidden" name="subject" value="1" />
<input id="search" name="search" type="text" />&nbsp;<input type="submit" value="{LANG->Search}" />
</form>

<ul>
<li><a href="/">Home</a></li>
</ul>

<ul>
{IF LOGGEDIN true}
<?php if (phorum_user_access_allowed(PHORUM_USER_ALLOW_NEW_TOPIC)) { ?>
<li><a href="{URL->POST}">New Post</a></li>
<?php } ?>
<li><a href="{URL->REGISTERPROFILE}">My Profile</a></li>
<li><a href="{URL->LOGINOUT}">Logout</a></li>
{ELSE}
<li><a href="{URL->LOGINOUT}">Login</a></li>
<li><a href="{URL->REGISTER}">Register</a></li>
{/IF}
</ul>

<ul>
<li><a href="http://phorum.org/">Phorum Home Page</a></li>
<li><a href="http://phorum.org/phorum5/">Phorum 5 Support</a></li>
<li><a href="http://phorum.org/cgi-bin/trac.cgi/report">Report Bugs</a></li>
</ul>

<a href="rss.php?<?=$PHORUM["forum_id"]?>"><img src="/images/rss20.gif" width="80" height="15" border="0" alt="RSS 2.0" /></a>

</div>
<div id="left-column">
{IF NAME}<a href="{URL->TOP}">{NAME}</a>&nbsp;:&nbsp;{/IF}{TITLE}</span>
You should read the docs about setting up the blog template before making it live.  Its in the docs dir in the distro called blog_howto.txt.  You can remove this comment after that.
