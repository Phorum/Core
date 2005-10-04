{IF ERROR}
  <div class="PhorumUserError">{ERROR}</div>
{/IF}

{IF OKMSG}
  <div class="PhorumOkMsg">{OKMSG}</div>
{/IF}

<form action="{ACTION}" method="post">
{POST_VARS}
<input type="hidden" name="panel" value="pm" />
<input type="hidden" name="action" value="folders" />
<input type="hidden" name="forum_id" value="{FORUM_ID}" />

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->PMFolderCreate}</div>
<div class="PhorumStdBlock" style="width:99%; padding-top: 15px; padding-bottom: 15px">
<input type="text" name="create_folder_name" value="{CREATE_FOLDER_NAME}" size="20" maxlength="20"/>
<input type="submit" name="create_folder" value="{LANG->Submit}"/>
</div>

{IF PM_USERFOLDERS}

    <br/>

    <div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->PMFolderRename}</div>
    <div class="PhorumStdBlock" style="width:99%; padding-top: 15px; padding-bottom: 15px">
    {LANG->PMFolderRename}
    <select name="rename_folder_from">
    <option value=""> {LANG->PMSelectAFolder}
    {LOOP PM_USERFOLDERS}
        <option value="{PM_USERFOLDERS->id}"> {PM_USERFOLDERS->name}
    {/LOOP PM_USERFOLDERS}
    </select>
    {LANG->PMFolderRenameTo}
    <input type="text" name="rename_folder_to" value="{RENAME_FOLDER_NAME}" size="20" maxlength="20"/>
    <input type="submit" name="rename_folder" value="{LANG->Submit}"/>
    </div>

    <br/>

    <div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->PMFolderDelete}</div>
    <div class="PhorumStdBlock" style="width:99%; padding-top: 15px; padding-bottom: 15px">
    {LANG->PMFolderDeleteExplain}<br/><br/>
    {LANG->PMFolderDelete}
    <select name="delete_folder_target">
    <option value=""> {LANG->PMSelectAFolder}
    {LOOP PM_USERFOLDERS}
        <option value="{PM_USERFOLDERS->id}"> {PM_USERFOLDERS->name}{IF PM_USERFOLDERS->total} ({PM_USERFOLDERS->total}){/IF}
    {/LOOP PM_USERFOLDERS}
    </select>
    <input type="submit" name="delete_folder" value="{LANG->Submit}"
     onclick="return confirm('{LANG->PMFolderDeleteConfirm}')"/>
    </div>

{/IF}

</form>


