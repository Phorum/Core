{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}
{IF EDIT->edit_allowed}
<div align="center">
<form action="{URL->ACTION}" method="post" style="display: inline;">
{POST_VARS}
<input type="hidden" name="mod_step" value="{EDIT->mod_step}" />
<input type="hidden" name="message_id" value="{EDIT->message_id}" />

<input type="hidden" name="thread" value="{EDIT->thread}" />
<input type="hidden" name="forum_id" value="{EDIT->forum_id}" />
<input type="hidden" name="parent_id" value="{EDIT->parent_id}" />
<input type="hidden" name="user_id" value="{EDIT->user_id}" />

<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>
</div>

<div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">{LANG->EditPost}</div>

<div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;">
<table cellspacing="0" border="0">
{IF EDIT->useredit}
<tr>
    <td>{LANG->YourName}:&nbsp;</td>
    <td>{EDIT->author}</td>
</tr>
{ELSEIF EDIT->moderator_useredit}
<tr>
    <td>{LANG->Author}:&nbsp;</td>
    <td><input type="text" name="author" size="30" value="{EDIT->author}" /></td>
</tr>
{ELSE}
<tr>
    <td>{LANG->Author}:&nbsp;</td>
    <td><input type="text" name="author" size="30" value="{EDIT->author}" /></td>
</tr>
<tr>
    <td>{LANG->Email}:&nbsp;</td>
    <td><input type="text" name="email" size="30" value="{EDIT->email}" /></td>
</tr>
{/IF}

<tr>
    <td>{LANG->Subject}:&nbsp;</td>
    <td><input type="text" name="subject" size="50" value="{EDIT->subject}" /></td>
</tr>
{IF EDIT->parent_id 0}
{IF EDIT->useredit 0}
<tr>
    <td>{LANG->Special}:&nbsp;</td>
    <td><select name="special"><option value=""></option><option value="sticky"{if EDIT->special PHORUM_SORT_STICKY} selected{/if}>{LANG->MakeSticky}</option>{IF EDIT->show_announcement true}<option value="announcement"{if EDIT->special PHORUM_SORT_ANNOUNCEMENT} selected{/if}>{LANG->MakeAnnouncement}</option>{/if}</select></td>
</tr>
{/IF}
{/IF}
<tr>
    <td colspan="2"><input type="checkbox" name="email_reply" value="1" {if EDIT->emailreply true} checked{/if} /> {LANG->EmailReplies}</td>
</tr>
<tr>
    <td colspan="2"><input type="checkbox" name="show_signature" value="1" {if EDIT->meta->show_signature} checked{/if} /> {LANG->AddSig}</td>
</tr>
{IF EDIT->attachments}
<tr>
    <td colspan="2">{LANG->Attachments} ({LANG->CheckToDelete}):<br />{LOOP EDIT->attachments}<input type="checkbox" name="attachments[]" value="{EDIT->attachments->file_id}" /> {EDIT->attachments->file_name}<br />{/LOOP EDIT->attachments}</td>
</tr>
{/IF}
</table>
</div>

<div class="PhorumStdBlock PhorumNarrowBlock">
<textarea name="body" rows="20" cols="50" style="width: 100%;">{EDIT->body}</textarea><br />
{if MODERATED}
{LANG->ModeratedForum}<br />
{/if}
<div style="margin-top: 3px;" align="right"><input type="submit" class="PhorumSubmit" value=" {LANG->Update} " /></div>

</form>
</div>
{/IF}