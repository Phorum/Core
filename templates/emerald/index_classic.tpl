<!-- BEGIN TEMPLATE index_classic.tpl -->
{IF FOLDERS}
    <table cellspacing="0" class="list">
        <tr>
            <th align="left">{LANG->Folders}</th>
        </tr>
        {LOOP FOLDERS}
            <tr>
                <td width="55%" ><h3><a href="{FOLDERS->URL->LIST}">{FOLDERS->name}</a></h3>
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
                <td width="55%">
                    <h3><a href="{FORUMS->URL->LIST}">{FORUMS->name}</a>{IF FORUMS->new_message_check}&nbsp;&nbsp;<span class="new-indicator">({LANG->NewMessages})</span>{/IF}</h3>
                    <p>{FORUMS->description}</p>
                    {IF FORUMS->URL->MARK_READ}<a class="icon icon-tag-green" href="{FORUMS->URL->MARK_READ}">{LANG->MarkForumRead}</a>&nbsp;&nbsp;&nbsp;{/IF}
                    {IF FORUMS->URL->FEED}<a class="icon icon-feed" href="{FORUMS->URL->FEED}">{FEED}</a>{/IF}
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
<!-- END TEMPLATE index_classic.tpl -->
