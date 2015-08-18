<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

$old_message = $PHORUM['DB']->get_message($msgthd_id);
$newpost     = array("status" => PHORUM_STATUS_HIDDEN);
$mids        = $PHORUM['DB']->get_messagetree(
                   $msgthd_id, $old_message["forum_id"]);

// Make an array from the string.
$mids_arr=  explode(",",$mids);

// Count the entries for later use.
$num_hidden = count($mids_arr);
foreach($mids_arr as $key => $mid) {
    // setting the new status
    $PHORUM['DB']->update_message($mid, $newpost);

}

/*
 * [hook]
 *     hide_thread
 *
 * [description]
 *     This hook can be used for performing actions like sending
 *     notifications or making log entries after hiding a message.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>moderation.php</filename>, right after a message has
 *     been hidden by a moderator.
 *
 * [input]
 *     The id of the thread that has been hidden (read-only).
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_hide_thread($msgthd_id)
 *     {
 *         global $PHORUM;
 *
 *         // Log the hidden thread id
 *         $PHORUM["mod_foo"]["hidden_threads"][] = $msgthd_id;
 *         $PHORUM['DB']->update_settings(array(
 *             "mod_foo" => $PHORUM["mod_foo"]
 *         ));
 *
 *         return $msgthd_id;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["hide_thread"])) {
    phorum_api_hook("hide_thread", $msgthd_id);
}

// updating the thread-info
phorum_api_thread_update_metadata($old_message['thread']);

// updating the forum-stats
$PHORUM['DB']->update_forum_stats(false, "-$num_hidden");

$PHORUM['DATA']['OKMSG'] =
    "$num_hidden " .
    $PHORUM['DATA']['LANG']['MsgHiddenOk'];

if (isset($PHORUM['args']["prepost"])) {
    $PHORUM['DATA']["URL"]["REDIRECT"] = phorum_api_url(
        PHORUM_CONTROLCENTER_URL,"panel=".PHORUM_CC_UNAPPROVED);
} else {
    $PHORUM['DATA']["URL"]["REDIRECT"] = $PHORUM["DATA"]["URL"]["LIST"];
}

?>
