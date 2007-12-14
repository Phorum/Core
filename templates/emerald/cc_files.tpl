<!-- BEGIN TEMPLATE cc_files.tpl -->
{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

<form action="{URL->ACTION}" method="post" enctype="multipart/form-data">
    {POST_VARS}
    <div class="generic">
        <h4>{LANG->UploadFile}</h4>
        {IF FILE_SIZE_LIMIT}<div>{FILE_SIZE_LIMIT}</div>{/IF}
        {IF FILE_TYPE_LIMIT}<div>{FILE_TYPE_LIMIT}</div>{/IF}
        {IF FILE_QUOTA_LIMIT}<div>{FILE_QUOTA_LIMIT}</div>{/IF}
        <br />
        <input type="file" name="newfile" size="30" /><br /><br />
        <input type="submit" value="{LANG->Submit}" />
    </div>
</form>

<form action="{URL->ACTION}" method="post">
    {POST_VARS}
    <table cellspacing="0" class="list">
        <tr>
            <th align="left">{LANG->Delete}</th>
            <th align="left">{LANG->Filename}</th>
            <th align="left">{LANG->Filesize}</th>
            <th align="left" nowrap="nowrap">{LANG->DateAdded}</th>
        </tr>
        {LOOP FILES}
            <tr>
                <td width="5%"><input type="checkbox" name="delete[]" value="{FILES->file_id}" /></td>
                <td width="35%"><a href="{FILES->url}">{FILES->filename}</a></td>
                <td width="20%">{FILES->filesize}</td>
                <td width="40%">{FILES->dateadded}</td>
            </tr>
        {/LOOP FILES}

        <tr>
            <td colspan="4" align="right" class="alt">
                <small>
                    {LANG->TotalFiles}: {TOTAL_FILES}&nbsp;&nbsp;{LANG->TotalFileSize}: {TOTAL_FILE_SIZE}
                </small>
            </td>
        </tr>


    </table>
    <input type="submit" value="{LANG->Delete}" />
</form>
<!-- END TEMPLATE cc_files.tpl -->
