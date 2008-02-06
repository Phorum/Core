<table cellspacing="0" class="list">
    {VAR first_pass 1}
    {LOOP FORUMS}
    {IF FORUMS->level 0}
            <tr>
                {IF FORUMS->forum_id FORUMS->vroot}
                    <th align="left">
                        {LANG->Forums}
                    </th>
                {ELSE}
                    <th align="left" class="icon-folder">
                        <a href="{FORUMS->URL->LIST}">{FORUMS->name}</a>
                    </th>
                {/IF}
                <th>{LANG->Threads}</th>
                <th>{LANG->Posts}</th>
                <th align="left">{LANG->LastPost}</th>
            </tr>
    {ELSE}
        <tr>
            {IF FORUMS->folder_flag}
              <td colspan="4">
                <a href="{FORUMS->URL->INDEX}">{FORUMS->name}</a><p>{FORUMS->description}</p>
              </td>
            {ELSE}
                <td width="55%">
                    <h3><a href="{FORUMS->URL->LIST}">{FORUMS->name}</a>{IF FORUMS->new_message_check}&nbsp;&nbsp;<span class="new-indicator">({LANG->NewMessages})</span>{/IF}</h3>
                    <p>{FORUMS->description}</p>
                    {IF FORUMS->URL->MARK_READ}&raquo; <a class="icon" href="{FORUMS->URL->MARK_READ}">{LANG->MarkForumRead}</a>{/IF}
                    {IF FORUMS->URL->FEED}&raquo; <a class="icon" href="{FORUMS->URL->FEED}">{FEED}</a>{/IF}
                </td>
                <td align="center" width="12%" nowrap="nowrap">
                    {FORUMS->thread_count}
                    {IF FORUMS->new_threads}
                        (<span class="new-flag">{FORUMS->new_threads} {LANG->newflag}</span>)
                    {/IF}
                </td>
                <td align="center" width="12%" nowrap="nowrap">
                    {FORUMS->message_count}
                    {IF FORUMS->new_messages}
                        (<span class="new-flag">{FORUMS->new_messages} {LANG->newflag}</span>)
                    {/IF}
                </td>
                <td width="21%" nowrap="nowrap">{FORUMS->last_post}</td>
        {/IF}
      </tr>
    {/IF}
    {/LOOP FORUMS}
</table>
