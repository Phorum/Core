<div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->PrivateMessages}</div>
<div class="PhorumStdBlock">

<div class="PhorumReadBodySubject">{MESSAGE->subject}</div>
<div class="PhorumReadBodyHead">{LANG->From}: <strong><a href="{MESSAGE->from_profile_url}">{MESSAGE->from}</a></strong></div>
<div class="PhorumReadBodyHead">{LANG->To}: <strong><a href="{MESSAGE->to_profile_url}">{MESSAGE->to}</a></strong></div>
<div class="PhorumReadBodyHead">{LANG->Date}: {MESSAGE->date}</div>
<br />
<div class="PhorumReadBodyText">{MESSAGE->message}</div><br />
</div>

<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>&nbsp;{IF MESSAGE->reply_url}<a class="PhorumNavLink" href="{MESSAGE->reply_url}">{LANG->Reply}</a>&bull;{/IF}<a class="PhorumNavLink" href="{MESSAGE->delete_url}">{LANG->Delete}</a>
</div>

</div>