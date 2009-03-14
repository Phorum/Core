{IF NOT PRINTVIEW}
  <div class="PhorumNavBlock" style="text-align: left;">
    <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->LIST}">{LANG->MessageList}</a>&bull;<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{IF LOGGEDIN true}<a class="PhorumNavLink" href="{URL->MARKTHREADREAD}">{LANG->MarkThreadRead}</a>&bull;<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogOut}</a>{ELSE}<a class="PhorumNavLink" href="{URL->LOGINOUT}">{LANG->LogIn}</a>{/IF}&bull;<a class="PhorumNavLink" href="{URL->PRINTVIEW}" target="_blank">{LANG->PrintView}</a>
  </div>
{/IF}
{IF MESSAGE->is_unapproved}
  <div class="PhorumStdBlock">
    <div class="PhorumReadBodyHead"><strong>{LANG->UnapprovedMessage}</strong></div>
  </div>
{/IF}
<div class="PhorumStdBlock">
  <div class="PhorumReadBodySubject">{MESSAGE->subject}</div>
  <div class="PhorumReadBodyHead">{LANG->Postedby}:
    <strong>
      {IF MESSAGE->URL->PROFILE}<a href="{MESSAGE->URL->PROFILE}">{/IF}
        {MESSAGE->author}
      {IF MESSAGE->URL->PROFILE}</a>{/IF}
    </strong> ({MESSAGE->ip})</div>
  <div class="PhorumReadBodyHead">{LANG->Date}: {MESSAGE->datestamp}</div><br />
  <div class="PhorumReadBodyText">{MESSAGE->body}</div><br />
  {IF MESSAGE->attachments}
    {LANG->Attachments}:
    {VAR MESSAGE_ATTACHMENTS MESSAGE->attachments}
    {LOOP MESSAGE_ATTACHMENTS}
      <a href="{MESSAGE_ATTACHMENTS->url}">{MESSAGE_ATTACHMENTS->name} ({MESSAGE_ATTACHMENTS->size})</a>&nbsp;&nbsp;
    {/LOOP MESSAGE_ATTACHMENTS}
  {/IF}
</div>
{IF NOT PRINTVIEW}
  {IF MODERATOR true}
    <div class="PhorumReadNavBlock" style="text-align: left;">
      <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Moderate}:</span>&nbsp;{IF MESSAGE->threadstart true}<a class="PhorumNavLink" href="{MESSAGE->URL->DELETE_THREAD}">{LANG->DeleteThread}</a>&bull;{IF TOPIC->URL->MOVE}<a class="PhorumNavLink" href="{TOPIC->URL->MOVE}">{LANG->MoveThread}</a>&bull;{/IF}<a class="PhorumNavLink" href="{TOPIC->URL->MERGE}">{LANG->MergeThread}</a>&bull;{IF MESSAGE->closed false}<a class="PhorumNavLink" href="{TOPIC->URL->CLOSE}">{LANG->CloseThread}</a>{ELSE}<a class="PhorumNavLink" href="{TOPIC->URL->REOPEN}">{LANG->ReopenThread}</a>{/IF}{ELSE}<a class="PhorumNavLink" href="{MESSAGE->URL->DELETE_MESSAGE}">{LANG->DeleteMessage}</a>&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->DELETE_THREAD}">{LANG->DelMessReplies}</a>&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->SPLIT}">{LANG->SplitThread}</a>{/IF}{IF MESSAGE->is_unapproved}&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->APPROVE}">{LANG->ApproveMessage}</a>{ELSE}&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->HIDE}">{LANG->HideMessage}</a>{/IF}&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->EDIT}">{LANG->EditPost}</a>
    </div>
  {/IF}
  <div class="PhorumNavBlock">
    <div style="float: right;">
      <span class="PhorumNavHeading">{LANG->Navigate}:</span>&nbsp;<a class="PhorumNavLink" href="{MESSAGE->URL->PREV}">{LANG->PreviousMessage}</a>&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->NEXT}">{LANG->NextMessage}</a>
    </div>
    <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>&nbsp;<a class="PhorumNavLink" href="{MESSAGE->URL->REPLY}">{LANG->Reply}</a>&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->QUOTE}">{LANG->QuoteMessage}</a>{IF LOGGEDIN}{IF MESSAGE->URL->PM}&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->PM}">{LANG->PrivateReply}</a>{/IF}&bull;<a class="PhorumNavLink" href="{TOPIC->URL->FOLLOW}">{LANG->FollowThread}</a>{IF MESSAGE->URL->REPORT}&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->REPORT}">{LANG->Report}</a>{/IF}{/IF}{IF MESSAGE->edit 1}&bull;<a class="PhorumNavLink" href="{MESSAGE->URL->EDIT}">{LANG->EditPost}</a>{/IF}
  </div>
{/IF}
<br /><br />
<table class="PhorumStdTable" cellspacing="0">
  <tr>
    <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
    {IF VIEWCOUNT_COLUMN}
      <th class="PhorumTableHeader" align="center">{LANG->Views}</th>
    {/IF}
    <th class="PhorumTableHeader" align="left" nowrap>{LANG->WrittenBy}</th>
    <th class="PhorumTableHeader" align="left" nowrap>{LANG->Posted}</th>
  </tr>
  {LOOP MESSAGES}
    {IF altclass ""}
      {VAR altclass "Alt"}
    {ELSE}
      {VAR altclass ""}
    {/IF}
    <tr>
      <td class="PhorumTableRow{altclass}" style="padding-left: {MESSAGES->indent_cnt}px">
        {marker}
        <?php
          if($PHORUM['TMP']['MESSAGES']['message_id'] == $PHORUM['DATA']['MESSAGE']['message_id']) {
            echo '<b>'. $PHORUM['TMP']['MESSAGES']['subject'].'</b>';
          } else {
        ?>
            <a href="{MESSAGES->URL->READ}">{MESSAGES->subject}</a>
            <span class="PhorumNewFlag">{MESSAGES->new}</span>
        <?php
          }
        ?>
        {IF MESSAGES->is_unapproved} ({LANG->UnapprovedMessage}){/IF}
      </td>
      {IF VIEWCOUNT_COLUMN}
        <td class="PhorumTableRow{altclass}" nowrap="nowrap" align="center" width="80">{MESSAGES->viewcount}</td>
      {/IF}
      <td class="PhorumTableRow{altclass}" nowrap="nowrap" width="150">
        {IF MESSAGES->URL->PROFILE}<a href="{MESSAGES->URL->PROFILE}">{/IF}
          {MESSAGES->author}
        {IF MESSAGES->URL->PROFILE}</a>{/IF}
      </td>
      <td class="PhorumTableRow{altclass} PhorumSmallFont" nowrap="nowrap" width="150">{MESSAGES->short_datestamp}</td>
    </tr>
  {/LOOP MESSAGES}
</table><br /><br />
