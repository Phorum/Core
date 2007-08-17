<?php
// These are the default settings for the Spam Hurdles module.

if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"] = array();

if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["blockaction"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["blockaction"] = "unapprove";

if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["blockmultipost"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["blockmultipost"] = "all";

if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["blockquickpost"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["blockquickpost"] = "all";
if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["key_min_ttl"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["key_min_ttl"] = 5;
if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["key_max_ttl"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["key_max_ttl"] = 3600*8;

if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["commentfieldcheck"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["commentfieldcheck"] = "all";

if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["jsmd5check"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["jsmd5check"] = "anonymous";

if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["register_captcha"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["register_captcha"] = 1;
if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["posting_captcha"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["posting_captcha"] = "anonymous";
if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["captcha_type"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["captcha_type"] = "javascript";
if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["spoken_captcha"]))
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["spoken_captcha"] = 1;
if (! isset($GLOBALS["PHORUM"]["mod_spamhurdles"]["flite_location"])) {
    // Try to determine automatically if flite is installed.
    $search = array("/bin", "/usr/bin", "/usr/local/bin",
                    "/usr/local/flite/bin", "/opt/flite/bin");
    $flite_location = "";
    foreach ($search as $path) {
        if (file_exists("$path/flite")) {
            $flite_location = "$path/flite";
            break;
        }
    }
    $GLOBALS["PHORUM"]["mod_spamhurdles"]["flite_location"] = $flite_location;
}
?>
