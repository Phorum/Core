<?php

    if(phorum_page=="read"){
        $PHORUM["DATA"]["POST"]["subject"]="";
    }

?>

{IF ERROR}
<h4>{ERROR}</h4>
{/IF}

<a id="REPLY"></a>
<form id="comment-form" action="{URL->ACTION}" method="post">
<input type="hidden" name="thread" value="{POST->thread}" />
<input type="hidden" name="parent_id" value="{POST->thread}" />
<input type="hidden" name="forum_id" value="{POST->forumid}" />
<input type="hidden" name="show_signature" value="1" />
{POST_VARS}

<table cellspacing="0" border="0">
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
</table>

<textarea name="body" rows="20" cols="50">{POST->body}</textarea><br />
<input name="preview" type="submit" value="{LANG->Preview}" />&nbsp;<input type="submit" value="{LANG->Post}" />

</form>
