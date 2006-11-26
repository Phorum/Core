<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
    <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
</div>
{IF ReportPostMessage}<div class="attention">{ReportPostMessage}</div>{/IF}

<div class="information">
  <strong>{LANG->ConfirmReportMessage}</strong><br /><br />
  <div class="PhorumReadBodySubject">{PostSubject}</div>
  <div class="PhorumReadBodyHead">{LANG->Postedby}: {PostAuthor}</div>
  <div class="PhorumReadBodyHead">{LANG->Date}: {PostDate}</div>
  <div class="PhorumReadBodyText">{PostBody}</div><br />
  {LANG->ReportPostExplanation}<br />
  <form method="post" action="{ReportURL}">
    <textarea name="explanation" rows="5" cols="60" wrap="virtual">{explanation}</textarea><br />
    <input type="submit" name="report" value="{LANG->Report}" />
  </form>
</div>
