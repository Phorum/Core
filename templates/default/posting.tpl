{! TODO this should go in css.tpl }
<style type="text/css">

.PhorumAttachmentRow {
    border-bottom: 1px solid {altbackcolor};
    padding: 3px 0px 3px 0px;
}

input.PhorumAttachmentButton {
    border: none;
    cursor: hand;
    cursor: pointer;
    background-color: transparent;
}

input.PhorumSubmitDisabled {
    color: {navhoverlinkcolor};
    background-color: {navhoverbackcolor};
}

</style>

<div id="phorum-post-form" align="center">

{IF ERROR}<div class="PhorumUserError">{ERROR}</div>{/IF}
{IF OKMSG}<div class="PhorumOkMsg">{OKMSG}</div>{/IF}

  {IF PREVIEW}
    {include posting_preview}
  {/IF}

  <form id="post_form" action="{URL->ACTION}" method="post"
   enctype="multipart/form-data">
  {POST_VARS}

  {include posting_menu}

  {include posting_messageform}

  {include posting_buttons}

  </form>

</div>
