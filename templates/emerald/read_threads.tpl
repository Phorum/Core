<!-- BEGIN TEMPLATE read_threads.tpl -->
<div class="nav">
    <div class="nav-right">
        <a class="icon icon-prev" href="{MESSAGE->URL->PREV}">{LANG->PreviousMessage}</a>
        <a class="icon icon-next" href="{MESSAGE->URL->NEXT}">{LANG->NextMessage}</a>
    </div>
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
    <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
    <a class="icon icon-printer" href="{URL->PRINTVIEW}" target="_blank">{LANG->PrintView}</a>
</div>

<div class="message">

    <div class="generic">

        <table border="0" cellspacing="0">
            <tr>
                <td width="100%">
                    <div class="message-author icon-user">
                        {IF MESSAGE->URL->PROFILE}<a href="{MESSAGE->URL->PROFILE}">{/IF}{MESSAGE->author}{IF MESSAGE->URL->PROFILE}</a>{/IF}
                        {IF MESSAGE->URL->PM}<small>[ <a href="{MESSAGE->URL->PM}">{LANG->PrivateReply}</a> ]</small>{/IF}
                    </div>
                    <small>{MESSAGE->datestamp}</small>
                </td>
                <td class="message-user-info" nowrap="nowrap">
                    {IF MESSAGE->user->admin}
                        <strong>{LANG->Admin}</strong><br />
                    {ELSEIF MESSAGE->moderator_post}
                        <strong>{LANG->Moderator}</strong><br />
                    {/IF}
                    {IF MESSAGE->ip}
                        {LANG->IP}: {MESSAGE->ip}<br />
                    {/IF}
                    {IF MESSAGE->user}
                        {LANG->DateReg}: {MESSAGE->user->date_added}<br />
                        {LANG->Posts}: {MESSAGE->user->posts}
                    {/IF}
                </td>
            </tr>
        </table>
    </div>

    <div class="message-body">
        {IF MESSAGE->is_unapproved}
            <div class="warning">
                {LANG->UnapprovedMessage}
            </div>
        {/IF}

        {MESSAGE->body}
        {IF MESSAGE->URL->CHANGES}
            (<a href="{MESSAGE->URL->CHANGES}">{LANG->ViewChanges}</a>)
        {/IF}
        <div class="message-options">
            {IF MESSAGE->edit 1}
                {IF MODERATOR false}
                    <a class="icon icon-comment-edit" href="{MESSAGE->URL->EDIT}">{LANG->EditPost}</a>
                {/IF}
            {/IF}
            <a class="icon icon-comment-add" href="{MESSAGE->URL->REPLY}">{LANG->Reply}</a>
            <a class="icon icon-comment-add" href="{MESSAGE->URL->QUOTE}">{LANG->QuoteMessage}</a>
            {IF MESSAGE->URL->REPORT}<a class="icon icon-exclamation" href="{MESSAGE->URL->REPORT}">{LANG->Report}</a>{/IF}
        </div>

        {IF MESSAGE->attachments}
            <div class="attachments">
                {LANG->Attachments}:<br/>
                {LOOP MESSAGE->attachments}
                    <a href="{MESSAGE->attachments->url}">{LANG->AttachOpen}</a> | <a href="{MESSAGE->attachments->download_url}">{LANG->AttachDownload}</a> -
                    {MESSAGE->attachments->name}
                    ({MESSAGE->attachments->size})<br/>
                {/LOOP MESSAGE->attachments}
            </div>
        {/IF}

        {IF MODERATOR true}
            <div class="message-moderation">
                {IF MESSAGE->threadstart false}
                    <a class="icon icon-delete" href="{MESSAGE->URL->DELETE_MESSAGE}">{LANG->DeleteMessage}</a>
                    <a class="icon icon-delete" href="{MESSAGE->URL->DELETE_THREAD}">{LANG->DelMessReplies}</a>
                    <a class="icon icon-split" href="{MESSAGE->URL->SPLIT}">{LANG->SplitThread}</a>
                {/IF}
                {IF MESSAGE->is_unapproved}
                    <a class="icon icon-accept" href="{MESSAGE->URL->APPROVE}">{LANG->ApproveMessage}</a>
                {ELSE}
                    <a class="icon icon-comment-delete" href="{MESSAGE->URL->HIDE}">{LANG->HideMessage}</a>
                {/IF}
                <a class="icon icon-comment-edit" href="{MESSAGE->URL->EDIT}">{LANG->EditPost}</a>
            </div>
        {/IF}

    </div>

</div>

<div class="nav">
    {IF MODERATOR true}
        <div class="nav-right">
            <a class="icon icon-merge" href="{TOPIC->URL->MERGE}">{LANG->MergeThread}</a>
            {IF TOPIC->closed false}
                <a class="icon icon-close" href="{TOPIC->URL->CLOSE}">{LANG->CloseThread}</a>
            {ELSE}
                <a class="icon icon-open" href="{TOPIC->URL->REOPEN}">{LANG->ReopenThread}</a>
            {/IF}
            <a class="icon icon-delete" href="{TOPIC->URL->DELETE_THREAD}">{LANG->DeleteThread}</a>
            {IF TOPIC->URL->MOVE}<a class="icon icon-move" href="{TOPIC->URL->MOVE}">{LANG->MoveThread}</a>{/IF}
        </div>
    {/IF}

    {IF URL->MARKTHREADREAD}
        <a class="icon icon-tag-green" href="{URL->MARKTHREADREAD}">{LANG->MarkThreadRead}</a>
    {/IF}
    {IF TOPIC->URL->FOLLOW}
        <a class="icon icon-note-add" href="{TOPIC->URL->FOLLOW}">{LANG->FollowThread}</a>
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
    </tr>

    {LOOP MESSAGES}
    {VAR alt ""}
    {VAR title ""}

    {! This is the current message }
    {IF MESSAGES->message_id MESSAGE->message_id}
        {VAR altclass "current"}
    {ELSE}
        {VAR altclass ""}
    {/IF}

    {IF MESSAGES->message_id MESSAGE->message_id}
        {VAR icon "bullet_go"}
    {ELSEIF MESSAGES->parent_id 0}
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
    {ELSE}
        {VAR icon "bullet_black"}
    {/IF}

    {IF MESSAGES->new}
        {VAR newclass "new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    <tr>
        <td width="65%" class="message-subject-threaded {altclass}">
            <h4 style="padding-left: {MESSAGES->indent_cnt}px;">
                <img src="{URL->TEMPLATE}/images/{icon}.png" class="icon1616" alt="{alt}" title="{title}" />
                <a href="{MESSAGES->URL->READ}" class="{newclass}">{MESSAGES->subject}</a>
                {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" class="icon1616" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}
            </h4>
        </td>
        <td width="10%" class="{altclass}" nowrap="nowrap">{IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}{MESSAGES->author}{IF MESSAGES->URL->PROFILE}</a>{/IF}</td>
        {IF VIEWCOUNT_COLUMN}
            <td width="10%" align="center" class="{altclass}" nowrap="nowrap">{MESSAGES->viewcount}</td>
        {/IF}
        <td width="15%" class="{altclass}" nowrap="nowrap">{MESSAGES->datestamp}</td>
    </tr>
    {/LOOP MESSAGES}
</table>
<br />
<br />
<br />
<!-- END TEMPLATE read_threads.tpl -->
