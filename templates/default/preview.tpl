<div class="PhorumNavBlock" style="text-align: left;">

<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>

</div>

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->Preview}</div>
<div class="PhorumReadMessageBlock">
<div class="PhorumStdBlock">
<div class="PhorumReadBodySubject">{PREVIEW->subject}</div>
<div class="PhorumReadBodyHead">{LANG->Postedby}: <strong>{PREVIEW->author}</strong> ({PREVIEW->ip})</div>
<br />
<div class="PhorumReadBodyText">{PREVIEW->body}</div><br />
{IF PREVIEW->attachments}
{LANG->Attachments}: 
{ASSIGN MESSAGE_ATTACHMENTS PREVIEW->attachments}
{LOOP MESSAGE_ATTACHMENTS}
<a href="{MESSAGE_ATTACHMENTS->url}">{MESSAGE_ATTACHMENTS->name} ({MESSAGE_ATTACHMENTS->size})</a>&nbsp;&nbsp;
{/LOOP MESSAGE_ATTACHMENTS}
{/IF}
</div>
</div>
{IF PREVIEW->edit_url}
<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>&nbsp;<a class="PhorumNavLink" href="{PREVIEW->edit_url}">{LANG->EditPost}</a></div>
{/IF}
<br /><br />