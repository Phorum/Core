<?php
// These are the default settings for the Spam Hurdles module.

global $PHORUM;

if (!isset($PHORUM['mod_spamhurdles']))
    $PHORUM['mod_spamhurdles'] = array();

// The default settings for the Spam Hurdles module.
$mod_spamhurdles_defaults = array(
    'captcha' => array(
        'type'             => 'javascript',
        'spoken_captcha'   => true,
        'flite_location'   => NULL,
        'recaptcha_pubkey' => '',
        'recaptcha_prvkey' => ''
    ),
    'posting' => array(
        'block_action' => 'unapprove',
        'hurdles' => array(
            'block_commented_field' => 'all',
            'block_quick_submit'    => 'all',
            'block_replay'          => 'all',
            'javascript_signature'  => 'anonymous',
            'captcha'               => 'anonymous'
        )
    ),
    'register' => array(
        'hurdles' => array(
            'block_commented_field' => 'all',
            'block_quick_submit'    => 'all',
            'block_replay'          => 'all',
            'javascript_signature'  => 'none',
            'captcha'               => 'all'
        )
    ),
    'pm' => array(
        'hurdles' => array(
            'block_commented_field' => 'all',
            'block_quick_submit'    => 'all',
            'block_replay'          => 'none',
            'javascript_signature'  => 'none',
            'captcha'               => 'none'
        )
    )
);

// Apply the default settings.
foreach ($mod_spamhurdles_defaults as $section => $config)
{
    if (!isset($PHORUM['mod_spamhurdles'][$section])) {
        $PHORUM['mod_spamhurdles'][$section] = array();
    }
    foreach ($config as $key => $val) {
        if (is_array($val)) {
            if (!isset($PHORUM['mod_spamhurdles'][$section][$key])) {
                $PHORUM['mod_spamhurdles'][$section][$key] = array();
            }
            foreach ($val as $key2 => $val2) {
                if (!isset($PHORUM['mod_spamhurdles'][$section][$key][$key2])) {
                    $PHORUM['mod_spamhurdles'][$section][$key][$key2] = $val2;
                }
            }
        } else {
            if (!isset($PHORUM['mod_spamhurdles'][$section][$key])) {
                $PHORUM['mod_spamhurdles'][$section][$key] = $val;
            }
        }
    }
}

if (!isset($PHORUM['mod_spamhurdles']['log_events']))
    $PHORUM['mod_spamhurdles']['log_events'] = 1;

// Some settings that are in here for possible future configuration
// through the admin panel. For now, it doesn't seem required to have
// these as a configuration option in the admin interface.
if (!isset($PHORUM['mod_spamhurdles']['key_min_ttl']))
    $PHORUM['mod_spamhurdles']['key_min_ttl'] = 5;
if (!isset($PHORUM['mod_spamhurdles']['key_max_ttl']))
    $PHORUM['mod_spamhurdles']['key_max_ttl'] = 3600*8;

// Handle upgrading of Spam Hurdles version 1 settings.
if (!isset($PHORUM['mod_spamhurdles']['config_version']) ||
    $PHORUM['mod_spamhurdles']['config_version'] < 2)
{
    // Convert the posting block action.
    if (isset($PHORUM['mod_spamhurdles']['blockaction'])) {
        $PHORUM['mod_spamhurdles']['posting']['block_action'] =
            $PHORUM['mod_spamhurdles']['blockaction'];
    }
    unset($PHORUM['mod_spamhurdles']['blockaction']);

    // Convert the posting hurdles configuration.
    foreach (array(
        'blockmultipost'    => 'block_replay',
        'blockquickpost'    => 'block_quick_submit',
        'commentfieldcheck' => 'block_commented_field',
        'jsmd5check'        => 'javascript_signature',
        'posting_captcha'   => 'captcha'
    ) as $old => $new) {
        if (isset($PHORUM['mod_spamhurdles'][$old])) {
            $PHORUM['mod_spamhurdles']['posting']['hurdles'][$new] =
                $PHORUM['mod_spamhurdles'][$old];
        }
        unset($PHORUM['mod_spamhurdles'][$old]);
    }

    // Convert the registration hurdles configuration.
    if (isset($PHORUM['mod_spamhurdles']['register_captcha'])) {
        $PHORUM['mod_spamhurdles']['register']['hurdles']['captcha'] =
            $PHORUM['mod_spamhurdles']['register_captcha']
            ? 'all' : 'nobody';
    }
    unset($PHORUM['mod_spamhurdles']['register_captcha']);

    // Convert the CAPTCHA configuration.
    foreach (array(
        'captcha_type'      => 'type',
        'spoken_captcha'    => 'spoken_captcha',
        'flite_location'    => 'flite_location',
        'recaptcha_pubkey'  => 'recaptcha_pubkey',
        'recaptcha_prvkey'  => 'recaptcha_prvkey'
    ) as $old => $new) {
        if (isset($PHORUM['mod_spamhurdles'][$old])) {
            $PHORUM['mod_spamhurdles']['captcha'][$new] =
                $PHORUM['mod_spamhurdles'][$old];
        }
        unset($PHORUM['mod_spamhurdles'][$old]);
    }

    // Keep track of our configuration upgrade.
    $PHORUM['mod_spamhurdles']['config_version'] = 2; 

    // Some stale config options that are no longer in use.
    unset($PHORUM['mod_spamhurdles']['captcha_flite']);
    unset($PHORUM['mod_spamhurdles']['anyhurdle']);

    // Save the upgraded configuration.
    phorum_db_update_settings(array(
        'mod_spamhurdles' => $PHORUM['mod_spamhurdles']
    ));
}

// Try to determine automatically if flite is installed.
if (!isset($PHORUM['mod_spamhurdles']['captcha']['flite_location']) ||
    $PHORUM['mod_spamhurdles']['captcha']['flite_location'] === NULL) {
    $flite_location = spamhurdles_find_flite();
    $PHORUM['mod_spamhurdles']['captcha']['flite_location'] = $flite_location;
}

// A helper function for searching for the flite binary in some standard places.
function spamhurdles_find_flite()
{
    $search = array('/bin', '/usr/bin', '/usr/local/bin',
                    '/usr/local/flite/bin', '/opt/flite/bin');
    $flite_location = '';
    foreach ($search as $path) {
        if (@file_exists("$path/flite")) {
            $flite_location = "$path/flite";
            break;
        }
    }

    return $flite_location;
}

?>
