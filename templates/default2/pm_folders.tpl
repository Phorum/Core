<form action="{URL->ACTION}" method="post">
    {POST_VARS}
    <input type="hidden" name="action" value="folders" />
    <input type="hidden" name="forum_id" value="{FORUM_ID}" />
    <div class="generic">
        <h4>{LANG->PMFolderCreate}</h4>
        <input type="text" name="create_folder_name" value="{CREATE_FOLDER_NAME}" size="20" maxlength="20" />
        <input type="submit" name="create_folder" value="{LANG->Submit}" class="PhorumSubmit" />
    </div>
</form>
{IF PM_USERFOLDERS}
    <form action="{URL->ACTION}" method="post">
        {POST_VARS}
        <input type="hidden" name="action" value="folders" />
        <input type="hidden" name="forum_id" value="{FORUM_ID}" />
        <div class="generic">
            <h4>{LANG->PMFolderRename}</h4>
            <select name="rename_folder_from" style="vertical-align: middle">
                <option value="">{LANG->PMSelectAFolder}</option>
                {LOOP PM_USERFOLDERS}
                    <option value="{PM_USERFOLDERS->id}">{PM_USERFOLDERS->name}</option>
                {/LOOP PM_USERFOLDERS}
            </select>
            {LANG->PMFolderRenameTo}
            <input type="text" name="rename_folder_to" value="{RENAME_FOLDER_NAME}" size="20" maxlength="20" />
            <input type="submit" name="rename_folder" value="{LANG->Submit}" class="PhorumSubmit" />
        </div>
    </form>

    <form action="{URL->ACTION}" method="post">
        {POST_VARS}
        <input type="hidden" name="action" value="folders" />
        <input type="hidden" name="forum_id" value="{FORUM_ID}" />
        <div class="generic">
            <h4>{LANG->PMFolderDelete}</h4>
            <p>{LANG->PMFolderDeleteExplain}</p>
            {LANG->PMFolderDelete}
            <select name="delete_folder_target" style="vertical-align: middle">
                <option value="">{LANG->PMSelectAFolder}</option>
                {LOOP PM_USERFOLDERS}
                    <option value="{PM_USERFOLDERS->id}">{PM_USERFOLDERS->name}{IF PM_USERFOLDERS->total} ({PM_USERFOLDERS->total}){/IF}</option>
                {/LOOP PM_USERFOLDERS}
            </select>
            <input type="submit" name="delete_folder" value="{LANG->Submit}" onclick="return confirm('{LANG->PMFolderDeleteConfirm}')" class="PhorumSubmit" />
        </div>
    </form>
{/IF}
