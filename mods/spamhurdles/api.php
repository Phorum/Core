<?php

// {{{ Required libraries

require_once './mods/spamhurdles/defaults.php' ;
require_once './mods/spamhurdles/db.php' ;
require_once './mods/spamhurdles/include/crypt/aes.php';

// For EVENTLOG_LVL_INFO. Made conditional, just in case the Event Logging
// module was removed from the Phorum tree.
if (file_exists('./mods/event_logging/constants.php')) {
    require_once './mods/event_logging/constants.php';
}

// }}}
// {{{ Definitions

// This definition defines the chance (in %) that the garbage
// collection is run on the database, to clear out expired items.
define('SPAMHURDLES_GARBAGE_COLLECTION_RATE', 1);

// Return values for spamhurdles_api_check_form().
define('SPAMHURDLES_OK',      0);
define('SPAMHURDLES_WARNING', 1);
define('SPAMHURDLES_FATAL',   2);

/**
 * This is the registry for registering the spam hurdle implementations.
 * If a new spam hurdle is implemented, it needs to be added to this registry.
 *
 * The keys in this registry are single letter keys that identify the
 * hurdle. We use this short key for storing the activated hurdles for a
 * form in the data that is sent to the user's browser.
 *
 * The values are arrays that describe the spam hurdle. Elements in these
 * arrays are:
 *
 * - name: the implementation name of the hurdle. The implementation of
 *   a hurdle is in "hurdles/<name>.php". This field is mandatory.
 *
 * - collect_garbage: when this field exists and contains a true value,
 *   this means that the hurdle has implemented a garbage collection function.
 *
 * - javascript_register: when this field exists and contains a true value,
 *   this means that the hurdle has implemented a javascript_register function.
 *
 */
$GLOBALS['PHORUM']['spamhurdles_registry'] = array
(
    'f' => array(
        'name' => 'block_commented_field'
    ),

    'r' => array(
        'name' => 'block_replay',
        'collect_garbage' => TRUE
    ),

    's' => array(
        'name' => 'javascript_signature',
        'javascript_register' => TRUE
    ),

    'q' => array(
        'name' => 'block_quick_submit',
        'javascript_register' => TRUE
    ),

    'c' => array(
        'name' => 'captcha'
    )
);

// }}}

// ----------------------------------------------------------------------
// API interface functions
// ----------------------------------------------------------------------

// {{{ Function: spamhurdles_api_javascript_register

/**
 * Spam hurdle implementations could need some global javascript code.
 * This function will call the javascript_register function for all
 * spam hurdles that implement this.
 *
 * @param array $data
 *     An array of javascript registrations, compatible with the data
 *     as used by the javascript_register hook from javascript.php.
 *
 * @return array
 *     The (possibly modified) $data.
 */
function spamhurdles_api_javascript_register($data)
{
    return spamhurdles_hurdle_call('javascript_register', $data);
}

// }}}
// {{{ Function: spamhurdles_api_init

/**
 * Initialize Spam Hurdles data for protecting a form.
 *
 * When a form is displayed for the first time, then this function has
 * to be called to initialize the data that is required by the Spam
 * Hurdles module.
 *
 * @param string $form_id
 *     An identifier for the current form. Although this can be a unique
 *     identifier, it is not required by the API. The spam hurdles module
 *     itself simply passes the type of form as the $form_id (e.g. "posting").
 *     The main issue to beware of, is that the form ids should be unique
 *     for a single page. So if there's more than one protected form on a
 *     page, then they should use different form ids.
 *
 * @param array $hurdles
 *     An array containing the names of the hurdles that have to be
 *     enabled for the form. These names can either be the single
 *     letter id's from the $PHORUM['spamhurdles_registry'] registry
 *     or the hurdle implementation names (e.g. the quick post hurdle
 *     can be enabled by using either "q" or "block_quick_submit".
 *
 * @return array
 *     An array containing the spam hurdles data for the form.
 */
