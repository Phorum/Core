<?php
if (!defined("PHORUM")) return;

require_once("./mods/spamhurdles/api.php");

$GLOBALS['PHORUM']['DATA']['SPAMHURDLES_BEFORE_FOOTER'] = '';

// ----------------------------------------------------------------------
// General purpose hooks
// ----------------------------------------------------------------------

// {{{ Function: phorum_mod_spamhurdles_css_register

/**
 * Register the additional CSS code for this module.
 */
function phorum_mod_spamhurdles_css_register($data)
{
    if ($data['css'] != 'css') return $data;

    $data['register'][] = array(
        "module" => "spamhurdles",
        "where"  => "after",
        "source" => "template(spamhurdles::css)"
    );
    return $data;
}

// }}}
// {{{ Function: phorum_mod_spamhurdles_javascript_register

/**
 * Register the javascript code for this module.
 */
function phorum_mod_spamhurdles_javascript_register($data)
{
    // A function that can be used to evaluate blocks of JavaScript code
    // inside a block of page data. This is used by the modified
    // iScramble code that is in use by this module.
    $data[] = array(
        "module" => "spamhurdles",
        "source" => "file(mods/spamhurdles/spamhurdles.js)"
    );

    // Allow spam hurdle implementations to add javascript code.
    $data = spamhurdles_api_javascript_register($data);

    return $data;
}

// }}}
// {{{ Function: phorum_mod_spamhurdles_before_footer

/**
 * Add data that needs to go to the end of the page.
 * The form specific hooks will fill the template variable
 * {SPAMHURDLES_BEFORE_FOOTER}. This hook will take care
 * of putting that data in the page.
 */
function phorum_mod_spamhurdles_before_footer()
{
    global $PHORUM;

    if (!empty($PHORUM['DATA']['SPAMHURDLES_BEFORE_FOOTER'])) {
        print $PHORUM['DATA']['SPAMHURDLES_BEFORE_FOOTER'];
    }
}

// }}}
// {{{ Function: phorum_mod_spamhurdles_addon

function phorum_mod_spamhurdles_addon()
{
    global $PHORUM;
    $args = $PHORUM['args'];

    if (isset($args['hurdle'])) {
        spamhurdles_hurdle_call('addon', $args, array($args['hurdle']));
    }
}

// }}}

// ----------------------------------------------------------------------
// Implement protection for posting messages
// ----------------------------------------------------------------------

// {{{ Function: phorum_mod_spamhurdles_tpl_editor_before_textarea

/**
 * Extend the message posting form with spam hurdles data.
 */
function phorum_mod_spamhurdles_tpl_editor_before_textarea()
{ 
    global $PHORUM;

    if (phorum_page != "post" && phorum_page != "read") return;

    // Only run the spamhurdle checks when writing a new message.
    // We do not need the checks for editing existing messages.
    if (isset($PHORUM["DATA"]["POSTING"]["message_id"])) {
        if (!empty($PHORUM["DATA"]["POSTING"]["message_id"])) return;
    } else trigger_error(
        "phorum_mod_spamhurdles_tpl_editor_before_textarea(): " .
        "Can't determine whether we're editing a new message",
        E_USER_ERROR
    );

    // Initialize the form, unless we have spamhurdles data in the
    // request already.
    if (!isset($_POST['spamhurdles_posting']))
    {
        $data = spamhurdles_api_init(
            'posting',
            spamhurdles_get_hurdles_for_form('posting')
        );

        $data['use_editor_block'] = 'posting';
    }
    // We are not initializing the form.
    // Valid Spam Hurdles data must be available.
    else
    {
        $data = spamhurdles_api_get_formdata('posting');
        if ($data === NULL) trigger_error(
            'No "spamhurdles_posting" data field was found in the POST ' . 
            'request. This should not happen.',
            E_USER_ERROR
        );  

    }

    // Output the required form data.
    print spamhurdles_api_build_form($data);

    // Prepare the data that needs to be added after the form.
    // We will display it from the before_footer hook, but since
    // we have the spam hurdles data at hand here, it's easiest
    // to format the after form data here.
    $PHORUM['DATA']['SPAMHURDLES_BEFORE_FOOTER'] .= 
        spamhurdles_api_build_after_form($data);
}

// }}}
// {{{ Function: phorum_mod_spamhurdles_check_post

/**
 * Check posted editor data.
 */
function phorum_mod_spamhurdles_check_post($args)
{
    global $PHORUM;
    list ($message, $error) = $args;

    // Return if another module already set an error.
    if (!empty($error)) return $args;

    // Our checks are only needed when finishing a post.
    if (!isset($_POST["finish"])) return $args;

    // Only run the checks when we are editing a new message.
    if (!empty($message["message_id"])) return $args;

    // Run the spam hurdle checks. If the checks fail, then check what
    // the block action is. For "unapprove", we will still post the
    // message, only in an unapproved state. Otherwise, we display
    // an error to the user.
    list($hurdle_status, $hurdle_error) = spamhurdles_api_check_form('posting');
    if ($hurdle_status == SPAMHURDLES_FATAL) {
        $block_action = $PHORUM['mod_spamhurdles']['posting']['block_action'];
        if ($block_action == 'unapprove') {
            // The pre_post hook will use this to make the message moderated.
            $PHORUM['mod_spamhurdles_unapprove'] = TRUE; 
        } else {
            $error = $hurdle_error;
        }
    }
    elseif ($hurdle_status == SPAMHURDLES_WARNING) {
        $error = $hurdle_error;
    }

    return array($message, $error);
}
// }}}
// {{{ Function: phorum_mod_spamhurdles_pre_post

