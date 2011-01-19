<!-- BEGIN TEMPLATE move_form.tpl -->

<div class="generic">
  <form method="POST" action="{URL->ACTION}">
    {POST_VARS}
    <input type="hidden" name="thread" value="{FORM->thread_id}" />
    <input type="hidden" name="mod_step" value="{FORM->mod_step}" />

    <p>
      <table class="form">
        <tr>
          <td style="width:1%; white-space:nowrap">
            {LANG->Subject}:
          </td>
          <td>
            <strong>{FORM->subject}</strong>
          </td>
        </tr>
        <tr>
          <td style="width:1%; white-space:nowrap">
            {LANG->Forum}:
          </td>
          <td>
            <strong>{NAME}</strong>
          </td>
        </tr>
      </table>
    </p>

    <p>
      {LANG->MoveThreadTo}:

      <select style="margin-left: 1em" name="moveto">
        <option value="0">{LANG->SelectForum}</option>
          {LOOP FORUMS}
            {IF FORUMS->folder_flag}
              <optgroup label="{FORUMS->indent_spaces}{FORUMS->name}"></optgroup>
            {ELSE}
              <option value="{FORUMS->forum_id}"{IF FORUMS->selected} selected="selected"{/IF}>{FORUMS->indent_spaces}{FORUMS->name}</option>
            {/IF}
          {/LOOP FORUMS}
      </select>
    </p>

    <p>
      <label>
        <input type="checkbox" name="create_notification" value="1"
               id="notify_checkbox"
               {IF FORM->create_notification}checked="checked"{/IF} />
        {LANG->MoveNotification}
      </label>

      <div {IF NOT FORM->create_notification}style="display:none"{/IF} id="move_hide_after">
        <label>
          <input type="checkbox" name="enable_hide_after" value="1"
                 id="enable_hide_after_checkbox"
               {IF FORM->enable_hide_after}checked="checked"{/IF} />
          {LANG->MoveHideAfter}
        </label>

        <select {IF NOT FORM->enable_hide_after}disabled="disabled{/IF}"
                style="margin-left: 1em" name="hide_period"
                id="hide_period_select">
          <option value="1" {IF FORM->hide_period 1}selected="selected"{/IF}>{LANG->relative_one_day}</option>
          <option value="2" {IF FORM->hide_period 2}selected="selected"{/IF}>2 {LANG->relative_days}</option>
          <option value="3" {IF FORM->hide_period 3}selected="selected"{/IF}>3 {LANG->relative_days}</option>
          <option value="4" {IF FORM->hide_period 4}selected="selected"{/IF}>4 {LANG->relative_days}</option>
          <option value="5" {IF FORM->hide_period 5}selected="selected"{/IF}>5 {LANG->relative_days}</option>
          <option value="6" {IF FORM->hide_period 6}selected="selected"{/IF}>6 {LANG->relative_days}</option>
          <option value="7" {IF FORM->hide_period 7}selected="selected"{/IF}>{LANG->relative_one_week}</option>
          <option value="14" {IF FORM->hide_period 14}selected="selected"{/IF}>2 {LANG->relative_weeks}</option>
          <option value="21" {IF FORM->hide_period 21}selected="selected"{/IF}>3 {LANG->relative_weeks}</option>
          <option value="28" {IF FORM->hide_period 28}selected="selected"{/IF}>{LANG->relative_one_month}</option>
          <option value="182" {IF FORM->hide_period 182}selected="selected"{/IF}>6 {LANG->relative_months}</option>
          <option value="365" {IF FORM->hide_period 365}selected="selected"{/IF}>{LANG->relative_one_year}</option>
        </select>
      </div>
    </p>

    <input type="submit" name="move" value="{LANG->MoveThread}" />
    <input type="submit" name="cancel" value="{LANG->Cancel}" />
  </form>
</div>

<script type="text/javascript">
$PJ('#notify_checkbox').click(function () {
  if ($PJ(this).attr('checked')) {
    $PJ('#move_hide_after').fadeIn();
  } else {
    $PJ('#move_hide_after').fadeOut();
  }
});

$PJ('#enable_hide_after_checkbox').click(function () {
  if ($PJ(this).attr('checked')) {
    $PJ('#hide_period_select').removeAttr('disabled');
  } else {
    $PJ('#hide_period_select').attr('disabled', 'disabled');
  }
});
</script>

<!-- END TEMPLATE move_form.tpl -->