function spamhurdles_api_init($form_id, $hurdles)
{
    global $PHORUM;
    $registry = $PHORUM['spamhurdles_registry'];

    // The expiration time of the spam hurdles data.
    $ttl = time() + $PHORUM['mod_spamhurdles']['key_max_ttl'];

    // Normalize and check the hurdles.
    $normalized_hurdles = array();
    foreach ($hurdles as $hurdle)
    {
        if (isset($registry[$hurdle])) {
            $normalized_hurdles[] = $hurdle;
        } else {
            foreach ($registry as $id => $info) {
                if ($info['name'] == $hurdle) {
                    $normalized_hurdles[] = $id;
                    continue 2;
                }
            }
            trigger_error(
                'spamhurdles_api_init(): Illegal hurdle id used: ' .
                htmlspecialchars($hurdle),
                E_USER_ERROR
            );
        }
    }

    // Initialize spam hurdles data.
    $data = array(
        'id'             => $form_id,
        'ttl'            => $ttl,
        'hurdles'        => $normalized_hurdles
    );

    // For each hurdle, run the init code from the hurdle implementation.
    $data = spamhurdles_hurdle_call('init', $data, $data['hurdles']);

    return $data;
}

// }}}
// {{{ Function: spamhurdles_api_build_form

/**
 * Each time that the form has to be displayed, this function can be used
 * to build the HTML code that needs to be added to the protected form.
 *
 * @param array $data
 *     An array containing the spam hurdles data for the form.
 *     This data can be initialized for a new form by means of
 *     the {@link spamhurdles_api_init()} function.
 *
 * @return string
 *     The data that has to be added to the form.
 */
function spamhurdles_api_build_form($data)
{
    global $PHORUM;
    ob_start();

    // Add the spam hurdles data encrypted to the form.
    // Remove work data. This data is only used for storing data
    // during a request. Also, update the TTL for the data.
    $send_data = $data;
    unset($send_data['work']);
    $send_data['ttl'] = time() + $PHORUM['mod_spamhurdles']['key_max_ttl'];
    $crypted = htmlspecialchars(spamhurdles_encrypt($send_data));
    print '<input type="hidden" name="spamhurdles_'.$data['id'].'" ' .
          'id="spamhurdles_'.$data['id'].'" value="'.$crypted.'"/>';

    // Let hurdles add their required data.
    spamhurdles_hurdle_call('build_form', $data, $data['hurdles']);

    $form = ob_get_contents();
    ob_end_clean();

    return $form;
}

// }}}
// {{{ Function: spamhurdles_api_build_after_form

/**
 * Each time that the form has to be displayed, this function can be used
 * to build the HTML code that needs to be added to the page, somewhere
 * after the protected form.
 *
 * @param array $data
 *     An array containing the spam hurdles data for the form.
 *     This data can be initialized for a new form by means of
 *     the {@link spamhurdles_api_init()} function.
 *
 * @return string
 *     The data that has to be added after the form.
 */
function spamhurdles_api_build_after_form($data)
{
    ob_start();

    // Let hurdles add their required data.
    spamhurdles_hurdle_call('build_after_form', $data, $data['hurdles']);

    $after_form = ob_get_contents();
    ob_end_clean();

    return $after_form;
}

// }}}
// {{{ Function: spamhurdles_api_get_formdata

/**
 * When a form is posted, then this function can be used to retrieved
 * the spam hurdles data from the request. This data should be in the
 * POST data in a field named "spamhurdles_<form id>".
 *
 * @param string $form_id
 *     An identifier for the current form. This must be the same form id
 *     as the one that was used when calling {@link spamhurdles_api_init()}.
 *
 * @return NULL | array
 *    If (valid) spamhurdles data is found in the form, then this data
 *    is returned. Otherwise NULL is returned.
 */
function spamhurdles_api_get_formdata($form_id)
{
    if (!isset($_POST['spamhurdles_'.$form_id])) return NULL;
    $data = spamhurdles_decrypt($_POST['spamhurdles_'.$form_id]);
    if (!is_array($data)) return NULL;
    return $data;
}

// }}}
// {{{ Function: spamhurdles_api_check_form

/**
 * Check the Spam Hurdles data for a posted form.
 *
 * When a form is posted, then this function has to be called to
 * check the data that was posted.
 *
 * @param string $form_id
 *     An identifier for the current form. This must be the same form id
 *     as the one that was used when calling {@link spamhurdles_api_init()}.
 *
 * @return array
 *     An array, containing two elements.
 *     The first element is the result status of the spam hurdle check.
 *     This is one of SPAMHURDLES_OK, SPAMHURDLES_WARNING or SPAMHURDLES_FATAL.
 *     The second element is the error message or NULL if there was no error.
 */
