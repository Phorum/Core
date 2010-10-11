<?php
/**
 * This file implements the "block_replay" spam hurdle. This hurdle will
 * block form post replay attacks, by handing out keys to forms,
 * which can only be used once to submit a form. If a spam bot tries to
 * replay a form post, the post key will no longer be valid and the
 * form post is blocked.
 *
 * Note that the default spam hurdles data cannot be used indefinitely
 * to replay form posts (because of the TTL). However, this hurdle will
 * make sure that the data can *never* be used twice, while the default
 * spam hurdles data remains usable during its configured TTL.
 *
 * The advantage of the default TTL schema, is that no database storage
 * is needed to record what keys have been used. This hurdle does need
 * this storage, so it will result in extra database activity.
 */ 

function spamhurdle_block_replay_init($data)
{
    $data['key'] = spamhurdles_generate_key();

    return $data;
}

function spamhurdle_block_replay_check_form($data)
{
    global $PHORUM;
    $lang = $PHORUM['DATA']['LANG']['mod_spamhurdles'];
    $error = $lang['PostingRejected'];

    // If an error is already set in the data (by another spam hurdle),
    // then do not run this spam hurdle check for now.
    if ($data['error']) return $data;

    // Initialize the database layer. This will handle automatic installation
    // and upgrading of the database structure. If the initialization fails,
    // we will simply ignore the block_replay spam hurdle.
    if (!spamhurdles_db_init()) return $data;

    // Check if the key was already used for posting a form.
    $used = spamhurdles_db_get($data['key']);
    if ($used) {
        $data['error']  = $error;
        $data['status'] = SPAMHURDLES_FATAL;
        $data['log'][]  =
            "The form posting key \"{$data['key']}\" was used multiple " .
            "times for submitting a form. This might be because of a user " .
            "that used the back button and accidentally reposted a form " .
            "or because of a spam bot that is trying to repost old form data.";
    }

    return $data;
}

function spamhurdle_block_replay_after_post($data)
{
    global $PHORUM;

    // Register the used key in the database.
    if (!spamhurdles_db_init()) return $data;
    spamhurdles_db_put(
        $data['key'], TRUE,
        $PHORUM['mod_spamhurdles']['key_max_ttl']
    );

    return $data;
}

function spamhurdle_block_replay_collect_garbage($data)
{
    spamhurldes_db_remove_expired();
    return $data;
}

?>
