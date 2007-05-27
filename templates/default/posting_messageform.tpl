<div class="PhorumStdBlockHeader PhorumNarrowBlock">
  <table class="PhorumFormTable" cellspacing="0" border="0">
    {! A submit button that will be used to catch users pressing enter }
    <script type="text/javascript">
      document.write('<input type="submit" name="ignore" style="display:none">');
    </script>
    {! Author =================================================================== }
    <tr>
      <td style="white-space: nowrap">{LANG->YourName}:&nbsp;</td>
      <td width="100%">
       {IF OPTION_ALLOWED->edit_author}
        <input type="text" name="author" size="30" value="{POSTING->author}" />
       {ELSE}
        <big><strong>{POSTING->author}</strong></big><br />
       {/IF}
      </td>
    </tr>
    {! Email ==================================================================== }
    {VAR EDIT_EMAIL FALSE}
    {IF MODE "post" OR MODE "reply"}
      {IF NOT LOGGEDIN}
        {VAR EDIT_EMAIL TRUE}
      {/IF}
    {ELSEIF MODE "moderation"}
      {IF POSTING->user_id 0}
        {VAR EDIT_EMAIL TRUE}
      {/IF}
    {/IF}

    {IF EDIT_EMAIL}
    <tr>
      <td style="white-space: nowrap">{LANG->YourEmail}:&nbsp;</td>
      <td width="100%">
        <input type="text" name="email" size="30" value="{POSTING->email}" />
      </td>
    </tr>
    {/IF}
    {! Subject ================================================================== }
    <tr>
      <td style="white-space: nowrap">{LANG->Subject}:&nbsp;</td>
      <td><input type="text" name="subject" id="phorum_subject" size="50" value="{POSTING->subject}" /></td>
    </tr>
    {HOOK "tpl_editor_after_subject"}
    {! Moderator only fields ==================================================== }
    {IF SHOW_THREADOPTIONS}
      <tr>
        <td>{LANG->Special}:&nbsp;</td>
        <td>
          {IF SHOW_SPECIALOPTIONS}{IF OPTION_ALLOWED->sticky}
            <input type="checkbox" name="sticky" id="phorum_sticky" value="1"
             {IF POSTING->special "sticky"}checked="checked"{/IF} />
            <label for="phorum_sticky">{LANG->MakeSticky}</label>
          {/IF}{/IF}
          {IF OPTION_ALLOWED->allow_reply}
            <input type="checkbox" name="allow_reply" value="1" {IF POSTING->allow_reply} checked="checked"{/IF}> {LANG->AllowReplies}
          {/IF}
        </td>
      </tr>
    {/IF}
    {! Email notify ============================================================= }
    {IF POSTING->user_id}
      {IF EMAILNOTIFY}
        <tr>
          <td colspan="2">
            <input type="checkbox" name="email_notify" value="1" {IF POSTING->email_notify} checked="checked"{/IF} /> {LANG->EmailReplies}
          </td>
        </tr>
      {/IF}
    {/IF}
    {! Show signature =========================================================== }
    {IF POSTING->user_id}
      <tr>
        <td colspan="2">
          <input type="checkbox" name="show_signature" value="1" {IF POSTING->show_signature} checked="checked"{/IF} /> {LANG->AddSig}
        </td>
      </tr>
    {/IF}
  </table>
</div>
{! Attachments ============================================================== }
{IF ATTACHMENTS}
  {INCLUDE "posting_attachments"}
{/IF}
{! Body ===================================================================== }
{HOOK "tpl_editor_before_textarea"}
<div class="PhorumStdBlock PhorumNarrowBlock">
  <textarea name="body" id="phorum_textarea" rows="15" cols="50" style="width: 99%">{POSTING->body}</textarea>
  {IF MODERATED}
    {LANG->ModeratedForum}<br />
  {/IF}
</div>
