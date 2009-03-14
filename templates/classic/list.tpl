{!
    comment
}

<div class="PhorumNavBlock" style="text-align: left;">
  <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{INCLUDE "loginout_menu"}
</div>
{INCLUDE "paging"}
<table border="0" cellspacing="0" class="PhorumStdTable">
  <tr>
    <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
    {IF VIEWCOUNT_COLUMN}
      <th class="PhorumTableHeader" align="center" width="40">{LANG->Views}</th>
    {/IF}
    <th class="PhorumTableHeader" align="center" nowrap="nowrap" width="80">{LANG->Posts}&nbsp;</th>
    <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->StartedBy}&nbsp;</th>
    <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->LastPost}&nbsp;</th>
  </tr>
  {LOOP MESSAGES}
    {IF altclass ""}
        {VAR altclass "Alt"}
    {ELSE}
        {VAR altclass ""}
    {/IF}
    <tr>
      <td class="PhorumTableRow{altclass}">
        {marker}
        {IF MESSAGES->sort PHORUM_SORT_STICKY}<span class="PhorumListSubjPrefix">{LANG->Sticky}:</span>{/IF}
        {IF MESSAGES->sort PHORUM_SORT_ANNOUNCEMENT}<span class="PhorumListSubjPrefix">{LANG->Announcement}:</span>{/IF}
        {IF MESSAGES->moved}<span class="PhorumListSubjPrefix">{LANG->MovedSubject}:</span>{/IF}
        <a href="{MESSAGES->URL->READ}">{MESSAGES->subject}</a>
        {IF MESSAGES->new}&nbsp;<span class="PhorumNewFlag">{MESSAGES->new}</span>{/IF}
        {IF MESSAGES->pages}<span class="PhorumListPageLink">&nbsp;&nbsp;&nbsp;{LANG->Pages}: {MESSAGES->pages}</span>{/IF}
        {IF MODERATOR true}<br /><span class="PhorumListModLink"><a href="{MESSAGES->URL->DELETE_THREAD}">{LANG->DeleteThread}</a>{IF MESSAGES->URL->MOVE}&nbsp;&#8226;&nbsp;<a href="{MESSAGES->URL->MOVE}">{LANG->MoveThread}</a>{/IF}&nbsp;&#8226;&nbsp;<a href="{MESSAGES->URL->MERGE}">{LANG->MergeThread}</a></span>{/IF}
      </td>
      {IF VIEWCOUNT_COLUMN}
        <td class="PhorumTableRow{altclass}" align="center">{MESSAGES->viewcount}&nbsp;</td>
      {/IF}
      <td class="PhorumTableRow{altclass}" align="center" nowrap="nowrap">{MESSAGES->thread_count}&nbsp;</td>
      <td class="PhorumTableRow{altclass}" nowrap="nowrap">{IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}&nbsp;</td>
      <td class="PhorumTableRow{altclass} PhorumSmallFont" nowrap="nowrap">
        {MESSAGES->lastpost}&nbsp;<br />
        <span class="PhorumListSubText">
          <a href="{MESSAGES->URL->LAST_POST}">{LANG->LastPostLink}</a> {LANG->by} {IF MESSAGES->URL->RECENT_AUTHOR_PROFILE}<a href="{MESSAGES->URL->RECENT_AUTHOR_PROFILE}">{/IF}{MESSAGES->recent_author}{IF MESSAGES->RECENT_AUTHOR_PROFILE}</a>{/IF}
        </span>
      </td>
    </tr>
  {/LOOP MESSAGES}
</table>
{INCLUDE "paging"}
<div class="PhorumNavBlock" style="text-align: left;">
  <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>
  {IF LOGGEDIN true}&nbsp;<a class="PhorumNavLink" href="{URL->MARK_READ}">{LANG->MarkRead}</a>{/IF}
</div>
