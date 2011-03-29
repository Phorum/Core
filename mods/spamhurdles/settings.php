<?php
if (!defined("PHORUM_ADMIN")) return;

require_once("./mods/spamhurdles/defaults.php");

// A list of available CAPTCHAs.
$captchaspec = array(
    'javascript' => 'Code, drawn using JavaScript',
    'image'      => 'Code, drawn using a GIF image',
    'asciiart'   => 'Code, drawn using ASCII art',
    'plaintext'  => 'Code, plain text format',
    'maptcha'    => 'Solve a simple math question',
    'recaptcha'  => 'Code, using the reCAPTCHA service'
);

// Save settings.
if (count($_POST))
{
    $captcha_type = basename($_POST['captcha_type']);
    if (!isset($captchaspec[$captcha_type])) trigger_error(
        'Illegal CAPTCHA specified.', E_USER_ERROR
    );

    if ($captcha_type === 'image' && !function_exists('imagecreatetruecolor'))
    {
        phorum_admin_error(
            "The settings were not saved. " .
            "PHP on your webserver lacks GD support, which is required " .
            "for using GIF image CAPTCHAs. Please contact your hosting " .
            "provider to enable GD support."
        );
    }
    else
    {
        $PHORUM["mod_spamhurdles"] = array
        (
            // Whether or not to enable event logging.
            'log_events' => isset($_POST['log_events']) ? 1 : 0,

            // CAPTCHA configuration options.
            'captcha' => array(
                'type'              => $captcha_type,
                'flite_location'    => trim($_POST['flite_location']),
                'spoken_captcha'    => isset($_POST['spoken_captcha']) ? 1 : 0,
                'recaptcha_pubkey'  => $_POST['recaptcha_pubkey'],
                'recaptcha_prvkey'  => $_POST['recaptcha_prvkey']
            ),

            // Spam Hurdles configuration for posting messages.
            'posting' => array (
                'block_action' => $_POST['posting_block_action'],
                'hurdles' => $_POST['posting']
            ),

            // Spam Hurdles configuration for registering user accounts.
            'register' => array (
                'hurdles' => $_POST['register']
            ),

            // Spam Hurdles configuration for posting private messages.
            'pm' => array (
                'hurdles' => $_POST['pm']
            ),

            // The version of the configuration format.
            'config_version' => 2 
        );

        phorum_db_update_settings(array(
            "mod_spamhurdles" => $PHORUM["mod_spamhurdles"]
        ));

        phorum_admin_okmsg('The settings were successfully saved');
    }
}
else
{
    // If Flite is installed after this module was installed, then
    // we might be able to find the flite binary. If we do, then update
    // the flite path in the settings.
    if (trim($PHORUM['mod_spamhurdles']['captcha']['flite_location']) == '')
    {
        $flite_location = spamhurdles_find_flite();
        if ($flite_location)
        {
            $PHORUM['mod_spamhurdles']['captcha']['flite_location'] =
                $flite_location;
            phorum_db_update_settings(array(
                "mod_spamhurdles" => $PHORUM["mod_spamhurdles"]
            ));
            phorum_admin_okmsg(
                'The "flite" binary was found on the server.<br/>' .
                'The path was automatically set to ' .
                '"' . htmlspecialchars($flite_location) . '"'
            );
        }
    }
}

$image_url = $PHORUM['http_path'] . '/mods/spamhurdles/images/' .
             'datasphorum_thumb.jpg';
?>
<a href="http://geekandpoke.typepad.com" target="_blank">
  <img style="border:none; padding:5px;float:right"
       title="Click here for more Geek and Poke cartoons!"
       alt="Geek and Poke cartoon"
       src="<?php print $image_url ?>"/>
</a>
<div style="font-size: xx-large; font-weight: bold">Spam Hurdles Module</div>
<div style="padding-bottom: 15px; font-size: large">
  Let those spammers jump hoops and trip over hurdles...
</div>
 This module sets up some hurdles for forum spammers. It implements
 both interactive (CAPTCHA) and non-interactive anti-spam methods
 to keep away spam bots. On this page, you can control exactly
 what spam hurdles to enable.

<br style="clear:both" />
<?php

include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Save");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "spamhurdles");

// ----------------------------------------------------------------------
// Configure log settings
// ----------------------------------------------------------------------

$frm->addbreak("Log settings");

