<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

// Fetch the POST data.
// Note: only "moveto" and "create_notification" are guaranteed to be
// available. The other options were added in Phorum 5.3.
// Because we promised that templates would remain the same in the
// transition from 5.2 to 5.3, missing post data must not be treated
// as erroneous.
$moveto        = (int) $_POST['moveto'];
$enable_notify = !empty($_POST['create_notification']);
$enable_hide   = !empty($_POST['enable_hide_after']);
$hide_period   = !empty($_POST['hide_period']) && $enable_hide
               ? (int) $_POST['hide_period'] * 24 * 3600 : 0;

// These template variables are setup for cases where we find
// errors in the input.
$PHORUM['DATA']['FORM']['moveto']              = $moveto;
$PHORUM['DATA']['FORM']['create_notification'] = $enable_notify;
$PHORUM['DATA']['FORM']['enable_hide_after']   = $enable_hide;
$PHORUM['DATA']['FORM']['hide_period']         = $hide_period;

// A forum must be selected.
if (empty($moveto)) {
    $PHORUM['DATA']['ERROR'] = $PHORUM['DATA']['LANG']['MsgMoveSelectForum'];
    return include dirname(__FILE__) . '/move_thread.php';
}

// Retrieve the thread to move.
$message = $PHORUM['DB']->get_message($msgthd_id);
if (!$message || $message['parent_id']) trigger_error(
    "Moderate move: the message to move ($msgthd_id) is not a " .
    "thread starter message", E_USER_ERROR
);

// find out if we have a notification-message already in this
// target-forum for this thread ... it doesn't make sense to keep this
// message any longer as the thread has reappeared on its original location
$temp_forum_id = $PHORUM['forum_id'];
$PHORUM['forum_id'] = $moveto;
$check_messages = $PHORUM['DB']->get_messages($msgthd_id);
unset($check_messages['users']);

// ok, we found exactly one message of this thread in the target forum
if (is_array($check_messages) && count($check_messages) == 1) {
    // ... going to delete it
    $tmp_message = array_shift($check_messages);
    $PHORUM['DB']->delete_message($tmp_message['message_id']);
}

// Restore the original forum_id.
$PHORUM['forum_id'] = $temp_forum_id;

// Handle moving the thread to the target forum.
$PHORUM['DB']->move_thread($msgthd_id, $moveto);

// Create a new message in place of the old one to notify
// visitors that the thread was moved.
if ($enable_notify)
{
    $newmessage                = $message;
    $newmessage['body']        = ' -- moved topic -- ';
    $newmessage['moved']       = 1;
    $newmessage['hide_period'] = $hide_period;
    $newmessage['sort']        = PHORUM_SORT_DEFAULT;
    unset($newmessage['message_id']);

    $PHORUM['DB']->post_message($newmessage);
}

// Setup the success message for the move action.
$PHORUM['DATA']['OKMSG'] = $PHORUM['DATA']['LANG']['MsgMoveOk'];
$PHORUM['DATA']['URL']['REDIRECT'] = $PHORUM['DATA']['URL']['LIST'];

/*
 * [hook]
 *     move_thread
 *
 * [description]
 *     This hook can be used for performing actions like sending
 *     notifications or for making log entries after moving a
 *     thread.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>moderation.php</filename>, right after a thread
 *     has been moved by a moderator.
 *
 * [input]
 *     The id of the thread that has been moved (read-only).
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_move_thread($msgthd_id)
 *     {
 *         global $PHORUM;
 *
 *         // Log the moved thread id
 *         $PHORUM["mod_foo"]["moved_threads"][] = $msgthd_id;
 *         $PHORUM['DB']->update_settings(array(
 *             "mod_foo" => $PHORUM["mod_foo"]
 *         ));
 *
 *         return $msgthd_id;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM['hooks']['move_thread'])) {
    phorum_api_hook('move_thread', $msgthd_id);
}

// Register the messages for which the message cache must be cleared.
foreach ($message['meta']['message_ids'] as $message_id) {
    $invalidate_message_cache[] = array(
        'message_id' => $message_id,
        'forum_id'   => $message['forum_id']
    );
}

?>
