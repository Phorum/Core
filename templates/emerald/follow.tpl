<!-- BEGIN TEMPLATE follow.tpl -->
<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
    <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
</div>

  <form action="{URL->ACTION}" method="post" style="display: inline;">
    {POST_VARS}
    <div class="information">
        {LANG->YouWantToFollow}<br /><br/>
        <strong>{SUBJECT}</strong><br />
        <strong>{LANG->StartedBy} {AUTHOR}</strong><br/><br />

        {LANG->FollowExplanation}

        {IF ALLOW_EMAIL_NOTIFY}
          <br />
          {LANG->FollowExplanationMail}
          <br />
          <input type="checkbox" name="send_email" id="send-email" checked="checked" /><label for="send-email">&nbsp;{LANG->FollowWithEmail}</label><br /><br />
        {/IF}
        <br/><br/>
        <input type="submit" value="{LANG->Submit}" />
    </div>
  </form>
<!-- END TEMPLATE follow.tpl -->
