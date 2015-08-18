<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

// Commit the thread merge.
if (!empty($_POST['thread1']))
{
    settype($_POST['thread1'], 'int');
    settype($_POST['thread'],  'int'); // Thread 2

    $PHORUM['DATA']['OKMSG'] = $PHORUM['DATA']['LANG']['MsgMergeOk'];
    $PHORUM['DATA']['URL']['REDIRECT'] = $PHORUM['DATA']['URL']['LIST'];
    $PHORUM['reverse_threading'] = 0;

    // Get the target thread.
    $target = $PHORUM['DB']->get_message($_POST['thread1'], 'message_id', true);
    if (!$target)
    {
        trigger_error(
            "Can't retrieve target thread " . $_POST['thread1'],
            E_USER_ERROR
        );
        exit;
    }

    // Check if we need to update the subjects of the merged messages.
    $new_subject = NULL;
    if (isset($_POST['update_subjects'])) {
        $new_subject = 'Re: ' . $target['subject'];
    }

    // Get all messages from the thread that we have to merge.
    $merge_messages = $PHORUM['DB']->get_messages($_POST['thread']);
    unset($merge_messages['users']);

    // Create new messages in the target thread for
    // all messages that have to be merged.
    $msgid_translation = array();
    foreach ($merge_messages as $msg)
    {
        $oldid = $msg['message_id'];

        $msg['thread']   = $target['thread'];   // the thread we merge with
        $msg['forum_id'] = $target['forum_id']; // forum_id of the new thread
        $msg['sort']     = $target['sort'];     // sort type of the new thread

        if($msg['message_id'] == $msg['thread']) {
            $msg['parent_id']=$target['thread'];
        } elseif(isset($msgid_translation[$msg['parent_id']])) {
            $msg['parent_id']=$msgid_translation[$msg['parent_id']];
        } else {
            $msg['parent_id']=$msg['thread'];
        }

        unset($msg['message_id']);
        unset($msg['modifystamp']);

        if ($new_subject) $msg['subject'] = $new_subject;

        $PHORUM['DB']->post_message($msg, TRUE);

        // Link attached files to the new message id.
        $linked_files = $PHORUM['DB']->get_message_file_list($oldid);
        foreach ($linked_files as $linked_file) {
            $PHORUM['DB']->file_link(
                $linked_file['file_id'], $msg['message_id'],
                PHORUM_LINK_MESSAGE
            );
        }

        // save the new message-id for later use
        $msgid_translation[$oldid] = $msg['message_id'];
    }

    // deleting messages which are now doubled
    $PHORUM['DB']->delete_message($_POST['thread'], PHORUM_DELETE_TREE);

    // Update message count / stats.
    $PHORUM['DB']->update_forum_stats(true);

    // Change forum_id for the following calls to update the right forum.
    $PHORUM['forum_id'] = $target['forum_id'];

    // update message count / stats
    phorum_api_thread_update_metadata($target['thread']);
    $PHORUM['DB']->update_forum_stats(true);

    /*
     * [hook]
     *     after_merge
     *
     * [description]
     *     This hook can be used for performing actions on
     *     merging threads
     *
     * [category]
     *     Moderation
     *
     * [when]
     *     In <filename>moderation.php</filename>, right after two threads have
     *     been merged by a moderator.
     *
     * [input]
     *     An array with the translated message-ids;
     *     old-message_id -> new-message_id
     *
     * [output]
     *     Same as input.
     */
    phorum_api_hook('after_merge', $msgid_translation);
}
// Cancel the thread merge.
else
{
    $PHORUM['DATA']['OKMSG'] = $PHORUM["DATA"]['LANG']['MsgMergeCancel'];
    $PHORUM['DATA']["URL"]["REDIRECT"] = $PHORUM["DATA"]["URL"]["LIST"];
}

// Unset temporary moderator data.
phorum_api_user_delete_setting('merge_t1');

?>
