<!-- BEGIN TEMPLATE cc_groupmod.tpl -->
{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

{IF GROUP->name}
    <h2>{LANG->Moderating} {GROUP->name}</h2>
    <div class="generic">
        <h4>{LANG->AddToGroup}</h4>
        <form method="post" action="{URL->ACTION}">
            {POST_VARS}
            {IF NEWMEMBERS}
                <select name="adduser">
                    <option value="0">&nbsp;</option>
                    {LOOP NEWMEMBERS}
                        <option value="{NEWMEMBERS->user_id}">{NEWMEMBERS->display_name}</option>
                    {/LOOP NEWMEMBERS}
                </select>
            {ELSE}
                <input type="text" name="adduser" />
            {/IF}
            <input type="submit" value="{LANG->Add}" />
        </form>
    </div>

    <div class="generic">
        <h4>{LANG->GroupMemberList}</h4>

        <form action="{URL->ACTION}" method="post">
            {POST_VARS}
            {LANG->Filter}:&nbsp;
            <select name="filter">
                {LOOP FILTER}
                    <option value="{FILTER->id}"{IF FILTER->enable} selected="selected"{/IF}>{FILTER->name}</option>
                {/LOOP FILTER}
            </select>
            <input type="submit" value="{LANG->Go}" />
        </form>

        <br />

        {IF USERS}
            <form method="post" action="{URL->ACTION}">
                {POST_VARS}
                <table class="list" cellspacing="0" border="0">
                    <tr>
                        <th align="left">{LANG->Member}</th>
                        <th align="left">{LANG->MembershipType}</th>
                    </tr>
                    {LOOP USERS}
                        <tr>
                            <td>
                                {IF USERS->flag}<strong><em>{/IF}<a href="{USERS->url}">{USERS->display_name}</a>{IF USERS->flag}</em></strong>{/IF}
                            </td>
                            <td>
                                {IF USERS->disabled}
                                    {USERS->statustext}
                                {ELSE}
                                    <select name="status[{USERS->userid}]">
                                        {LOOP STATUS_OPTIONS}
                                            <?php
                                            // to get around a minor templating problem, we will figure
                                            // out if we have this line selected here
                                            $PHORUM['TMP']['STATUS_OPTIONS']['selected'] = ($PHORUM['TMP']['STATUS_OPTIONS']['value'] == $PHORUM['TMP']['USERS']['status']);
                                            ?>
                                            <option value="{STATUS_OPTIONS->value}"{IF STATUS_OPTIONS->selected} selected="selected"{/IF}>{STATUS_OPTIONS->name}</option>
                                        {/LOOP STATUS_OPTIONS}
                                    </select>
                                {/IF}
                            </td>
                        </tr>
                    {/LOOP USERS}
                </table>
                <input type="submit" value="{LANG->SaveChanges}" />
            </form>
        {ELSE}
            {LANG->NoUserMatchFilter}
        {/IF}
    </div>

{ELSE}
    <div class="generic">
        <h4>{LANG->SelectGroupMod}</h4>
        <br />
        <dl>
            {LOOP GROUPS}
                <dt><a href="{GROUPS->URL->VIEW}">{GROUPS->name}</a></dt>
                <dd>
                    {IF GROUPS->unapproved}
                        <a href="{GROUPS->URL->UNAPPROVED}">{GROUPS->unapproved} {LANG->Unapproved}</a>
                    {ELSE}
                        {LANG->NoUnapprovedUsers}
                    {/IF}
                </dd>
            {/LOOP GROUPS}
        </dl>
    </div>
{/IF}
<!-- END TEMPLATE cc_groupmod.tpl -->
