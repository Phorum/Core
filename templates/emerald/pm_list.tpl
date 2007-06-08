<form action="{URL->ACTION}" method="post" id="phorum-pm-list">
    {POST_VARS}
    <input type="hidden" name="action" value="list" />
    <input type="hidden" name="folder_id" value="{FOLDER_ID}" />
    {IF FOLDER_IS_INCOMING}
        {INCLUDE "pm_list_incoming"}
    {ELSE}
        {INCLUDE "pm_list_outgoing"}
    {/IF}
</form>