if (!file_exists('./mods/event_logging')) {
      $check = '<span style="color:red">The Event Logging module ' .
               'is currently not installed; logging cannot ' .
               'be enabled</span>';
      $disabled = 'disabled="disabled"';
      $PHORUM["mod_spamhurdles"]["log_events"] = 0;
} elseif (empty($PHORUM['mods']['event_logging'])) {
      $check = '<span style="color:red">The Event Logging module ' .
               'is currently not activated; logging cannot ' .
               'be enabled</span>';
      $disabled = 'disabled="disabled"';
      $PHORUM["mod_spamhurdles"]["log_events"] = 0;
} else {
      $check = '<span style="color:darkgreen">The Event Logging module ' .
               'is activated; events can be logged by enabling the ' .
               'feature below</span>';
      $disabled = '';
}

$frm->addrow($check, '');

$row = $frm->addrow(
    'Log blocked form posts to the Event Logging module?',
    $frm->checkbox(
        "log_events", 1, "Yes",
        $PHORUM["mod_spamhurdles"]["log_events"],
        $disabled
    )
);

$url = phorum_admin_build_url(array(
   'module=modsettings',
   'mod=event_logging',
   'el_action=logviewer'
));

$frm->addhelp(
    $row, "Log blocked form posts to the Event Logging module?",
    "When both this feature and the Event Logging module are enabled,
     then the Spam Hurdles module will log information about blocked
     form posts to the Phorum Event Log. To view this log, go to
     <a href=\"$url\">the Event Log viewer</a>"
);


// ----------------------------------------------------------------------
// Configure spam hurdles for posting messages
// ----------------------------------------------------------------------

$frm->addbreak("Spam Hurdles for posting new messages");

$row = $frm->addrow(
    'What action has to be taken when a spam message is suspected?',
    $frm->select_tag(
        'posting_block_action',
        array(
            'blockerror' => 'Fully block and show an error',
            'unapprove'  => 'Accept, but make unapproved'
        ),
        $PHORUM['mod_spamhurdles']['posting']['block_action']
    )
);
$frm->addhelp(
    $row, "Action when a spam message is suspected",
    "You can choose whether you want to fully block suspected spam messages
     or that you want to have them posted in a moderated state, so they
     will need approval by a moderator.<br/>
     <br/>
     A message is suspicious if it fails one of the spam hurdles:<br/>
     <ul>
       <li>Block forms that are submitted multiple times</li>
       <li>Check if an HTML commented form field is submitted</li>
       <li>Let the browser sign the form using JavaScript</li>
     </ul>
     For the remaining hurdles, an error state might be resolved by
     the user (e.g. by filling in the correct CAPTCHA code or by
     resubmitting a too quickly posted form). For those errors,
     there will always be an error message and a chance for the
     user to fix the issue to make posting possible."
);

create_spamhurdle_options(
    $frm, 'posting',
    array('posting.tpl', 'posting_messageform.tpl'),
    'tpl_editor_before_textarea',
    array(
        "none"      => "Disable hurdle",
        "anonymous" => "Enable for anonymous users",
        "all"       => "Enable for all users"
    )
);

// ----------------------------------------------------------------------
// Configure spam hurdles for user registration 
// ----------------------------------------------------------------------

$frm->addbreak("Spam Hurdles for user registration");

create_spamhurdle_options(
    $frm, 'register',
    array('register.tpl'),
    'tpl_register_form',
    array(
        "none"      => "Disable hurdle",
        "all"       => "Enable hurdle"
    )
);

// ----------------------------------------------------------------------
// Configure spam hurdles for PM 
// ----------------------------------------------------------------------

$frm->addbreak("Spam Hurdles for posting private messages (PM)");

create_spamhurdle_options(
    $frm, 'pm',
    array('pm_post.tpl'),
    'tpl_pm_editor_before_textarea',
    array(
        "none"      => "Disable hurdle",
        "all"       => "Enable hurdle"
    )
);

// ----------------------------------------------------------------------
// Utility function for generating spam hurdle configuration options 
// ----------------------------------------------------------------------

