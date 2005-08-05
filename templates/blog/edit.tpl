{IF ERROR}
<h4>{ERROR}</h4>
{/IF}
{IF EDIT->edit_allowed}

<h2>Edit</h2>

<form id="edit-form" action="{URL->ACTION}" method="POST">
{POST_VARS}
<input type="hidden" name="mod_step" value="{EDIT->mod_step}" />
<input type="hidden" name="message_id" value="{EDIT->message_id}" />
<input type="hidden" name="thread" value="{EDIT->thread}" />
<input type="hidden" name="forum_id" value="{EDIT->forum_id}" />
<input type="hidden" name="parent_id" value="{EDIT->parent_id}" />
<input type="hidden" name="user_id" value="{EDIT->user_id}" />
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
</table>

<textarea name="body" rows="20" cols="50">{EDIT->body}</textarea><br />
<input type="submit" value="{LANG->Update}" />
</form>

{/IF}
