<form action="{ACTION}" method="post">
{POST_VARS}
<input type="hidden" name="panel" value="pm" />
<input type="hidden" name="page" value="buddies" />
<input type="hidden" name="action" value="buddies" />
<input type="hidden" name="forum_id" value="{FORUM_ID}" />

<table border="0" cellspacing="0" class="PhorumStdTable">

  <tr>
    <th class="PhorumTableHeader" align="left" width="20">&nbsp;</th>
    <th class="PhorumTableHeader" align="left">{LANG->Buddy}</th>
    <th class="PhorumTableHeader" align="left">{LANG->RealName}</th>
    <th class="PhorumTableHeader" align="center">{LANG->Mutual}</th>
    {IF USERTRACK}
    <th class="PhorumTableHeader" align="right">{LANG->DateActive}&nbsp;</th>
    {/IF}
  </tr>

{IF BUDDYCOUNT}
  {LOOP BUDDIES}
    <tr>
      <td><input type="checkbox" name="checked[]" value="{BUDDIES->user_id}"></td>
      <td><a href="{BUDDIES->profile_url}"><strong>{BUDDIES->username}</strong></a></td>
      <td>{BUDDIES->real_name}</td>
      <td align="center">{IF BUDDIES->mutual}{LANG->Yes}{ELSE}{LANG->No}{/IF}</td>
      {IF USERTRACK}
      <td align="right">{BUDDIES->date_last_active}&nbsp;</td>
      {/IF USERTRACK}
    </tr>
  {/LOOP BUDDIES}

  </table>

  <div class="PhorumStdBlock" style="border-top:none">
    <input type="submit" name="delete" class="PhorumSubmit" value="{LANG->Delete}"
     onclick="return confirm('<?php print addslashes($PHORUM["DATA"]["LANG"]["AreYouSure"])?>')"/>
    <input type="submit" name="send_pm" class="PhorumSubmit" value="{LANG->SendPM}"/>
  </div>
{ELSE}
  <tr>
    <td colspan="4" style="text-align: center" class="PhorumListTableRow">
      <br/>
      <i>{LANG->BuddyListIsEmpty}</i><br/>
      <br/>
    </td>
  </tr>
  </table>
{/IF}

</form>

