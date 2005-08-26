<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;"><span class="PhorumNavHeading">{LANG->GotoThread}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->NEWERTHREAD}">{LANG->PrevPage}</a>&bull;<a class="PhorumNavLink" href="{URL->OLDERTHREAD}">{LANG->NextPage}</a></div>
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->MARKTHREADREAD}">{LANG->MarkThreadRead}</a>{/IF}{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}
</div>

{if PAGES}
{include paging}
{/if}

{loop MESSAGES}
<a name="msg-{MESSAGES->message_id}"></a>
<div class="PhorumReadMessageBlock">
{IF MESSAGES->is_unapproved}
<div class="PhorumStdBlock">
<div class="PhorumReadBodyHead"><strong>{LANG->UnapprovedMessage}</strong></div>
</div>
{/IF}
<div class="PhorumStdBlock">
{IF MESSAGES->parent_id 0}
<div class="PhorumReadBodySubject">{MESSAGES->subject}</div>
{ELSE}
<div class="PhorumReadBodyHead"><strong>{MESSAGES->subject}</strong></div>
{/IF}
<div class="PhorumReadBodyHead">{LANG->Postedby}: <strong>{MESSAGES->linked_author}</strong> ({MESSAGES->ip})</div>
<div class="PhorumReadBodyHead">{LANG->Date}: {MESSAGES->datestamp}</div>
<br />
<div class="PhorumReadBodyText">{MESSAGES->body}</div><br />
{IF ATTACHMENTS}
{IF MESSAGES->attachments}
{LANG->Attachments}:
{ASSIGN MESSAGE_ATTACHMENTS MESSAGES->attachments}
{LOOP MESSAGE_ATTACHMENTS}
<a href="{MESSAGE_ATTACHMENTS->url}">{MESSAGE_ATTACHMENTS->name} ({MESSAGE_ATTACHMENTS->size})</a>&nbsp;&nbsp;
{/LOOP MESSAGE_ATTACHMENTS}
{/IF}
{/IF}
</div>

{if MODERATOR true}
<div class="PhorumReadNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Moderate}:</span>&nbsp;{if MESSAGES->threadstart true}
<a class="PhorumNavLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->delete_url2}';">{LANG->DeleteThread}</a>{IF MESSAGES->move_url}&bull;<a class="PhorumNavLink" href="{MESSAGES->move_url}">{LANG->MoveThread}</a>{/IF}&bull;<a class="PhorumNavLink" href="{MESSAGES->merge_url}">{LANG->MergeThread}</a>{if MESSAGES->closed false}&bull;<a class="PhorumNavLink" href="{MESSAGES->close_url}">{LANG->CloseThread}</a>{/if}{if MESSAGES->closed true}&bull;<a class="PhorumNavLink" href="{MESSAGES->reopen_url}">{LANG->ReopenThread}</a>{/if}
{/if}
{if MESSAGES->threadstart false}
<a class="PhorumNavLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->delete_url1}';">{LANG->DeleteMessage}</a>&bull;<a class="PhorumNavLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->delete_url2}';">{LANG->DelMessReplies}</a>
&bull;<a class="PhorumNavLink" href="{MESSAGES->split_url}">{LANG->SplitThread}</a>
{/if}
{if MESSAGES->is_unapproved}
&bull;<a class="PhorumNavLink" href="{MESSAGES->approve_url}">{LANG->ApproveMessage}</a>
{else}
&bull;<a class="PhorumNavLink" href="{MESSAGES->hide_url}">{LANG->HideMessage}</a>
{/if}
&bull;<a class="PhorumNavLink" href="{MESSAGES->edit_url}">{LANG->EditPost}</a>
</div>
{/if}

<div class="PhorumReadNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>&nbsp;<a class="PhorumNavLink" href="{MESSAGES->reply_url}">{LANG->Reply}</a>{IF MESSAGES->private_reply_url}&bull;<a class="PhorumNavLink" href="{MESSAGES->private_reply_url}">{LANG->PrivateReply}</a>{/IF}&bull;<a class="PhorumNavLink" href="{MESSAGES->quote_url}">{LANG->QuoteMessage}</a>&bull;{IF LOGGEDIN}<a class="PhorumNavLink" href="{MESSAGES->follow_url}">{LANG->FollowThread}</a>&bull;{/IF}<a class="PhorumNavLink" href="{MESSAGES->report_url}">{LANG->Report}</a>{if MESSAGES->edit 1}&bull;<a class="PhorumNavLink" href="{MESSAGES->edituser_url}">{LANG->EditPost}</a>{/if}
</div>

</div>
{/loop MESSAGES}

{if PAGES}
{include paging}
{/if}

<br /><br />
