<div id="phorum-post-form" align="center">
  {IF ERROR}<div class="PhorumUserError">{ERROR}</div>{/IF}
  {IF OKMSG}<div class="PhorumOkMsg">{OKMSG}</div>{/IF}
  {IF PREVIEW}
    {INCLUDE "posting_preview"}
  {/IF}
  {IF NOT PRINTVIEW}
  <form id="post_form" name="post_form" action="{URL->ACTION}" method="post" enctype="multipart/form-data">
    {POST_VARS}
    {INCLUDE "posting_menu"}
    {INCLUDE "posting_messageform"}
    {INCLUDE "posting_buttons"}
  </form>
  {/IF}
</div>