function spamhurdles_api_check_form($form_id)
{
    global $PHORUM;
    $error = $PHORUM['DATA']['LANG']['mod_spamhurdles']['PostingRejected'];
    $status = SPAMHURDLES_FATAL;

    // Retrieve the spam hurdles data from the form.
    $data = spamhurdles_api_get_formdata($form_id);

    // Check if we were able to retrieve spam hurdles data from the form.
    if ($data === NULL)
    {
        spamhurdles_log(
            "Spam Hurdles blocked post, form id \"$form_id\"",
            "The posting form was posted without or with invalid " .
            "Spam Hurdles data."
        );

        return array($status, $error);
    }

    // Check if the id in the form data is the same as the
    // id that we expect to see.
    if ($data['id'] != $form_id)
    {
        spamhurdles_log(
            "Spam Hurdles blocked post, form id \"$form_id\"",
            "The posting form was posted with an invalid Spam Hurdles " .
            "form id.<br/>" .
            "<br/>" .
            "Posted form id = {$data['id']}<br/>" .
            "Expected form id =  $form_id"
        );

        return array($status, $error);
    }

    // Check if the TTL on the data didn't expire.
    if ($data['ttl'] < time())
    {
        // Only for 5.2. In 5.3 this was moved to formatting API functions.
        if (file_exists('./include/format_functions.php')) {
            require_once './include/format_functions.php';
        }

        spamhurdles_log(
            "Spam Hurdles blocked post, form id \"$form_id\"",
            "The posting form was posted with valid Spam Hurdles data, " .
            "but the data expired at " .
            phorum_date($PHORUM["short_date_time"], $data['ttl'])
        );

        return array($status, $error);
    }

    // Let the spam hurdles check the data. If one sees a problem, then
    // it can set the $data['error'] and $data['status'] elements. It can
    // also add log messages to the $data['log'] array.
    $data['error']  = NULL;
    $data['status'] = SPAMHURDLES_OK;
    $data['log']    = array();
    $data = spamhurdles_hurdle_call('check_form', $data, $data['hurdles']);

    $status = $data['status'];
    if ($status !== SPAMHURDLES_OK)
    {
        spamhurdles_log(
            "Spam Hurdles blocked post, form id \"$form_id\"",
            "Block type: " .
            ($status === SPAMHURDLES_FATAL ? 'fatal' : 'warning') . "\n" .
            "Block error: \"" . $data['error'] . "\"" .
            (empty($data['log'])
             ? '' : "\n\nInfo: " . implode(' ', $data['log']))
        );
    }

    return array($data['status'], $data['error']);
}

// }}}
// {{{ Function: spamhurdles_api_after_post
/**
 * Tasks to handle after posting a message.
 *
 * @param string $form_id
 *     An identifier for the current form. This must be the same form id
 *     as the one that was used when calling {@link spamhurdles_api_init()}.
 */
function spamhurdles_api_after_post($form_id)
{
    global $PHORUM;

    // Retrieve the spam hurdles data from the form.
    $data = spamhurdles_api_get_formdata($form_id);

    // Let the spam hurdles run after post tasks.
    spamhurdles_hurdle_call('after_post', $data, $data['hurdles']);
}

// }}}
// {{{ Function: spamhurdles_api_collect_garbage

/**
 * Spam hurdle implementations could be using some form of storage that
 * requires garbage collection management. This function will call
 * the collect_garbage function for all spam hurdles that implement
 * garbage collection.
 */
function spamhurdles_api_collect_garbage()
{
    spamhurdles_hurdle_call('collect_garbage', NULL);
}

// }}}

// ----------------------------------------------------------------------
// API utility functions
// ----------------------------------------------------------------------

// {{{ Function: spamhurdles_encrypt

/**
 * Crypt data using Phorum's secret key.
 * This is used to be able to send Spam Hurdles data to the client,
 * without allowing the client to read the data.
 *
 * @param mixed $data
 *     The data to crypt. This can be an array. This function will
 *     serialize the array.
 *
 * @return string
 *     The encrypted data, safe to be sent to the client.
 */
function spamhurdles_encrypt($data)
{
    global $PHORUM;

    $aes = new Crypt_AES();
    $aes->setKey($PHORUM['private_key']);

    return base64_encode($aes->encrypt(serialize($data)));
}

