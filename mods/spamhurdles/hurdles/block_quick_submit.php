<?php
/**
 * This file implements the "block_quick_submit" spam hurdle. This hurdle
 * asumes that for humans it takes a little while to type a message and
 * to post it. If a message is posted really quickly after showing
 * a posting form, then this hurdle concludes that it must be a bot that
 * is posting the message and the post is blocked.
 */ 

function spamhurdle_block_quick_submit_javascript_register($data)
{
    $data[] = array(
        'module' => 'spamhurdles',
        'source' => 'file(mods/spamhurdles/hurdles/block_quick_submit.js)'
    );

    return $data;
}

function spamhurdle_block_quick_submit_init($data)
{
    // Record the time at which the form was initialized.
    $data['init_time'] = time();

    return $data;
}

// Disable the post message button as long as the user cannot yet
// post the message according to this spam hurldle check.
function spamhurdle_block_quick_submit_build_after_form($data)
{
    global $PHORUM;

    $delay = $PHORUM['mod_spamhurdles']['key_min_ttl'] -
             (time() - $data['init_time']);

    if ($delay > 0) {
        print "<script type=\"text/javascript\">\n";
        print "spamhurdles_block_quick_submit('{$data['id']}', $delay);\n";
        print "</script>\n";
    }

    return $data;
}

// Check if the form was not submitted too quickly.
function spamhurdle_block_quick_submit_check_form($data)
{
    global $PHORUM;
    $lang = $PHORUM['DATA']['LANG']['mod_spamhurdles'];
    $error = $lang['PostingRejected'] . ' ' .
             $lang['TryResubmit'] . ' ' .
             $lang['ContactSiteOwner'];

    // If an error is already set in the data (by another spam hurdle),
    // then do not run this spam hurdle check for now.
    if ($data['error']) return $data;

    // Check if the form wasn't posted too quickly.
    $min_ttl = $PHORUM['mod_spamhurdles']['key_min_ttl'];
    $delay = $min_ttl - (time() - $data['init_time']);
    if ($delay > 0)
    {
        $data['error']  = $error;
        $data['status'] = SPAMHURDLES_WARNING;
        $data['log'][]  =
            "The form was posted too quickly. This most probably " .
            "indicates a posting bot. The form was posted within " .
            ($min_ttl-$delay) . " second(s), while " .
            "$min_ttl second(s) or more were expected.";
    }

    return $data;
}

?>
