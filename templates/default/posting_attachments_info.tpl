{IF ATTACHMENTS_FULL}
  <b>{LANG->AttachFull}</b>
{ELSE}
	<b>{LANG->AttachFiles}</b>

	<ul>
	{IF ATTACH_FILE_TYPES}
	    <li>{LANG->AttachFileTypes} {ATTACH_FILE_TYPES}</li>
	{/IF}
	<li>{LANG->AttachFileSize} {ATTACH_FILE_SIZE}</li>
	{IF NOT ATTACH_MAX_ATTACHMENTS 1}
	  {IF NOT ATTACH_TOTALFILE_SIZE 0}
	    {IF NOT ATTACH_TOTALFILE_SIZE ATTACH_FILE_SIZE}
	      <li>{LANG->AttachTotalFileSize} {ATTACH_TOTALFILE_SIZE}</li>
	     {/IF}
	  {/IF}
	  <li>{LANG->AttachMaxAttachments} {ATTACH_MAX_ATTACHMENTS}</li>
	{/IF}
	</ul>

	<input type="file" size="50" name="attachment" />
    <input type="submit" name="attach" value="{LANG->Attach}" />
{/IF}