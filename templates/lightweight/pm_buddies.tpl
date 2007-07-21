{IF BUDDYCOUNT}
    <form id="phorum-pm-list" action="{URL->ACTION}" method="post">
        {POST_VARS}
        <input type="hidden" name="page" value="buddies" />
        <input type="hidden" name="action" value="buddies" />
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
                <th align="left">{LANG->Buddy}</th>
                <th align="center">{LANG->Mutual}</th>
                {IF USERTRACK}
                    <th align="right">{LANG->DateActive}&nbsp;</th>
                {/IF}
            </tr>
            {LOOP BUDDIES}
                <tr>
                    <td width="5%"><input type="checkbox" name="checked[]" value="{BUDDIES->user_id}"></td>
                    <td width="40%"><a href="{BUDDIES->URL->PROFILE}"><strong>{BUDDIES->display_name}</strong></a></td>
                    <td width="20%" align="center">{IF BUDDIES->mutual}{LANG->Yes}{ELSE}{LANG->No}{/IF}</td>
                    {IF USERTRACK}
                        <td width="20%" align="right">{BUDDIES->date_last_active}&nbsp;</td>
                    {/IF USERTRACK}
                </tr>
            {/LOOP BUDDIES}
        </table>
        <input type="submit" name="delete" value="{LANG->Delete}" onclick="return confirm('<?php echo addslashes($PHORUM['DATA']['LANG']['AreYouSure'])?>')" />
        <input type="submit" name="send_pm" value="{LANG->SendPM}" />
    </form>
{ELSE}
    <div class="generic">{LANG->BuddyListIsEmpty}</>
{/IF}