/**
 * Handle marking the message unapproved if requested by this mod.
 */
function phorum_mod_spamhurdles_pre_post($message)
{
    global $PHORUM;

    if (!empty($PHORUM['mod_spamhurdles_unapprove'])) {
        $message['status'] = PHORUM_STATUS_HOLD;
    }
    return $message;
}

// }}}
// {{{ Function: phorum_mod_spamhurdles_after_post

/**
 * Let hurdles run tasks after posting a message.
 */
function phorum_mod_spamhurdles_after_post($message)
{
    spamhurdles_api_after_post('posting');
    return $message;
}

// }}}

// ----------------------------------------------------------------------
// Implement protection for account registration
// ----------------------------------------------------------------------

// {{{ Function: phorum_mod_spamhurdles_tpl_register_form

/**
 * Extend the account registration form with spam hurdles data.
 */
function phorum_mod_spamhurdles_tpl_register_form()
{
    global $PHORUM;

    // Initialize the form, unless we have spamhurdles data in the
    // request already.
    if (!isset($_POST['spamhurdles_register']))
    {
        $data = spamhurdles_api_init(
            'register',
            spamhurdles_get_hurdles_for_form('register')
        );
    }
    // We are not initializing the form.
    // Valid Spam Hurdles data must be available.
    else
    {
        $data = spamhurdles_api_get_formdata('register');
        if ($data === NULL) trigger_error(
            'No "spamhurdles_register" data field was found in the POST ' .
            'request. This should not happen.',
            E_USER_ERROR
        );  
    }

    // Output the required form data.
    print spamhurdles_api_build_form($data);

    // Prepare the data that needs to be added after the form.
    // We will display it from the before_footer hook, but since
    // we have the spam hurdles data at hand here, it's easiest
    // to format the after form data here.
    $PHORUM['DATA']['SPAMHURDLES_BEFORE_FOOTER'] .= 
        spamhurdles_api_build_after_form($data);
}

// }}}
// {{{ Function: phorum_mod_spamhurdles_before_register

/**
 * Check posted account registration data.
 */
function phorum_mod_spamhurdles_before_register($user)
{
    // Return if another module already set an error.
    if (isset($user["error"]) && !empty($user["error"])) {
        return $user;
    }

    // Run the spam hurdle checks.
    list ($status, $error) = spamhurdles_api_check_form('register');
    if ($status != SPAMHURDLES_OK) {
        $user["error"] = $error;
    }

    return $user;
}

// }}}

// ----------------------------------------------------------------------
// Implement protection for PM posting
// ----------------------------------------------------------------------

// {{{ Function: phorum_mod_spamhurdles_tpl_pm_editor_before_textarea

/**
 * Extend the PM sending form with spam hurdles data.
 */
function phorum_mod_spamhurdles_tpl_pm_editor_before_textarea()
{
    global $PHORUM;

    // Initialize the form, unless we have spamhurdles data in the
    // request already.
    if (!isset($_POST['spamhurdles_pm']))
    {
        $data = spamhurdles_api_init(
            'pm',
            spamhurdles_get_hurdles_for_form('pm')
        );

        $data['use_editor_block'] = 'pm';
    }
    // We are not initializing the form.
    // Valid Spam Hurdles data must be available.
    else
    {
        $data = spamhurdles_api_get_formdata('pm');
        if ($data === NULL) trigger_error(
            'No "spamhurdles_pm" data field was found in the POST request. ' .
            'This should not happen.',
            E_USER_ERROR
        );  
    }

    // Output the required form data.
    print spamhurdles_api_build_form($data);

    // Prepare the data that needs to be added after the form.
    // We will display it from the before_footer hook, but since
    // we have the spam hurdles data at hand here, it's easiest
    // to format the after form data here.
    $PHORUM['DATA']['SPAMHURDLES_BEFORE_FOOTER'] .= 
        spamhurdles_api_build_after_form($data);
}

// }}}
// {{{ Function: phorum_mod_spamhurdles_pm_before_send

/**
 * Check posted account registration data.
 */
function phorum_mod_spamhurdles_pm_before_send($message)
{
    // Return if another module already set an error.
    if ($message['error'] !== NULL) {
        return $message;
    }

    // Run the spam hurdle checks.
    list ($status, $error) = spamhurdles_api_check_form('pm');
    if ($status != SPAMHURDLES_OK) {
        $message["error"] = $error;
    }

    return $message;
}

// }}}
?>
