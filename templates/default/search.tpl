<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/IF}{IF LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}</a>
</div>


<form action="{URL->ACTION}" method="get" style="display: inline;">
{POST_VARS}
<input type="hidden" name="forum_id" value="{SEARCH->forum_id}" />

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->Search}</div>

<div class="PhorumStdBlock" style="text-align: center;">
<div class="PhorumFloatingText">
<input type="text" name="search" size="30" maxlength="" value="{SEARCH->safe_search}" />&nbsp;<input type="submit" value="{LANG->Search}" />
</div>
<div class="PhorumFloatingText" align="center">
<table class="PhorumFormTable" cellspacing="0" border="0">
<tr>
    <td><select name="match_forum"><option value="ALL" {if SEARCH->match_forum ALL}selected{/if}>{LANG->MatchAllForums}</option>{if SEARCH->allow_match_one_forum}<option value="THISONE" {if SEARCH->match_forum THISONE}selected{/if}>{LANG->MatchThisForum}</option>{/if}</select></td>
    <td>&nbsp;&nbsp;<input type="checkbox" name="body" {if SEARCH->body}checked{/if} value="1" />&nbsp;{LANG->SearchBody}<br /></td>
</tr>
<tr>
    <td><select name="match_type"><option value="ALL" {if SEARCH->match_type ALL}selected{/if}>{LANG->MatchAll}</option><option value="ANY" {if SEARCH->match_type ANY}selected{/if}>{LANG->MatchAny}</option><option value="PHRASE" {if SEARCH->match_type PHRASE}selected{/if}>{LANG->MatchPhrase}</option></select></td>
    <td>&nbsp;&nbsp;<input type="checkbox" name="author" {if SEARCH->author}checked{/if} value="1" />&nbsp;{LANG->SearchAuthor}</td>
</tr>
<tr>
    <td><select name="match_dates"><option value="30" {if SEARCH->match_dates 30}selected{/if}>{LANG->Last30Days}</option><option value="90" {if SEARCH->match_dates 90}selected{/if}>{LANG->Last90Days}</option><option value="365" {if SEARCH->match_dates 365}selected{/if}>{LANG->Last365Days}</option><option value="0" {if SEARCH->match_dates 0}selected{/if}>{LANG->AllDates}</option></select><br /></td>
    <td>&nbsp;&nbsp;<input type="checkbox" name="subject" {if SEARCH->subject}checked{/if} value="1" />&nbsp;{LANG->SearchSubject}</td>
</tr>
</table>
</div>
</div>

</form>
<br />
{IF SEARCH->noresults}
<div align="center">
<div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">{LANG->NoResults}</div>

<div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
<div class="PhorumFloatingText">{LANG->NoResults Help}</div>
</div>
</div>
{/IF}

{IF SEARCH->showresults}

{IF PAGES}
<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;"><span class="PhorumNavHeading">{LANG->Pages}:</span>&nbsp;
{IF URL->PREVPAGE}<a class="PhorumNavLink" href="{URL->PREVPAGE}">{LANG->PrevPage}</a>{/IF}
{IF URL->FIRSTPAGE}<a class="PhorumNavLink" href="{URL->FIRSTPAGE}">{LANG->FirstPage}...</a>{/IF}
{LOOP PAGES}<a class="PhorumNavLink" href="{PAGES->url}">{PAGES->pageno}</a>{/LOOP PAGES}
{IF URL->LASTPAGE}<a class="PhorumNavLink" href="{URL->LASTPAGE}">...{LANG->LastPage}</a>{/IF}
{IF URL->NEXTPAGE}<a class="PhorumNavLink" href="{URL->NEXTPAGE}">{LANG->NextPage}</a>{/IF}
</div>
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->CurrentPage}:</span>{CURRENTPAGE} {LANG->of} {TOTALPAGES}
</div>
{/IF}

<div class="PhorumStdBlockHeader" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->Results} {RANGE_START} - {RANGE_END} {LANG->of} {TOTAL}</span></div>

<div class="PhorumStdBlock">
{LOOP MATCHES}
<div class="PhorumRowBlock">
<div class="PhorumColumnFloatLarge">{MATCHES->datestamp}</div>
<div class="PhorumColumnFloatMedium">{MATCHES->author}</div>
<div style="margin-right: 370px" class="PhorumLargeFont">{MATCHES->number}.&nbsp;<a href="{MATCHES->url}">{MATCHES->subject}</a></div>
<div class="PhorumFloatingText">{MATCHES->short_body}<br />{LANG->Forum}: <a href="{MATCHES->forum_url}">{MATCHES->forum_name}</a></div>
</div>
{/LOOP MATCHES}
</div>

{IF PAGES}
<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;"><span class="PhorumNavHeading">{LANG->Pages}:</span>&nbsp;
{IF URL->PREVPAGE}<a class="PhorumNavLink" href="{URL->PREVPAGE}">{LANG->PrevPage}</a>{/IF}
{IF URL->FIRSTPAGE}<a class="PhorumNavLink" href="{URL->FIRSTPAGE}">{LANG->FirstPage}...</a>{/IF}
{LOOP PAGES}<a class="PhorumNavLink" href="{PAGES->url}">{PAGES->pageno}</a>{/LOOP PAGES}
{IF URL->LASTPAGE}<a class="PhorumNavLink" href="{URL->LASTPAGE}">...{LANG->LastPage}</a>{/IF}
{IF URL->NEXTPAGE}<a class="PhorumNavLink" href="{URL->NEXTPAGE}">{LANG->NextPage}</a>{/IF}
</div>
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->CurrentPage}:</span>{CURRENTPAGE} {LANG->of} {TOTALPAGES}
</div>
{/IF}

<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URLINDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URLTOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URLPOST}">{LANG->NewTopic}</a>{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URLREGISTERPROFILE}">{LANG->MyProfile}</a>&bull;<a class="PhorumNavLink" href="{URLLOGINOUT}">{LANG->LogOut}</a>{/IF}{IF LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URLLOGINOUT}">{LANG->LogIn}</a>{/IF}</a>
</div>
{ELSE}
<div class="PhorumStdBlockHeader" style="text-align: left;">
    <span class="PhorumHeadingLeft">
    {LANG->SearchTips}
    </span>
</div>
<div class="PhorumStdBlock">
{LANG->SearchTip}
</div>

{/IF}
