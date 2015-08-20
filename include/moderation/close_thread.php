<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$PHORUM['DB']->close_thread($msgthd_id);

$invalidate_message_cache[] = array(
    "message_id" => $msgthd_id,
    "forum_id"   => $PHORUM["forum_id"]
);

$PHORUM['DATA']['OKMSG'] = $PHORUM["DATA"]['LANG']['ThreadClosedOk'];
$PHORUM['DATA']["URL"]["REDIRECT"] = phorum_moderation_back_url();

/*
 * [hook]
 *     close_thread
 *
 * [description]
 *     This hook can be used for performing actions like sending
 *     notifications or making log entries after closing threads.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>moderation.php</filename>, right after a thread has
 *     been closed by a moderator.
 *
 * [input]
 *     The id of the thread that has been closed (read-only).
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_close_thread($msgthd_id)
 *     {
 *         global $PHORUM;
 *
 *         // Log the closed thread id
 *         $PHORUM["mod_foo"]["closed_threads"][] = $msgthd_id;
 *         $PHORUM['DB']->update_settings(array(
 *             "mod_foo" => $PHORUM["mod_foo"]
 *         ));
 *
 *         return $msgthd_id;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["close_thread"])) {
    phorum_api_hook("close_thread", $msgthd_id);
}

?>
