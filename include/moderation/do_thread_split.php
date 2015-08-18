<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

settype($_POST['forum_id'], "int");
settype($_POST['message'],  "int");
settype($_POST['thread'],   "int");

$PHORUM['DATA']['OKMSG'] = $PHORUM["DATA"]['LANG']['MsgSplitOk'];
$PHORUM['DATA']["URL"]["REDIRECT"] = $PHORUM["DATA"]["URL"]["LIST"];

$new_subject = isset($_POST['new_subject']) ? $_POST['new_subject'] : NULL;
$update_subjects = isset($_POST['update_subjects']);

$PHORUM['DB']->split_thread(
    $_POST['message'],
    $_POST['forum_id'],
    $new_subject,
    $update_subjects
);

if ($PHORUM['cache_messages']) {
    $message = $PHORUM['DB']->get_message($_POST['thread']);
    foreach ($message['meta']['message_ids'] as $message_id) {
        phorum_api_cache_remove('message', $message_id);
    }
}

// update message count / stats
phorum_api_thread_update_metadata($_POST['thread']);
phorum_api_thread_update_metadata($_POST['message']);
$PHORUM['DB']->update_forum_stats(TRUE);

/*
 * [hook]
 *     after_split
 *
 * [description]
 *     This hook can be used for performing actions on
 *     splitting threads
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>moderation.php</filename>, right after a thread has
 *     been split by a moderator.
 *
 * [input]
 *     The id of the newly created thread
 *
 * [output]
 *     Same as input.
 */
phorum_api_hook('after_split', $_POST['message']);

?>
