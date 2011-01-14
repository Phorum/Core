<?php 

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$movetoid=(int)$_POST['moveto'];

// only do something if a forum was selected
if(empty($movetoid)) {
    $PHORUM['DATA']['MESSAGE']=$PHORUM["DATA"]['LANG']['MsgMoveSelectForum'];
} else {
    $PHORUM['DATA']['OKMSG']=$PHORUM["DATA"]['LANG']['MsgMoveOk'];
    $PHORUM['DATA']["URL"]["REDIRECT"]=$PHORUM["DATA"]["URL"]["LIST"];
    $message = phorum_db_get_message($msgthd_id);

    // find out if we have a notification-message already in this
    // target-forum for this thread ... it doesn't make sense to keep this
    // message any longer as the thread has reappeared on its original location
    $temp_forum_id=$PHORUM['forum_id'];
    $PHORUM['forum_id']=$movetoid;
    $check_messages=phorum_db_get_messages($msgthd_id);

    unset($check_messages['users']);

    // ok, we found exactly one message of this thread in the target forum
    if(is_array($check_messages) && count($check_messages) == 1) {
        // ... going to delete it
        $tmp_message=array_shift($check_messages);
        $retval=phorum_db_delete_message($tmp_message['message_id']);
    }

    $PHORUM['forum_id']=$temp_forum_id;

    // Move the thread to another forum.
    phorum_db_move_thread($msgthd_id, $movetoid);

    // Create a new message in place of the old one to notify
    // visitors that the thread was moved.
    if(isset($_POST['create_notification']) && $_POST['create_notification']) {
        $newmessage = $message;
        $newmessage['body']=" -- moved topic -- ";
        $newmessage['moved']=1;
        $newmessage['sort']=PHORUM_SORT_DEFAULT;
        unset($newmessage['message_id']);

        phorum_db_post_message($newmessage);
    }

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
     *         phorum_db_update_settings(array("mod_foo" => $PHORUM["mod_foo"]));
     *
     *         return $msgthd_id;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["move_thread"])) {
        phorum_api_hook("move_thread", $msgthd_id);
    }

    foreach ($message['meta']['message_ids'] as $message_id) {
        $invalidate_message_cache[] = array(
            "message_id" => $message_id,
            "forum_id"   => $message['forum_id']
        );
    }
}

?>
