{LOOP ROWS}
<div class="entry">
<h1>{ROWS->subject}</h1>
<p>{ROWS->body}</p>
<small>Post by {ROWS->linked_author} on {ROWS->datestamp}</small>
<small>{IF MODERATOR true}<a href="<?=phorum_get_url(PHORUM_MODERATION_URL, PHORUM_MOD_EDIT_POST, $PHORUM["TMP"]["ROWS"]["message_id"])?>">edit</a>&nbsp;|&nbsp;{/IF}<a href="{ROWS->url}"><? echo $PHORUM["TMP"]["ROWS"]["thread_count"]-1; ?> comment(s)</a></small>
</div>
{/LOOP ROWS}
