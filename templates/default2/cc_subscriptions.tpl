<form action="{URL->ACTION}" method="POST">
    {POST_VARS}
    <input type="hidden" name="panel" value="{PROFILE->PANEL}" />
    <input type="hidden" name="forum_id" value="{PROFILE->forum_id}" />
    <div class="generic">
        {LANG->Activity}&nbsp;
        <select name="subdays">
            <option value="1"{IF SELECTED 1} selected{/IF}>1 {LANG->Day}</option>
            <option value="2"{IF SELECTED 2} selected{/IF}>2 {LANG->Days}</option>
            <option value="7"{IF SELECTED 7} selected{/IF}>7 {LANG->Days}</option>
            <option value="30"{IF SELECTED 30} selected{/IF}>1 {LANG->Month}</option>
            <option value="180"{IF SELECTED 180} selected{/IF}>6 {LANG->Months}</option>
            <option value="365"{IF SELECTED 365} selected{/IF}>1 {LANG->Year}</option>
            <option value="0"{IF SELECTED 0} selected{/IF}>{LANG->AllDates}</option>
        </select>
        <input type="submit" class="PhorumSubmit" value="{LANG->Go}" />
    </div>
</form>

{IF subscriptions}
    <form action="{URL->ACTION}" method="POST" id="phorum-sub-list">
        {POST_VARS}
        <input type="hidden" name="forum_id" value="{PROFILE->forum_id}" />
        <input type="hidden" name="panel" value="{PROFILE->PANEL}" />
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
            {LOOP subscriptions}
                <tr>
                    <td width="5%"><input type="checkbox" name="delthreads[]" value="{subscriptions->thread}" /></td>
    
                    <td width="65%" class="message-subject {altclass}">
                        <a href="{subscriptions->readurl}">{subscriptions->subject}</a><br />
                        <small>{LANG->Forum}: {subscriptions->forum}</small>
                    </td>
                    <td width="10%" class="{altclass}" nowrap="nowrap">{subscriptions->linked_author}</td>
                    <td width="15%" class="{altclass}" nowrap="nowrap">{subscriptions->datestamp}</td>
                    <td width="5%">
                        <input type="hidden" name="thread_forum_id[{subscriptions->thread}]" value="{subscriptions->forum_id}" />
                        <input type="hidden" name="old_sub_type[{subscriptions->thread}]" value="{subscriptions->sub_type}" />
                        <select name="sub_type[{subscriptions->thread}]">
                            <option {if subscriptions->sub_type PHORUM_SUBSCRIPTION_MESSAGE}selected{/IF} value="{PHORUM_SUBSCRIPTION_MESSAGE}">{LANG->Yes}</option>
                            <option {if subscriptions->sub_type PHORUM_SUBSCRIPTION_BOOKMARK}selected{/IF} value="{PHORUM_SUBSCRIPTION_BOOKMARK}">{LANG->No}</option>
                        </select>
                    </td>
                </tr>
            {/LOOP subscriptions}
        </table>
        <input type="submit" class="PhorumSubmit" name="button_update" value="{LANG->Update}" />
    </form>
{ELSE}
    <div class="generic">{LANG->NoFollowedThreads}</div>
{/IF}        
<p>{LANG->HowToFollowThreads}</p>
