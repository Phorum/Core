<!-- BEGIN TEMPLATE openid_register.tpl -->
{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

<div class="generic">
    <form action="{URL->ACTION}" method="post" style="display: inline;">
    {POST_VARS}
    <input type="hidden" name="open_id" value="{OPENID->open_id}" />
    <table cellspacing="0" border="0">
      <tr>
        <td nowrap="nowrap">{LANG->OpenID}*:&nbsp;</td>
        <td>{OPENID->open_id}</td>
      </tr>
      <tr>
        <td nowrap="nowrap">{LANG->Username}*:&nbsp;</td>
        <td><input type="text" name="username" size="30" value="{OPENID->username}" /></td>
      </tr>
      <tr>
        <td nowrap="nowrap">{LANG->RealName}:&nbsp;</td>
        <td><input type="text" name="real_name" size="30" value="{OPENID->real_name}" /></td>
      </tr>
      <tr>
        <td nowrap="nowrap">{LANG->Email}*:&nbsp;</td>
        <td><input type="text" name="email" size="30" value="{OPENID->email}" /></td>
      </tr>
    </table>

    {HOOK "tpl_register_form"}

    <div style="margin-top: 15px;">
      <small>*{LANG->Required}</small>
    </div>

    <div style="margin-top: 15px;">
      <input type="submit" value=" {LANG->Submit} " />
    </div>

  </form>
</div>
<!-- END TEMPLATE openid_register.tpl -->
