<!-- BEGIN TEMPLATE report.tpl -->
<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    <a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>
    <a class="icon icon-comment-add" href="{URL->POST}">{LANG->NewTopic}</a>
</div>

{IF ReportPostMessage}<div class="attention">{ReportPostMessage}</div>{/IF}

<div class="generic">
    <h4>{LANG->ConfirmReportMessage}</h4>
    <p>{LANG->ReportPostExplanation}</p>
    <form method="post" action="{ReportURL}">
        <textarea name="explanation" rows="5" cols="60" wrap="virtual">{explanation}</textarea><br />
        <br />
        <input type="submit" name="report" value="{LANG->Report}" />
    </form>
</div>

<p>&nbsp;</p>

<div class="generic">
<strong>{PostSubject}</strong>
<p>{LANG->Postedby}: {PostAuthor}</p>
<p>{PostBody}</p>

</div>
<!-- END TEMPLATE report.tpl -->
