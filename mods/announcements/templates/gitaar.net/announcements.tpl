<div class="phorum-titleblock">
  <strong>{LANG->Announcements}</strong>
</div>

<div class="phorum-block">
  {LOOP ANNOUNCEMENTS}
    {ANNOUNCEMENTS->datestamp}
    &nbsp;&nbsp;&nbsp;
    <a href="{ANNOUNCEMENTS->URL->READ}" title="{LANG->Announcement}">{ANNOUNCEMENTS->subject}</a>{IF ANNOUNCEMENTS->new}&nbsp;<a href="{ANNOUNCEMENTS->URL->NEWPOST}"><span class="phorum-newflag">{ANNOUNCEMENTS->new}</span></a>{/IF}<br/>
  {/LOOP ANNOUNCEMENTS}
</div>

