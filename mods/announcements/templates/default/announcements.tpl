<table class="phorum-announcements-table">
  <tr>
    <th align="left">
      {LANG->Announcements}
    </th>
    <th align="left">
      {LANG->Posted}
    </th>
    <th align="left">
      {LANG->LastPost}
    </th>
  </tr>

  {LOOP ANNOUNCEMENTS}
  <tr>
    <td>
      <a href="{ANNOUNCEMENTS->URL->READ}">{ANNOUNCEMENTS->subject}</a>{IF ANNOUNCEMENTS->new}&nbsp;<a href="{ANNOUNCEMENTS->URL->NEWPOST}"><span class="phorum-announcements-new">{ANNOUNCEMENTS->new}</span></a>{/IF}
    </td>
    <td>{ANNOUNCEMENTS->datestamp}</td>
    <td>{ANNOUNCEMENTS->lastpost}</td>
  </tr>
  {/LOOP ANNOUNCEMENTS}

</table>

