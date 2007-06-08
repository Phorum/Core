<?php
require_once(dirname(__FILE__) . "/class.captcha_base.php");

class captcha_plaintext extends captcha_base
{
    function generate_captcha_html($question)
    {
        $captcha = '<div id="spamhurdles_captcha_image">'.$question.'</div>';
        return array($captcha, "");
    }
}
?>
