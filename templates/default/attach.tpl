{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->AttachFiles}</div>

<div class="PhorumReadMessageBlock">
<div class="PhorumStdBlock">
<div class="PhorumReadBodyHead">
{IF INPUTS}
<ul>
{IF ATTACH_FILE_TYPES}
  <li>{LANG->AttachFileTypes} {ATTACH_FILE_TYPES}</li>
{/IF}
<li>{LANG->AttachFileSize} {ATTACH_FILE_SIZE}</li>
{IF NOT ATTACH_MAX_ATTACHMENTS 1}
 {IF NOT ATTACH_TOTALFILE_SIZE 0}
  <li>{LANG->AttachTotalFileSize} {ATTACH_TOTALFILE_SIZE}</li>
 {/IF}
{/IF}
<li>{LANG->AttachInstructions}</li>
</ul>
<form action="{URL->ACTION}" method="post" style="display: inline;" enctype="multipart/form-data">
<input type="hidden" name="message_id" value="{MESSAGE->message_id}" />
<input type="hidden" name="forum_id" value="{MESSAGE->forum_id}" />
{LOOP INPUTS}
&nbsp;&nbsp;<input type="file" name="attachment{INPUTS->number}" size="50" /><br /><br />
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
