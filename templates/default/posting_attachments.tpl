<div class="PhorumStdBlockHeader PhorumNarrowBlock">

{IF POST->attachments}
  {include posting_attachments_list}

  {ASSIGN AttachPhrase LANG->AttachAnotherFile}

{ELSE}
  {ASSIGN AttachPhrase LANG->AttachAFile}
{/IF}

{IF ATTACHMENTS_FULL}
  <b>{LANG->AttachFull}</b>
{ELSE}
<script>
function phorumShowAttachForm() {
    document.getElementById('phorum-attach-link').style.display='none';
    document.getElementById('phorum-attach-form').style.display='block';
}
document.write("<div id=\"phorum-attach-link\" style=\"display: block;\"><a href=\"javascript:phorumShowAttachForm();\"><b>{AttachPhrase} ...</b></a></div>\n");
document.write("<div id=\"phorum-attach-form\" style=\"display: none;\">");
</script>

    <b>{AttachPhrase}</b>

    <ul>
    {IF ATTACH_FILE_TYPES}
        <li>{LANG->AttachFileTypes} {ATTACH_FILE_TYPES}</li>
    {/IF}
    {IF NOT ATTACH_FILE_SIZE 0}
        <li>{LANG->AttachFileSize} {ATTACH_FORMATTED_FILE_SIZE}</li>
    {/IF}
    {IF NOT ATTACH_MAX_ATTACHMENTS 1}
      {IF NOT ATTACH_TOTALFILE_SIZE 0}
        {IF NOT ATTACH_TOTALFILE_SIZE ATTACH_FILE_SIZE}
          <li>{LANG->AttachTotalFileSize} {ATTACH_FORMATTED_TOTALFILE_SIZE}</li>
         {/IF}
      {/IF}
      <li>{ATTACH_REMAINING_ATTACHMENTS} {LANG->AttachMaxAttachments}</li>
    {/IF}
    </ul>

    <input type="file" size="50" name="attachment" />
    <input type="submit" name="attach" value="{LANG->Attach}" />
<script>document.write('</div>');</script>
{/IF}

</div>