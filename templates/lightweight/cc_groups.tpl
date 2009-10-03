{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

<div class="generic">
    <h4>{LANG->JoinAGroup}</h4>
    {IF JOINGROUP}
        <form method="post" action="{GROUP->url}">
        {POST_VARS}
            {LANG->JoinGroupDescription}
            <br/><br/>
            <select name="joingroup">
                <option value="0">&nbsp;</option>
                {LOOP JOINGROUP}
                    <option value="{JOINGROUP->group_id}">{JOINGROUP->name}</option>
                {/LOOP JOINGROUP}
            </select>
            <input type="submit" value="{LANG->Join}" />
        </form>
    {ELSE}
        {LANG->NoGroupsJoin}
    {/IF}
</div>
<div class="generic">
    <h4>{LANG->GroupMembership}</h4>
    {IF Groups}
        <table cellspacing="0" border="0">
            <tr>
                <th>{LANG->Group}</th>
                <th>{LANG->Permission}</th>
            </tr>
            {LOOP Groups}
                <tr>
                    <td>{Groups->groupname}&nbsp;&nbsp;</td>
                    <td>{Groups->perm}</td>
                </tr>
            {/LOOP Groups}
        </table>
    {ELSE}
        {LANG->NoGroupMembership}
    {/IF}
</div>
