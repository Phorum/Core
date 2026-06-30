<?php

// reCAPTCHA v2 ("I'm not a robot" checkbox) implementation.
// Configuration: set recaptcha_sitekey and recaptcha_secret in the
// Spam Hurdles admin settings page.

class captcha_recaptcha
{
    function generate_captcha()
    {
        $conf = $GLOBALS['PHORUM']['mod_spamhurdles']['captcha'];
        $sitekey = htmlspecialchars(
            empty($conf['recaptcha_sitekey']) ? '' : $conf['recaptcha_sitekey'],
            ENT_QUOTES, 'UTF-8'
        );

        $lang = $GLOBALS['PHORUM']['DATA']['LANG']['mod_spamhurdles'];

        $html_form =
            '<script src="https://www.google.com/recaptcha/api.js" async defer></script>' .
            '<div class="g-recaptcha" data-sitekey="' . $sitekey . '"></div>';

        return array(
            'question'        => 'not used',
            'answer'          => 'not used',
            'input_fieldname' => 'not used',
            'spoken_text'     => 'not used',
            'html_form'       => $html_form,
            'html_after_form' => '',
            'error'           => $lang['CaptchaWrongCode'],
        );
    }

    function check_answer($info)
    {
        $conf   = $GLOBALS['PHORUM']['mod_spamhurdles']['captcha'];
        $secret = empty($conf['recaptcha_secret']) ? '' : $conf['recaptcha_secret'];

        $response = isset($_POST['g-recaptcha-response'])
                  ? $_POST['g-recaptcha-response'] : '';

        if ($response === '') {
            return $info['error'];
        }

        $result = $this->verify($secret, $response, $_SERVER['REMOTE_ADDR']);

        return $result ? NULL : $info['error'];
    }

    private function verify($secret, $response, $remoteip)
    {
        $data = http_build_query(array(
            'secret'   => $secret,
            'response' => $response,
            'remoteip' => $remoteip,
        ));

        $ctx = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $data,
                'timeout' => 5,
            )
        ));

        $result = @file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify',
            false, $ctx
        );

        if ($result === false) {
            return false;
        }

        $json = json_decode($result, true);
        return !empty($json['success']);
    }
}
?>
