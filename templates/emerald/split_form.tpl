<!-- BEGIN TEMPLATE split_form.tpl -->
<div class="generic">
    <h4>{LANG->SplitThread}</h4>
    <form method="POST" action="{URL->ACTION}">
        {POST_VARS}
        <input type="hidden" name="thread" value="{FORM->thread_id}" />
        <input type="hidden" name="message" value="{FORM->message_id}" />
        <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
        <p>{LANG->SplitThreadInfo}</p>
        <p>{LANG->Message}: <strong>{FORM->message_subject}</strong></p>
        <input type="submit" name="move" value="{LANG->SplitThread}" />
    </form>
</div>
<!-- END TEMPLATE split_form.tpl -->
