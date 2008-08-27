<div align="center">
  {INCLUDE "posting_menu"}
  <form method="POST" action="{URL->ACTION}">
    {POST_VARS}
    <input type="hidden" name="thread" value="{FORM->thread_id}" />
    <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
    <div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;">
      <span class="PhorumHeadingLeft">{LANG->MoveThread}</span>
    </div>
    <div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
      <div class="PhorumFloatingText">
        {LANG->MoveThreadTo}:<br />
        <strong>{FORM->subject}</strong><br /><br />
        <select name="moveto">
          <option value="0">{LANG->SelectForum}</option>
                {LOOP FORUMS}
                    {IF FORUMS->folder_flag}
                        <optgroup label="{FORUMS->indent_spaces}{FORUMS->name}"></optgroup>
                    {ELSE}
                        <option value="{FORUMS->forum_id}"{IF FORUMS->selected} selected="selected"{/IF}>{FORUMS->indent_spaces}{FORUMS->name}</option>
                    {/IF}
                {/LOOP FORUMS}
        </select><br /><br />
        <input type="checkbox" name="create_notification" value="1">{LANG->MoveNotification}<br /><br />
        <input type="submit" class="PhorumSubmit" name="move" value="{LANG->MoveThread}" />
      </div>
    </div>
  </form>
</div>
