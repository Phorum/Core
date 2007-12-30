<div align="center">
  {INCLUDE "posting_menu"}
  {IF FORM->merge_none}
    <div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->MergeThread}</span></div>
    <div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
      <div class="PhorumFloatingText">
        <p>{LANG->MergeThreadInfo1}</p>
        <strong>{FORM->merge_subject1}</strong>
        <p>{LANG->MergeThreadInfo2}</p>   
      </div>
    </div>
  {/IF}
  {IF FORM->merge_t1}
    <form method="POST" action="{URL->ACTION}">
      {POST_VARS}
      <input type="hidden" name="thread" value="{FORM->thread_id}" />
      <input type="hidden" name="thread1" value="{FORM->merge_t1}" />
      <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
      <div class="PhorumStdBlockHeader PhorumNarrowBlock" style="text-align: left;"><span class="PhorumHeadingLeft">{LANG->MergeThread}</span></div>
      <div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left;">
        <div class="PhorumFloatingText">
            <p>{LANG->MergeThreadAction1}</p>
            {LANG->Thread} 1: <strong>{FORM->merge_subject1}</strong><br />
            {LANG->Thread} 2: <strong>{FORM->thread_subject}</strong><br /><br />
            <p>{LANG->MergeThreadAction2}</p>
          <input type="submit" class="PhorumSubmit" name="move" value="{LANG->MergeThread}" />
        </div>
      </div>
    </form>
  {/IF}
  {IF FORM->thread_id}
    <div class="PhorumFloatingText">
      <form method="POST" action="{URL->ACTION}">
        {POST_VARS}
        <input type="hidden" name="thread" value="{FORM->thread_id}" />
        <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
        <input type="submit" class="PhorumSubmit" name="move" value="{LANG->MergeThreadCancel}" />
      </form>
    </div>
  {/IF}
</div>
