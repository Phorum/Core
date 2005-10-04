{include pm_max_messagecount}

{IF ERROR}
  <div class="PhorumUserError">{ERROR}</div>
{/IF}

<form action="{ACTION}" method="post">
{POST_VARS}
<input type="hidden" name="panel" value="pm" />
<input type="hidden" name="action" value="list" />
<input type="hidden" name="folder_id" value="{FOLDER_ID}" />
<input type="hidden" name="forum_id" value="{FORUM_ID}" />

{IF FOLDER_IS_INCOMING}
  {INCLUDE cc_pm_list_incoming}
{ELSE}
  {INCLUDE cc_pm_list_outgoing}
{/IF}

</form>

