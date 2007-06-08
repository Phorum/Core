<form action="{URL->ACTION}" method="POST">
  {POST_VARS}
  <div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->Subscriptions}</div>
  <div class="PhorumStdBlock PhorumFloatingText" style="text-align: left;">
    {LANG->Activity}:&nbsp;
    <select name="subdays">
      <option value="1"{IF SELECTED 1} selected="selected"{/IF}>1 {LANG->Day}</option>
      <option value="2"{IF SELECTED 2} selected="selected"{/IF}>2 {LANG->Days}</option>
      <option value="7"{IF SELECTED 7} selected="selected"{/IF}>7 {LANG->Days}</option>
      <option value="30"{IF SELECTED 30} selected="selected"{/IF}>1 {LANG->Month}</option>
      <option value="180"{IF SELECTED 180} selected="selected"{/IF}>6 {LANG->Months}</option>
      <option value="365"{IF SELECTED 365} selected="selected"{/IF}>1 {LANG->Year}</option>
      <option value="0"{IF SELECTED 0} selected="selected"{/IF}>{LANG->AllDates}</option>
    </select>
    <input type="submit" class="PhorumSubmit" value="{LANG->Go}" />
  </div>
</form><br />
<form action="{URL->ACTION}" method="POST">
  {POST_VARS}
  <input type="hidden" name="subdays" value="{SELECTED}" />
  <table border="0" cellspacing="0" class="PhorumStdTable">
    <tr>
      <th align="left" class="PhorumTableHeader">{LANG->Delete}</th>
      <th align="left" class="PhorumTableHeader">{LANG->Subject}</th>
      <th align="left" class="PhorumTableHeader">{LANG->Author}</th>
      <th align="left" class="PhorumTableHeader">{LANG->LastPost}</th>
      <th align="left" class="PhorumTableHeader">{LANG->Email}</th>
    </tr>
    {LOOP TOPICS}
      <tr>
        <td class="PhorumTableRow">
          <input type="checkbox" name="delthreads[]" value="{TOPICS->thread}" />
        </td>
        <td class="PhorumTableRow">
          <a href="{TOPICS->URL->READ}">{TOPICS->subject}</a><br />
          <span class="PhorumListSubText">{LANG->Forum}: {TOPICS->forum}</span>
        </td>
        <td class="PhorumTableRow">
          {IF TOPICS->URL->PROFILE}<a href="{TOPICS->URL->PROFILE}">{/IF}
            {TOPICS->author}
          {IF TOPICS->URL->PROFILE}</a>{/IF}
        </td>
        <td class="PhorumTableRow">
          {TOPICS->datestamp}
        </td>
        <td class="PhorumTableRow">
          <input type="hidden" name="thread_forum_id[{TOPICS->thread}]" value="{TOPICS->forum_id}" />
          <input type="hidden" name="old_sub_type[{TOPICS->thread}]" value="{TOPICS->sub_type}" />
          <select name="sub_type[{TOPICS->thread}]">
            <option {if TOPICS->sub_type PHORUM_SUBSCRIPTION_MESSAGE}selected="selected"{/IF} value="{PHORUM_SUBSCRIPTION_MESSAGE}">{LANG->Yes}</option>
            <option {if TOPICS->sub_type PHORUM_SUBSCRIPTION_BOOKMARK}selected="selected"{/IF} value="{PHORUM_SUBSCRIPTION_BOOKMARK}">{LANG->No}</option>
          </select>
         </td>
      </tr>
    {/LOOP TOPICS}
    <tr>
      <th colspan="5" align="right" class="PhorumTableHeader">
        <input type="submit" class="PhorumSubmit" name="button_update" value="{LANG->Update}" />
      </th>
    </tr>
  </table>
</form>
<div class="PhorumFloatingText">{LANG->HowToFollowThreads}</div>
