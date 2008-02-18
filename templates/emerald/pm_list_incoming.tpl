<!-- BEGIN TEMPLATE pm_list_incoming.tpl -->
{IF MESSAGECOUNT}
    <table border="0" cellspacing="0" class="list">
        <tr>
            <th align="left" width="20">
                <script type="text/javascript">
                    function checkAll() {
                        var lf=document.getElementById('phorum-pm-list');
                        for (var i=0;i<lf.elements.length;i++) {
                            var elt=lf.elements[i];
                            if (elt.type=='checkbox' && elt.name!='toggle') {
                                elt.checked = document.getElementById('toggle').checked;
                            }
                        }
                    }
                    document.write ( '<input type="checkbox" name="toggle" id="toggle" onclick="checkAll()" />' );
                </script>
                <noscript>&nbsp;</noscript>
            </th>
            <th align="left">{LANG->Subject}</th>
            <th align="left" nowrap="nowrap">{LANG->From}&nbsp;</th>
            <th align="left" nowrap="nowrap">{LANG->Date}&nbsp;</th>
        </tr>
        {LOOP MESSAGES}
            <tr>
                <td><input type="checkbox" name="checked[]" value="{MESSAGES->pm_message_id}" /></td>
                <td>
                    <a href="{MESSAGES->URL->READ}">{MESSAGES->subject}</a>
                    {IF NOT MESSAGES->read_flag}
                        <img src="{URL->TEMPLATE}/images/flag_red.png" class="icon1616" alt="NEW!" />
                    {/IF}
                </td>
                <td nowrap="nowrap"><a href="{MESSAGES->URL->PROFILE}">{MESSAGES->author}</a>&nbsp;</td>
                <td width="10%" nowrap="nowrap">{MESSAGES->date}</td>
            </tr>
        {/LOOP MESSAGES}
    </table>
    {IF PM_USERFOLDERS}
        <select name="target_folder" style="vertical-align: middle;">
            <option value=""> {LANG->PMSelectAFolder}</option>
            {LOOP PM_FOLDERS}
                {IF NOT PM_FOLDERS->id FOLDER_ID}
                    {IF NOT PM_FOLDERS->is_outgoing}
                        <option value="{PM_FOLDERS->id}"> {PM_FOLDERS->name}</option>
                    {/IF}
                {/IF}
            {/LOOP PM_FOLDERS}
        </select>
        <input type="submit" name="move" value="{LANG->PMMoveToFolder}" />
    {/IF}
    <input type="submit" name="delete" value="{LANG->Delete}" onclick="return confirm('<?php echo addslashes($PHORUM['DATA']['LANG']['AreYouSure'])?>')" />

{ELSE}

    <div class="generic">{LANG->PMFolderIsEmpty}</div>

{/IF}
<!-- END TEMPLATE pm_list_incoming.tpl -->
