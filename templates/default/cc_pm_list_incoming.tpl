<table border="0" cellspacing="0" class="PhorumStdTable">
    <tr>
      <th class="PhorumTableHeader" align="left" width="20">&nbsp;</th>
      <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
      <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->From}&nbsp;</th>
      <th class="PhorumTableHeader" align="left" nowrap="nowrap" width="150">{LANG->Date}&nbsp;</th>
    </tr>
{IF MESSAGECOUNT}
  {LOOP MESSAGES}
    <tr>
      <td class="PhorumListTableRow"><input type="checkbox" name="checked[]" value="{MESSAGES->pm_message_id}" /></td>
      <td class="PhorumListTableRow"><a href="{MESSAGES->read_url}">{MESSAGES->subject}</a>{IF NOT MESSAGES->read_flag}<span class="PhorumNewFlag">&nbsp;{LANG->newflag}</span>{/IF}</td>
      <td class="PhorumListTableRow" nowrap="nowrap" width="150"><a href="{MESSAGES->from_profile_url}">{MESSAGES->from_username}</a>&nbsp;</td>
      <td class="PhorumListTableRowSmall" nowrap="nowrap" width="150">{MESSAGES->date}&nbsp;</td>
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

<div class="PhorumNavBlock" style="text-align: left;">
<input type="submit" name="delete" class="PhorumSubmit" value="{LANG->Delete}" 
 onclick="return confirm('<?php print addslashes($PHORUM["DATA"]["LANG"]["AreYouSure"])?>')"/>
{var MOVE_SUBMIT_NAME move}
{include cc_pm_moveselect}
</div>