// }}}
// {{{ Function: spamhurdles_decrypt

/**
 * Decrypt data using Phorum's secret key.
 * This is used to decrypt data that was encrypted by the
 * {@link spamhurdles_encrypt()} function.
 *
 * @param $data
 *     The data to decrypt.
 *
 * @return mixed
 *     The decrypted data. This can be array data, if the original
 *     data that was passed to {@link spamhurdles_encrypt()} was an array.
 */
function spamhurdles_decrypt($data)
{
    global $PHORUM;

    $aes = new Crypt_AES();
    $aes->setKey($PHORUM['private_key']);

    // Don't splash decrypting and unpacking warnings all over the browser.
    $decrypted = @$aes->decrypt(base64_decode($data));
    $unpacked = @unserialize($decrypted);
    if ($decrypted === FALSE || $unpacked === FALSE) trigger_error(
        'Cannot decrypt the spam hurdles data. ' .
        'This probably means that somebody or something tampered with ' .
        'the crypted spam hurdles data string that was sent to the server.',
        E_USER_ERROR
    );
    return $unpacked;
}

// }}}
// {{{ Function: spamhurdles_hurdle_call

/**
 * Call a function in the spam hurdle implementations.
 *
 * This is a small hook system for the spam hurdles module, that
 * structures the implementation of spam hurdles.
 *
 * @param string $call
 *     The call to execute. For each enabled spam hurdle, it is checked
 *     whether a function "spamhurdle_<hurdle name>_<call>" exists. If
 *     yes, then that function is called with the {@link $data} as the
 *     argument. The funtion has to return (a possibly modified) $data.
 *
 * @param mixed $data
 *     Used as the argument for the spamhurdle function that is called.
 *     The spamhurdle has to return this argument, possibly modified.
 *
 * @param NULL | array $hurdles
 *     An array of hurdles for which the function has to be called.
 *     If NULL (default), then the function will be called in all
 *     spam hurdle implementations that have the call defined as
 *     TRUE in the hurdle registry.
 *
 * @return mixed
 *     The (possibily modified) {@link $data}.
 */
function spamhurdles_hurdle_call($call, $data, $hurdles = NULL)
{
    global $PHORUM;
    $registry =& $PHORUM['spamhurdles_registry'];

    if (empty($hurdles)) {
        $hurdles = array();
        foreach ($registry as $id => $info) {
            if (!empty($info[$call])) {
                $hurdles[] = $id;
            }
        }
    }

    $path = dirname(__FILE__).'/hurdles';

    // Call the function for each enabled spam hurdle.
    foreach ($hurdles as $hurdle)
    {
        // If needed, look up the hurdle id by its symbolic name.
        if (!isset($registry[$hurdle])) {
            foreach ($registry as $h => $hdata) {
                if ($hdata['name'] == $hurdle) {
                    $hurdle = $h;
                    break;
                }
            }
        }

        // Check the hurdle name.
        if (!isset($registry[$hurdle])) trigger_error(
            "spamhurdles_hurdle_call(): Illegal hurdle name: " .
            htmlspecialchars($hurdle),
            E_USER_ERROR
        );

        // Load the code for the hurdle if it wasn't yet loaded.
        if (empty($registry[$hurdle]['loaded']))
        {
            $file = $path . '/' . $registry[$hurdle]['name'] . '.php';
            require_once($file);
            $registry[$hurdle]['loaded'] = TRUE;
        }

        // Run the hurdle's function code if available.
        // This code could add extra information to the spam hurdles data.
        $func = 'spamhurdle_'.$registry[$hurdle]['name'].'_'.$call;

        if (function_exists($func)) {
            $data = $func($data);
        }
    }

    return $data;
}

// }}}
// {{{ Function: spamhurdles_generate_key

function spamhurdles_generate_key()
{
    // A bit of time, remote info and random data to create a nice key.
    $chars = "0123456789" .
             "abcdefghijklmnopqrstuvwxyz" .
             "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $key = microtime() . ":" .  $_SERVER["REMOTE_ADDR"];
    for ($i = 0; $i<40; $i++) {
        $key .= substr($chars, rand(0, strlen($chars)-1), 1);
    }
    // And MD5 will bring it into a nice shape.
    $key = md5($key);

    return $key;
}

