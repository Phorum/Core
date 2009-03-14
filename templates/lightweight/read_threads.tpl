<div class="nav">
    <div class="nav-right">
        &raquo; <a class="icon" href="{MESSAGE->URL->PREV}">{LANG->PreviousMessage}</a>
        &raquo; <a class="icon" href="{MESSAGE->URL->NEXT}">{LANG->NextMessage}</a>
    </div>
    {IF URL->INDEX}&raquo; <a class="icon" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    &raquo; <a class="icon" href="{URL->LIST}">{LANG->MessageList}</a>
    &raquo; <a class="icon" href="{URL->POST}">{LANG->NewTopic}</a>
    &raquo; <a class="icon" href="{URL->PRINTVIEW}" target="_blank">{LANG->PrintView}</a>
</div>

<div class="message">

    <div class="generic">

        <table border="0" cellspacing="0">
            <tr>
                <td width="100%">
                    <div class="message-author">
                        {IF MESSAGE->URL->PROFILE}<a href="{MESSAGE->URL->PROFILE}">{/IF}{MESSAGE->author}{IF MESSAGE->URL->PROFILE}</a>{/IF}
                        {IF MESSAGE->URL->PM}<small>[ <a href="{MESSAGE->URL->PM}">{LANG->PrivateReply}</a> ]</small>{/IF}
                    </div>
                    <div class="message-date">{MESSAGE->datestamp}</div>
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
                    &raquo; <a class="icon" href="{MESSAGE->URL->EDIT}">{LANG->EditPost}</a>
                {/IF}
            {/IF}
            &raquo; <a class="icon" href="{MESSAGE->URL->REPLY}">{LANG->Reply}</a>
            &raquo; <a class="icon" href="{MESSAGE->URL->QUOTE}">{LANG->QuoteMessage}</a>
            {IF MESSAGE->URL->REPORT}&raquo; <a class="icon" href="{MESSAGE->URL->REPORT}">{LANG->Report}</a>{/IF}
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
                    &raquo; <a class="icon" href="{MESSAGE->URL->DELETE_MESSAGE}">{LANG->DeleteMessage}</a>
                    &raquo; <a class="icon" href="{MESSAGE->URL->DELETE_THREAD}">{LANG->DelMessReplies}</a>
                    &raquo; <a class="icon" href="{MESSAGE->URL->SPLIT}">{LANG->SplitThread}</a>
                {/IF}
                {IF MESSAGE->is_unapproved}
                    &raquo; <a class="icon" href="{MESSAGE->URL->APPROVE}">{LANG->ApproveMessage}</a>
                {ELSE}
                    &raquo; <a class="icon" href="{MESSAGE->URL->HIDE}">{LANG->HideMessage}</a>
                {/IF}
                &raquo; <a class="icon" href="{MESSAGE->URL->EDIT}">{LANG->EditPost}</a>
            </div>
        {/IF}

    </div>

</div>

<div class="nav">
    {IF MODERATOR true}
        <div class="nav-right">
            &raquo; <a class="icon" href="{TOPIC->URL->MERGE}">{LANG->MergeThread}</a>
            {IF TOPIC->closed false}
                &raquo; <a class="icon" href="{TOPIC->URL->CLOSE}">{LANG->CloseThread}</a>
            {ELSE}
                &raquo; <a class="icon" href="{TOPIC->URL->REOPEN}">{LANG->ReopenThread}</a>
            {/IF}
            &raquo; <a class="icon" href="{TOPIC->URL->DELETE_THREAD}">{LANG->DeleteThread}</a>
            {IF TOPIC->URL->MOVE}&raquo; <a class="icon" href="{TOPIC->URL->MOVE}">{LANG->MoveThread}</a>{/IF}
        </div>
    {/IF}

    {IF URL->MARKTHREADREAD}
        &raquo; <a class="icon" href="{URL->MARKTHREADREAD}">{LANG->MarkThreadRead}</a>
    {/IF}
    {IF TOPIC->URL->FOLLOW}
        &raquo; <a class="icon" href="{TOPIC->URL->FOLLOW}">{LANG->FollowThread}</a>
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
    </tr>

    {LOOP MESSAGES}

    {! This is the current message }
    {IF MESSAGES->message_id MESSAGE->message_id}
        {VAR altclass "current"}
        {VAR title "&raquo;"}
    {ELSE}
        {VAR altclass ""}
        {VAR title "&bull;"}
    {/IF}

    {IF MESSAGES->new}
        {VAR newclass "new"}
    {ELSE}
        {VAR newclass ""}
    {/IF}

    <tr>
        <td width="65%" class="message-subject-threaded {altclass}">
            <h4 style="padding-left: {MESSAGES->indent_cnt}px;">
                {title}
                <a href="{MESSAGES->URL->READ}" class="{newclass}">{MESSAGES->subject}</a>
                {IF MESSAGES->new}<span class="new-indicator">{LANG->New}</span>{/IF}
                {IF MESSAGES->meta->attachments}<img src="{URL->TEMPLATE}/images/attach.png" width="16" height="16" border="0" title="{LANG->Attachments}"  alt="{LANG->Attachments}" /> {/IF}
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

