
{IF PREVIEW}
<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->Preview}</div>
<div class="PhorumStdBlock" style="text-align: left;">
<div class="PhorumReadBodySubject">{PREVIEW->subject}</div>
<div class="PhorumReadBodyHead">{LANG->From}: <strong><a href="{PREVIEW->from_profile_url}">{PREVIEW->from_username}</a></strong></div>
<div class="PhorumReadBodyHead">{LANG->To}: <strong><a href="{PREVIEW->to_profile_url}">{PREVIEW->to}</a></strong></div>
<br />
<div class="PhorumReadBodyText">{PREVIEW->message}</div><br />
</div>
<br/>
{/IF}

{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<form action="{ACTION}" method="post">
{POST_VARS}
<input type="hidden" name="panel" value="pm" />
<input type="hidden" name="action" value="post" />
<input type="hidden" name="forum_id" value="{FORUM_ID}" />

<div class="PhorumStdBlockHeader" style="text-align: left; width:99%">
<table class="PhorumFormTable" cellspacing="0" border="0">
<tr>
    <td>{LANG->From}:&nbsp;</td>
    <td>{MESSAGE->from_username}</td>
</tr>
<tr>
    <td>{LANG->To}:&nbsp;</td>
    {IF USERS}
    <td><select name="to" size="1">
    {LOOP USERS}
    <option value="{USERS->username}">{USERS->displayname}</option>
    {/LOOP USERS}
    </select></td>
    {ELSE}
    <td><input type="text" name="to" size="30" value="{MESSAGE->to}" /></td>
    {/IF}
</tr>
<tr>
    <td>{LANG->Subject}:&nbsp;</td>
    <td><input type="text" name="subject" size="50" value="{MESSAGE->subject}" /></td>
</tr>
<tr>
    <td colspan="2"><input type="checkbox" name="keep" value="1"{IF MESSAGE->keep} checked="checked" {/IF} /> {LANG->KeepCopy}</td>
</tr>
</table>
</div>

<div class="PhorumStdBlock" style="width:99%">

<textarea name="message" rows="20" cols="50" style="width: 99%">{MESSAGE->message}</textarea>

<div style="margin-top: 3px;" align="right">
    <input name="preview" type="submit" class="PhorumSubmit" value=" {LANG->Preview} " />
    <input type="submit" class="PhorumSubmit" value=" {LANG->Post} " />
</div>

</div>

