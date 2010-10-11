<?php
/**
 * This function will feed the $say parameter to a speech 
 * synthesizer and send the resulting audio file to the browser
 *
 * @param string $say
 */
function spamhurdles_spoken_captcha($say)
{
    global $PHORUM;
    $conf = $PHORUM["mod_spamhurdles"]["captcha"];

    if ($conf["spoken_captcha"] && file_exists($conf["flite_location"]))
    {
        // Generate the command for building the wav file.
        $tmpfile = tempnam($PHORUM["cache"], 'spokencaptcha_');
        $cmd = escapeshellcmd($conf["flite_location"]);
        $cmd .= " -t " . escapeshellarg($say);
        $cmd .= " -o " . escapeshellarg($tmpfile);

        // Build the wav file.
        system($cmd);

        // Did we succeed in building the wav? Then stream it to the user.
        if (file_exists($tmpfile) and filesize($tmpfile) > 0) {
            header("Content-Type: audio/x-wav");
            header("Content-Disposition: attachment; filename=captchacode.wav");
            header("Content-Length: " . filesize($tmpfile)); 
            readfile($tmpfile);
            unlink($tmpfile);
            exit(0);
        // Something in the setup is apparently wrong here.
        } else {
            die("<h1>Internal Spam Hurdles module error</h1>" .
                "Failed to generate a wave file using flite.\n" .
                "Please contact the site maintainer to report this problem.");
        }
    } else {
        die("<h1>Internal Spam Hurdles module error</h1>" .
            "Spoken captcha requested, but no spoken text is available\n" .
            "or the speech system has not been enabled/configured. " .
            "Please contact the site maintainer to report this problem.");
    }
}
?>