// }}}
// {{{ Function: spamhurdles_log

function spamhurdles_log($message, $details)
{
    global $PHORUM;

    if (!empty($PHORUM["mod_spamhurdles"]["log_events"]) &&
        function_exists('event_logging_writelog')) {
        event_logging_writelog(array(
            'message'   => $message,
            'details'   => $details,
            'loglevel'  => EVENTLOG_LVL_INFO
        ));
    }
}

// }}}
// {{{ Function: spamhurdles_get_hurdles_for_form

/**
 * Check what spam hurdles to run for a specific form.
 * This relates to the configuration in the module's settings screen,
 * in which the various spam hurdles are configured for various forms.
 *
 * @param string $form_id
 *     The idenfitier for the the form for which to retrieve a list of
 *     Spam Hurdles to run.
 *
 * @return array
 *     An array containing a list of spam hurdles to run.
 */
function spamhurdles_get_hurdles_for_form($form_id)
{
    global $PHORUM;

    $enabled_hurdles = array();

    // Check if a configuration is available for the provided form_id.
    // If not then we trigger an error.
    if (!isset($PHORUM['mod_spamhurdles'][$form_id]['hurdles'])) {
        trigger_error(
            'spamhurdles_get_hurdles_for_form(): No configuration found for ' .
            'form_id ' . $form_id,
            E_USER_ERROR
        );
    }

    // Check what hurdles should be activated for the current user.
    $hurdles = $PHORUM['mod_spamhurdles'][$form_id]['hurdles'];
    foreach ($PHORUM['spamhurdles_registry'] as $key => $info)
    {
        if (!isset($hurdles[$info['name']])) {
            continue;
        }

        switch ($hurdles[$info['name']])
        {
            case 'anonymous':
                if (!$GLOBALS['PHORUM']['DATA']['LOGGEDIN']) {
                    $active = TRUE;
                } else {
                    $active = FALSE;
                }
                break;

            case "all":
                $active = TRUE;
                break;

            case "none":
                $active = FALSE;
                break;

            default:
                trigger_error(
                    'spamhurdles_get_hurdles_for_form(): Illegal ' .
                    'configuration value for spam hurdle ' .
                    $info['name'] . ': ' . $hurdles[$info['name']]
                );
                break;
        }

        if ($active) {
            $enabled_hurdles[$key] = $key;
        }
    }

    return $enabled_hurdles;
}
// }}}

// ----------------------------------------------------------------------
// Modified iScramble code
// ----------------------------------------------------------------------

// {{{ iScramble functions
/******************************************************************************
 * iScramble - Scramble HTML source to make it difficult to read              *
 *                                                                            *
 * Visit the iScramble homepage at http://www.z-host.com/php/iscramble        *
 *                                                                            *
 * Copyright (C) 2003 Ian Willis. All rights reserved.                        *
 *                                                                            *
 * This script is FreeWare.                                                   *
 *                                                                            *
 ******************************************************************************/

/******************************************************************************
 * Modified by Maurice Makaay <maurice@phorum.org> for making this code work  *
 * inside xml/xhtml content as well. This includes some code to execute       *
 * javascript code that is in the scrambled data (the original                *
 * document.writeln() method will execute this JavaScript code as well,       *
 * but when adding javascript to the DOM tree using DOM manipulation          *
 * functions, it will not execute).                                           *
 ******************************************************************************/

/* Perform ROT13 encoding on a string */
function spamhurdles_iScramble_rot13($str)
{
    $from = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $to = 'nopqrstuvwxyzabcdefghijklmNOPQRSTUVWXYZABCDEFGHIJKLM';

    return strtr($str, $from, $to);
}

/* Perform the equivalent of the JavaScript escape function */
function spamhurdles_iScramble_escape($plain)
{
    $escaped = "";
    $passChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789*@-_+./";

    for ($i = 0; $i < strlen($plain); $i++)
    {
        $char = $plain{$i};
        if (strpos($passChars, $char) === false)
        {
            // $char is not in the list of $passChars. Encode in hex format
            $escaped .= sprintf("%%%02X", ord($char));
        }
        else
        {
            $escaped .= $char;
        }
    }

    return $escaped;
}


