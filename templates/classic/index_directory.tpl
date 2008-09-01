<div class="PhorumNavBlock">
  <span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{INCLUDE "loginout_menu"}
</div>

{IF FOLDERS}
  <div class="PhorumStdBlockHeader PhorumHeaderText">
    <div style="margin-right: 425px">{LANG->Folders}</div>
  </div>
  <div class="PhorumStdBlock" style="margin-bottom: 1px">
    {LOOP FOLDERS}
    <div class="PhorumColumnFloatXLarge">{LANG->ForumFolder}</div>
        <div style="margin-right: 425px" class="PhorumLargeFont"><a href="{FOLDERS->URL->LIST}">{FOLDERS->name}</a></div>
        <div style="margin-right: 425px" class="PhorumFloatingText">{FOLDERS->description}</div>
    {/LOOP FOLDERS}
  </div>
{/IF}

{IF FORUMS}
  <div class="PhorumStdBlockHeader PhorumHeaderText">
    <div class="PhorumColumnFloatLarge">{LANG->LastPost}</div>
    <div class="PhorumColumnFloatSmall">{LANG->Posts}</div>
    <div class="PhorumColumnFloatSmall">{LANG->Threads}</div>
    <div style="margin-right: 425px">{LANG->Forums}</div>
  </div>
  <div class="PhorumStdBlock">
    {LOOP FORUMS}
      {IF altclass ""}
        {VAR altclass "Alt"}
      {ELSE}
        {VAR altclass ""}
      {/IF}
      <div class="PhorumRowBlock{altclass}">
        <div class="PhorumColumnFloatLarge">{FORUMS->last_post}&nbsp;</div>
        <div class="PhorumColumnFloatSmall">{FORUMS->message_count}{IF FORUMS->new_messages} (<span class="PhorumNewFlag">{FORUMS->new_messages} {LANG->newflag}</span>){/IF}</div>
        <div class="PhorumColumnFloatSmall">{FORUMS->thread_count}{IF FORUMS->new_threads} (<span class="PhorumNewFlag">{FORUMS->new_threads} {LANG->newflag}</span>){/IF}</div>
        <div style="margin-right: 425px" class="PhorumLargeFont"><a href="{FORUMS->URL->LIST}">{FORUMS->name}</a></div>
        <div style="margin-right: 425px" class="PhorumFloatingText">{FORUMS->description}</div>
      </div>
    {/LOOP FORUMS}
  </div>
{/IF}
