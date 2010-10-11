<?php
/**
 * This file implements the "captcha" spam hurdle. This hurdle adds a
 * CAPTCHA to a form. When the form is posted, the user must have solved
 * the CAPTCHA. Otherwise, the form post is blocked.
 */ 

function spamhurdles_load_captcha_class($type)
{
    // Load the CAPTCHA generation class. If, for some reason, the
    // CAPTCHA class file cannot be found, we will fallback to the
    // plaintext CAPTCHA.
    $type  = basename($type);
    $class = "captcha_$type";
    $file  = dirname(__FILE__) . "/../captcha/class.{$class}.php";

    // Fallback to a plaintext CAPTCHA in case the class file cannot be found.
    // This shouldn't really be happening, but just in case somebody deletes
    // a CAPTCHA type from the tree that is currently active.
    if (!file_exists($file)) {
        $type  = 'plaintext';
        $class = 'captcha_plaintext';
        $file  = dirname(__FILE__) . "/../captcha/class.captcha_plaintext.php";
    }

    require_once($file);

    return array($type, $class);
}

function spamhurdle_captcha_init($data)
{
    global $PHORUM;
    $config = $PHORUM['mod_spamhurdles']['captcha'];

    // Create the CAPTCHA object and let it generate a new CAPTCHA.
    list ($type, $class) =
        spamhurdles_load_captcha_class($config['type']);
    $captcha = new $class();
    $captcha = $captcha->generate_captcha();
    $captcha['type'] = $type;

    $data['captcha'] = $captcha;

    return $data;
}

function spamhurdle_captcha_build_form($data)
{
    if (!isset($data['captcha']['html_form'])) return $data;

    global $PHORUM;
    $config  = $PHORUM['mod_spamhurdles']['captcha'];
    $captcha = $data['captcha'];
    $form    = $captcha['html_form'];

    // The actual value in the captcha is named {FIELDVALUE} in the
    // generated captcha HTML code. Replace it with the actual value.
    $fn = $captcha["input_fieldname"];
    $fieldvalue = isset($_POST[$fn]) ? $_POST[$fn] : "";
    $form = str_replace(
        "{FIELDVALUE}", htmlspecialchars($fieldvalue), $form
    );

    // Replace SPOKENURL with the URL for the spoken captcha code.
    if ($config["spoken_captcha"] && file_exists($config["flite_location"])) {
        $url = phorum_get_url(
            PHORUM_ADDON_URL,
            'module=spamhurdles',
            'hurdle=captcha',
            'spokencaptcha=' .
                rawurlencode(spamhurdles_encrypt($captcha['spoken_text']))
        );
        $form = str_replace(
            '{SPOKENURL}', htmlspecialchars($url), $form
        );
    }

    // Replace IMAGE with the URL for the captcha image.
    $url = phorum_get_url(
        PHORUM_ADDON_URL,
        'module=spamhurdles',
        'hurdle=captcha',
        'imagecaptcha=' . rawurlencode(spamhurdles_encrypt(array(
            'question' => $captcha['question'],
            'type'     => $captcha['type']
        )))
    );
    $form = str_replace(
        '{IMAGEURL}', htmlspecialchars($url), $form
    );

    if (!empty($data['use_editor_block'])) {
      $PHORUM['DATA']['CONTENT'] = $form;
      $PHORUM['DATA']['EDITOR'] = $data['use_editor_block'];
      include phorum_get_template('spamhurdles::editor_block');
    } else {
      print $form;
    }

    return $data;
}

function spamhurdle_captcha_build_after_form($data)
{
    if (!isset($data['captcha']['html_after_form'])) return $data;

    print $data['captcha']['html_after_form'];

    return $data;
}

function spamhurdle_captcha_check_form($data)
{
    global $PHORUM;

    // If an error is already set in the data (by another spam hurdle),
    // then do not run this spam hurdle check for now.
    if ($data['error']) return $data;

    list ($type, $class) =
        spamhurdles_load_captcha_class($data['captcha']['type']);
    $captcha = new $class();
    $error = $captcha->check_answer($data['captcha']);
    if ($error) {
        $data['error']  = $error;
        $data['status'] = SPAMHURDLES_WARNING;
        $data['log'][]  =
            "The entered CAPTCHA code was incorrect.";
    }

    return $data;
}

function spamhurdle_captcha_addon($args)
{
    if (isset($args['spokencaptcha'])) {
        $say = spamhurdles_decrypt($args['spokencaptcha']);
        require_once dirname(__FILE__) . '/../captcha/spoken_captcha.php';
        spamhurdles_spoken_captcha($say);
    }
    else if (isset($args['imagecaptcha'])) {
        $req = spamhurdles_decrypt($args['imagecaptcha']);
        list($type, $class) = spamhurdles_load_captcha_class($req['type']);
        $captcha = new $class();
        $captcha->generate_image($req['question']);
    }
}

?>