/* Main iScramble function
 *
 * This function takes plain text and scrambles them. It returns some JavaScript
 * that contains the scrambled text and JavaScript to unscramble it.
 *
 * RETURNS:     JavaScript code to display the scrambled message.
 *
 * PARAMETERS:
 *
 *  NAME        TYPE
 *
 *  $plain      String      Plan text to scramble
 *  $longPwd    Boolean     True for better scrambling, using a longer password.
 *                          This produces larger JavaScript code.
 *                          Defaults to False.
 *  $rot13      Boolean     True for better scrambling, using rot13 encoding of
 *                          the plain text. This produces larger JavaScript
 *                          code and takes longer to decode. Not recommended
 *                          for large $plain strings.
 *                          Defaults to False.
 *  $sorry      String      Message displayed if visitor does not have
 *                          JavaScript enabled in their web browser.
 *                          Defaults to "<i>[Please Enable JavaScript]</i>".
 */
function spamhurdles_iScramble($plain, $longPwd=False, $rot13=False, $sorry="<i>[Please Enable JavaScript]</i>")
{
    $escaped = spamhurdles_iScramble_escape($plain);
    if ($rot13)
    {
        $escaped = spamhurdles_iScramble_rot13($escaped);
    }

    $numberOfColumns = 10;
    $numberOfRows = ceil(strlen($escaped) / $numberOfColumns);
    $scrambled = "";

    $escaped = str_pad($escaped, $numberOfColumns * $numberOfRows);

    // Choose a password
    $password = "";
    srand(time());
    for ($j = 0; $j < ($longPwd ? $numberOfRows : 1); $j++)
    {
        $availChars = substr("0123456789", 0, $numberOfColumns);
        for ($i = 0 ; $i < $numberOfColumns; $i++)
        {
            $char = $availChars{ rand(0, strlen($availChars)-1) };
            $password .= $char;
            $availChars = str_replace($char, "", $availChars);
        }
    }

    $scramblePassword = str_repeat($password, $longPwd ? 1 : $numberOfRows);

    // Do the scrambling
    $scrambled = str_repeat(" ", $numberOfColumns * $numberOfRows);
    $k = 0;
    for ($i = 0; $i < $numberOfRows; $i++)
    {
        for($j = 0; $j < $numberOfColumns; $j++ )
        {
            $ts1=(((int)$scramblePassword{$k}) * $numberOfRows) + $i;
            $ts2=$k;

            $scrambled{(int)(((int)$scramblePassword{$k}) * $numberOfRows) + $i} = $escaped{$k};
            $k++;
        }
    }

    // Generate the JavaScript
    // Phorum change: make script compliant with w3 checks.
    $id = 'iscramble_' . md5($scrambled);
    $javascript = "<span id=\"$id\"></span>";
    $javascript .= "<script type=\"text/javascript\">\n";
    $javascript .= "//<![CDATA[\n";

    $javascript .= "var box = document.getElementById('$id');";
    $javascript .= "var a='';var b='$scrambled';var c='$password';";
    if ($rot13)
    {
        $javascript .= "var d='';";
    }
    $javascript .= "for(var i=0;i<$numberOfRows;i++) for(var j=0;j<$numberOfColumns;j++) ";

    if ($rot13)
    {
        $javascript .= "{d=b.charCodeAt(";
    }
    else
    {
        $javascript .= "a+=b.charAt(";
    }

    if ($longPwd)
    {
        $javascript .= "(parseInt(c.charAt(i*$numberOfColumns+j))*$numberOfRows)+i); ";
    }
    else
    {
        $javascript .= "(parseInt(c.charAt(j))*$numberOfRows)+i);";
    }

    if ($rot13)
    {
        $javascript .= "if ((d>=65 && d<78) || (d>=97 && d<110)) d+=13; else if ((d>=78 && d<91) || (d>=110 && d<123)) d-=13;a+=String.fromCharCode(d);}";
    }

    $javascript .= "var unscrambled_data = unescape(a);\n";
    $javascript .= "box.innerHTML = unscrambled_data;\n";
    $javascript .= "spamhurdles_eval_javascript(unscrambled_data);\n";
    $javascript .= "//]]>\n";
    $javascript .= "</script>\n";
    $javascript .= "<noscript>\n$sorry\n</noscript>\n";

    return $javascript;
}
// }}}

?>
