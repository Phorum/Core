<div align="center">
  <div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
    <span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}{IF URL->LIST}<a class="PhorumNavLink" href="{URL->LIST}">{LANG->MessageList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{INCLUDE "loginout_menu"}
  </div>
  <div class="PhorumStdBlockHeader PhorumNarrowBlock PhorumHeaderText" style="text-align: left;">
    {LANG->UserProfile}&nbsp;:&nbsp;{PROFILE->display_name}
    {IF ENABLE_PM}
      {IF PROFILE->is_buddy} ({LANG->Buddy}){/IF}
    {/IF}
  </div>
  <div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
    <table cellspacing="0" border="0">
      <tr>
        <td nowrap="nowrap">{LANG->Email}:&nbsp;</td>
        <td>{PROFILE->email}</td>
      </tr>
      {IF PROFILE->real_name}
        <tr>
          <td nowrap="nowrap">{LANG->RealName}:&nbsp;</td>
          <td>{PROFILE->real_name}</td>
        </tr>
      {/IF}
      {IF PROFILE->posts}
        <tr>
          <td nowrap="nowrap">{LANG->Posts}:&nbsp;</td>
          <td>{PROFILE->posts}</td>
        </tr>
      {/IF}
      {IF PROFILE->date_added}
        <tr>
          <td nowrap="nowrap">{LANG->DateReg}:&nbsp;</td>
          <td>{PROFILE->date_added}</td>
        </tr>
      {/IF}
      {IF PROFILE->date_last_active}
        <tr>
          <td nowrap="nowrap">{LANG->DateActive}:&nbsp;</td>
          <td>{PROFILE->date_last_active}</td>
        </tr>
      {/IF}
      {HOOK "tpl_profile" PROFILE} 
    </table>
  </div>
  <div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
    {IF ENABLE_PM}
      <span class="PhorumNavHeading">{LANG->Options}:</span>&nbsp;<a class="PhorumNavLink" href="{PROFILE->URL->PM}">{LANG->SendPM}</a>{IF NOT PROFILE->is_buddy}&bull;<a class="PhorumNavLink" href="{PROFILE->URL->ADD_BUDDY}">{LANG->BuddyAdd}</a>&bull;{/IF}
    {/IF}
    <a class="PhorumNavLink" href="{PROFILE->URL->SEARCH}">{LANG->ShowPosts}</a>
  </div>
</div>
