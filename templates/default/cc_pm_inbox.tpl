<div class="PhorumLargeFont">{LANG->PrivateMessages} : {LANG->INBOX}</div>
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
        <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->From}&nbsp;</th>
        <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->Date}&nbsp;</th>
    </tr>
{LOOP INBOX}
    <tr>
        <td class="PhorumListTableRow"><input type="checkbox" name="to_delete[]" value="{INBOX->message_id}" /></td>
        <td class="PhorumListTableRow"><a href="{INBOX->read_url}">{INBOX->subject}</a>{IF INBOX->read 0}<span class="PhorumNewFlag">&nbsp;{LANG->newflag}</span>{/IF}</td>
        <td class="PhorumListTableRow" nowrap="nowrap" width="150"><a href="{INBOX->profile_url}">{INBOX->from}</a>&nbsp;</td>
        <td class="PhorumListTableRowSmall" nowrap="nowrap" width="150">{INBOX->date}&nbsp;</td>
    </tr>
{/LOOP INBOX}
</table>
<div class="PhorumNavBlock" style="text-align: left;">
<input type="submit" class="PhorumSubmit" value="{LANG->Delete}" />
</div>
</form>
