{IF PHORUM_PAGE "list"}
    <div class="nav">
        {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
        <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
        {IF USER->user_id}
            <a class="icon icon-tag-green" href="{URL->MARK_READ}">{LANG->MarkForumRead}</a>
        {/IF}
        {IF URL->FEED}
            <a class="icon icon-feed" href="{URL->FEED}">{FEED}</a>
        {/IF}
    </div>
{/IF}

<table cellspacing="0" class="list">
    <tr>
        <th align="left" colspan="2">
            {IF PHORUM_PAGE "index"}
                {LANG->Announcements}
            {ELSE}
                {LANG->Subject}
            {/IF}
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

    {IF MESSAGES->sort PHORUM_SORT_ANNOUNCEMENT}
        {VAR icon "information"}
        {VAR title LANG->Announcement}
    {ELSEIF MESSAGES->sort PHORUM_SORT_STICKY}
        {VAR icon "bell"}
        {VAR title LANG->Sticky}
    {ELSEIF MESSAGES->moved}
        {VAR icon "page_go"}
        {VAR title LANG->MovedSubject}
    {ELSEIF MESSAGES->new}
        {VAR icon "flag_red"}
        {VAR title LANG->NewMessage}
    {ELSE}
        {VAR icon "comment"}
        {VAR title ""}
    {/IF}

    {IF MESSAGES->new}
        {VAR newclass "message-new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    <tr>

        <td width="1%" class="{altclass}"><a href="{MESSAGES->URL->READ}" title="{title}"><img src="{URL->TEMPLATE}/images/{icon}.png" width="16" height="16" border="0" /></a></td>
        <td width="59%" class="{altclass}">
            <h4>
                <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
                {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" width="16" height="16" border="0" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}
                {IF MESSAGES->pages}&nbsp;<small>&nbsp;({LANG->Pages}:&nbsp;{MESSAGES->pages})</small>{/IF}
            </h4>
            {LANG->by} {MESSAGES->linked_author}
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
            <td width="15%" class="{altclass}" nowrap="nowrap">{MESSAGES->lastpost}<br /><a href="{MESSAGES->URL->LAST_POST}">{LANG->LastPostLink}</a> {LANG->by} {MESSAGES->last_post_by}</td>

        {/IF}

        {IF MODERATOR true}
            <td width="1%" class="{altclass}" nowrap="nowrap">
                {IF MESSAGES->moved}
                    &nbsp;
                {ELSE}
                    <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><img src="{URL->TEMPLATE}/images/page_go.png" width="16" height="16" alt="{LANG->MoveThread}" border="0" /></a>
                    <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><img src="{URL->TEMPLATE}/images/arrow_join.png" width="16" height="16" alt="{LANG->MergeThread}" border="0" /></a>
                    <a title="{LANG->DeleteThread}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><img src="{URL->TEMPLATE}/images/delete.png" width="16" height="16" alt="{LANG->DeleteThread}" border="0" /></a>
                {/IF}
            </td>
        {/IF}

    </tr>
  {/LOOP MESSAGES}
</table>
{INCLUDE "paging"}

