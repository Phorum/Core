<div align="center">
  {INCLUDE "posting_menu"}
  <form method="post" action="{URL->ACTION}">
    {POST_VARS}
    <input type="hidden" name="thread" value="{FORM->thread_id}" />
    <input type="hidden" name="message" value="{FORM->message_id}" />
    <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
    <div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->SplitThread}</span></div>
    <div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
      <div class="PhorumFloatingText">
        {LANG->SplitThreadInfo}<br /><br />
        {LANG->Message}: '{FORM->message_subject}'<br /><br />

        <p>
          <label>
            {LANG->SplitThreadNewSubject}:
            <input type="text" size="40" name="new_subject"
                   value="{FORM->new_message_subject}" />
          </label>
        </p>

        <p>
          <label>
            <input type="checkbox" name="update_subjects">
            {LANG->SplitThreadUpdateSubjects}
          </label>
        </p>

        <input type="submit" class="PhorumSubmit" name="move" value="{LANG->SplitThread}" />
      </div>
    </div>
  </form>
</div>
