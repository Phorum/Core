<form action="{ACTION}" method="post">
    {POST_VARS}
    <table cellspacing="0" class="list">
        <tr>
            <th align="left">&nbsp;</th>
            <th align="left">{LANG->Username}</th>
            <th align="left" nowrap="nowrap" width="150">{LANG->Email}</th>
        </tr>
        {LOOP USERS}
            <tr>
                <td><input type="checkbox" name="user_ids[]" value="{USERS->user_id}" /></td>
                <td width="50%">{USERS->username}</td>
                <td width="50%" nowrap="nowrap" width="150">{USERS->email}</td>
            </tr>
        {/LOOP USERS}
    </table>
    <input type="submit" name="approve" value="{LANG->ApproveUser}" />&nbsp;<input type="submit" name="disapprove" value="{LANG->DenyUser}" />
</form>
