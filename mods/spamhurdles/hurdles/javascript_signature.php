<?php
/**
 * This file implements the "javascript_signature" spam hurdle. This hurdle
 * lets the browser sign some random data through javascript. Most bots are
 * not capable of interpreting javascript code, so these bots would not
 * return the signed data. If no correctly signed data is in the form post,
 * then it is blocked.
 */ 

function spamhurdle_javascript_signature_javascript_register($data)
{
    $data[] = array(
        'module' => 'spamhurdles',
        'source' => 'file(mods/spamhurdles/hurdles/javascript_signature.js)'
    );

    return $data;
}

function spamhurdle_javascript_signature_init($data)
{
    $rand = spamhurdles_generate_key();
    $data['sig'] = array(
        substr($rand, 0, 8), // used for constructing form element ids
        substr($rand, 8, 8)  // the data that has to be signed
    );
    return $data;
}

function spamhurdle_javascript_signature_build_form($data)
{
    global $PHORUM;

    print spamhurdles_iScramble(
        '<img style="display:none" ' .
        'src="' . $PHORUM['DATA']['URL']['HTTP_PATH'] .
        '/mods/spamhurdles/images/pixel.gif" ' .
        'alt="'.$data['sig'][1].'" ' .
        'id="javascript_signature_data_'.$data['sig'][0].'" />',
        FALSE, FALSE, ''
    );

    return $data;
}

function spamhurdle_javascript_signature_build_after_form($data)
{
    print spamhurdles_iScramble(
        "<script type=\"text/javascript\">\n" .
        "spamhurdle_javascript_signature('{$data['sig'][0]}');\n" .
        "</script>\n"
    );

    return $data;
}

function spamhurdle_javascript_signature_check_form($data)
{
    global $PHORUM;
    $lang = $PHORUM['DATA']['LANG']['mod_spamhurdles'];
    $error = $lang['PostingRejected'] . ' ' . $lang['NeedJavascript'];

    // If an error is already set in the data (by another spam hurdle),
    // then do not run this spam hurdle check for now.
    if ($data['error']) return $data;

    // Check if the signature is available in the POST data.
    $fld = 'javascript_signature_'.$data['sig'][0];
    if (!isset($_POST[$fld]))
    {
        $data['error']  = $error;
        $data['status'] = SPAMHURDLES_FATAL;
        $data['log'][]  =
            "The javascript signature spam hurdle was enabled for the " .
            "form, but the posted form data does not contain the " .
            "expected signature field \"$fld\". This is either a " .
            "spam bot that is not processing the javascript signature " .
            "or a user who does not have JavaScript enabled in his browser.";
    }

    // Check if the signature is what we expected to to be.
    else
    {
        $sig = $_POST[$fld];
        $expected_sig = md5($data['sig'][1]);
        if ($sig != $expected_sig)
        {
            $data['error']  = $error;
            $data['status'] = SPAMHURDLES_FATAL;
            $data['log'][]  =
                "The client posted an incorrect signature for the " .
                "javascript signature spam hurdle. The expected signature " .
                "was \"$expected_sig\", but the client posted \"$sig\". " .
                "This might be a spam bot that is trying to repost " .
                "old formdata.";
        }
    }

    return $data;
}

?>
