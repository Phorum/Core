<?php

// Note:
// This CAPTCHA needs a public and private key for communicating
// to the reCAPTCHA servers. The configuration of these keys is
// implemented in the settings.php script. That script takes care of
// filling $PHORUM['mod_spamhurdles']['recaptcha_pubkey'] and
// $PHORUM['mod_spamhurdles']['recaptcha_prvkey'].
// This is not a clean separation of functionality. If we are going
// to implement more CAPTCHAs which need extra configuration, then
// we might implement the configuration as a part of the CAPTCHA class.
// For now, this setup will do just fine.

define('RECAPTCHA_LIB', './mods/spamhurdles/captcha/recaptcha-php-1.9/recaptchalib.php');

class captcha_recaptcha
{
    function generate_captcha()
    {
        require_once(RECAPTCHA_LIB);

        $conf = $GLOBALS["PHORUM"]["mod_spamhurdles"]["captcha"];
        $pub = empty($conf['recaptcha_pubkey'])
             ? '' : $conf['recaptcha_pubkey'];

        $html_form = recaptcha_get_html($pub);

        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];

        return array(
            "question"        => 'not used',
            "answer"          => 'not used',
            "input_fieldname" => 'not used',
            "spoken_text"     => 'not used',
            "html_form"       => $html_form,
            "html_after_form" => '',
            "error"           => $lang["CaptchaWrongCode"],
        );
    }

    function check_answer($info)
    {
        require_once(RECAPTCHA_LIB);

        $conf = $GLOBALS["PHORUM"]["mod_spamhurdles"]["captcha"];
        $prv = empty($conf['recaptcha_prvkey'])
             ? '' : $conf['recaptcha_prvkey'];

        $response = recaptcha_check_answer(
            $prv,
            $_SERVER['REMOTE_ADDR'],
            $_POST['recaptcha_challenge_field'],
            $_POST['recaptcha_response_field']
        );

        if (! $response->is_valid) {
          return $info['error'];
        }

        return NULL;
    }
}

?>
