<div align="center">

<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}
</div>


<div class="PhorumStdBlock PhorumNarrowBlock">

{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

{IF MESSAGE}
<div class="PhorumFloatingText">{MESSAGE}</div>
{/IF}

{IF URL->REDIRECT}
<div class="PhorumFloatingText"><a href="{URL->REDIRECT}">{BACKMSG}</a></div>
{/IF}

</div>

</div>
