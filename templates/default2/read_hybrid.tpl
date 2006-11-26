<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
    <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
    <a class="icon icon-printer" href="{URL->PRINTVIEW}" target="_blank">{LANG->PrintView}</a>
</div>

{LOOP MESSAGES}

    {IF NOT MESSAGES->parent_id 0}
        <a name="msg-{MESSAGES->message_id}"></a>
    {/IF}

    <div class="message" style="padding-left: {MESSAGES->indent_cnt}px;">

        <div class="generic">

            <table border="0" cellspacing="0">
                <tr>
                    <td width="100%">
                        <div class="message-author icon-user">
                            {MESSAGES->linked_author}
                            {IF LOGGEDIN}
                                {IF MESSAGES->URL->PM}
                                    <small>[ <a href="{MESSAGES->URL->PM}">{LANG->PrivateReply}</a> ]</small>
                                {/IF}
                            {/IF}
                        </div>
                        <small>
                        <strong>{MESSAGES->subject} <span class="new">{MESSAGES->new}</span></strong><br />
                        {MESSAGES->datestamp}
                        </small>
                    </td>
                    <td class="message-user-info" nowrap="nowrap">
                        {IF MESSAGES->user->admin}
                            <strong>{LANG->Admin}</strong><br />
                        {ELSEIF MESSAGES->moderator_post}
                            <strong>{LANG->Moderator}</strong><br />
                        {/IF}
                        {IF MESSAGES->ip}
                            {LANG->IP}: {MESSAGES->ip}<br />
                        {/IF}
                        {IF MESSAGES->user}
                            {LANG->DateReg}: {MESSAGES->user->date_added}<br />
                            {LANG->Posts}: {MESSAGES->user->posts}
                        {/IF}
                    </td>
                </tr>
            </table>
        </div>

        <div class="message-body">
            {IF MESSAGES->is_unapproved}
                <div class="warning">
                    {LANG->UnapprovedMessage}
                </div>
            {/IF}

            {MESSAGES->body}
            <div class="message-options">
                {IF MESSAGES->edit 1}
                    {IF MODERATOR false}
                        <a class="icon icon-comment-edit" href="{MESSAGES->URL->EDIT}">{LANG->EditPost}</a>
                    {/IF}
                {/IF}
                <a class="icon icon-comment-add" href="{MESSAGES->URL->REPLY}">{LANG->Reply}</a>
                <a class="icon icon-comment-add" href="{MESSAGES->URL->QUOTE}">{LANG->QuoteMessage}</a>
                <a class="icon icon-exclamation" href="{MESSAGES->URL->REPORT}">{LANG->Report}</a>
            </div>

            {IF MESSAGES->attachments}
                <div class="attachments">
                    {LANG->Attachments}:
                    {LOOP MESSAGES->attachments}
                        <a href="{MESSAGES->attachments->url}">{MESSAGES->attachments->name} ({MESSAGES->attachments->size})</a>&nbsp;&nbsp;
                    {/LOOP MESSAGES->attachments}
                </div>
            {/IF}

        {IF MODERATOR true}
            <div class="message-moderation">
                {IF MESSAGES->threadstart true}
                    <a class="icon icon-delete" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->URL->DELETE_THREAD}';">{LANG->DeleteThread}</a>
                    {IF MESSAGES->URL->MOVE}<a class="icon icon-move" href="{MESSAGES->URL->MOVE}">{LANG->MoveThread}</a>{/IF}
                {ELSE}
                    <a class="icon icon-delete" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_MESSAGE}';">{LANG->DeleteMessage}</a>
                    <a class="icon icon-delete" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->URL->DELETE_THREAD}';">{LANG->DelMessReplies}</a>
                    <a class="icon icon-split" href="{MESSAGES->URL->SPLIT}">{LANG->SplitThread}</a>
                {/IF}
                {IF MESSAGES->is_unapproved}
                    <a class="icon icon-accept" href="{MESSAGES->URL->APPROVE}">{LANG->ApproveMessage}</a>
                {ELSE}
                    <a class="icon icon-comment-delete" href="{MESSAGES->URL->HIDE}">{LANG->HideMessage}</a>
                {/IF}
                <a class="icon icon-comment-edit" href="{MESSAGES->URL->EDIT}">{LANG->EditPost}</a>
            </div>
        {/IF}


        </div>
    </div>
{/LOOP MESSAGES}

<div class="nav">
    {INCLUDE "paging"}
    <a class="icon icon-prev" href="{URL->NEWERTHREAD}">{LANG->NewerThread}</a>
    <a class="icon icon-next" href="{URL->OLDERTHREAD}">{LANG->OlderThread}</a>
</div>

<div id="thread-options" class="nav">
    <a class="icon icon-printer" href="{URL->PRINTVIEW}" target="_blank">{LANG->PrintView}</a>
    {IF USER->user_id}
        <a class="icon icon-tag-green" href="{URL->MARKTHREADREAD}">{LANG->MarkThreadRead}</a>
        <a class="icon icon-note-add" href="{TOPIC->URL->FOLLOW}">{LANG->FollowThread}</a>
    {/IF}
    {IF URL->FEED}
        <a class="icon icon-feed" href="{URL->FEED}">{FEED}</a>
    {/IF}
    {IF MODERATOR true}
        <a class="icon icon-merge" href="{TOPIC->URL->MERGE}">{LANG->MergeThread}</a>
        {IF TOPIC->closed false}
            <a class="icon icon-close" href="{TOPIC->URL->CLOSE}">{LANG->CloseThread}</a>
        {ELSE}
            <a class="icon icon-open" href="{TOPIC->URL->REOPEN}">{LANG->ReopenThread}</a>
        {/IF}
    {/IF}
</div>

