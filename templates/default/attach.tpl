{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->AttachFiles}</div>

<div class="PhorumReadMessageBlock">
<div class="PhorumStdBlock">
<div class="PhorumReadBodyHead">
{IF INPUTS}
<ul>
<li>{LANG->AttachFileTypes} {ATTACH_FILE_TYPES}</li>
<li>{LANG->AttachFileSize} {ATTACH_FILE_SIZE}</li>
<li>{LANG->AttachInstuctions}</li>
</ul>
<form action="{URL->ACTION}" method="post" style="display: inline;" enctype="multipart/form-data">
<input type="hidden" name="message_id" value="{MESSAGE->message_id}" />
<input type="hidden" name="forum_id" value="{MESSAGE->forum_id}" />
{LOOP INPUTS}
&nbsp;&nbsp;<input type="file" name="attachment{INPUTS->number}" size="50" /><br /<br />
{/LOOP INPUTS}
&nbsp;&nbsp;<input name="cancel" class="PhorumSubmit" type="submit" value=" {LANG->Cancel} " />&nbsp;&nbsp;<input name="attach" class="PhorumSubmit" type="submit" value=" {LANG->Attach} " />&nbsp;&nbsp;
</form>
{ELSE}
{LANG->AttachFull}<br /><br />
{/IF}
<form action="{URL->ACTION}" method="post" style="display: inline;" enctype="multipart/form-data">
<input type="hidden" name="message_id" value="{MESSAGE->message_id}" />
<input type="hidden" name="forum_id" value="{MESSAGE->forum_id}" />
<input name="finalize" class="PhorumSubmit" type="submit" value=" {LANG->Post} " />
</div>
</div>
</div>
