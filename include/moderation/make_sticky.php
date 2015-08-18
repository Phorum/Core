<?php

if (!defined('PHORUM') || phorum_page !== 'moderation') return;

phorum_api_thread_set_sort($msgthd_id, PHORUM_SORT_STICKY);

$PHORUM['DATA']['OKMSG'] = $PHORUM['DATA']['LANG']['MsgStickyOk'];
$PHORUM['DATA']["URL"]["REDIRECT"] = phorum_moderation_back_url();

/*
 * [hook]
 *     make_sticky
 *
 * [description]
 *     This hook can be used for performing actions like sending
 *     notifications or making log entries after making a message sticky.
 *
 * [category]
 *     Moderation
 *
 * [when]
 *     In <filename>include/moderation/make_sticky.php</filename>,
 *     right after a message has been made sticky by a moderator.
 *
 * [input]
 *     The id of the thread that has to be made sticky.
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_make_sticky($msgthd_id)
 *     {
 *         // ... extra processing for make_sticky operations goes here ...
 *
 *         return $msgthd_id;
 *     }
 *     </hookcode>
 */
if (isset($PHORUM["hooks"]["make_sticky"])) {
    phorum_api_hook("make_sticky", $msgthd_id);
}

?>
