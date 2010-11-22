{IF NOT PRINTVIEW}
  <div class="PhorumNavBlock" style="text-align: left;">
    <div style="float: right;">
      <span class="PhorumNavHeading">{LANG->GotoThread}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->NEWERTHREAD}">{LANG->PrevPage}</a>&bull;<a class="PhorumNavLink" href="{URL->OLDERTHREAD}">{LANG->NextPage}</a>
    </div>
    <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->LIST}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{IF LOGGEDIN true}<a class="PhorumNavLink" href="{URL->MARKTHREADREAD}">{LANG->MarkThreadRead}</a>&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{ELSE}<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->PRINTVIEW}" target="_blank">{LANG->PrintView}</a>
  </div>
  {IF PAGES}
    {INCLUDE "paging"}
  {/IF}
{/IF}
{LOOP MESSAGES}
  {IF NOT MESSAGES->parent_id 0}
    <a name="msg-{MESSAGES->message_id}"></a>
  {/IF}
  <div class="PhorumReadMessageBlock">
    {IF MESSAGES->is_unapproved}
      <div class="PhorumStdBlock">
        <div class="PhorumReadBodyHead"><strong>{LANG->UnapprovedMessage}</strong></div>
      </div>
    {/IF}
    <div class="PhorumStdBlock">
      {IF MESSAGES->parent_id 0}
        <div class="PhorumReadBodySubject">{MESSAGES->subject} <span class="PhorumNewFlag">{MESSAGES->new}</span></div>
      {ELSE}
        <div class="PhorumReadBodyHead"><strong>{MESSAGES->subject}</strong> <span class="PhorumNewFlag">{MESSAGES->new}</span></div>
      {/IF}
      <div class="PhorumReadBodyHead">{LANG->Postedby}:
        <strong>
          {IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}
            {MESSAGES->author}
          {IF MESSAGES->URL->PROFILE}</a>{/IF}
        </strong> ({MESSAGES->ip})</div>
      <div class="PhorumReadBodyHead">{LANG->Date}: {MESSAGES->datestamp}</div><br />
      <div class="PhorumReadBodyText">{MESSAGES->body}</div><br />
      {IF MESSAGES->attachments}
        {LANG->Attachments}:
        {LOOP MESSAGES->attachments}
          <a href="{MESSAGES->attachments->url}">{MESSAGES->attachments->name} ({MESSAGES->attachments->size})</a>&nbsp;&nbsp;
        {/LOOP MESSAGES->attachments}
      {/IF}
    </div>
    {IF NOT PRINTVIEW}
      {IF MODERATOR true}
        <div class="PhorumReadNavBlock" style="text-align: left;">
          <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Moderate}:</span>&nbsp;{IF MESSAGES->threadstart true}<a class="PhorumNavLink" href="{MESSAGES->URL->DELETE_THREAD}">{LANG->DeleteThread}</a>&bull;{IF MESSAGES->URL->MOVE}<a class="PhorumNavLink" href="{MESSAGES->URL->MOVE}">{LANG->MoveThread}</a>&bull;{/IF}<a class="PhorumNavLink" href="{TOPIC->URL->MERGE}">{LANG->MergeThread}</a>&bull;{IF MESSAGES->closed false}<a class="PhorumNavLink" href="{TOPIC->URL->CLOSE}">{LANG->CloseThread}</a>{ELSE}<a class="PhorumNavLink" href="{TOPIC->URL->REOPEN}">{LANG->ReopenThread}</a>{/IF}{ELSE}<a class="PhorumNavLink" href="{MESSAGES->URL->DELETE_MESSAGE}">{LANG->DeleteMessage}</a>&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->DELETE_THREAD}">{LANG->DelMessReplies}</a>&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->SPLIT}">{LANG->SplitThread}</a>{/IF}{IF MESSAGES->is_unapproved}&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->APPROVE}">{LANG->ApproveMessage}</a>{ELSE}&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->HIDE}">{LANG->HideMessage}</a>{/IF}&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->EDIT}">{LANG->EditPost}</a>
        </div>
      {/IF}
      <div class="PhorumReadNavBlock" style="text-align: left;">
        <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>&nbsp;<a class="PhorumNavLink" href="{MESSAGES->URL->REPLY}" rel="nofollow">{LANG->Reply}</a>&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->QUOTE}" rel="nofollow">{LANG->QuoteMessage}</a>{IF LOGGEDIN}{IF MESSAGES->URL->PM}&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->PM}">{LANG->PrivateReply}</a>{/IF}&bull;<a class="PhorumNavLink" href="{TOPIC->URL->FOLLOW}">{LANG->FollowThread}</a>{IF MESSAGES->URL->REPORT}&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->REPORT}">{LANG->Report}</a>{/IF}{/IF}{IF MESSAGES->edit 1}&bull;<a class="PhorumNavLink" href="{MESSAGES->URL->EDIT}">{LANG->EditPost}</a>{/IF}
      </div>
    {/IF}
  </div>
{/LOOP MESSAGES}
{IF NOT PRINTVIEW}
  {IF PAGES}
    {INCLUDE "paging"}
  {/IF}
  <br /><br />
{/IF}

{IF REPLY_ON_READ}
  <a name="REPLY"></a>
{/IF}

