<html><body>
<?php
require_once ("recaptchalib.php");

// get a key at http://mailhide.recaptcha.net/apikey
$mailhide_pubkey = '';
$mailhide_privkey = '';

?>

The Mailhide version of example@example.com is
<?php echo recaptcha_mailhide_html ($mailhide_pubkey, $mailhide_privkey, "example@example.com"); ?>. <br />

The url for the email is:
<?php echo recaptcha_mailhide_url ($mailhide_pubkey, $mailhide_privkey, "example@example.com"); ?> <br />

</body></html>
