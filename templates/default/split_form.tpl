<div align="center">

<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>
</div>

<form method="POST" action="{URL->ACTION}">
{POST_VARS}
<input type="hidden" name="forum_id" value="{FORM->forum_id}" />
<input type="hidden" name="thread" value="{FORM->thread_id}" />
<input type="hidden" name="message" value="{FORM->message_id}" />
<input type="hidden" name="mod_step" value="{FORM->mod_step}" />

<div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->SplitThread}</span></div>
<div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
<div class="PhorumFloatingText">
  {LANG->SplitThreadInfo}<br /><br />
  {LANG->Message}: '{FORM->message_subject}'<br />
  <br />
  <input type="submit" class="PhorumSubmit" name="move" value="{LANG->SplitThread}" />
</div>
</div>

</form>
</div>
