
{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

<form action="{URL->ACTION}" method="POST">
    {POST_VARS}
    <div class="generic">

        <dl>
            {IF PROFILE->USERPROFILE}
                <dt>{LANG->RealName}:&nbsp;</dt>
                <dd><input type="text" name="real_name" size="30" value="{PROFILE->real_name}" /></dd>
            {/IF}

            {IF PROFILE->SIGSETTINGS}
                <dt>{LANG->Signature}:&nbsp;</dt>
                <dd><div id="post-body"><textarea name="signature" id="body" class="body" rows="15" cols="50">{PROFILE->signature}</textarea></div></dd>
            {/IF}
            {IF PROFILE->MAILSETTINGS}
                <dt>{LANG->Email}:&nbsp;*&nbsp;</dt>
                <dd>
                    <input type="text" name="email" size="30" value="{PROFILE->email}" />
                    {IF PROFILE->EMAIL_CONFIRM}
                        <br /><small>{LANG->EmailConfirmRequired}</small>
                    {/IF}
                </dd>
                {IF PROFILE->email_temp_part}
                    <dt>{LANG->EmailVerify}:&nbsp;</dt>
                    <dd>
                        {LANG->EmailVerifyDesc} {PROFILE->email_temp_part}<br />
                        {LANG->EmailVerifyEnterCode}: <input type="text" name="email_verify_code" value="" />
                    </dd>
                {/IF}
                {IF SHOW_EMAIL_HIDE}<dd><input type="checkbox" name="hide_email" value="1"{PROFILE->hide_email_checked} /> {LANG->AllowSeeEmail}</dd>{/IF}
                {IF PROFILE->show_moderate_options}
                    <dd><input type="checkbox" name="moderation_email" value="1"{PROFILE->moderation_email_checked} /> {LANG->ReceiveModerationMails}</dt>
                {/IF}
            {/IF}

            {IF PROFILE->PRIVACYSETTINGS}
            {IF SHOW_EMAIL_HIDE}<dd><input type="checkbox" name="hide_email" value="1"{PROFILE->hide_email_checked} /> {LANG->AllowSeeEmail}</dd>{/IF}
                <dd><input type="checkbox" name="hide_activity" value="1"{PROFILE->hide_activity_checked} /> {LANG->AllowSeeActivity}</dd>
            {/IF}

            {IF PROFILE->BOARDSETTINGS}
                    {IF PROFILE->TZSELECTION}
                    <dt>{LANG->Timezone}:&nbsp;</dt>
                    <dd>
                        <select name="tz_offset">
                            {LOOP TIMEZONE}
                                <option value="{TIMEZONE->tz}"{TIMEZONE->sel}>{TIMEZONE->str}</option>
                            {/LOOP TIMEZONE}
                        </select>
                    </dd>
                    <dt>{LANG->IsDST}:&nbsp;</dt>
                    <dd><input type="checkbox" name="is_dst" value="1"{IF PROFILE->is_dst 1} checked="checked"{/IF}/></dd>
                {/IF}
                <dt>{LANG->Language}:&nbsp;</dt>
                <dd>
                    <select name="user_language">
                        {LOOP LANGUAGES}
                            <option value="{LANGUAGES->file}"{LANGUAGES->sel}>{LANGUAGES->name}</option>
                        {/LOOP LANGUAGES}
                    </select>
                </dd>

                {IF PROFILE->TMPLSELECTION}
                    <dt>{LANG->Template}:&nbsp;</dt>
                    <dd>
                        <select name="user_template">
                            {LOOP TEMPLATES}
                                <option value="{TEMPLATES->file}"{TEMPLATES->sel}>{TEMPLATES->name}</option>
                            {/LOOP TEMPLATES}
                        </select>
                    </dd>
                {/IF}

                <dt>{LANG->ThreadViewList}:&nbsp;</dt>
                <dd>
                    <select name="threaded_list">
                        <option value="0">{LANG->Default}</option>
                        <option value="1" {IF PROFILE->threaded_list} selected="selected"{/IF}>{LANG->ViewThreadedList}</option>
                        <option value="2" {IF PROFILE->threaded_list 2} selected="selected"{/IF}>{LANG->ViewFlatList}</option>
                    </select>
                </dd>

                <dt>{LANG->ThreadViewRead}:&nbsp;</dt>
                <dd>
                    <select name="threaded_read">
                        <option value="0">{LANG->Default}</option>
                        <option value="1" {IF PROFILE->threaded_read} selected="selected"{/IF}>{LANG->ViewThreadedRead}</option>
                        <option value="2" {IF PROFILE->threaded_read 2} selected="selected"{/IF}>{LANG->ViewFlatRead}</option>
                        <option value="3" {IF PROFILE->threaded_read 3} selected="selected"{/IF}>{LANG->ViewHybridRead}</option>
                    </select>
                </dd>

                <dt>{LANG->EnableNotifyDefault}:&nbsp;</dt>
                <dd>
                    <select name="email_notify">
                      <option value="0"{IF PROFILE->email_notify 0} selected="selected" {/IF}>{LANG->None}</option>
                      <option value="1"{IF PROFILE->email_notify 1} selected="selected" {/IF}>{LANG->FollowThread}</option>
                      <option value="2"{IF PROFILE->email_notify 2} selected="selected" {/IF}>{LANG->FollowWithEmailCC}</option>
                    </select>
                </dd>

                <dt>{LANG->AddSigDefault}:&nbsp;</dt>
                <dd>
                    <select name="show_signature">
                        <option value="0"{IF PROFILE->show_signature 0} selected="selected" {/IF}>{LANG->No}</option>
                        <option value="1"{IF PROFILE->show_signature 1} selected="selected" {/IF}>{LANG->Yes}</option>
                    </select>
                </dd>

                {IF SHOW_PM_EMAIL_NOTIFY}
                <dt>{LANG->PMNotifyEnableSetting}:&nbsp;</dt>
                <dd>
                    <select name="pm_email_notify">
                        <option value="0"{IF PROFILE->pm_email_notify 0} selected="selected" {/IF}>{LANG->No}</option>
                        <option value="1"{IF PROFILE->pm_email_notify 1} selected="selected" {/IF}>{LANG->Yes}</option>
                    </select>
                </dd>
                {/IF}
            {/IF}

            {IF PROFILE->CHANGEPASSWORD}
                <dt>{LANG->OriginalPassword}:&nbsp;*&nbsp;</dt>
                <dd>
                    <input type="password" name="password_old" size="30" value="" />
                </dd>
                <dt>{LANG->NewPassword}:&nbsp;*&nbsp;</dt>
                <dd><input type="password" name="password_new" size="30" value="" /></dd>
                <dd><input type="password" name="password_new2" size="30" value="" /> ({LANG->again})</dd>
            {/IF}

            {HOOK "tpl_cc_usersettings" PROFILE}

            <dd><small>*{LANG->Required}</small></dd>

        </dl>

        <div><input type="submit" value=" {LANG->Submit} " /></div>

    </div>

</form>
