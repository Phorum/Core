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
        <th class="messages-posts" nowrap="nowrap">{LANG->Posts}</th>
        <th class="messages-started-by" nowrap="nowrap">{LANG->StartedBy}</th>
        <th class="messages-last-post" nowrap="nowrap">{LANG->LastPost}</th>
        {IF MODERATOR true}
            <th class="messages-moderate" nowrap="nowrap">{LANG->Moderate}</th>
        {/IF}
    </tr>

    {LOOP MESSAGES}
    {IF altclass ""}
        {var altclass "message-alt"}
    {ELSE}
        {var altclass ""}
    {/IF}

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

    {IF MESSAGES->new}
        {var newclass "message-new"}
    {ELSE}
        {var newclass ""}
    {/IF}

    <tr>
    <td class="message-subject {altclass}" style="background-image: url('{URL->BASE_URL}/templates/{TEMPLATE}/images/{icon}.png');">
        <a href="{MESSAGES->URL->READ}" class="{newclass}">{MESSAGES->subject}</a>
        {IF MESSAGES->pages}&nbsp;<small>&nbsp;[{LANG->Pages}: {MESSAGES->pages}]</small>{/IF}
      </td>
      {IF VIEWCOUNT_COLUMN}
        <td class="message-view-count {altclass}" nowrap="nowrap">{MESSAGES->viewcount}</td>
      {/IF}
      <td class="message-thread-count {altclass}" nowrap="nowrap">{MESSAGES->thread_count}</td>
      <td class="message-author {altclass}" nowrap="nowrap">{MESSAGES->linked_author}</td>
      <td class="message-last-post {altclass}" nowrap="nowrap">{MESSAGES->lastpost}<br /><a href="{MESSAGES->URL->LAST_POST}">{LANG->LastPostLink}</a> {LANG->by} {MESSAGES->last_post_by}</td>
      {IF MODERATOR true}
      <td class="message-actions {altclass}" nowrap="nowrap">
            <a title="{LANG->DeleteThread}" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_THREAD}';"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/delete.png" width="16" height="16" alt="{LANG->DeleteThread}" border="0" /></a>
            <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/page_go.png" width="16" height="16" alt="{LANG->MoveThread}" border="0" /></a>
            <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><img src="{URL->BASE_URL}/templates/{TEMPLATE}/images/arrow_join.png" width="16" height="16" alt="{LANG->MergeThread}" border="0" /></a>
      </td>
      {/IF}

    </tr>
  {/LOOP MESSAGES}
</table>
{INCLUDE paging}

