<table style="width: 100%">
<tr>
<td style="vertical-align: top; width: 200px" nowrap="nowrap">
{include cc_menu}
</td>
<td style="vertical-align: top;">
<!---<div style="padding-top: 1px;margin-left: 21%;width: 75%;">-->
{IF content_template}
{include_var content_template}
{else}
<div class="PhorumFloatingText">{MESSAGE}</div>
{/IF}
</td>
</tr>
</table>
