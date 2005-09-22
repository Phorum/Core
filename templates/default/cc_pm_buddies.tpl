{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<div class="PhorumLargeFont">{LANG->PrivateMessages} : {LANG->Buddies}</div>
<br />
<form action="{ACTION}" method="post">
{POST_VARS}
<input type="hidden" name="panel" value="pm" />
<input type="hidden" name="action" value="folders" />
<input type="hidden" name="forum_id" value="{FORUM_ID}" />

<div class="PhorumStdBlockHeader" style="text-align: left; width:99%">

Here comes phorum PM buddies management.

</div>

