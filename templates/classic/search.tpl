<div class="PhorumNavBlock" style="text-align: left;">
  <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}{IF URL->LIST}<a class="PhorumNavLink" href="{URL->LIST}">{LANG->MessageList}</a>&bull;{/IF}{IF URL->POST}<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;{/IF}{INCLUDE "loginout_menu"}
</div><br />
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
  </div><br />
{/IF}
{IF SEARCH->showresults}
  {INCLUDE "paging"}
  <div class="PhorumStdBlockHeader" style="text-align: left;">
    <span class="PhorumHeadingLeft">{LANG->Results} {RANGE_START} - {RANGE_END} {LANG->of} {TOTAL}</span>
  </div>
  <div class="PhorumStdBlock">
    {LOOP MATCHES}
      <div class="PhorumRowBlock">
        <div class="PhorumColumnFloatLarge">{MATCHES->datestamp}</div>
        <div class="PhorumColumnFloatMedium">{MATCHES->author}</div>
        <div style="margin-right: 370px" class="PhorumLargeFont">{MATCHES->number}.&nbsp;<a href="{MATCHES->URL->READ}">{MATCHES->subject}</a></div>
        <div class="PhorumFloatingText">
          {MATCHES->short_body}<br />
          {IF MATCHES->forum_id}
            {LANG->Forum}: <a href="{MATCHES->URL->LIST}">{MATCHES->forum_name}</a>
          {ELSE}
            ({LANG->Announcement})
          {/IF}
        </div>
      </div>
    {/LOOP MATCHES}
  </div>
  {INCLUDE "paging"}
  <div class="PhorumNavBlock" style="text-align: left;">
    <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->LIST}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;{IF LOGGEDIN true}<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{ELSE}<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}
  </div><br />
{/IF}
{IF NOT SEARCH->match_type "USER_ID"}
<table width=100% border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td class="PhorumStdBlock PhorumNarrowBlock" style="padding: 10px;">
      <form action="{URL->ACTION}" method="get">
            {POST_VARS}
            {LANG->SearchMessages}:<br />
            <input type="text" name="search" id="phorum_search_message" size="30" maxlength="" value="{SEARCH->safe_search}" />
            <select name="match_type">
                <option value="ALL" {IF SEARCH->match_type "ALL"}selected="selected"{/IF}>{LANG->MatchAll}</option>
                <option value="ANY" {IF SEARCH->match_type "ANY"}selected="selected"{/IF}>{LANG->MatchAny}</option>
                <option value="PHRASE" {IF SEARCH->match_type "PHRASE"}selected="selected"{/IF}>{LANG->MatchPhrase}</option>
            </select>
            <input type="submit" value="{LANG->Search}" /><br />
            <br />
            {LANG->SearchAuthors}:<br />
            <input type="text" id="phorum_search_author" name="author" size="30" maxlength="" value="{SEARCH->safe_author}" /><br />
            <br />
            {LANG->Forums}:<br />
            <select name="match_forum[]" size="{SEARCH->forum_list_length}" multiple="multiple">
                <option value="ALL" {IF SEARCH->match_forum "ALL"}selected="selected"{/IF}>{LANG->MatchAllForums}</option>
                {LOOP SEARCH->forum_list}
                    {IF SEARCH->forum_list->folder_flag}
                        <optgroup label="{SEARCH->forum_list->indent_spaces}{SEARCH->forum_list->name}"></optgroup>
                    {ELSE}
                        <option value="{SEARCH->forum_list->forum_id}" {IF SEARCH->forum_list->selected}selected="selected"{/IF}>{SEARCH->forum_list->indent_spaces}{SEARCH->forum_list->name}</option>
                    {/IF}
                {/LOOP SEARCH->forum_list}
            </select>
            <br />
            <br />
            {LANG->Options}:<br />
            <select name="match_threads">
                <option value="1" {IF SEARCH->match_threads "1"}selected="selected"{/IF}>{LANG->MatchThreads}</option>
                <option value="0" {IF SEARCH->match_threads "0"}selected="selected"{/IF}>{LANG->MatchMessages}</option>
            </select>
            &nbsp;
            &nbsp;
            <select name="match_dates">
                <option value="30" {IF SEARCH->match_dates 30}selected="selected"{/IF}>{LANG->Last30Days}</option>
                <option value="90" {IF SEARCH->match_dates 90}selected="selected"{/IF}>{LANG->Last90Days}</option>
                <option value="365" {IF SEARCH->match_dates 365}selected="selected"{/IF}>{LANG->Last365Days}</option>
                <option value="0" {IF SEARCH->match_dates 0}selected="selected"{/IF}>{LANG->AllDates}</option>
            </select>
            <br />
        </form>
    </td>
  </tr>
</table>
{/IF}