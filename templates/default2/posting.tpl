
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
                    {LANG->Attachments}:
                    {LOOP PREVIEW->attachments}
                        <a href="{PREVIEW->attachments->url}">{PREVIEW->attachments->name} ({PREVIEW->attachments->size})</a>&nbsp;&nbsp;
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
        
            <small>
                {IF MODERATOR}
    
                    <div id="post-moderation">
    
                        {LANG->Special}:<br />
                        <select name="special">
                          <option value=""></option>
                          {IF OPTION_ALLOWED->sticky}
                            <option value="sticky"{IF MESSAGE->special "sticky"} selected{/IF}>{LANG->MakeSticky}</option>
                          {/IF}
                          {IF OPTION_ALLOWED->announcement}
                            <option value="announcement" {IF MESSAGE->special "announcement"} selected{/IF}>{LANG->MakeAnnouncement}</option>
                          {/IF}
                        </select><br />
                        <br />
                        
                        <input type="checkbox" id="allow-reply" name="allow_reply" value="1" {IF MESSAGE->allow_reply} checked="checked"{/IF}> <label for="allow-reply">{LANG->AllowReplies}</label>
                    
                    </div>
                    
                {/IF}
            
                {IF MODE "post"}
            
                    {IF NOT LOGGEDIN}
                    
                        {LANG->YourName}:<br />
                        <input type="text" name="author" size="30" value="{MESSAGE->author}" /><br />
                        <br />
                        {LANG->YourEmail}:<br />
                        <input type="text" name="email" size="30" value="{MESSAGE->email}" /><br />
                        <br />
                        
                    {/IF}
    
                {ELSEIF MODE "moderation"}
                
                    {IF MESSAGE->user_id 0}
                    
                        {LANG->Author}:<br />
                        <input type="text" name="author" size="30" value="{MESSAGE->author}" /><br />
                        <br />
                        {LANG->Email}:<br />
                        <input type="text" name="email" size="30" value="{MESSAGE->email}" /><br />
                        <br />
                        
                    {ELSE}
                    
                        {LANG->Author}:<br />
                        <big><strong>{MESSAGE->author}</strong></big><br />
                        <br />
                        
                    {/IF}
                    
                {/IF}
    
                {LANG->Subject}:<br />
                <input type="text" name="subject" id="subject" size="50" value="{MESSAGE->subject}" /><br />
                <br />
                    
                {HOOK "tpl_editor_after_subject"}
    
                {IF MESSAGE->user_id}
                    {LANG->Options}:<br />
                    {IF EMAILNOTIFY}
                        <input type="checkbox" id="email-notify" name="email_notify" value="1" {IF MESSAGE->email_notify} checked="checked"{/IF} /> <label for="email-notify">{LANG->EmailReplies}</label><br />            
                    {/IF}
                    <input type="checkbox" id="show-signature" name="show_signature" value="1" {IF MESSAGE->show_signature} checked="checked"{/IF} /> <label for="show-signature">{LANG->AddSig}</label><br />
                    <br/>
                {/IF}
            </small>

            {IF ATTACHMENTS}
                <small>{LANG->Attachments}:</small><br />            
                {IF MESSAGE->attachments}
                    <table id="attachment-list" cellspacing="0">
                      {VAR LIST MESSAGE->attachments}
                      {LOOP LIST}
                        {IF LIST->keep}
                          <tr>
                            <td>{LIST->name} ({LIST->size})</td>
                            <td align="right">
                              {HOOK "tpl_editor_attachment_buttons" LIST}
                              <input type="submit" name="detach:{LIST->file_id}" value="{LANG->Detach}" class="PhorumSubmit" />
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
                    <strong>{LANG->AttachFull}</strong>
                {ELSE}
                    <script type="text/javascript">
                      function phorumShowAttachForm() {
                        document.getElementById('attach-link').style.display='none';
                        document.getElementById('attach-form').style.display='block';
                      }
                      document.write("<div id=\"attach-link\" class=\"attach-link\" style=\"display: block;\"><a href=\"javascript:phorumShowAttachForm();\"><b>{AttachPhrase} ...<\/b><\/a><\/div>\n");
                      document.write("<div id=\"attach-form\" style=\"display: none;\">");
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
                    <script type="text/javascript">document.write('<\/div>');</script>
                {/IF}
    
                <br />
            {/IF}

            {HOOK "tpl_editor_before_textarea"}
            <small>{LANG->Message}:</small>
            <div id="post-body">
              <!-- fieldset is a work around for an MSIE rendering bug -->
              <fieldset>
                <textarea name="body" id="body" class="body" rows="15" cols="50">{MESSAGE->body}</textarea>
              </fieldset>
            </div>
            
        </div>
        
        <div id="post-buttons">
        
            {HOOK "tpl_editor_buttons"}
            
            <input type="submit" name="preview" class="PhorumSubmit" value=" {LANG->Preview} " />
            <input type="submit" name="finish" class="PhorumSubmit" value=" {MESSAGE->submitbutton_text} " />
            <input type="submit" name="cancel" onclick="return confirm('{LANG->CancelConfirm}')" class="PhorumSubmit" value=" {LANG->Cancel} " />
            
        </div>
        
    </form>

</div>

{IF MODERATED}
    <div class="notice">{LANG->ModeratedForum}</div>
{/IF}

