<!-- BEGIN TEMPLATE merge_form.tpl -->

<div class="generic">

    {IF FORM->merge_none}
        <p>{LANG->MergeThreadInfo1}</p>
        <p>{LANG->Thread} 1: <strong>{FORM->merge_subject1}</strong></p>
        <p>{LANG->MergeThreadInfo2}</p>
    {/IF}

    {IF FORM->merge_t1}
        <form method="POST" action="{URL->ACTION}" style="display:inline">
            {POST_VARS}
            <input type="hidden" name="thread" value="{FORM->thread_id}" />
            <input type="hidden" name="thread1" value="{FORM->merge_t1}" />
            <input type="hidden" name="mod_step" value="{FORM->mod_step}" />

            <p>{LANG->MergeThreadAction1}</p>

            <p>
              {LANG->Thread} 1: <strong>{FORM->merge_subject1}</strong><br/>
              {LANG->Thread} 2: <strong>{FORM->thread_subject}</strong>
            </p>

            <p>
              <label>
                <input type="checkbox" name="update_subjects">
                {LANG->MergeThreadUpdateSubjects}
              </label>
            </p>

            <p>{LANG->MergeThreadAction2}</p>
            <input type="submit" name="move" value="{LANG->MergeThreads}" />

        </form>
    {/IF}

    {IF FORM->thread_id}
        <form method="POST" action="{URL->ACTION}" style="display:inline">
            {POST_VARS}
            <input type="hidden" name="thread" value="{FORM->thread_id}" />
            <input type="hidden" name="mod_step" value="{FORM->mod_step}" />
            {IF FORM->merge_none}
              <input type="button" onclick="history.go(-1)"
                     value="{LANG->BacktoForum}" />
            {/IF}
            <input type="submit" name="move" value="{LANG->MergeThreadCancel}" />
        </form>
    {/IF}

</div>
<!-- END TEMPLATE merge_form.tpl -->