function create_spamhurdle_options($frm, $section, $tpl, $tplhook, $statusspec)
{
    global $PHORUM;

    $hurdles = $PHORUM['mod_spamhurdles'][$section]['hurdles'];

    $row = $frm->addrow(
        'Hurdle: Block forms that are submitted multiple times',
        $frm->select_tag(
            $section.'[block_replay]', $statusspec,
            $hurdles['block_replay']
        )
    );
    $frm->addhelp(
        $row, "Block forms that are submitted multiple times",
        "If this option is enabled, then a unique key will be generated
         for each new form. As soon as the form is posted, this key
         will be invalidated for posting. This effectively prevents people
         from going back in the browser and resubmitting a (slightly changed)
         form (flooding) as well as spammers who directly submit posting
         forms to Phorum's post.php, without fetching a fresh unique key
         first.<br/>
         <br/>
         <b>User impact:</b><br/>
         This does not affect the way in which people can use Phorum,
         so it is recommended to enable this option."
    );

    $row = $frm->addrow(
        "Hurdle: Block forms that are submitted too quickly",
        $frm->select_tag(
            $section.'[block_quick_submit]', $statusspec,
            $hurdles['block_quick_submit']
        )
    );
    $frm->addhelp(
        $row, "Block forms that are submitted too quickly",
        "If this option is enabled, Phorum will check how much time there
         is between showing a form and actually posting it. If a form is
         posted too quickly, then it's considered to come from a
         posting robot.<br/>
         <br/>
         To prevent users from accidentally posting the form too quickly
         themselves, the submit button is disabled as long as the server
         would block the form. On the button, a countdown is shown to
         display how many seconds the user has to wait before posting.<br/>
         <br/>
         <b>User impact:</b>
         <br/>This option does work for all browsers, only for the posting
         button to be disabled, JavaScript support is required."
    );

    $row = $frm->addrow(
        'Hurdle: Check if an HTML commented form field is submitted',
        $frm->select_tag(
            $section.'[block_commented_field]', $statusspec,
            $hurdles['block_commented_field']
        )
    );
    $frm->addhelp(
        $row, "Check if an HTML commented form field is submitted",
        "If this option is enabled, then an extra form field is added to
         the posting form. However, this form field is embedded within an
         HTML comment block. Because of that, normal web browsers will
         fully ignore this extra field. On the other hand, some badly
         written spam bots will recognize the code as a form field.
         If such a spam bot posts a form including this extra form field,
         the form will be blocked.<br/>
         <br/>
         <b>User impact:</b><br/>
         This does not affect the way in which people can use Phorum,
         so it is recommended to enable this option."
    );

    $row = $frm->addrow(
        "Hurdle: Let the browser sign the form using JavaScript",
        $frm->select_tag(
            $section.'[javascript_signature]', $statusspec,
            $hurdles["javascript_signature"]
        )
    );
    $frm->addhelp(
        $row, "Let the browser sign the message",
        "If this option is enabled, then the browser will retrieve two
        pieces of data from the server. The browser will have to create a
        signature for this data (using MD5) and does so by running some
        JavaScript. The signing JavaScript code is put in the message
        editor in a scrambled way (using iScramble) and the browser will
        have to descramble it using JavaScript to be able to run the
        signing code.<br/>
        <br/>
        Functionally, this is all done to force the use of JavaScript
        when posting a form. This can block those spambots that do not
        interpret JavaScript, but only try to post the unmodified form
        information that is found in the form.<br/>
        <br/>
        <b>User impact:</b><br/>
        This option requires JavaScript support in the browser.
        If a user does not have JavaScript (enabled), then posting a form
        is not possible."
    );

    $row = $frm->addrow(
        "Hurdle: Let visitors solve a CAPTCHA when posting a form",
        $frm->select_tag(
            $section.'[captcha]', $statusspec,
            $hurdles['captcha']
        )
    );
    $frm->addhelp(
        $row, "Let visitors solve a CAPTCHA when posting a form",
        "If this option is enabled, a CAPTCHA (Completely Automated Public
         Turing-test to tell Computers and Humans Apart) will be used
         when posting a form. A check is added to the form, where the user
         has to prove that he/she is a human, by solving a simple puzzle.
         Below you can configure what type of CAPTCHA to use for this.<br/>
         <br/>
         <b>User impact:</b><br/>
         The user will have to solve the CAPTCHA before a form can be posted.
         So this will require an extra action by the user. The exact user
         impact depends on the type of CAPTCHA that is used."
    );

    // Check if the require template code is supported by all templates.
    // If not, then show a warning message about the issue.
    $incompatibilties = array();
    $dh = opendir($PHORUM['template_path']);
    if ($dh) {
        while ($entry = readdir($dh))
        {
          if ($entry[0] !== '.' &&
              file_exists($PHORUM['template_path'].'/'.$entry.'/info.php')) {

                $version = '(version unknown)';
                $name = $entry;
                include $PHORUM['template_path'].'/'.$entry.'/info.php';
                $template = "$name $version";

                $incompatible = FALSE;
                foreach ($tpl as $t)
                {
                    $check_file = $PHORUM['template_path'].'/'.$entry.'/'.$t;
                    if (!file_exists($check_file)) continue;
                    $contents = file_get_contents($check_file);

                    $althook = preg_replace('/^tpl_/', 'sh_', $tplhook);
                    if (!preg_match("/$tplhook/", $contents) &&
                        !preg_match("/$althook/", $contents)) {
                        $incompatible = htmlspecialchars($check_file);
                    } else {
                        $incompatible = FALSE;
                        break;
                    }
                }

                if ($incompatible) {
                    $incompatibilities[] =
                        "<b>$incompatible</b> from the $template template";
                }
            }
        }
        closedir($dh);
    }
    if (!empty($incompatibilities))
    {
        $frm->addmessage(  
            "<b style=\"color:red\">" .
            "Warning: Incompatible template files detected" .
            "</b><br/><br/>" .
            "Not all template files contain the template hook " .
            "<tt>{HOOK \"$tplhook\"}</tt> that is required for the " .
            "$section protection to work. If your Phorum site is using " .
            "the following template files, then check them against the " .
            "template files in the Phorum distribution and add the " .
            "required hook code to them:</br>" .
            "<ul><li>" . implode("</li><li>", $incompatibilities) . "</li></ul>"
        );
    }

}

