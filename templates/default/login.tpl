{IF ERROR}
<div class="PhorumUserError">{ERROR}</div>
{/IF}

<div align="center">
<div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>
</div>

<form action="{URL->ACTION}" method="post" style="display: inline;">
{POST_VARS}
<input type="hidden" name="forum_id" value="{LOGIN->forum_id}" />
<input type="hidden" name="redir" value="{LOGIN->redir}" />
<div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">{LANG->LoginTitle}</div>
<div align="center" class="PhorumStdBlock PhorumNarrowBlock">
<table cellspacing="0" align="center">
<tr>
    <td>{LANG->Username}:&nbsp;</td>
    <td><input type="text" name="username" size="30" value="{LOGIN->username}" /></td>
</tr>
<tr>
    <td>{LANG->Password}:&nbsp;</td>
    <td><input type="password" name="password" size="30" value="" /></td>
</tr>
<tr>
    <td colspan="2" align="right"><input type="submit" class="PhorumSubmit" value="{LANG->Submit}" /></td>
</tr>
</table>
<div class="PhorumFloatingText"><a href="{URL->REGISTER}">{LANG->NotRegistered}</a></div>
</div>
</form>

</div>

<div align="center" style="margin-top: 30px;">

<form action="{URL->ACTION}" method="post" style="display: inline;">
{POST_VARS}
<input type="hidden" name="lostpass" value="1" />
<input type="hidden" name="forum_id" value="{LOGIN->forum_id}" />
<input type="hidden" name="redir" value="{LOGIN->redir}" />
<div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">{LANG->LostPassword}</div>
<div class="PhorumStdBlock PhorumNarrowBlock">
<div class="PhorumFloatingText">{LANG->LostPassInfo}</div><div class="PhorumFloatingText"><input type="text" name="lostpass" size="30" value="" /> <input type="submit" class="PhorumSubmit" value="{LANG->Submit}" /></div>
</div>
</form>

</div>