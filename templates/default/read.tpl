{IF ReportPost}
<div class="PhorumUserError">{ReportPostMessage}</div>
{/IF}

<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;"><span class="PhorumNavHeading">{LANG->GotoThread}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->NEWERTHREAD}">{LANG->PrevPage}</a>&bull;<a class="PhorumNavLink" href="{URL->OLDERTHREAD}">{LANG->NextPage}</a></div>
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}
</div>

{if PAGES}
<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;">
<span class="PhorumNavHeading">{LANG->Pages}:</span>&nbsp;
{if URL->PREVPAGE}<a class="PhorumNavLink" href="{URL->PREVPAGE}">{LANG->PrevPage}</a>{/if}
{if URL->FIRSTPAGE}<a class="PhorumNavLink" href="{URL->FIRSTPAGE}">{LANG->FirstPage}...</a>{/if}
{loop PAGES}<a class="PhorumNavLink" href="{PAGES->url}">{PAGES->pageno}</a>{/loop PAGES}
{if URL->LASTPAGE}<a class="PhorumNavLink" href="{URL->LASTPAGE}">...{LANG->LastPage}</a>{/if}
{if URL->NEXTPAGE}<a class="PhorumNavLink" href="{URL->NEXTPAGE}">{LANG->NextPage}</a>{/if}
</div>
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->CurrentPage}:</span>{CURRENTPAGE} {LANG->of} {TOTALPAGES}
</div>
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
<a class="PhorumNavLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->delete_url2}';">{LANG->DeleteThread}</a>&bull;<a class="PhorumNavLink" href="{MESSAGES->move_url}">{LANG->MoveThread}</a>{if MESSAGES->closed false}&bull;<a class="PhorumNavLink" href="{MESSAGES->close_url}">{LANG->CloseThread}</a>{/if}{if MESSAGES->closed true}&bull;<a class="PhorumNavLink" href="{MESSAGES->reopen_url}">{LANG->ReopenThread}</a>{/if}
{/if}
{if MESSAGES->threadstart false}
<a class="PhorumNavLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->delete_url1}';">{LANG->DeleteMessage}</a>&bull;<a class="PhorumNavLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->delete_url2}';">{LANG->DelMessReplies}</a>
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
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>&nbsp;<a class="PhorumNavLink" href="{MESSAGES->reply_url}">{LANG->Reply}</a>&bull;<a class="PhorumNavLink" href="{MESSAGES->quote_url}">{LANG->QuoteMessage}</a>&bull;{IF LOGGEDIN}<a class="PhorumNavLink" href="{MESSAGES->follow_url}">{LANG->FollowThread}</a>&bull;{/IF}<a class="PhorumNavLink" href="javascript:if(window.confirm('{LANG->ConfirmReportMessage}')) window.location='{MESSAGES->report_url}';">{LANG->Report}</a>{if MESSAGES->edit 1}&bull;<a class="PhorumNavLink" href="{MESSAGES->edituser_url}">{LANG->EditPost}</a>{/if}
</div>

</div>
{/loop MESSAGES}

{if PAGES}
<div class="PhorumNavBlock" style="text-align: left;">
<div style="float: right;">
<span class="PhorumNavHeading">{LANG->Pages}:</span>&nbsp;
{if URL->PREVPAGE}<a class="PhorumNavLink" href="{URL->PREVPAGE}">{LANG->PrevPage}</a>{/if}
{if URL->FIRSTPAGE}<a class="PhorumNavLink" href="{URL->FIRSTPAGE}">{LANG->FirstPage}...</a>{/if}
{loop PAGES}<a class="PhorumNavLink" href="{PAGES->url}">{PAGES->pageno}</a>{/loop PAGES}
{if URL->LASTPAGE}<a class="PhorumNavLink" href="{URL->LASTPAGE}">...{LANG->LastPage}</a>{/if}
{if URL->NEXTPAGE}<a class="PhorumNavLink" href="{URL->NEXTPAGE}">{LANG->NextPage}</a>{/if}
</div>
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->CurrentPage}:</span>{CURRENTPAGE} {LANG->of} {TOTALPAGES}
</div>
{/if}
<br /><br />
