
{IF PREVIEW->parent_id 0}
<div class="entry">
<h1>{PREVIEW->subject}</h1>
<p>{PREVIEW->body}</p>
<small>Post by {PREVIEW->linked_author} on {PREVIEW->datestamp}</small>
</div>
{ELSE}
<a name="msg-{PREVIEW->message_id}"></a>
<div class="comment">
<h1>{PREVIEW->subject}</h1>
<p>{PREVIEW->body}</p>
<small>Post by {PREVIEW->author}</small>
</div>

{/IF}

<hr /><br /><br />