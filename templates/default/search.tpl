<div class="PhorumNavBlock" style="text-align: left;">
  <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>{IF URL->POST}&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>{/IF}{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{URL->PM}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/IF}{IF LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}</a>
</div>
<br/>

{IF SEARCH->noresults}
  <div align="center">
    <div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">
      {LANG->NoResults}
    </div>
    <div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
      <div class="PhorumFloatingText">
        {LANG->NoResults Help}
      </div>
    </div>
  </div>
  <br/>
{/IF}

{IF SEARCH->showresults}

  {include paging}

  <div class="PhorumStdBlockHeader" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->Results} {RANGE_START} - {RANGE_END} {LANG->of} {TOTAL}</span></div>

  <div class="PhorumStdBlock">
    {LOOP MATCHES}
      <div class="PhorumRowBlock">
        <div class="PhorumColumnFloatLarge">{MATCHES->datestamp}</div>
        <div class="PhorumColumnFloatMedium">{MATCHES->author}</div>
        <div style="margin-right: 370px" class="PhorumLargeFont">{MATCHES->number}.&nbsp;<a href="{MATCHES->url}">{MATCHES->subject}</a></div>
        <div class="PhorumFloatingText">
          {MATCHES->short_body}<br />
          {IF MATCHES->forum_id}
            {LANG->Forum}: <a href="{MATCHES->forum_url}">{MATCHES->forum_name}</a>
          {ELSE}
            ({LANG->Announcement})
          {/IF}
        </div>
      </div>
   {/LOOP MATCHES}
  </div>

  {include paging}

  <div class="PhorumNavBlock" style="text-align: left;">
    <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/IF}{IF LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}</a>
  </div>
  <br />
{/IF}

<table width=100% border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><b>{LANG->SearchMessages}</b></td>
    <td style="width: 10px">&nbsp;</td>
    <td class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><b>{LANG->SearchAuthors}</b></td>
  </tr>      
  <tr>
    <td class="PhorumStdBlock PhorumNarrowBlock" style="padding: 10px;">
      <form action="{URL->ACTION}" method="get" style="display: inline;">
      <input type="hidden" name="forum_id" value="{SEARCH->forum_id}" />
      {POST_VARS}
      <input type="text" name="search" size="30" maxlength="" value="{SEARCH->safe_search}" />&nbsp;<input type="submit" value="{LANG->Search}" /><br />
      <div style="margin-top: 3px;"><select name="match_forum"><option value="ALL" {if SEARCH->match_forum ALL}selected{/if}>{LANG->MatchAllForums}</option>{if SEARCH->allow_match_one_forum}<option value="THISONE" {if SEARCH->match_forum THISONE}selected{/if}>{LANG->MatchThisForum}</option>{/if}</select></div>
      <div style="margin-top: 3px;"><select name="match_dates"><option value="30" {if SEARCH->match_dates 30}selected{/if}>{LANG->Last30Days}</option><option value="90" {if SEARCH->match_dates 90}selected{/if}>{LANG->Last90Days}</option><option value="365" {if SEARCH->match_dates 365}selected{/if}>{LANG->Last365Days}</option><option value="0" {if SEARCH->match_dates 0}selected{/if}>{LANG->AllDates}</option></select>&nbsp;<select name="match_type"><option value="ALL" {if SEARCH->match_type ALL}selected{/if}>{LANG->MatchAll}</option><option value="ANY" {if SEARCH->match_type ANY}selected{/if}>{LANG->MatchAny}</option><option value="PHRASE" {if SEARCH->match_type PHRASE}selected{/if}>{LANG->MatchPhrase}</option></select></div>
      </form>
    </td>
    <td style="width: 10px">&nbsp;</td>
    <td class="PhorumStdBlock PhorumNarrowBlock" style="padding: 10px;">
      <form action="{URL->ACTION}" method="get" style="display: inline;">
      <input type="hidden" name="forum_id" value="{SEARCH->forum_id}" />
      <input type="hidden" name="match_type" value="AUTHOR" />
      {POST_VARS}
      <input type="text" name="search" size="30" maxlength="" value="{SEARCH->safe_search}" />&nbsp;<input type="submit" value="{LANG->Search}" /><br />
      <div style="margin-top: 3px;"><select name="match_forum"><option value="ALL" {if SEARCH->match_forum ALL}selected{/if}>{LANG->MatchAllForums}</option>{if SEARCH->allow_match_one_forum}<option value="THISONE" {if SEARCH->match_forum THISONE}selected{/if}>{LANG->MatchThisForum}</option>{/if}</select></div>
      <div style="margin-top: 3px;"><select name="match_dates"><option value="30" {if SEARCH->match_dates 30}selected{/if}>{LANG->Last30Days}</option><option value="90" {if SEARCH->match_dates 90}selected{/if}>{LANG->Last90Days}</option><option value="365" {if SEARCH->match_dates 365}selected{/if}>{LANG->Last365Days}</option><option value="0" {if SEARCH->match_dates 0}selected{/if}>{LANG->AllDates}</option></select></div>
      </form>
    </td>
  </tr>
</table>    
