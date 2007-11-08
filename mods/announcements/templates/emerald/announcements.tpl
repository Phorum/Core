<table cellspacing="0" class="list announcements">
    <tr>
        <th align="left" colspan="2">
            {LANG->Announcements}
        </th>
        <th align="left" nowrap="nowrap">{LANG->LastPost}</th>
    </tr>

    {LOOP ANNOUNCEMENTS}

        {IF ANNOUNCEMENTS->new}
          {VAR icon "flag_red"}
          {VAR read_url ANNOUNCEMENTS->URL->NEWPOST}
        {ELSE}
          {VAR icon "information"}
          {VAR read_url ANNOUNCEMENTS->URL->READ}
        {/IF}
        {VAR title LANG->Announcement}

        <tr>
            <td width="1%"><a href="{read_url}" title="{title}"><img src="{URL->TEMPLATE}/images/{icon}.png" width="16" height="16" border="0" alt="{title}" /></a></td>
            <td width="80%"><a href="{ANNOUNCEMENTS->URL->READ}" title="{title}">{ANNOUNCEMENTS->subject}</a></td>
            <td width="19%" nowrap="nowrap">{ANNOUNCEMENTS->lastpost}</td>
        </tr>
  {/LOOP ANNOUNCEMENTS}
</table>

