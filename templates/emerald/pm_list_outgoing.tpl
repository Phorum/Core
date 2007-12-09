<!-- BEGIN TEMPLATE pm_list_outgoing.tpl -->
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
            <th align="left" nowrap="nowrap">{LANG->To}&nbsp;</th>
            <th align="left" nowrap="nowrap">{LANG->PMRead}&nbsp;</th>
            <th align="left" nowrap="nowrap">{LANG->Date}&nbsp;</th>
        </tr>
        {LOOP MESSAGES}
            <tr>
                <td width="5%"><input type="checkbox" name="checked[]" value="{MESSAGES->pm_message_id}" /></td>
                <td width="60%"><a href="{MESSAGES->URL->READ}">{MESSAGES->subject}</a></td>
                <td width="15%" nowrap="nowrap">
                    {IF MESSAGES->recipient_count 1}
                        {LOOP MESSAGES->recipients}
                            <a href="{MESSAGES->recipients->URL->PROFILE}">{MESSAGES->recipients->display_name}</a>&nbsp;
                        {/LOOP MESSAGES->recipients}
                    {ELSE}
                        {MESSAGES->recipient_count}&nbsp;{LANG->Recipients}&nbsp;
                    {/IF}
                </td>
                <td width="10%" nowrap="nowrap">
                    {IF MESSAGES->recipient_count 1}
                        {LOOP MESSAGES->recipients}
                            {IF MESSAGES->recipients->read_flag}
                                {LANG->Yes}
                            {ELSE}
                                {LANG->No}
                            {/IF}
                        {/LOOP MESSAGES->recipients}
                    {ELSE}
                        {IF MESSAGES->receive_count MESSAGES->recipient_count}
                            {LANG->Yes}
                        {ELSE}
                            {MESSAGES->receive_count}&nbsp;{LANG->of}&nbsp;{MESSAGES->recipient_count}
                        {/IF}
                    {/IF}
                </td>
                <td width="10%" nowrap="nowrap">{MESSAGES->date}</td>
            </tr>
        {/LOOP MESSAGES}
    </table>
    <input type="submit" name="delete" value="{LANG->Delete}" onclick="return confirm('<?php echo addslashes($PHORUM['DATA']['LANG']['AreYouSure'])?>')" />
{ELSE}
    <div class="generic">{LANG->PMFolderIsEmpty}</div>
{/IF}
<!-- END TEMPLATE pm_list_outgoing.tpl -->
