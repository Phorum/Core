<div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: right; border-top: none">
  {HOOK "tpl_editor_buttons"}
  <input type="submit" name="preview" class="PhorumSubmit" tabindex="1" value=" {LANG->Preview} " />
  <input type="submit" name="finish" class="PhorumSubmit" tabindex="2" value=" {POSTING->submitbutton_text} " />
  {IF SHOW_CANCEL_BUTTON}
    <input type="submit" name="cancel" onclick="return confirm('{LANG->CancelConfirm}')" class="PhorumSubmit" value=" {LANG->Cancel} " />
  {/IF}
</div>
