<div class="generic">

    {IF FORM->merge_none}
        <h4>{LANG->MergeThread}</h4>
        <p>{LANG->MergeThreadInfo1}</p>
        <strong>{FORM->merge_subject1}</strong>
        <p>{LANG->MergeThreadInfo2}</p>
    {/IF}

    {IF FORM->merge_t1}
        <h4>{LANG->MergeThread}</h4>
        <form method="POST" action="{URL->ACTION}">
            {POST_VARS}
            <input type="hidden" name="thread" value="{FORM->thread_id}" />
            <input type="hidden" name="thread1" value="{FORM->merge_t1}" />
            <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
            <p>{LANG->MergeThreadAction1}</p>
            {LANG->Thread} 1: <strong>{FORM->merge_subject1}</strong><br />
            {LANG->Thread} 2: <strong>{FORM->thread_subject}</strong><br /><br />
            <p>{LANG->MergeThreadAction2}</p>
            <input type="submit" name="move" value="{LANG->MergeThreads}" />
        </form>
    {/IF}

    {IF FORM->thread_id}
        <form method="POST" action="{URL->ACTION}">
            {POST_VARS}
            <input type="hidden" name="thread" value="{FORM->thread_id}" />
            <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
            <input type="submit" name="move" value="{LANG->MergeThreadCancel}" />
        </form>
    {/IF}

</div>
