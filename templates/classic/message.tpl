<div align="center">
  <div class="PhorumNavBlock PhorumNarrowBlock" style="text-align: left;">
    <span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}{IF URL->LIST}&bull;<a class="PhorumNavLink" href="{URL->LIST}">{LANG->MessageList}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{INCLUDE "loginout_menu"}
  </div>
  <div class="PhorumStdBlock PhorumNarrowBlock">
    {IF ERROR}
      <div class="PhorumUserError">{ERROR}</div>
    {/IF}
    {IF OKMSG}
        <div class="PhorumFloatingText">{OKMSG}</div>
        {IF URL->CLICKHERE}
            <div  class="PhorumFloatingText"><a href="{URL->CLICKHERE}">{CLICKHEREMSG}</a></div>
        {/IF}
        {IF URL->REDIRECT}
            <div  class="PhorumFloatingText"><a href="{URL->REDIRECT}">{BACKMSG}</a></div>
        {/IF}    
    {/IF}
  </div>
</div>
