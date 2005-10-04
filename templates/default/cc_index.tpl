<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}{IF FORUM_ID}<a class="PhorumNavLink" href="{URL->TOP}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{IF LOGGEDIN}{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{URL->PM}">{LANG->PrivateMessages}</a>{/IF}{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>
</div>

<table id="phorum-menu-table" cellspacing="0" border="0">
<tr>
<td id="phorum-menu" nowrap="nowrap">
{include cc_menu}
</td>
<td id="phorum-content">
{IF content_template}
{include_var content_template}
{else}
<div class="PhorumFloatingText">{MESSAGE}</div>
{/IF}
</td>
</tr>
</table>
