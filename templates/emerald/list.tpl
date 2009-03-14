<!-- BEGIN TEMPLATE list.tpl -->
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list.tpl -->
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

    {IF MESSAGES->new}
        {VAR newclass "message-new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    <tr>

        <td width="1%" class="{altclass}"><a href="{IF MESSAGES->new}{MESSAGES->URL->NEWPOST}{ELSE}{MESSAGES->URL->READ}{/IF}" title="{title}"><img src="{URL->TEMPLATE}/images/{icon}.png" class="icon1616" alt="{alt}" /></a></td>
        <td width="59%" class="{altclass}">
            <h4>
                <a href="{MESSAGES->URL->READ}" class="{newclass}" title="{title}">{MESSAGES->subject}</a>
                {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" class="icon1616" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}
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
            <td width="30%" class="{altclass}">&nbsp;</td>
            <td width="30%" align="left" class="{altclass}" nowrap="nowrap">{LANG->MovedSubject}</td>
        {ELSE}

            <td width="12%" align="center" class="{altclass}" nowrap="nowrap">{MESSAGES->thread_count}</td>
            <td width="15%" class="{altclass}" nowrap="nowrap">{MESSAGES->lastpost}<br /><a href="{MESSAGES->URL->LAST_POST}">{LANG->LastPostLink}</a> {LANG->by} {IF MESSAGES->URL->RECENT_AUTHOR_PROFILE}<a href="{MESSAGES->URL->RECENT_AUTHOR_PROFILE}">{/IF}{MESSAGES->recent_author}{IF MESSAGES->URL->RECENT_AUTHOR_PROFILE}</a>{/IF}</td>

        {/IF}

        {IF MODERATOR true}
            <td width="1%" align="right" class="{altclass}" nowrap="nowrap">
                {IF MESSAGES->moved}
                    <a title="{LANG->DeleteMessage}" href="{MESSAGES->URL->DELETE_THREAD}"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteMessage}" /></a>
                {ELSE}
                    {IF MESSAGES->URL->MOVE}
                        <a title="{LANG->MoveThread}" href="{MESSAGES->URL->MOVE}"><img src="{URL->TEMPLATE}/images/page_go.png" class="icon1616" alt="{LANG->MoveThread}" /></a>
                    {/IF}
                    <a title="{LANG->MergeThread}" href="{MESSAGES->URL->MERGE}"><img src="{URL->TEMPLATE}/images/arrow_join.png" alt="{LANG->MergeThread}" /></a>
                    <a title="{LANG->DeleteThread}" href="{MESSAGES->URL->DELETE_THREAD}"><img src="{URL->TEMPLATE}/images/delete.png" class="icon1616" alt="{LANG->DeleteThread}" /></a>
                {/IF}
            </td>
        {/IF}

    </tr>
  {/LOOP MESSAGES}
</table>
<div class="nav">
    {INCLUDE "paging"}
    <!-- CONTINUE TEMPLATE list.tpl -->
</div>
<br />
<!-- END TEMPLATE list.tpl -->
