<div align="center">

<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>
</div>


<form action="{URL->ACTION}" method="post" style="display: inline;">
{POST_VARS}
<input type="hidden" name="forum_id" value="{FORUM_ID}" />
<input type="hidden" name="thread" value="{THREAD}" />
<div class="PhorumStdBlock PhorumNarrowBlock">

<div class="PhorumFloatingText">
{LANG->YouWantToFollow}
<div class="PhorumLargeFont">{SUBJECT}</div><br />
{LANG->FollowExplination}<br /><br />
<input type="checkbox" name="send_email" checked="checked" />&nbsp;{LANG->FollowWithEmail}<br /><br />
<input type="submit" value="{LANG->Submit}" />
</div>

</div>
</form>

</div>
