<table cellspacing="0" class="list announcements">
    <tr>
        <th align="left" colspan="2">
            {LANG->Announcements}
        </th>
        <th align="left" style="white-space: nowrap">{LANG->LastPost}</th>
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
            <td width="1%"><a href="{read_url}" title="{title}"><span class="new-flag[hide,{MESSAGES->forum_id},{MESSAGES->thread}]"><span class="new-indicator">{LANG->New}</span></span></a></td>
            <td width="80%"><a href="{ANNOUNCEMENTS->URL->READ}" title="{title}">{ANNOUNCEMENTS->subject}</a></td>
            <td width="19%" style="white-space: nowrap">{ANNOUNCEMENTS->lastpost}</td>
        </tr>
  {/LOOP ANNOUNCEMENTS}
</table>

