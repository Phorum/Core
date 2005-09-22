<table border="0" cellspacing="0" class="PhorumStdTable">
    <tr>
      <th class="PhorumTableHeader" align="left" width="20">&nbsp;</th>
      <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
      <th class="PhorumTableHeader" align="left" nowrap="nowrap">{LANG->To}&nbsp;</th>
      <th class="PhorumTableHeader" align="left" nowrap="nowrap">{LANG->Date}&nbsp;</th>
      <th class="PhorumTableHeader" align="left" nowrap="nowrap">{LANG->Received}&nbsp;</th>
    </tr>
{IF MESSAGECOUNT}
  {LOOP MESSAGES}
    <tr>
      <td class="PhorumListTableRow"><input type="checkbox" name="checked[]" value="{MESSAGES->pm_message_id}" /></td>
      <td class="PhorumListTableRow"><a href="{MESSAGES->read_url}">{MESSAGES->subject}</a></td>
      <td class="PhorumListTableRow" nowrap="nowrap"><a href="{MESSAGES->to_profile_url}">{MESSAGES->to_username}</a>&nbsp;</td>
      <td class="PhorumListTableRowSmall" nowrap="nowrap">{MESSAGES->date}&nbsp;</td>
      <td class="PhorumListTableRowSmall" nowrap="nowrap">{IF MESSAGES->read_flag}{LANG->Yes}{ELSE}{LANG->No}{/IF}</td>
    </tr>
  {/LOOP MESSAGES}
{ELSE}
  <tr>
      <td colspan="5" style="text-align: center" class="PhorumListTableRow">
        <br/>
        <i>{LANG->PMFolderIsEmpty}</i><br/>
        <br/>
      </td>
  </tr>
{/IF}

</table>

<div class="PhorumNavBlock" style="text-align: left;">
<input type="submit" name="delete" class="PhorumSubmit" value="{LANG->Delete}" 
 onclick="return confirm('<?php print addslashes($PHORUM["DATA"]["LANG"]["AreYouSure"])?>')"/>
</div>