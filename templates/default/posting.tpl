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

<div align="center">

  {IF PREVIEW}
    {include posting_preview}
  {/IF}

  <form id="post_form" action="{URL->ACTION}" method="post"
   enctype="multipart/form-data">
  {POST_VARS}

  {include posting_menu}

  {include posting_messageform}

  {IF ATTACHMENTS}
    {include posting_attachments}
  {/IF}

  {include posting_buttons}

  </form>

</div>
