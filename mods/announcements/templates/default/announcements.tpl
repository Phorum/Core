<table>
  <tr>
    <th align="left" colspan="2">
      {LANG->Announcements}
    </th>
    <th align="left" nowrap="nowrap">
      {LANG->LastPost}
    </th>
  </tr>

  {LOOP ANNOUNCEMENTS}
  <tr>
    <td width="80%">
      <a href="{ANNOUNCEMENTS->URL->READ}">{ANNOUNCEMENTS->subject}</a>
    </td>
    <td width="20%" nowrap="nowrap">
      {ANNOUNCEMENTS->lastpost}
    </td>
  </tr>
  {/LOOP ANNOUNCEMENTS}

</table>

