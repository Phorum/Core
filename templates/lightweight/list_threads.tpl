<div class="nav">
    {INCLUDE "paging"}
    {IF URL->INDEX}&raquo; <a class="icon" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    &raquo; <a class="icon" href="{URL->POST}">{LANG->NewTopic}</a>
    {IF URL->MARK_READ}
        &raquo; <a class="icon" href="{URL->MARK_READ}">{LANG->MarkForumRead}</a>
    {/IF}
    {IF URL->FEED}
        &raquo; <a class="icon" href="{URL->FEED}">{FEED}</a>
    {/IF}
</div>

<table cellspacing="0" class="list">
    <tr>
        <th align="left">{LANG->Subject}</th>
        <th align="left" nowrap="nowrap">{LANG->Author}</th>
        {IF VIEWCOUNT_COLUMN}
          <th>{LANG->Views}</th>
        {/IF}
        <th align="left" nowrap="nowrap">{LANG->Posted}</th>
        {IF MODERATOR true}
            <th nowrap="nowrap">{LANG->Moderate}</th>
        {/IF}
    </tr>

    {LOOP MESSAGES}

    {IF MESSAGES->parent_id 0}
        {IF altclass ""}
            {VAR altclass "alt"}
        {ELSE}
            {VAR altclass ""}
        {/IF}
    {/IF}

    {IF MESSAGES->parent_id 0}
        {IF MESSAGES->sort PHORUM_SORT_STICKY}
            {VAR title LANG->Sticky}
        {ELSEIF MESSAGES->moved}
            {VAR title LANG->MovedSubject}
        {ELSE}
            {VAR title ""}
        {/IF}
    {ELSE}
        {VAR title ""}
    {/IF}

    {IF MESSAGES->new}
        {VAR newclass "message-new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    <tr>
    <td width="65%" class="{altclass}">
        <h4 style="padding-left: {MESSAGES->indent_cnt}px">
            {IF MESSAGES->new}<span class="new-indicator">{LANG->New}</span>{/IF}{title}
            <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
            {IF MESSAGES->meta->attachments}<small>(@ {LANG->Attachments})</small>{/IF}
            {IF MESSAGES->sort PHORUM_SORT_STICKY}<small>({MESSAGES->thread_count} {LANG->Posts})</small>{/IF}
        </h4>
    </td>
    <td width="10%" class="{altclass}" nowrap="nowrap">{IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}</td>
    {IF VIEWCOUNT_COLUMN}
        <td align="center" width="10%" class="{altclass}" nowrap="nowrap">{MESSAGES->viewcount}</td>
    {/IF}
    <td width="15%" class="{altclass}" nowrap="nowrap">{MESSAGES->datestamp}</td>
    {IF MODERATOR true}
        <td width="1%" class="{altclass}" nowrap="nowrap">
            {IF MESSAGES->moved}
                <a title="{LANG->DeleteThread}" href="{MESSAGES->URL->DELETE_MESSAGE}">{LANG->DeleteThread}</a>
            {ELSE}
                {IF MESSAGES->threadstart true}
                    &raquo; <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}">{LANG->MoveThread}</a><br />
                    &raquo; <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}">{LANG->MergeThread}</a><br />
                    &raquo; <a title="{LANG->DeleteThread}" href="{MESSAGES->URL->DELETE_THREAD}">{LANG->DeleteThread}</a>
                {ELSE}
                    &raquo; <a title="{LANG->DeleteMessage}" href="{MESSAGES->URL->DELETE_MESSAGE}">{LANG->DeleteMessage}</a>
                {/IF}
            {/IF}
        </td>
    {/IF}
    </tr>
    {/LOOP MESSAGES}
</table>
<div class="nav">
    {INCLUDE "paging"}
</div>

