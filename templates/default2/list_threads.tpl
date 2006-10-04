<div class="nav">
<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>
<a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
{IF USER->user_id}
    <a class="icon icon-tag-green" href="{URL->MARK_READ}">{LANG->MarkForumRead}</a>
{/IF}
{IF URL->FEED}
    <a class="icon icon-feed" href="{URL->FEED}">{FEED}</a>
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
    {ELSEIF MESSAGES->new}
        {VAR icon "flag_red"}
        {VAR title LANG->New}
    {ELSE}
        {VAR icon "bullet_black"}
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
            <img src="{URL->TEMPLATE}/images/{icon}.png" width="16" height="16" border="0" />
            <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
            {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" width="16" height="16" border="0" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}        
            {IF MESSAGES->sort PHORUM_SORT_ANNOUNCEMENT}<small>({MESSAGES->thread_count} {LANG->Posts})</small>{/IF}        
            {IF MESSAGES->sort PHORUM_SORT_STICKY}<small>({MESSAGES->thread_count} {LANG->Posts})</small>{/IF}        
        </h4>
    </td>
    <td width="10%" class="{altclass}" nowrap="nowrap">{MESSAGES->linked_author}</td>
    {IF VIEWCOUNT_COLUMN}
        <td align="center" width="10%" class="{altclass}" nowrap="nowrap">{MESSAGES->viewcount}</td>
    {/IF}
    <td width="15%" class="{altclass}" nowrap="nowrap">{MESSAGES->datestamp}</td>
    {IF MODERATOR true}
        <td width="1%" class="{altclass}" nowrap="nowrap">
            {IF NOT MESSAGES->moved}
                {IF MESSAGES->threadstart true}
                    <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><img src="{URL->TEMPLATE}/images/page_go.png" width="16" height="16" alt="{LANG->MoveThread}" border="0" /></a>
                    <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><img src="{URL->TEMPLATE}/images/arrow_join.png" width="16" height="16" alt="{LANG->MergeThread}" border="0" /></a>
                    <a title="{LANG->DeleteThread}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><img src="{URL->TEMPLATE}/images/delete.png" width="16" height="16" alt="{LANG->DeleteThread}" border="0" /></a>
                {ELSE}
                    <a title="{LANG->DeleteMessage}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_MESSAGE}';"><img src="{URL->TEMPLATE}/images/delete.png" width="16" height="16" alt="{LANG->DeleteMessage}" border="0" /></a>
                {/IF}
            {/IF}
        </td>
    {/IF}
    </tr>
    {/LOOP MESSAGES}
</table>
{INCLUDE "paging"}

