{IF PREVIEW}
  <div class="PhorumStdBlockHeader PhorumHeaderText" style="text-align: left;">{LANG->Preview}</div>
  <div class="PhorumStdBlock" style="text-align: left;">
    <div class="PhorumReadBodySubject">{PREVIEW->subject}</div>
    <div class="PhorumReadBodyHead">{LANG->From}: <strong><a href="#">{PREVIEW->author}</a></strong></div>
    <div class="PhorumReadBodyHead">
      {LANG->To}:
      {VAR ISFIRST true}
      {LOOP PREVIEW->recipients}
        <div style="display:inline; white-space: nowrap">
          {IF NOT ISFIRST} / {/IF}
          <strong><a href="#">{PREVIEW->recipients->display_name}</a></strong>
          {VAR ISFIRST false}
        </div>
      {/LOOP PREVIEW->recipients}
    </div><br />
    <div class="PhorumReadBodyText">{PREVIEW->message}</div><br />
  </div><br />
{/IF}
<form action="{URL->ACTION}" method="post">
  {POST_VARS}
  <input type="hidden" name="action" value="post" />
  <input type="hidden" name="hide_userselect" value="{HIDE_USERSELECT}" />
  <div class="PhorumStdBlockHeader" style="text-align: left; width:99%">
    <table class="PhorumFormTable" cellspacing="0" border="0" style="width:99%">
      <tr>
        <td>{LANG->From}:&nbsp;</td>
        <td>{MESSAGE->author}</td>
      </tr>
      <tr>
        <td valign="top">{LANG->To}:&nbsp;</td>
        <td valign="top" width="100%">
          {! Show user selection}
          {IF SHOW_USERSELECTION}
            <div class="phorum-pmuserselection">
              {IF USERS}
                <select id="userselection" name="to_id" size="1" align="middle">
                  <option value=""> {LANG->PMSelectARecipient}</option>
                  {LOOP USERS}
                    <option value="{USERS->user_id}" <?php if (isset($_POST['to_id']) && $_POST['to_id'] == $PHORUM['TMP']['USERS']['user_id']) echo 'selected="selected"'?>>{USERS->display_name}</option>
                  {/LOOP USERS}
                </select>
              {ELSE}
                <input type="text" id="userselection" name="to_name" value="<?php if (isset($_POST['to_name'])) echo htmlspecialchars($_POST['to_name'])?>" />
              {/IF}
              <input type="submit" class="PhorumSubmit" style="font-size: {smallfontsize}" name="rcpt_add" value="{LANG->PMAddRecipient}" />
              {! Always show recipient list on a separate line}
              {IF RECIPIENT_COUNT}<br style="clear:both" />{/IF}
            </div>
          {/IF}
          {! Display the current list of recipients}
          {LOOP MESSAGE->recipients}
            <div class="phorum-recipientblock">
              {MESSAGE->recipients->display_name}
              <input type="hidden" name="recipients[{MESSAGE->recipients->user_id}]" value="1" />
              <input type="image" src="{delete_image}" name="del_rcpt::{MESSAGE->recipients->user_id}" style="margin-left: 3px;vertical-align:top">
            </div>
          {/LOOP MESSAGE->recipients}
        </td>
      </tr>
      <tr>
        <td>{LANG->Subject}:&nbsp;</td>
        <td><input type="text" id="subject" name="subject" size="50" value="{MESSAGE->subject}" /></td>
      </tr>
      <tr>
        <td colspan="2"><input type="checkbox" name="keep" value="1"{IF MESSAGE->keep} checked="checked" {/IF} /> {LANG->KeepCopy}</td>
      </tr>
    </table>
  </div>

  {HOOK "tpl_pm_editor_before_textarea"}

  <div class="PhorumStdBlock" style="width:99%; text-align: center">
    <textarea id="message" name="message" rows="20" cols="50" style="width: 98%">{MESSAGE->message}</textarea>
    <div style="margin-top: 3px; width:99%" align="right">
      <input name="preview" type="submit" class="PhorumSubmit" value=" {LANG->Preview} " />
      <input name="post" type="submit" class="PhorumSubmit" value=" {LANG->PostPM} " />
    </div>
  </div>
</form>
