<form action="{URL->ACTION}" method="POST">
    {POST_VARS}
    <input type="hidden" name="panel" value="{PROFILE->PANEL}" />
    <input type="hidden" name="forum_id" value="{PROFILE->forum_id}" />
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
        <input type="submit" class="PhorumSubmit" value="{LANG->Go}" />
    </div>
</form>

{IF UNAPPROVEDMESSAGE}
    <div class="information">{UNAPPROVEDMESSAGE}</div>
{ELSE}
    <table cellspacing="0" class="list">
        {LOOP PREPOST}
            {IF PREPOST->checkvar 1}
                <tr>
                    <th align="left">{PREPOST->forumname}</th>
                    <th align="left" nowrap="nowrap" width="150">{LANG->Author}&nbsp;</th>
                    <th align="left" nowrap="nowrap" width="150">{LANG->Date}&nbsp;</th>
                </tr>
            {/IF}
            <tr>
                <td>
                    <a href="{PREPOST->URL->READ}" target="_blank">{PREPOST->subject}</a><br />
                    <small>&nbsp;&nbsp;&nbsp;&nbsp;<a href="{PREPOST->URL->DELETE}">{LANG->DeleteMessage}</a>&nbsp;&bull;&nbsp;<a href="{PREPOST->URL->APPROVE_MESSAGE}">{LANG->ApproveMessage Short}</a>&nbsp;&bull;&nbsp;<a href="{PREPOST->URL->APPROVE_TREE}">{LANG->ApproveMessageReplies}</a></small>
                </td>
                <td nowrap="nowrap" width="150">{PREPOST->linked_author}&nbsp;</td>
                <td nowrap="nowrap" width="150">{PREPOST->short_datestamp}&nbsp;</td>
            </tr>
        {/LOOP PREPOST}
    </table>        
{/IF}

