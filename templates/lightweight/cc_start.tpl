<div class="generic">
    <dl>
        <dt>{LANG->Username}:</dt>
        <dd>{PROFILE->username}</dd>
        <dt>{LANG->RealName}:</dt>
        {IF PROFILE->real_name}
            <dd>{PROFILE->real_name}</dd>
            <dt>{LANG->Email}:</dt>
        {/IF}
        <dd>{PROFILE->email}</dd>
        <dt>{LANG->DateReg}:</dt>
        <dd>{PROFILE->date_added}</dd>
        {IF PROFILE->date_last_active}
            <dt>{LANG->DateActive}:</dt>
            <dd>{PROFILE->date_last_active}</dd>
        {/IF}
        <dt>{LANG->Posts}:</dt>
        <dd>{PROFILE->posts}</dd>
        <dt>{LANG->Signature}:</dt>
        <dd>{PROFILE->signature_formatted}</dd>
        {HOOK "tpl_cc_start" PROFILE} 
    </dl>
</div>
{IF PROFILE->admin}{VAR SHOWPERMS 1}{/IF}
{IF UserPerms}{VAR SHOWPERMS 1}{/IF}
{IF SHOWPERMS}
    <div class="generic">
    <h4>{LANG->UserPermissions}</h4>
    <table cellspacing="0" border="0">
      {IF PROFILE->admin}
        <tr>
          <td>{LANG->PermAdministrator}</td>
        </tr>
      {ELSEIF UserPerms}
        <tr>
          <th>{LANG->Forum}</th>
          <th>{LANG->Permission}</th>
        </tr>
        {LOOP UserPerms}
          <tr>
            <td>{UserPerms->forum}&nbsp;&nbsp;</td>
            <td>{UserPerms->perm}</td>
          </tr>
        {/LOOP UserPerms}
      {/IF}
    </table>
  </div>
{/IF}
