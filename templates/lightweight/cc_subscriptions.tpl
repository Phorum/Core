<form action="{URL->ACTION}" method="POST">
    {POST_VARS}
    <div class="generic">
        {LANG->Activity}&nbsp;
        <select name="subdays">
            <option value="1"{IF SELECTED 1} selected="selected"{/IF}>1 {LANG->Day}</option>
            <option value="2"{IF SELECTED 2} selected="selected"{/IF}>2 {LANG->Days}</option>
            <option value="7"{IF SELECTED 7} selected="selected"{/IF}>7 {LANG->Days}</option>
            <option value="30"{IF SELECTED 30} selected="selected"{/IF}>1 {LANG->Month}</option>
            <option value="180"{IF SELECTED 180} selected="selected"{/IF}>6 {LANG->Months}</option>
            <option value="365"{IF SELECTED 365} selected="selected"{/IF}>1 {LANG->Year}</option>
            <option value="0"{IF SELECTED 0} selected="selected"{/IF}>{LANG->AllDates}</option>
        </select>
        <input type="submit" value="{LANG->Go}" />
    </div>
</form>

{IF TOPICS}
    <form action="{URL->ACTION}" method="POST" id="phorum-sub-list">
        {POST_VARS}
        <input type="hidden" name="subdays" value="{SELECTED}" />
        <table cellspacing="0" class="list">
            <tr>
                <th align="left" nowrap="nowrap">
                    <script type="text/javascript">
                        function checkAll() {
                            var lf=document.getElementById('phorum-sub-list');
                            for (var i=0;i<lf.elements.length;i++) {
                                var elt=lf.elements[i];
                                if (elt.type=='checkbox' && elt.name!='toggle') {
                                    elt.checked = document.getElementById('toggle').checked;
                                }
                            }
                        }
                        document.write ( '<input type="checkbox" name="toggle" id="toggle" onclick="checkAll()" />' );
                    </script>
                    {LANG->Delete}
                </th>
                <th align="left">{LANG->Subject}</th>
                <th align="left" nowrap="nowrap">{LANG->Author}</th>
                <th align="left" nowrap="nowrap">{LANG->LastPost}</th>
                <th align="left" nowrap="nowrap">{LANG->Email}</th>
            </tr>
            {LOOP TOPICS}
                {IF altclass ""}
                    {VAR altclass "alt"}
                {ELSE}
                    {VAR altclass ""}
                {/IF}
                <tr>
                    <td width="5%"><input type="checkbox" name="delthreads[]" value="{TOPICS->thread}" /></td>

                    <td width="65%" class="message-subject {altclass}">
                        <a href="{TOPICS->URL->READ}">
                        {IF TOPICS->new}<img src="{URL->TEMPLATE}/images/flag_red.png" width="16" height="16" border="0" alt="{LANG->New}" title="{LANG->New}"/>{/IF}
                        {TOPICS->subject}</a><br />
                        <small>{LANG->Forum}: {TOPICS->forum}</small>
                    </td>
                    <td width="10%" class="{altclass}" nowrap="nowrap">{IF TOPICS->URL->PROFILE}<a href="{TOPICS->URL->PROFILE}">{/IF}{TOPICS->author}{IF TOPICS->URL->PROFILE}</a>{/IF}</td>
                    <td width="15%" class="{altclass}" nowrap="nowrap">{TOPICS->datestamp}</td>
                    <td width="5%">
                        <input type="hidden" name="thread_forum_id[{TOPICS->thread}]" value="{TOPICS->forum_id}" />
                        <input type="hidden" name="old_sub_type[{TOPICS->thread}]" value="{TOPICS->sub_type}" />
                        <select name="sub_type[{TOPICS->thread}]">
                            <option {if TOPICS->sub_type PHORUM_SUBSCRIPTION_MESSAGE}selected="selected"{/IF} value="{PHORUM_SUBSCRIPTION_MESSAGE}">{LANG->Yes}</option>
                            <option {if TOPICS->sub_type PHORUM_SUBSCRIPTION_BOOKMARK}selected="selected"{/IF} value="{PHORUM_SUBSCRIPTION_BOOKMARK}">{LANG->No}</option>
                        </select>
                    </td>
                </tr>
            {/LOOP TOPICS}
        </table>
        <input type="submit" name="button_update" value="{LANG->Update}" />
    </form>
{ELSE}
    <div class="generic">{LANG->NoFollowedThreads}</div>
{/IF}
<p>{LANG->HowToFollowThreads}</p>
