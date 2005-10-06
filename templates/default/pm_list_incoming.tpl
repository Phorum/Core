<table border="0" cellspacing="0" class="PhorumStdTable">
    <tr>
      <th class="PhorumTableHeader" align="left" width="20">&nbsp;</th>
      <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
      <th class="PhorumTableHeader" align="left" nowrap="nowrap">{LANG->From}&nbsp;</th>
      <th class="PhorumTableHeader" align="left" nowrap="nowrap">{LANG->Date}&nbsp;</th>
    </tr>
{IF MESSAGECOUNT}
  {LOOP MESSAGES}
    <tr>
      <td class="PhorumListTableRow"><input type="checkbox" name="checked[]" value="{MESSAGES->pm_message_id}" /></td>
      <td class="PhorumListTableRow"><a href="{MESSAGES->read_url}">{MESSAGES->subject}</a>{IF NOT MESSAGES->read_flag}<span class="PhorumNewFlag">&nbsp;{LANG->newflag}</span>{/IF}</td>
      <td class="PhorumListTableRow" nowrap="nowrap"><a href="{MESSAGES->from_profile_url}">{MESSAGES->from_username}</a>&nbsp;</td>
      <td class="PhorumListTableRowSmall" nowrap="nowrap" width="1"><div style="white-space:nowrap">{MESSAGES->date}&nbsp;</div></td>
    </tr>
  {/LOOP MESSAGES}
{ELSE}
  <tr>
      <td colspan="4" style="text-align: center" class="PhorumListTableRow">
        <br/>
        <i>{LANG->PMFolderIsEmpty}</i><br/>
        <br/>
      </td>
  </tr>
{/IF}
</table>

<div class="PhorumStdBlock" style="border-top:none">
{var MOVE_SUBMIT_NAME move}
{include pm_moveselect}
<input type="submit" name="delete" class="PhorumSubmit" value="{LANG->Delete}" 
 onclick="return confirm('<?php print addslashes($PHORUM["DATA"]["LANG"]["AreYouSure"])?>')"/>
</div>
