<div class="pm">

    <h4>{MESSAGE->subject}</h4>

    <div class="message-author icon-user">
        {LANG->From}: <a href="{MESSAGE->URL->FROM}">{MESSAGE->from_username}</a>
    </div>
    <div class="message-author icon-user">
        {LANG->To}:
        {LOOP MESSAGE->recipients}
            <a href="{MESSAGE->recipients->URL->TO}">{MESSAGE->recipients->username}</a>
            {IF USER->user_id MESSAGE->from_user_id}
                {IF NOT MESSAGE->recipients->read_flag}({LANG->PMUnread}){/IF}
            {/IF}
        {/LOOP MESSAGE->recipients}
    </div>
    <div class="message-date">{MESSAGE->date}</div>
</div>

<div class="message-body">

    {MESSAGE->message}
    
</div>


<form action="{URL->ACTION}" method="post">
    {POST_VARS}
    <input type="hidden" name="action" value="list" />
    <input type="hidden" name="folder_id" value="{FOLDER_ID}" />
    <input type="hidden" name="forum_id" value="{FORUM_ID}" />
    <input type="hidden" name="pm_id" value="{MESSAGE->pm_message_id}" />
    {IF FOLDER_IS_INCOMING}
        {VAR MOVE_SUBMIT_NAME "move_message"}
        {INCLUDE "pm_moveselect"}
    {/IF}
    <input type="submit" name="close_message" value="{LANG->PMCloseMessage}" />
    {IF NOT MESSAGE->from_user_id USERINFO->user_id}
        <input type="submit" name="reply" value="{LANG->PMReply}" />
        {IF NOT MESSAGE->recipient_count 1}
            <input type="submit" name="reply_to_all" value="{LANG->PMReplyToAll}" />
        {/IF}
    {/IF}
    <input type="submit" name="delete_message" value="{LANG->Delete}" onclick="return confirm('<?php echo addslashes($PHORUM['DATA']['LANG']['AreYouSure'])?>')" />
  </div>
</form>
