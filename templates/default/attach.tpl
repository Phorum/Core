{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>
</div>

<form action="{URL->ACTION}" method="post" style="display: inline;" enctype="multipart/form-data">
<input type="hidden" name="message_id" value="{MESSAGE->message_id}" />
<input type="hidden" name="forum_id" value="{MESSAGE->forum_id}" />

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->AttachFiles}</div>

<div class="PhorumReadMessageBlock">
<div class="PhorumStdBlock">
<div class="PhorumReadBodyHead"><strong>{LANG->Subject}:</strong> {MESSAGE->subject}</div>
<blockquote>
&bull; {LANG->AttachFileTypes} {ATTACH_FILE_TYPES}<br />
&bull; {LANG->AttachFileSize} {ATTACH_FILE_SIZE}
</blockquote>
{LOOP INPUTS}
&nbsp;&nbsp;<input type="file" name="attachment{INPUTS->number}" size="50" /><br /<br />
{/LOOP INPUTS}
&nbsp;&nbsp;<input name="cancel" class="PhorumSubmit" type="submit" value=" {LANG->Cancel} " />&nbsp;&nbsp;<input name="attach" class="PhorumSubmit" type="submit" value=" {LANG->Attach} " />
</div>
</form>
<br /><br />