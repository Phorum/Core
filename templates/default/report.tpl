{IF ReportPostMessage}
<div class="PhorumUserError">{ReportPostMessage}</div>
{/IF}

<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->Report}</div>
<div class="PhorumStdBlock" style="text-align: left;">
{LANG->ConfirmReportMessage}
<div class="PhorumReadBodySubject">{PostSubject}</div>
<div class="PhorumReadBodyHead">{LANG->Postedby}: {PostAuthor}</div>
<div class="PhorumReadBodyHead">{LANG->Date}: {PostDate}</div>
<div class="PhorumReadBodyText">{PostBody}</div>
<br />

{LANG->ReportPostExplanation}<br />
<form method="post" action="{ReportURL}">
<textarea name="explanation" rows="5" cols="60" wrap="virtual">{explanation}</textarea>
<br /><input type="submit" name="report" value="{LANG->Report}" />
</form>
</div>
</div>