<div id="list-nav">
<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/folder.png');" href="{URL->INDEX}">{LANG->ForumList}</a>
<a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/comment_add.png');" href="{URL->POST}">{LANG->NewTopic}</a>
{IF USER->user_id}
    <a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/tag_green.png');" href="{URL->MARK_READ}">{LANG->MarkForumRead}</a>
{/IF}
{IF URL->RSS}
    <a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/feed.png');" href="{URL->RSS}">{LANG->RSS}</a>
{/IF}
</div>
<table border="0" cellspacing="0" id="messages">
    <tr>
        <th class="messages-subject">{LANG->Subject}</th>
        {IF VIEWCOUNT_COLUMN}
          <th class="messages-views">{LANG->Views}</th>
        {/IF}
        <th class="messages-started-by" nowrap="nowrap">{LANG->StartedBy}</th>
        <th class="messages-last-post" nowrap="nowrap">{LANG->Posted}</th>
        {IF MODERATOR true}
            <th class="messages-moderate" nowrap="nowrap">{LANG->Moderate}</th>
        {/IF}
    </tr>

    {LOOP MESSAGES}

    {IF MESSAGES->parent_id 0}    
        {IF altclass ""}
            {var altclass "message-threaded-alt"}
        {ELSE}
            {var altclass ""}
        {/IF}
    {/IF}

    {IF MESSAGES->parent_id 0}
        {IF MESSAGES->sort PHORUM_SORT_ANNOUNCEMENT}
            {var icon "information"} 
        {ELSEIF MESSAGES->sort PHORUM_SORT_STICKY}
            {var icon "bell"}
        {ELSEIF MESSAGES->moved}
            {var icon "page_go"}
        {ELSEIF MESSAGES->new}
            {var icon "flag_red"}
        {ELSE}
            {var icon "comment"}
        {/IF}
    {ELSEIF MESSAGES->new}
        {var icon "flag_red"}
    {ELSE}
        {var icon "bullet_black"}
    {/IF}

    {IF MESSAGES->new}
        {var newclass "message-new"}
    {ELSE}
        {var newclass ""}
    {/IF}

    <tr>
    <td class="message-subject-threaded {altclass}" style="padding-left: {MESSAGES->indent_cnt}px">
        <a style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/{icon}.png');" href="{MESSAGES->url}" class="list-threaded-subject {newclass}">{MESSAGES->subject}</a>
        {IF MESSAGES->sort PHORUM_SORT_ANNOUNCEMENT}<small>({MESSAGES->thread_count} {LANG->Posts})</small>{/IF}        
        {IF MESSAGES->sort PHORUM_SORT_STICKY}<small>({MESSAGES->thread_count} {LANG->Posts})</small>{/IF}        
        {IF MESSAGES->pages}&nbsp;<small>&nbsp;[{LANG->Pages}: {MESSAGES->pages}]</small>{/IF}
    </td>
    {IF VIEWCOUNT_COLUMN}
        <td class="message-view-count {altclass}" nowrap="nowrap">{MESSAGES->viewcount}</td>
    {/IF}
    <td class="message-author {altclass}" nowrap="nowrap">{MESSAGES->linked_author}</td>
    <td class="message-posted {altclass}" nowrap="nowrap">{MESSAGES->datestamp}</td>
    {IF MODERATOR true}
        <td class="message-actions {altclass}" nowrap="nowrap">
            {IF MESSAGES->threadstart true}
                <a title="{LANG->DeleteThread}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/delete.png" width="16" height="16" alt="{LANG->DeleteThread}" border="0" /></a>
                <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/page_go.png" width="16" height="16" alt="{LANG->MoveThread}" border="0" /></a>
                <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/arrow_join.png" width="16" height="16" alt="{LANG->MergeThread}" border="0" /></a>
            {ELSE}
                <a title="{LANG->DeleteMessage}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_MESSAGE}';"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/delete.png" width="16" height="16" alt="{LANG->DeleteMessage}" border="0" /></a>
            {/IF}
        </td>
    {/IF}
    </tr>
    {/LOOP MESSAGES}
</table>
{INCLUDE paging}

