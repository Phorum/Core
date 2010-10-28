<!-- BEGIN TEMPLATE cc_start.tpl -->
<div class="generic">
    <dl>
        <dt>{LANG->Username}:</dt>
        <dd>{PROFILE->username}</dd>
        {IF PROFILE->real_name}
            <dt>{LANG->RealName}:</dt>
            <dd>{PROFILE->real_name}</dd>
        {/IF}
        <dt>{LANG->Email}:</dt>
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
{IF PROFILE->admin OR UserPerms}
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
<!-- END TEMPLATE cc_start.tpl -->
