{IF PM_USERFOLDERS}
 &nbsp;&nbsp;&nbsp;
 <span style="white-space: nowrap">
 <select name="target_folder" style="vertical-align: middle;">
 <option value=""> {LANG->PMSelectAFolder}
 {LOOP PM_FOLDERS}
   {IF NOT PM_FOLDERS->id FOLDER_ID}
    {IF NOT PM_FOLDERS->is_outgoing}
     <option value="{PM_FOLDERS->id}"> {PM_FOLDERS->name}
     {/IF}
   {/IF}
 {/LOOP PM_FOLDERS}
 </select><input type="submit" name="{MOVE_SUBMIT_NAME}" class="PhorumSubmit" value="{LANG->PMMoveToFolder}" />
 </span>
{/IF}