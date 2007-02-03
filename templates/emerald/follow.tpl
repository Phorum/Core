<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
    <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
</div>

  <form action="{URL->ACTION}" method="post" style="display: inline;">
    {POST_VARS}
    <input type="hidden" name="forum_id" value="{FORUM_ID}" />
    <input type="hidden" name="thread" value="{THREAD}" />
    <div class="information">
        {LANG->YouWantToFollow}<br />
        <strong>{SUBJECT}</strong><br /><br />
        {LANG->FollowExplanation}<br /><br />
        <input type="checkbox" name="send_email" checked="checked" />&nbsp;{LANG->FollowWithEmail}<br /><br />
        <input type="submit" value="{LANG->Submit}" />
    </div>
  </form>
