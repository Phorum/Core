{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<div align="center">
<form action="{URL->ACTION}" method="post" style="display: inline;">
{POST_VARS}
<input type="hidden" name="forum_id" value="{REGISTER->forum_id}" />

<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{if LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{if LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}</a>
</div>

<div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">{LANG->Register}</div>

<div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
<table class="PhorumFormTable" cellspacing="0" border="0">
<tr>
    <td nowrap="nowrap">{LANG->Username}*:&nbsp;</td>
    <td><input type="text" name="username" size="30" value="{REGISTER->username}" /></td>
</tr>
<tr>
    <td nowrap="nowrap">{LANG->Email}*:&nbsp;</td>
    <td><input type="text" name="email" size="30" value="{REGISTER->email}" /></td>
</tr>
<tr>
    <td nowrap="nowrap">{LANG->Password}*:&nbsp;</td>
    <td><input type="password" name="password" size="30" value="" /></td>
</tr>
<tr>
    <td nowrap="nowrap">&nbsp;</td>
    <td><input type="password" name="password2" size="30" value="" /> ({LANG->again})</td>
</tr>
</table>

<div style="float: left; margin-top: 5px;">*{LANG->Required}</div>
<div style="margin-top: 3px;" align="right"><input type="submit" class="PhorumSubmit" value=" {LANG->Submit} " /></div>

</div>


</form>
</div>
