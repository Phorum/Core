<?php
/**
 * This file implements the "block_commented_field" spam hurdle. This hurdle
 * adds a commented form field to the form. Spam bots sometimes make the
 * mistake of interpreting the commented form field as a real form field.
 * If this field is included in the post data, then the form post is blocked.
 */ 

/**
 * build_form: Add a commented form field to the form.
 */
function spamhurdle_block_commented_field_build_form($data)
{
    print "<!-- \n";
    print "<input type=\"text\" name=\"message_body\" value=\"1\">\n";
    print "-->\n";

    return $data;
}

function spamhurdle_block_commented_field_check_form($data)
{
    global $PHORUM;
    $lang = $PHORUM['DATA']['LANG']['mod_spamhurdles'];
    $error = $lang['PostingRejected'];

    // If an error is already set in the data (by another spam hurdle),
    // then do not run this spam hurdle check for now.
    if ($data['error']) return $data;

    // Check if the commented field is posted unexpectedly.
    if (isset($_POST['message_body']))
    {
        $data['error']  = $error;
        $data['status'] = SPAMHURDLES_FATAL;
        $data['log'][]  =
            "An HTML commented form field was submitted. This most " .
            "probably indicates a badly programmed posting bot.";
    }

    return $data;
}

?>
