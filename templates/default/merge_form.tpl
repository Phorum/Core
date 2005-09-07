<div align="center">

<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>
</div>

{IF FORM->merge_none}
<div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->MergeThread}</span></div>
<div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
<div class="PhorumFloatingText">
  {LANG->MergeThreadWith}:<br /><br />
  {LANG->MergeThreadInfo}<br />
</div>
</div>
{/IF}
{IF FORM->merge_t1}
<form method="POST" action="{URL->ACTION}">
{POST_VARS}
<input type="hidden" name="forum_id" value="{FORM->forum_id}" />
<input type="hidden" name="thread" value="{FORM->thread_id}" />
<input type="hidden" name="thread1" value="{FORM->merge_t1}" />
<input type="hidden" name="mod_step" value="{FORM->mod_step}" />

<div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->MergeThread}</span></div>
<div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
<div class="PhorumFloatingText">
  {LANG->MergeThreadAction}:<br /><br />
  {LANG->Thread}: '{FORM->merge_subject1}'<br />
  {LANG->Thread}: '{FORM->thread_subject}'<br />
  <br />
  <input type="submit" class="PhorumSubmit" name="move" value="{LANG->MergeThread}" />
</div>
</div>

</form>
{/IF}
{IF FORM->thread_id}
<div class="PhorumFloatingText">
<form method="POST" action="{URL->ACTION}">
<input type="hidden" name="forum_id" value="{FORM->forum_id}" />
<input type="hidden" name="thread" value="{FORM->thread_id}" />
<input type="hidden" name="mod_step" value="{FORM->mod_step}" />
 <input type="submit" class="PhorumSubmit" name="move" value="{LANG->MergeThreadCancel}" />
</div>
</form>
{/IF}
