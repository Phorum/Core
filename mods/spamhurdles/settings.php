<?php
    if (!defined("PHORUM_ADMIN")) return;

    require_once("./mods/spamhurdles/defaults.php");

    // save settings
    if (count($_POST))
    {
        $PHORUM["mod_spamhurdles"] = array(
            "blockaction"       => $_POST["blockaction"],
            "blockmultipost"    => $_POST["blockmultipost"],
            "blockquickpost"    => $_POST["blockquickpost"],
            "jsmd5check"        => $_POST["jsmd5check"],
            "register_captcha"  => isset($_POST["register_captcha"]) ? 1 : 0,
            "posting_captcha"   => $_POST["posting_captcha"],
            "captcha_flite"     => $_POST["captcha_flite"],
            "captcha_type"      => $_POST["captcha_type"],
            "spoken_captcha"    => isset($_POST["spoken_captcha"]) ? 1 : 0,
            "flite_location"    => $_POST["flite_location"],
            "commentfieldcheck" => $_POST["commentfieldcheck"],
            "recaptcha_pubkey"  => $_POST["recaptcha_pubkey"],
            "recaptcha_prvkey"  => $_POST["recaptcha_prvkey"],

            // meta field that is true if any of the hurdles is enabled.
            "anyhurdle"         => $_POST["blockmultipost"]    !== "none" ||
                                   $_POST["blockquickpost"]    !== "none" ||
                                   $_POST["jsmd5check"]        !== "none" ||
                                   $_POST["posting_captcha"]   !== "none" ||
                                   $_POST["commentfieldcheck"] !== "none"
        );

        if (!phorum_db_update_settings(array(
            "mod_spamhurdles" => $PHORUM["mod_spamhurdles"]
        ))) {
            phorum_admin_error("Updating the settings in the database failed.");
        } else {
            phorum_admin_okmsg("Settings updated");
        }
    }

    ?>
    <div style="font-size: xx-large; font-weight: bold">Spam Hurdles Module</div>
    <div style="padding-bottom: 15px; font-size: small">
      Let those spammers jump hoops and trip over hurdles...
    </div>
    <?php

    include_once "./include/admin/PhorumInputForm.php";
    $frm = new PhorumInputForm ("", "post", "Save");
    $frm->hidden("module", "modsettings");
    $frm->hidden("mod", "spamhurdles");

    $frm->addbreak("Non interactive spam blocking methods for posting messages");

    $userspec = array(
        "none"      => "Disable hurdle",
        "anonymous" => "Enable for anonymous users",
        "all"       => "Enable for all users"
    );

    $blockspec = array(
        "blockerror" => "Fully block and show an error",
        "unapprove"  => "Accept, but make unapproved",
    );

    $row = $frm->addrow("What action has to be taken when blocking a message?", $frm->select_tag("blockaction", $blockspec, $PHORUM["mod_spamhurdles"]["blockaction"]));

    $row = $frm->addrow("Block message forms that are submitted multiple times", $frm->select_tag("blockmultipost", $userspec, $PHORUM["mod_spamhurdles"]["blockmultipost"]));
    $frm->addhelp($row, "Block multiple submits", "If this option is enabled, then a unique key will be generated for each new message. As soon as the message is posted, this key will be invalidated for posting. This effectively prevents people from going back in the browser and resubmitting a (slightly changed) message (flooding) as well as spammers who directly submit posting forms to Phorum's post.php, without fetching a fresh unique key first.<br/><br/><b>User impact:</b><br/>This does not affect the way in which people can use Phorum, so the recommended value for this option is \"Enable for all users\".");

    $row = $frm->addrow("Block message forms that are submitted too quickly", $frm->select_tag("blockquickpost", $userspec, $PHORUM["mod_spamhurdles"]["blockquickpost"]));
    $frm->addhelp($row, "Block quick message submits", "If this option is enabled, Phorum will check how much time there is between starting a new message and actually posting it. If a message is posted too quickly, then it's considered to come from a posting robot. To prevent users from accidentally posting the message too quickly themselves (For example by typing only \"yes\" in the body and hitting the submit button), the posting button is disabled as long as the server would block the message. On the button, a countdown is shown to display how many seconds the user has to wait before posting.<br/><br/><b>User impact:</b><br/>This option does work for all browsers, only for the posting button to be disabled, JavaScript support is required.");

    $row = $frm->addrow("Check if an HTML commented form field is submitted", $frm->select_tag("commentfieldcheck", $userspec, $PHORUM["mod_spamhurdles"]["commentfieldcheck"]));
    $frm->addhelp($row, "Comment form field check", "If this option is enabled, then an extra form field is added to the posting form. However, this form field is embedded within an HTML comment block. Because of that, normal web browsers will fully ignore this extra field. On the other hand, some badly written spam bots will recognize the code as a form field. If such a spam bot posts a message including this extra form field, the message will be blocked.<br/><br/><b>User impact:</b><br/>This does not affect the way in which people can use Phorum, so the recommended value for this option is \"Enable for all users\".");

    $row = $frm->addrow("Let the browser sign the message using JavaScript", $frm->select_tag("jsmd5check", $userspec, $PHORUM["mod_spamhurdles"]["jsmd5check"]));
    $frm->addhelp($row, "Let the browser sign the message", "If this option is enabled, then the browser will retrieve two pieces of data from the server. The browser will have to create a signature for this data (using MD5) and does so by running some JavaScript. The signing JavaScript code is put in the message editor in a scrambled way (using iScramble) and the browser will have to descramble it using JavaScript to be able to run the signing code.<br/><br/>Functionally, this is all done to force the use of JavaScript when posting a message. This can block those spambots that do not interpret JavaScript, but only try to post the unmodified form information that is found on the message posting page.<br/><br/><b>User impact:</b><br/>This option requires JavaScript support in the browser. If a user does not have JavaScript (enabled), then posting is not possible.");

    $frm->addbreak("Interactive CAPTCHA");

    $row = $frm->addrow("Let visitors solve a CAPTCHA when registering a new account?", $frm->checkbox("register_captcha", 1, "", $PHORUM["mod_spamhurdles"]["register_captcha"]));
    $frm->addhelp($row, "Registering CAPTCHA", "If this option is enabled, a CAPTCHA (Completely Automated Public Turing-test to tell Computers and Humans Apart) will be used when visitors are registering a new user account. A check is added to the registering process, where the user has to prove that he/she is a human, by solving a simple puzzle. Below you can specify which type of CAPTCHA to use for this.<br/><br/><b>User impact:</b><br/>The user will have to solve the CAPTCHA before a new account can be registered. So this will require an extra action by the user. The exact user impact depends on the type of CAPTCHA that is used.");

    $row = $frm->addrow("Let visitors solve a CAPTCHA when posting a new message", $frm->select_tag("posting_captcha", $userspec, $PHORUM["mod_spamhurdles"]["posting_captcha"]));
    $frm->addhelp($row, "Posting CAPTCHA", "If this option is enabled, a CAPTCHA (Completely Automated Public Turing-test to tell Computers and Humans Apart) will be used when posting a new message. A check is added to the posting process, where the user has to prove that he/she is a human, by solving a simple puzzle. Below you can specify which type of CAPTCHA to use for this.<br/><br/><b>User impact:</b><br/>The user will have to solve the CAPTCHA before a message can be posted. So this will require an extra action by the user. The exact user impact depends on the type of CAPTCHA that is used.");

    $captchaspec = array(
        'javascript' => 'Code, drawn using JavaScript',
        'image'      => 'Code, drawn using a GIF image',
        'asciiart'   => 'Code, drawn using ASCII art',
        'plaintext'  => 'Code, plain text format',
        'maptcha'    => 'Solve a simple math question',
        'recaptcha'  => 'Code, using the reCAPTCHA service',
#        'quiz'       => 'Solve a quiz question',
    );
    $row = $frm->addrow("Which type of CAPTCHA?", $frm->select_tag("captcha_type", $captchaspec, $PHORUM["mod_spamhurdles"]["captcha_type"], "id=\"captcha_select\" onchange=\"handle_captcha_select()\""));
    $frm->addhelp($row, "Type of CAPTCHA", "This module supports a wide range of CAPTCHA types. See the README that was bundled with this module for detailed information on these types and for deciding which one you want to use for your site.");

    $row = $frm->addrow("Enable spoken CAPTCHA? You will need the program \"Flite\" for this.", $frm->checkbox("spoken_captcha", 1, "", $PHORUM["mod_spamhurdles"]["spoken_captcha"]));
    $frm->addhelp($row, "Enable spoken CAPTCHA", 'Vision impaired people can have trouble reading and thus solving a CAPTCHA. For those people, you can supply a spoken CAPTCHA code. To be able to use this option, the program "Flite" (Festival-Lite) has to be installed on the webserver. For information on this, see http://www.speech.cs.cmu.edu/flite/');
    $warn = '';
    if (!empty($PHORUM["mod_spamhurdles"]["flite_location"]) &&
        !file_exists($PHORUM["mod_spamhurdles"]["flite_location"])) {
        $warn = '<div style="color:red">The flite program does not exist at the specified location</div>';
    }
    $row = $frm->addrow("What is full path of the \"flite\" executable? (e.g. /usr/bin/flite)$warn", $frm->text_box("flite_location", $PHORUM["mod_spamhurdles"]["flite_location"], 30));

    $frm->addmessage(
     "<div id=\"settings_recaptcha\" class=\"input-form-td\"
           style=\"margin:0; padding:10px; border: 1px solid navy\">
      For using reCAPTCHA, you need a (free) public and private key.
      Please signup at <a href=\"http://recaptcha.net\" target=\"_new\">the
      reCAPTCHA</a> web site and enter the public and private key for your
      web site's domain in the fields below.<br/><br/>
      <table><tr><td>public key</td><td>" . $frm->text_box("recaptcha_pubkey", $PHORUM["mod_spamhurdles"]["recaptcha_pubkey"], 40) . "</td></tr>" .
      "<tr><td>private key</td><td>" . $frm->text_box("recaptcha_prvkey", $PHORUM["mod_spamhurdles"]["recaptcha_prvkey"], 40) . "</td></tr></table>
      </div>"
    );

    $frm->show();
?>

<script type="text/javascript">
//<![CDATA[
function handle_captcha_select()
{
    if (! document.getElementById) return;
    sel = document.getElementById("captcha_select");
    set = document.getElementById("settings_recaptcha");
    captcha_id = sel.options[sel.selectedIndex].value;

    set.style.display = (captcha_id == 'recaptcha' ? 'block' : 'none');
}
//]]>

handle_captcha_select();
</script>

