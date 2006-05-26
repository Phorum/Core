{IF FOLDERS}
    <table cellspacing="0" class="list">
        <tr>
            <th align="left">{LANG->Folders}</th>
        </tr>
        {LOOP FOLDERS}
            <tr>
                <td width="55%" ><h3><a href="{FOLDERS->URL->LIST}">{FOLDERS->name}</h3></a>
                    <p>{FOLDERS->description}</p>
                </td>
            </tr>
        {/LOOP FOLDERS}
    </table>
{/IF FOLDERS}

{IF FORUMS}
    <table class="list" cellspacing="0">
        <tr>
            <th align="left">{LANG->Forums}</th>
            <th>{LANG->Threads}</th>
            <th>{LANG->Posts}</th>
            <th align="left">{LANG->LastPost}</th>
        </tr>
    
        {LOOP FORUMS}
            <tr>
                <td width="55%"><h3><a href="{FORUMS->URL->LIST}">{FORUMS->name}</a></h3>
                    <p>{FORUMS->description}</p>
                    {IF USER->user_id}<a class="icon icon-tag-green" href="{FORUMS->URL->MARK_READ}">{LANG->MarkForumRead}</a>&nbsp;&nbsp;&nbsp;{/IF}
                    {IF FORUMS->URL->RSS}<a class="icon icon-feed" href="{FORUMS->URL->RSS}">{LANG->RSS}</a>{/IF}
                </td>
                <td width="12%" nowrap="nowrap" align="center">
                    {FORUMS->thread_count}
                    {IF FORUMS->new_threads}
                        (<span class="new-flag">{FORUMS->new_threads} {LANG->newflag}</span>)
                    {/IF}
                </td>
                <td width="12%" nowrap="nowrap" align="center">
                    {FORUMS->message_count}
                    {IF FORUMS->new_messages}
                        (<span class="new-flag">{FORUMS->new_messages} {LANG->newflag}</span>)
                    {/IF}
                </td>
                <td width="21%" nowrap="nowrap">{FORUMS->last_post}</td>
            </tr>
        {/LOOP FORUMS}
    </table>
{/IF FORUMS}
