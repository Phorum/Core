<table border="0" cellspacing="0" class="PhorumStdTable">
{LOOP PREPOST}
{IF PREPOST->checkvar 1}
    <tr>
      <th class="PhorumTableHeader" align="left" colspan="3">
      <div class="PhorumLargeFont">
      {LANG->UnapprovedMessages}&nbsp;:&nbsp;{PREPOST->forumname}
      </div>      
      </th>
    </tr>
    <tr>
        <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
        <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->Author}&nbsp;</th>
        <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->Date}&nbsp;</th>
    </tr>
{/IF}
    <tr>
        <td class="PhorumListTableRow"><?php echo $PHORUM['TMP']['marker'] ?><a href="{PREPOST->url}" target="_blank">{PREPOST->subject}</a><br /><span class="PhorumListModLink">&nbsp;&nbsp;&nbsp;&nbsp;<a class="PhorumListModLink" href="{PREPOST->delete_url}">{LANG->DeleteMessage}</a>&nbsp;&bull;&nbsp;<a class="PhorumListModLink" href="{PREPOST->approve_url}">{LANG->ApproveMessage Short}</a>&nbsp;&bull;&nbsp;<a class="PhorumListModLink" href="{PREPOST->approve_tree_url}">{LANG->ApproveMessageReplies}</a></span></td>
        <td class="PhorumListTableRow" nowrap="nowrap" width="150">{PREPOST->linked_author}&nbsp;</td>
        <td class="PhorumListTableRowSmall" nowrap="nowrap" width="150">{PREPOST->short_datestamp}&nbsp;</td>
    </tr>
{/LOOP PREPOST}
</table>