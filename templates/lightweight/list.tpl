<div class="nav">
    {INCLUDE "paging"}
    {IF URL->INDEX}&raquo; <a class="icon" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    &raquo; <a class="icon" href="{URL->POST}">{LANG->NewTopic}</a>
    {IF URL->MARK_READ}
        &raquo; <a class="icon" href="{URL->MARK_READ}">{LANG->MarkForumRead}</a>
    {/IF}
    {IF URL->FEED}
        &raquo; <a class="icon icon-feed" href="{URL->FEED}">{FEED}</a>
    {/IF}
</div>

<table cellspacing="0" class="list">
    <tr>
        <th align="left" colspan="2">
            {LANG->Subject}
        </th>
        {IF VIEWCOUNT_COLUMN}
          <th>{LANG->Views}</th>
        {/IF}
        <th nowrap="nowrap">{LANG->Posts}</th>
        <th align="left" nowrap="nowrap">{LANG->LastPost}</th>
        {IF MODERATOR true}
            <th nowrap="nowrap">{LANG->Moderate}</th>
        {/IF}
    </tr>

    {LOOP MESSAGES}
    {IF altclass ""}
        {VAR altclass "alt"}
    {ELSE}
        {VAR altclass ""}
    {/IF}

    {IF MESSAGES->sort PHORUM_SORT_STICKY}
        {VAR title LANG->Sticky}
    {ELSEIF MESSAGES->moved}
        {VAR title LANG->MovedSubject}
    {ELSEIF MESSAGES->new}
        {VAR title LANG->NewMessage}
    {ELSE}
        {VAR title "&nbsp;"}
    {/IF}

    {IF MESSAGES->new}
        {VAR newclass "message-new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    <tr>

        <td width="1%" class="{altclass}">{IF MESSAGES->new}<a href="{MESSAGES->URL->NEWPOST}"><span class="new-indicator">{LANG->New}</span></a>{else}{title}{/IF}</td>
        <td width="59%" class="{altclass}">
            <h4>
                <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
                {IF MESSAGES->meta->attachments}<small>(@ {LANG->Attachments})</small>{/IF}
                {IF MESSAGES->pages}&nbsp;<small>&nbsp;({LANG->Pages}:&nbsp;{MESSAGES->pages})</small>{/IF}
            </h4>
            {LANG->by} {IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}
        </td>

        {IF VIEWCOUNT_COLUMN}
            <td width="12%" align="center" class="{altclass}" nowrap="nowrap">
                {IF MESSAGES->moved}
                    &nbsp;
                {ELSE}
                    {MESSAGES->viewcount}
                {/IF}
            </td>
        {/IF}

        {IF MESSAGES->moved}
            <td colspan="2" width="30%" align="center" class="{altclass}" nowrap="nowrap">{LANG->MovedSubject}</td>
        {ELSE}

            <td width="12%" align="center" class="{altclass}" nowrap="nowrap">{MESSAGES->thread_count}</td>
            <td width="15%" class="{altclass}" nowrap="nowrap">{MESSAGES->lastpost}<br /><a href="{MESSAGES->URL->LAST_POST}">{LANG->LastPostLink}</a> {LANG->by} {IF MESSAGES->URL->RECENT_AUTHOR_PROFILE}<a href="{MESSAGES->URL->RECENT_AUTHOR_PROFILE}">{/IF}{MESSAGES->recent_author}{IF MESSAGES->URL->RECENT_AUTHOR_PROFILE}</a>{/IF}</td>

        {/IF}

        {IF MODERATOR true}
            <td width="1%" class="{altclass}" nowrap="nowrap">
            <small>
                {IF MESSAGES->moved}
                    <a title="{LANG->DeleteMessage}" href="{MESSAGES->URL->DELETE_THREAD}">{LANG->DeleteMessage}</a>
                {ELSE}
                    {IF MESSAGES->URL->MOVE}
                        &raquo; <a href="{MESSAGES->URL->MOVE}">{LANG->MoveThread}</a><br />
                    {/IF}
                    &raquo; <a href="{MESSAGES->URL->MERGE}">{LANG->MergeThread}</a><br />
                    &raquo; <a href="{MESSAGES->URL->DELETE_THREAD}">{LANG->DeleteThread}</a>
                {/IF}
            </td>
        {/IF}

    </tr>
  {/LOOP MESSAGES}
</table>
<div class="nav">
    {INCLUDE "paging"}
</div>

