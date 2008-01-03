<!-- BEGIN TEMPLATE cc_index.tpl -->
<div class="nav">
    {IF URL->INDEX}<a class="icon icon-folder" href="{URL->INDEX}">{LANG->ForumList}</a>{/IF}
    {IF FORUM_ID}<a class="icon icon-list" href="{URL->LIST}">{LANG->MessageList}</a>{/IF}
</div>

<table class="menu" cellspacing="0" border="0">
    <tr>
        <td class="menu" nowrap="nowrap">
            <div class="generic">
                {LANG->PersProfile}
                <ul>
                    <li><a {IF PROFILE->PANEL "summary"}class="current" {/IF}href="{URL->CC0}">{LANG->ViewProfile}</a></li>
                    <li><a {IF PROFILE->PANEL "user"}class="current" {/IF}href="{URL->CC3}">{LANG->EditUserinfo}</a></li>
                    <li><a {IF PROFILE->PANEL "sig"}class="current" {/IF}href="{URL->CC4}">{LANG->EditSignature}</a></li>
                    <li><a {IF PROFILE->PANEL "email"}class="current" {/IF}href="{URL->CC5}">{LANG->EditMailsettings}</a></li>
                    <li><a {IF PROFILE->PANEL "privacy"}class="current" {/IF}href="{URL->CC14}">{LANG->EditPrivacy}</a></li>
                    <li><a {IF PROFILE->PANEL "groups"}class="current" {/IF}href="{URL->CC16}">{LANG->ViewJoinGroups}</a></li>
                </ul>

                {LANG->Subscriptions}
                <ul>
                    <li><a {IF PROFILE->PANEL "subthreads"}class="current" {/IF}href="{URL->CC1}">{LANG->ListThreads}</a></li>
                </ul>

                {LANG->Options}
                <ul>
                    <li><a {IF PROFILE->PANEL "forum"}class="current" {/IF}href="{URL->CC6}">{LANG->EditBoardsettings}</a></li>
                    <li><a {IF PROFILE->PANEL "password"}class="current" {/IF}href="{URL->CC7}">{LANG->ChangePassword}</a></li>
                    {HOOK "tpl_cc_menu_options_hook"}
                </ul>

                {IF MYFILES}
                    {LANG->Files}
                    <ul>
                        <li><a {IF PROFILE->PANEL "files"}class="current" {/IF}href="{URL->CC9}">{LANG->EditMyFiles}</a></li>
                    </ul>
                {/IF}

                {IF MODERATOR}
                    {LANG->Moderate}
                    <ul>
                        {IF MESSAGE_MODERATOR}
                            <li><a {IF PROFILE->PANEL "messages"}class="current" {/IF}href="{URL->CC8}">{LANG->UnapprovedMessages}</a></li>
                        {/IF}
                        {IF USER_MODERATOR}
                            <li><a {IF PROFILE->PANEL "users"}class="current" {/IF}href="{URL->CC10}">{LANG->UnapprovedUsers}</a></li>
                        {/IF}
                        {IF GROUP_MODERATOR}
                            <li><a {IF PROFILE->PANEL "groupmod"}class="current" {/IF}href="{URL->CC15}">{LANG->GroupMembership}</a></li>
                        {/IF}
                        {HOOK "tpl_cc_menu_moderator_hook"}
                    </ul>
                {/IF}

            </div>
        </td>

        <td class="content">

            {IF content_template}
                {INCLUDE content_template}
                <!-- CONTINUE TEMPLATE cc_index.tpl -->
            {ELSE}
                <div class="information">{OKMSG}</div>
            {/IF}

        </td>
    </tr>
</table>
<!-- END TEMPLATE cc_index.tpl -->
