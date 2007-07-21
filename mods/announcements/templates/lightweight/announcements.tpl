<table cellspacing="0" class="list announcements">
    <tr>
        <th align="left">
            {LANG->Announcements}
        </th>
        <th align="left" nowrap="nowrap">{LANG->LastPost}</th>
    </tr>

    {LOOP ANNOUNCEMENTS}

        <tr>
            <td width="80%">&bull; <a href="{ANNOUNCEMENTS->URL->READ}" title="{title}">{ANNOUNCEMENTS->subject}</a></td>
            <td width="20%" nowrap="nowrap">{ANNOUNCEMENTS->lastpost}</td>
        </tr>
  {/LOOP ANNOUNCEMENTS}
</table>

