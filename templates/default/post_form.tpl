{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<div align="center">
<a id="REPLY"></a>
<form action="{URL->ACTION}" method="post" style="display: inline;">
<input type="hidden" name="thread" value="{POST->thread}" />
<input type="hidden" name="parent_id" value="{POST->parentid}" />
<input type="hidden" name="forum_id" value="{POST->forumid}" />
{POST_VARS}
<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/IF}{IF LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}
</div>

<div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;">
<table class="PhorumFormTable" cellspacing="0" border="0">
{IF LOGGEDIN true}
<tr>
    <td>{LANG->YourName}:&nbsp;</td>
    <td>{POST->username}</td>
</tr>
{/IF}
{IF LOGGEDIN false}
<tr>
    <td>{LANG->YourName}:&nbsp;</td>
    <td><input type="text" name="author" size="30" value="{POST->author}" /></td>
</tr>
<tr>
    <td>{LANG->YourEmail}:&nbsp;</td>
    <td><input type="text" name="email" size="30" value="{POST->email}" /></td>
</tr>
{/IF}
<tr>
    <td>{LANG->Subject}:&nbsp;</td>
    <td><input type="text" name="subject" size="50" value="{POST->subject}" /></td>
</tr>
{IF POST->parentid 0}
{IF POST->show_special true}
<tr>
    <td>{LANG->Special}:&nbsp;</td>
    <td><select name="special">
    <option value=""{IF POST->special  } selected{/IF}></option>
    <option value="sticky"{IF POST->special sticky} selected{/IF}>{LANG->MakeSticky}</option>
    {IF POST->show_announcement true}<option value="announcement"{IF POST->special announcement} selected{/IF}>{LANG->MakeAnnouncement}</option>{/IF}
    </select></td>
</tr>
{/IF}
{/IF}
{IF LOGGEDIN true}
<tr>
    <td colspan="2"><input type="checkbox" name="email_reply" value="1"{IF POST->email_reply} checked="checked" {/IF} /> {LANG->EmailReplies}</td>
</tr>
<tr>
    <td colspan="2"><input type="checkbox" name="show_signature" value="1"{IF POST->show_signature} checked="checked" {/IF} /> {LANG->AddSig}</td>
</tr>
{/IF}
</table>
</div>

<div class="PhorumStdBlock PhorumNarrowBlock">
<textarea name="body" rows="20" cols="50" style="width: 100%;">{POST->body}</textarea><br />
{IF MODERATED}
{LANG->ModeratedForum}<br />
{/IF}
<div style="margin-top: 3px;" align="right">{IF ATTACHMENTS}<input name="attach" class="PhorumSubmit" type="submit" value=" {LANG->Attach} " />&nbsp;{/IF}<input name="preview" type="submit" class="PhorumSubmit" value=" {LANG->Preview} " />&nbsp;<input type="submit" class="PhorumSubmit" value=" {LANG->Post} " /></div>

</div>

</form>
</div>
