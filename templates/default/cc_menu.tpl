<div class="PhorumNavBlock" style="text-align: left;">
<span class="PhorumNavHeading">{LANG->PersProfile}</span><br />
&bull;<a class="PhorumNavLink" href="{URL->CC0}">{LANG->ViewProfile}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC3}">{LANG->EditUserinfo}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC4}">{LANG->EditSignature}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC5}">{LANG->EditMailsettings}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC14}">{LANG->EditPrivacy}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC16}">{LANG->ViewJoinGroups}</a><br />
<br />
{IF ENABLE_PM}
<span class="PhorumNavHeading">{LANG->PrivateMessages}</span><br />
&bull;<a class="PhorumNavLink" href="{URL->CC11}">{LANG->INBOX}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC12}">{LANG->SentItems}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC13}">{LANG->SendPM}</a><br />
<br />
{/IF}
<span class="PhorumNavHeading">{LANG->Subscriptions}</span><br />
&bull;<a class="PhorumNavLink" href="{URL->CC1}">{LANG->ListThreads}</a><br />
<!--&bull;<a class="PhorumNavLink" href="{URL->CC2}">{LANG->ListForums}</a><br />-->
<br />
<span class="PhorumNavHeading">{LANG->Options}</span><br />
&bull;<a class="PhorumNavLink" href="{URL->CC6}">{LANG->EditBoardsettings}</a><br />
&bull;<a class="PhorumNavLink" href="{URL->CC7}">{LANG->ChangePassword}</a><br />
<br />
{IF MYFILES}
<span class="PhorumNavHeading">{LANG->Files}</span><br />
&bull;<a class="PhorumNavLink" href="{URL->CC9}">{LANG->EditMyFiles}</a><br />
<br />
{/IF}
{IF MODERATOR}
<span class="PhorumNavHeading">{LANG->Moderate}</span><br />
{IF MESSAGE_MODERATOR}
&bull;<a class="PhorumNavLink" href="{URL->CC8}">{LANG->UnapprovedMessages}</a><br />
{/IF}
{IF USER_MODERATOR}
&bull;<a class="PhorumNavLink" href="{URL->CC10}">{LANG->UnapprovedUsers}</a><br />
{/IF}
{IF GROUP_MODERATOR}
&bull;<a class="PhorumNavLink" href="{URL->CC15}">{LANG->GroupMembership}</a><br />
{/IF}
<br />
{/IF}
<div align="center"><a class="PhorumNavLink" href="{URL->BACK}">{URL->BACKTITLE}</a></div>
</div>
