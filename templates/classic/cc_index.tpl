<div class="PhorumNavBlock" style="text-align: left;">
  <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}{IF URL->LIST}<a class="PhorumNavLink" href="{URL->LIST}">{LANG->MessageList}</a>&bull;{/IF}{IF URL->POST}<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{IF LOGGEDIN true}{IF ENABLE_PM}<a class="PhorumNavLink" href="{URL->PM}">{LANG->PrivateMessages}</a>&bull;{/IF}{/IF}<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>
</div>
<table id="phorum-menu-table" cellspacing="0" border="0">
  <tr>
    <td id="phorum-menu" nowrap="nowrap">{INCLUDE "cc_menu"}</td>
    <td id="phorum-content">
      {IF content_template}
        {INCLUDE content_template}
      {ELSE}
        <div class="PhorumFloatingText">{OKMSG}</div>
      {/IF}
    </td>
  </tr>
</table>
