
<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/IF}{IF LOGGEDIN false}&bull;    <a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}
</div>


{IF PAGES}
<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;">
<span class="PhorumNavHeading">{LANG->Pages}:</span>&nbsp;
{IF URL->PREVPAGE}<a class="PhorumNavLink" href="{URL->PREVPAGE}">{LANG->PrevPage}</a>{/IF}
{IF URL->FIRSTPAGE}<a class="PhorumNavLink" href="{URL->FIRSTPAGE}">{LANG->FirstPage}...</a>{/IF}
{LOOP PAGES}<a class="PhorumNavLink" href="{PAGES->url}">{PAGES->pageno}</a>{/LOOP PAGES}
{IF URL->LASTPAGE}<a class="PhorumNavLink" href="{URL->LASTPAGE}">...{LANG->LastPage}</a>{/IF}
{IF URL->NEXTPAGE}<a class="PhorumNavLink" href="{URL->NEXTPAGE}">{LANG->NextPage}</a>{/IF}
</div>
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->CurrentPage}:</span>{CURRENTPAGE} {LANG->of} {TOTALPAGES}
</div>
{/IF}


<table border="0" cellspacing="0" class="PhorumStdTable">
<tr>
    <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
{IF VIEWCOUNT_COLUMN}    <th class="PhorumTableHeader" align="center" width="40">{LANG->Views}</th>{/IF}
    <th class="PhorumTableHeader" align="center" nowrap="nowrap" width="80">{LANG->Posts}&nbsp;</th>
    <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->StartedBy}&nbsp;</th>
    <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->LastPost}&nbsp;</th>
</tr>
<?php
$rclass="Alt";
?>
{LOOP ROWS}
<?php
  if($rclass=="Alt")
    $rclass="";
  else
    $rclass="Alt";
?>
<tr>
    <td class="PhorumTableRow<?php echo $rclass;?>"><?php echo $PHORUM['TMP']['marker'] ?>{IF ROWS->sort PHORUM_SORT_STICKY}<span class="PhorumListSubjPrefix">{LANG->Sticky}:</span>{/IF}{IF ROWS->sort PHORUM_SORT_ANNOUNCEMENT}<span class="PhorumListSubjPrefix">{LANG->Announcement}:</span>{/IF}<a href="{ROWS->url}">{ROWS->subject}</a>{IF ROWS->new}&nbsp;<span class="PhorumNewFlag">{ROWS->new}</span>{/IF}{IF ROWS->pages}<span class="PhorumListPageLink">&nbsp;&nbsp;&nbsp;{LANG->Pages}: {ROWS->pages}</span>{/IF}{IF MODERATOR true}<br /><span class="PhorumListModLink"><a href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{ROWS->delete_url2}';">{LANG->DeleteThread}</a>&nbsp;&#8226;&nbsp;<a href="{ROWS->move_url}">{LANG->MoveThread}</a></span>{/IF}</td>
{IF VIEWCOUNT_COLUMN}    <td class="PhorumTableRow<?php echo $rclass;?>" align="center">{ROWS->viewcount}&nbsp;</td>    {/IF}
    <td class="PhorumTableRow<?php echo $rclass;?>" align="center" nowrap="nowrap">{ROWS->thread_count}&nbsp;</td>
    <td class="PhorumTableRow<?php echo $rclass;?>" nowrap="nowrap">{ROWS->linked_author}&nbsp;</td>
    <td class="PhorumTableRow<?php echo $rclass;?> PhorumSmallFont" nowrap="nowrap">{ROWS->lastpost}&nbsp;<br /><span class="PhorumListSubText"><a href="{ROWS->last_post_url}">{LANG->LastPostLink}</a> {LANG->by} {ROWS->last_post_by}</span></td>
</tr>
{/LOOP ROWS}

</table>


{IF PAGES}
<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;">
<span class="PhorumNavHeading">{LANG->Pages}:</span>&nbsp;
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
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>{IF LOGGEDIN true}&nbsp;<a class="PhorumNavLink" href="{URL->MARKREAD}">{LANG->MarkRead}</a>{/IF}
</div>
