<div class="PhorumNavBlock">
<span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>{IF LOGGEDIN true}&bull;<a class="PhorumNavLink" href="{URL->REGISTERPROFILE}">{LANG->MyProfile}</a>{IF ENABLE_PM}&bull;<a class="PhorumNavLink" href="{PRIVATE_MESSAGES->inbox_url}">{LANG->PrivateMessages}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{/if}{IF LOGGEDIN false}&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/if}
</div>

<div class="PhorumStdBlockHeader PhorumHeaderText">
<div class="PhorumColumnFloatLarge">{LANG->LastPost}</div>
<div class="PhorumColumnFloatSmall">{LANG->Posts}</div>
<div class="PhorumColumnFloatSmall">{LANG->Threads}</div>
<div style="margin-right: 425px">{LANG->Forums}</div>
</div>
<?php
$rclass="Alt";
?>
<div class="PhorumStdBlock">
{LOOP FORUMS}
<?php
  if($rclass=="Alt")
    $rclass="";
  else
    $rclass="Alt";
?>
<div class="PhorumRowBlock<?php echo $rclass;?>">
{IF FORUMS->folder_flag}
<div class="PhorumColumnFloatXLarge">{LANG->ForumFolder}</div>
{else}
<div class="PhorumColumnFloatLarge">{FORUMS->last_post}&nbsp;</div>
<div class="PhorumColumnFloatSmall">{FORUMS->message_count}{IF FORUMS->new_messages} (<span class="PhorumNewFlag">{FORUMS->new_messages} {LANG->newflag}</span>){/IF}</div>
<div class="PhorumColumnFloatSmall">{FORUMS->thread_count}{IF FORUMS->new_threads} (<span class="PhorumNewFlag">{FORUMS->new_threads} {LANG->newflag}</span>){/IF}</div>
{/IF}
<div style="margin-right: 425px" class="PhorumLargeFont"><a href="{FORUMS->url}">{FORUMS->name}</a></div>
<div style="margin-right: 425px" class="PhorumFloatingText">{FORUMS->description}</div>
</div>
{/LOOP FORUMS}
</div>