// ----------------------------------------------------------------------
// CAPTCHA configuration
// ----------------------------------------------------------------------

$row = $frm->addbreak("CAPTCHA Configuration");
$frm->addhelp($row,
    "CAPTCHA Configuration",
    "In the configuration options above, you can enable the use of
     CAPTCHAs (Completely Automated Public Turing-test to tell Computers
     and Humans Apart) to prevent automated bots from posting forms.
     A check is added to the forms, where the user has to prove that
     he/she is a human, by solving a simple puzzle. In this configuration
     block, you can configure the CAPTCHA that is used in the forms."
);

$row = $frm->addrow(
    "What type of CAPTCHA?",
    $frm->select_tag(
        "captcha_type", $captchaspec,
        $PHORUM["mod_spamhurdles"]["captcha"]["type"],
        'id="captcha_select" onchange="handle_captcha_select()"'
    )
);
$frm->addhelp(
    $row, "Type of CAPTCHA",
    "This module supports a wide range of CAPTCHA types. See the README that
     was bundled with this module for detailed information on these types
     and for deciding which one you want to use for your site."
);

$row = $frm->addrow(
    'Enable spoken CAPTCHA? You will need the program "Flite" for this.',
    $frm->checkbox(
        "spoken_captcha", 1, "Yes",
        $PHORUM["mod_spamhurdles"]["captcha"]["spoken_captcha"]
    )
);
$flite_url = "http://www.speech.cs.cmu.edu/flite/";
$frm->addhelp(
    $row, "Enable spoken CAPTCHA",
    "Vision impaired people can have trouble reading and thus solving a
     CAPTCHA. For those people, you can supply a spoken CAPTCHA code.
     To be able to use this option, the program \"Flite\" (Festival-Lite)
     has to be installed on the webserver. For information on this,
     see <a href=\"$flite_url\">$flite_url</a>"
);

$warn = '';
if (!empty($PHORUM["mod_spamhurdles"]["captcha"]["flite_location"]) &&
    !file_exists($PHORUM["mod_spamhurdles"]["captcha"]["flite_location"])) {
    $warn = '<div style="color:red">' .
            'The flite program does not exist at the specified location' .
            '</div>';
}
$row = $frm->addrow(
    "What is full path of the \"flite\" executable? " .
    "(e.g. /usr/bin/flite)$warn",
    $frm->text_box(
        "flite_location",
        $PHORUM["mod_spamhurdles"]["captcha"]["flite_location"], 30
    )
);

$frm->addmessage(
    "<div id=\"settings_recaptcha\" class=\"input-form-td\"
        style=\"margin:0; padding:10px; border: 1px solid navy\">
      For using reCAPTCHA, you need a (free) public and private key.
      Please signup at <a href=\"http://recaptcha.net\" target=\"_new\">the
      reCAPTCHA</a> web site and enter the public and private key for your
      web site's domain in the fields below.<br/><br/>
      <table>
        <tr>
          <td>public key</td>
          <td>" .
            $frm->text_box(
              'recaptcha_pubkey',
              $PHORUM['mod_spamhurdles']['captcha']['recaptcha_pubkey'], 40
            ) . "
          </td>
        </tr>
        <tr>
          <td>private key</td>
          <td>" .
            $frm->text_box(
              'recaptcha_prvkey',
              $PHORUM['mod_spamhurdles']['captcha']['recaptcha_prvkey'], 40
            ) . "
          </td>
        </tr>
      </table>
    </div>"
);

$frm->show();
?>

<script type="text/javascript">
//<![CDATA[
function handle_captcha_select()
{
    if (! document.getElementById) return;
    var sel = document.getElementById("captcha_select");
    var captcha_id = sel.options[sel.selectedIndex].value;

    var settings = document.getElementById("settings_recaptcha");
    settings.style.display = (captcha_id == 'recaptcha' ? 'block' : 'none');
}
//]]>

handle_captcha_select();
</script>

