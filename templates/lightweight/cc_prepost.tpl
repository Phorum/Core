<script type="text/javascript">
var phorum_marked_all = false;
function phorum_markAllCheckboxes() {
	var pageform = document.getElementById('fprepost');
	var elems = pageform.getElementsByTagName('input');

	if(phorum_marked_all) {
		newval = false;
	} else {
		newval = true;
	}
	for(i=0; i<elems.length; i++){
        if(elems[i].type == 'checkbox') {
	   	   elems[i].checked=newval;
        }

	}
	phorum_marked_all = newval;
}
</script>

<form action="{URL->ACTION}" method="POST">
    {POST_VARS}
    <div class="generic">
        {LANG->ShowOnlyMessages}&nbsp;
        <select name="onlyunapproved">
            <option value="0"{IF SELECTED_2 0} selected="selected"{/IF}>{LANG->AllNotShown}</option>
            <option value="1"{IF SELECTED_2 1} selected="selected"{/IF}>{LANG->OnlyUnapproved}</option>
        </select>
        {LANG->DatePosted}&nbsp;
        <select name="moddays">
            <option value="1"{IF SELECTED 1} selected="selected"{/IF}>1 {LANG->Day}</option>
            <option value="2"{IF SELECTED 2} selected="selected"{/IF}>2 {LANG->Days}</option>
            <option value="7"{IF SELECTED 7} selected="selected"{/IF}>7 {LANG->Days}</option>
            <option value="30"{IF SELECTED 30} selected="selected"{/IF}>1 {LANG->Month}</option>
            <option value="180"{IF SELECTED 180} selected="selected"{/IF}>6 {LANG->Months}</option>
            <option value="365"{IF SELECTED 365} selected="selected"{/IF}>1 {LANG->Year}</option>
            <option value="0"{IF SELECTED 0} selected="selected"{/IF}>{LANG->AllDates}</option>
        </select>
        <input type="submit" value="{LANG->Go}" />
    </div>
</form>

{IF UNAPPROVEDMESSAGE}
    <div class="information">{UNAPPROVEDMESSAGE}</div>
{ELSE}
<form action="{URL->ACTION}" method="POST" id="fprepost">
  {POST_VARS}
    <table cellspacing="0" class="list">
        {LOOP PREPOST}
            {IF PREPOST->checkvar 1}
                <tr>
                    <th align="left">{PREPOST->forumname}</th>
                    <th align="left" nowrap="nowrap" width="150">{LANG->Author}&nbsp;</th>
                    <th align="left" nowrap="nowrap" width="150">{LANG->Date}&nbsp;</th>
                    <th align="left" nowrap="nowrap" width="150" onclick="phorum_markAllCheckboxes()">{LANG->Delete}&nbsp;</th>
                </tr>
            {/IF}
            <tr>
                <td>
                    <a href="{PREPOST->URL->READ}" target="_blank">{PREPOST->subject}</a><br />
                    <small>&nbsp;&nbsp;&nbsp;&nbsp;<a href="{PREPOST->URL->DELETE}">{LANG->DeleteMessage}</a>&nbsp;&bull;&nbsp;<a href="{PREPOST->URL->APPROVE_MESSAGE}">{LANG->ApproveMessage Short}</a>&nbsp;&bull;&nbsp;<a href="{PREPOST->URL->APPROVE_TREE}">{LANG->ApproveMessageReplies}</a></small>
                </td>
                <td nowrap="nowrap" width="150">{IF PREPOST->URL->PROFILE}<a href="{PREPOST->URL->PROFILE}">{/IF}{PREPOST->author}{IF PREPOST->URL->PROFILE}</a>{/IF}&nbsp;</td>
                <td nowrap="nowrap" width="150">{PREPOST->short_datestamp}&nbsp;</td>
                <td nowrap="nowrap" width="150"><input type="checkbox" name="deleteids[{PREPOST->message_id}]" value="1" /></td>
            </tr>
        {/LOOP PREPOST}
<tr>
<td colspan="3">&nbsp;</td>
<td><input type="submit" name="submit" value="{LANG->Delete}" /></td>
</tr>
</table>
</form>

{/IF}

