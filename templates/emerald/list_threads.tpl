<!-- BEGIN TEMPLATE list_threads.tpl -->
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list_threads.tpl -->
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
    {IF URL->MARK_READ}
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
        {IF MESSAGES->sort PHORUM_SORT_STICKY}
            {IF MESSAGES->new}
               {VAR icon "flag_red"}
               {VAR alt LANG->NewMessage}
            {ELSE}
               {VAR icon "bell"}
               {VAR alt LANG->Sticky}
            {/IF}

            {VAR title LANG->Sticky}
        {ELSEIF MESSAGES->moved}
            {VAR icon "page_go"}
            {VAR title LANG->MovedSubject}
            {VAR alt LANG->MovedSubject}
        {ELSEIF MESSAGES->new}
            {VAR icon "flag_red"}
            {VAR title LANG->NewMessage}
            {VAR alt LANG->NewMessage}
        {ELSE}
            {VAR icon "comment"}
            {VAR title ""}
            {VAR alt ""}
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
            <img src="{URL->TEMPLATE}/images/{icon}.png" class="icon1616" alt="{alt}" title="{title}" />
            <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
            {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" class="icon1616" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}
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
                <a title="{LANG->DeleteThread}" href="{MESSAGES->URL->DELETE_MESSAGE}"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteThread}" /></a>
            {ELSE}
                {IF MESSAGES->threadstart true}
                    {IF MESSAGES->URL->MOVE}
                        <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><img src="{URL->TEMPLATE}/images/page_go.png" class="icon1616" alt="{LANG->MoveThread}" /></a>
                    {/IF}
                    <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><img src="{URL->TEMPLATE}/images/arrow_join.png" alt="{LANG->MergeThread}" /></a>
                    <a title="{LANG->DeleteThread}" href="{MESSAGES->URL->DELETE_THREAD}"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteThread}" /></a>
                {ELSE}
                    <a title="{LANG->DeleteMessage}" href="{MESSAGES->URL->DELETE_MESSAGE}"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteMessage}" /></a>
                {/IF}
            {/IF}
        </td>
    {/IF}
    </tr>
    {/LOOP MESSAGES}
</table>
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list_threads.tpl -->
</div>
<br />
<!-- END TEMPLATE list_threads.tpl -->
