{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

{IF PREVIEW}

    <div class="information">
        {LANG->PreviewExplain}
    </div>

    <div class="message">

        <div class="generic">

            <table border="0" cellspacing="0">
                <tr>
                    <td width="100%">
                        <div class="message-author icon-user">
                            {PREVIEW->author}
                        </div>
                        <div class="message-date">{PREVIEW->datestamp}</div>
                    </td>
                    <td class="message-user-info" nowrap="nowrap">
                    </td>
                </tr>
            </table>
        </div>

        <div class="message-body">

            {PREVIEW->body}

            {IF PREVIEW->attachments}
                <div class="attachments">
                    {LANG->Attachments}:<br/>
                    {LOOP PREVIEW->attachments}
                        <a href="{PREVIEW->attachments->url}">{LANG->AttachOpen}</a> | <a href="{PREVIEW->attachments->download_url}">{LANG->AttachDownload}</a> -
                        {PREVIEW->attachments->name}
                        ({PREVIEW->attachments->size})</a><br/>
                    {/LOOP PREVIEW->attachments}
                </div>
            {/IF}

        </div>

    </div>

{/IF}

<div id="post">

    <form id="post-form" name="post_form" action="{URL->ACTION}" method="post" enctype="multipart/form-data">

        {POST_VARS}

        <div class="generic">
                {IF SHOW_SPECIALOPTIONS}

                  <div id="post-moderation">
                    <small>
                    {LANG->Special}:<br />

                    {IF OPTION_ALLOWED->sticky}
                    <input type="checkbox" name="sticky"
                     id="phorum_sticky" value="1"
                     {IF POSTING->special "sticky"}checked="checked"{/IF} />
                    <label for="phorum_sticky">{LANG->MakeSticky}</label>
                    <br />
                    {/IF}

                    <input type="checkbox" id="allow-reply" name="allow_reply" value="1" {IF POSTING->allow_reply} checked="checked"{/IF} /> <label for="allow-reply">{LANG->AllowReplies}</label>
                    </small>
                  </div>
                {/IF}
                <small>
                {IF MODE "moderation"}
                  {LANG->YourName}:<br/>
                {ELSE}
                  {LANG->Author}:<br />
                {/IF}
                {IF OPTION_ALLOWED->edit_author}
                    <input type="text" name="author" size="30" value="{POSTING->author}" />
                {ELSE}
                    <big><strong>{POSTING->author}</strong></big><br />
                {/IF}
                <br/>

                {IF MODE "post" OR MODE "reply"}

                    {IF NOT LOGGEDIN}

                        {LANG->YourEmail}:<br />
                        <input type="text" name="email" size="30" value="{POSTING->email}" /><br />
                        <br />

                    {/IF}

                {ELSEIF MODE "moderation"}

                    {IF POSTING->user_id 0}

                        {LANG->Email}:<br />
                        <input type="text" name="email" size="30" value="{POSTING->email}" /><br />
                        <br />

                    {/IF}

                {/IF}

                {LANG->Subject}:<br />
                <input type="text" name="subject" id="subject" size="50" value="{POSTING->subject}" /><br />
                <br />

                {HOOK "tpl_editor_after_subject"}

                </small>
                {IF POSTING->user_id}

                    <small>{LANG->Options}:</small><br />

                    {IF OPTION_ALLOWED->subscribe}

                        <input type="checkbox" id="subscription-follow" name="subscription_follow" value="1" {IF POSTING->subscription}checked="checked"{/IF} {IF OPTION_ALLOWED->subscribe_mail}onclick="phorum_subscription_displaystate()"{/IF} /> <label for="subscription-follow"><small>{LANG->FollowThread}</small></label><br />

                        {IF OPTION_ALLOWED->subscribe_mail}
                          <div id="subscription-mail-div">
                            <img src="{URL->TEMPLATE}/images/tree-L.gif" border="0" alt="tree-L" />
                            <input type="checkbox" id="subscription-mail" name="subscription_mail" value="1" {IF POSTING->subscription "message"}checked="checked"{/IF} /> <label for="subscription-mail"><small>{LANG->EmailReplies}</small></label>
                          </div>

                          <script type="text/javascript">
                          // <![CDATA[
                          function phorum_subscription_displaystate() {
                            if (document.getElementById) {
                              var f = document.getElementById('subscription-follow');
                              var d = document.getElementById('subscription-mail-div');
                              var e = document.getElementById('subscription-mail');
                              d.style.display  = f.checked ? 'block' : 'none';
                            }
                          }

                          // Setup initial display state for subscription options.
                          phorum_subscription_displaystate();
                          // ]]>
                          </script>
                        {/IF}
                    {/IF}

                    <input type="checkbox" id="show-signature" name="show_signature" value="1" {IF POSTING->show_signature} checked="checked"{/IF} /> <label for="show-signature"><small>{LANG->AddSig}</small></label><br />
                    <br/>

                {/IF}

            {IF ATTACHMENTS}
                <small>{LANG->Attachments}:</small><br />
                {IF POSTING->attachments}
                    <table id="attachment-list" cellspacing="0">
                      {VAR LIST POSTING->attachments}
                      {LOOP LIST}
                        {IF LIST->keep}
                          <tr>
                            <td>{LIST->name} ({LIST->size})</td>
                            <td align="right">
                              {HOOK "tpl_editor_attachment_buttons" LIST}
                              <input type="submit" name="detach:{LIST->file_id}" value="{LANG->Detach}" />
                            </td>
                          </tr>
                        {/IF}
                      {/LOOP LIST}
                    </table>
                    {VAR AttachPhrase LANG->AttachAnotherFile}
                {ELSE}
                    {VAR AttachPhrase LANG->AttachAFile}
                {/IF}

                {IF ATTACHMENTS_FULL}
                    <strong>{LANG->AttachFull}</strong><br />
                {ELSE}
                    <script type="text/javascript">
                    //<![CDATA[
                      function phorumShowAttachForm() {
                        document.getElementById('attach-link').style.display='none';
                        document.getElementById('attach-form').style.display='block';
                      }
                      document.write("<div id=\"attach-link\" class=\"attach-link\" style=\"display: block;\"><a href=\"javascript:phorumShowAttachForm();\"><b>{AttachPhrase} ...<\/b><\/a><\/div>\n");
                      document.write("<div id=\"attach-form\" style=\"display: none;\">");
                    // ]]>
                    </script>
                    <div class="attach-link">{AttachPhrase}</div>
                    <ul>
                      {IF EXPLAIN_ATTACH_FILE_TYPES}<li>{EXPLAIN_ATTACH_FILE_TYPES}</li>{/IF}
                      {IF EXPLAIN_ATTACH_FILE_SIZE}<li>{EXPLAIN_ATTACH_FILE_SIZE}</li>{/IF}
                      {IF EXPLAIN_ATTACH_TOTALFILE_SIZE}<li>{EXPLAIN_ATTACH_TOTALFILE_SIZE}</li>{/IF}
                      {IF EXPLAIN_ATTACH_MAX_ATTACHMENTS}<li>{EXPLAIN_ATTACH_MAX_ATTACHMENTS}</li>{/IF}
                    </ul>
                    <input type="file" size="50" name="attachment" />
                    <input type="submit" name="attach" value="{LANG->Attach}" />
                    <script type="text/javascript">
                    //<![CDATA[
                    document.write('<\/div>');
                    // ]]>
                    </script>
                {/IF}

                <br />
            {/IF}

            {HOOK "tpl_editor_before_textarea"}
            <small>{LANG->Message}:</small>
            <div id="post-body">
              <!-- fieldset is a work around for an MSIE rendering bug -->
              <fieldset>
                <textarea name="body" id="body" class="body" rows="15" cols="50">{POSTING->body}</textarea>
              </fieldset>
            </div>

        </div>

        <div id="post-buttons">

            {HOOK "tpl_editor_buttons"}

            <input type="submit" name="preview" value=" {LANG->Preview} " />
            <input type="submit" name="finish" value=" {POSTING->submitbutton_text} " />
            {IF SHOW_CANCEL_BUTTON}
            <input type="submit" name="cancel" onclick="return confirm('{LANG->CancelConfirm}')" value=" {LANG->Cancel} " />
            {/IF}

        </div>

    </form>

</div>

{IF MODERATED}
    <div class="notice">{LANG->ModeratedForum}</div>
{/IF}

