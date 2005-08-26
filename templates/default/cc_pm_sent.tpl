<div class="PhorumLargeFont">{LANG->PrivateMessages} : {LANG->SentItems}</div>
<br />
<form action="{ACTION}" method="post">
{POST_VARS}
<input type="hidden" name="panel" value="pm" />
<input type="hidden" name="forum_id" value="{FORUM_ID}" />
<input type="hidden" name="action" value="delete" />
<table border="0" cellspacing="0" class="PhorumStdTable">
    <tr>
        <th class="PhorumTableHeader" align="left">&nbsp;</th>
        <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
        <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->To}&nbsp;</th>
        <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->Date}&nbsp;</th>
        <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->Received}&nbsp;</th>
    </tr>
{LOOP SENT}
    <tr>
        <td class="PhorumListTableRow"><input type="checkbox" name="from_delete[]" value="{SENT->message_id}" /></td>
        <td class="PhorumListTableRow"><a href="{SENT->read_url}">{SENT->subject}</a></td>
        <td class="PhorumListTableRow" nowrap="nowrap" width="150"><a href="{SENT->profile_url}">{SENT->to}</a>&nbsp;</td>
        <td class="PhorumListTableRowSmall" nowrap="nowrap" width="150">{SENT->date}&nbsp;</td>
        <td class="PhorumListTableRowSmall" nowrap="nowrap" width="150">{IF SENT->read}{LANG->Yes}{ELSE}{LANG->No}{/IF}</td>
    </tr>
{/LOOP SENT}
</table>
<div class="PhorumNavBlock" style="text-align: left;">
<input type="submit" class="PhorumSubmit" value="{LANG->Delete}" />
</div>

</form>
