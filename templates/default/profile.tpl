<div align="center">

<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>
</div>

<div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">{LANG->UserProfile}&nbsp;:&nbsp;{PROFILE->username}</div>

<div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
<table cellspacing="0" border="0">
<tr>
    <td nowrap="nowrap">{LANG->Email}:&nbsp;</td>
    <td>{PROFILE->email}</td>
</tr>
{IF PROFILE->real_name}
<tr>
    <td nowrap="nowrap">{LANG->RealName}:&nbsp;</td>
    <td>{PROFILE->real_name}</td>
</tr>
{/IF}
{IF PROFILE->date_added}
<tr>
    <td nowrap="nowrap">{LANG->DateReg}:&nbsp;</td>
    <td>{PROFILE->date_added}</td>
</tr>
{/IF}
{IF PROFILE->date_last_active}
<tr>
    <td nowrap="nowrap">{LANG->DateActive}:&nbsp;</td>
    <td>{PROFILE->date_last_active}</td>
</tr>
{/IF}
</table>

</div>

{IF ENABLE_PM}
<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->Options}:</span>&nbsp;<a class="PhorumNavLink" href="{PROFILE->pm_url}">{LANG->SendPM}</a>
</div>
{/IF}
</div>
