<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2016  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * This script implements functions for processing page requests.
 *
 * @package    PhorumAPI
 * @subpackage Request
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// {{{ Function: phorum_api_request_parse()
/**
 * Parse a Phorum page request.
 *
 * This will handle a couple of tasks for parsing Phorum requests:
 *
 * - When PHP magic quotes are enabled, the automatically added
 *   slashes are stripped from the request data.
 * - The "parse_request" hook is called.
 * - The $_SERVER["QUERY_STRING"] or $PHORUM_CUSTOM_QUERY_STRING
 *   (a global variable that can be set to override the standard
 *   QUERY_STRING) is parsed. The request variables are stored
 *   in $PHORUM["args"].
 * - For the file download script, $_SERVER['PATH_INFO'] might be
 *   used to set the file to download. If this is the case, then
 *   this path info is parsed into standard Phorum arguments.
 * - If a forum_id is available in the request, then it is stored
 *   in $PHORUM['forum_id'].
 * - If a ref_thread_id is available in the request, then it is stored
 *   in $PHORUM['ref_thread_id']. This is the referring thread id.
 * - If a ref_message_id is available in the request, then it is stored
 *   in $PHORUM['ref_message_id']. This is the referring message id.
 */
function phorum_api_request_parse()
{
    global $PHORUM;

    // Thanks a lot for magic quotes :-/
    // In PHP6, magic quotes are (finally) removed, so we have to check for
    // the get_magic_quotes_gpc() function here. The "@" is for suppressing
    // deprecation warnings that are spawned by PHP 5.3 and higher when
    // using the get_magic_quotes_gpc() function.
    if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() &&
        (count($_POST) || count($_GET))) {

        foreach ($_POST as $key => $value) {
            if (!is_array($value)) {
                $_POST[$key] = stripslashes($value);
            } else {
                $_POST[$key] = phorum_api_request_stripslashes($value);
            }
        }
        foreach ($_GET as $key => $value) {
            if (!is_array($value)) {
                $_GET[$key] = stripslashes($value);
            } else {
                $_GET[$key] = phorum_api_request_stripslashes($value);
            }
        }
    }

    // Also make sure that magic_quotes_runtime is disabled.
    if (function_exists('set_magic_quotes_runtime')) {
        @set_magic_quotes_runtime(FALSE);
    }

    // Thanks a lot for configurable argument separators :-/
    // In some cases we compose GET based URLs, with & and = as respectively
    // argument and key/value separators. On some systems, the "&" character
    // is not configured as a valid separator. For those systems, we have
    // to parse the query string ourselves.
    if (isset($_SERVER['QUERY_STRING']) &&
        strpos($_SERVER['QUERY_STRING'], '&') !== FALSE)
    {
        $separator = get_cfg_var('arg_separator.input');
        if ($separator !== FALSE && strpos($separator, '&') === FALSE)
        {
            $parts = explode('&', $_SERVER['QUERY_STRING']);
            $_GET = array();
            foreach ($parts as $part)
            {
                list ($key, $val) = explode('=', rawurldecode($part), 2);

                // Handle array[] style GET arguments.
                if (preg_match('/^(.+)\[(.*)\]$/', $key, $m))
                {
                    if (!isset($_GET[$m[1]]) || !is_array($_GET[$m[1]])) {
                        $_GET[$m[1]] = array();
                    }
                    if ($m[2] == '') {
                        $_GET[$m[1]][] = $val;
                    } else {
                        $_GET[$m[1]][$m[2]] = $val;
                    }
                }
                // Handle standard GET arguments.
                else
                {
                    $_GET[$key] = $val;
                    $_REQUEST[$key] = $val;
                }
            }
        }
    }

    /*
     * [hook]
     *     parse_request
     *
     * [description]
     *     This hook gives modules a chance to tweak the request environment,
     *     before Phorum parses and handles the request data. For tweaking the
     *     request environment, some of the options are:
     *     <ul>
     *       <li>
     *         Changing the value of <literal>$_REQUEST["forum_id"]</literal>
     *         to override the used forum_id.
     *       </li>
     *       <li>
     *         Changing the value of <literal>$_SERVER["QUERY_STRING"]</literal>
     *         or setting the global override variable
     *         <literal>$PHORUM_CUSTOM_QUERY_STRING</literal> to feed Phorum a
     *         different query string than the one provided by the webserver.
     *       </li>
     *     </ul>
     *     Tweaking the request data should result in data that Phorum can handle.
     *
     * [category]
     *     Request initialization
     *
     * [when]
     *     Right before Phorum runs the request parsing code in
     *     <filename>common.php</filename>.
     *
     * [input]
     *     No input.
     *
     * [output]
     *     No output.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_parse_request()
     *     {
     *         // Override the query string.
     *         global $PHORUM_CUSTOM_QUERY_STRING
     *         $PHORUM_CUSTOM_QUERY_STRING = "1,some,phorum,query=string";
     *
     *         // Override the forum_id.
     *         $_REQUEST['forum_id'] = "1234";
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["parse_request"])) {
        phorum_api_hook("parse_request");
    }

    // Look for and parse the QUERY_STRING or custom query string in
    // $PHORUM_CUSTOM_QUERY_STRING..
    // For the admin environment, we don't handle this request handling step.
    // The admin scripts use $_POST and $_GET instead of $PHORUM['args'].
    if (!defined("PHORUM_ADMIN") &&
        (isset($_SERVER["QUERY_STRING"]) ||
         isset($GLOBALS["PHORUM_CUSTOM_QUERY_STRING"]))) {

        // Standard GET request parameters.
        if (strpos($_SERVER["QUERY_STRING"], "&") !== FALSE)
        {
            $PHORUM["args"] = $_GET;
        }
        // Phorum formatted parameters. Phorum formatted URLs do not
        // use the standard GET parameter schema. Instead, parameters
        // are added comma separated to the URL.
        else
        {
            $Q_STR = empty($GLOBALS["PHORUM_CUSTOM_QUERY_STRING"])
                   ? $_SERVER["QUERY_STRING"]
                   : $GLOBALS["PHORUM_CUSTOM_QUERY_STRING"];

            // Ignore stuff past a # (HTML anchors).
            if (strstr($Q_STR, '#')) {
                list($Q_STR, $other) = explode('#', $Q_STR, 2);
            }

            // Explode the query string on commas.
            $PHORUM['args'] = $Q_STR == '' ? array() : explode(',', $Q_STR);

            // Check for any named parameters. These are parameters that
            // are added to the URL in the "name=value" form.
            if (strstr($Q_STR, "="))
            {
                foreach($PHORUM['args'] as $key => $arg)
                {
                    $arg = rawurldecode($arg);
                    $PHORUM['args'][$key] = $arg;

                    // If an arg has an =, then create an element in the
                    // argument array with the left part as the key and
                    // the right part as the value.
                    if (strstr($arg, '='))
                    {
                        list($var, $value) = explode('=', $arg, 2);

                        // Get rid of the original numbered arg.
                        unset($PHORUM["args"][$key]);

                        // Add the named arg
                        $PHORUM['args'][$var] = $value;
                    }
                }
            }
            else {
                foreach($PHORUM['args'] as $key => $arg) {
                    $PHORUM['args'][$key] = rawurldecode($arg);
                }
            }

        }

        // Handle path info based URLs for the file downloading script.
        if (phorum_page == 'file' &&
            !empty($_SERVER['PATH_INFO']) &&
            preg_match('!^/(download/)?(\d+)/(\d+)/!', $_SERVER['PATH_INFO'], $m)) {
            $PHORUM['args']['file'] = $m[3];
            $PHORUM['args'][0] = $PHORUM['forum_id'] = $m[2];
            $PHORUM['args']['download'] = empty($m[1]) ? 0 : 1;
        }

        // Set the active forum_id if not already set by a forum_id
        // request parameter, when the forum_id is passed as the first
        // Phorum request parameter.
        if (empty($PHORUM['forum_id']) && isset($PHORUM['args'][0])) {
            $PHORUM['forum_id'] = (int) $PHORUM['args'][0];
        }

        // Get the forum_id, ref_thread_id and ref_message_id if set using
        // a named POST, GET or Phorum query arg parameter.
        foreach (array('forum_id', 'ref_thread_id', 'ref_message_id') as $field) {
            if (isset($_POST[$field]) && is_numeric($_POST[$field])) {
                $PHORUM[$field] = (int) $_POST[$field];
            } elseif (isset($_GET[$field]) && is_numeric($_GET[$field])) {
                $PHORUM[$field] = (int) $_GET[$field];
            } elseif (isset($PHORUM['args'][$field]) &&
                      is_numeric($PHORUM['args'][$field])) {
                $PHORUM[$field] = (int) $PHORUM['args'][$field];
            }
        }

        // Set the active ref_thread_id and ref_message_id if not already set
        // by a request parameter and when these are passed as numeric
        // arguments.
        if (empty($PHORUM['ref_thread_id']))
        {
            if (in_array(phorum_page, array(
                // The pages that use the numeric "forum,thread,message" format.
                'list', 'read', 'login', 'register', 'pm', 'control'
            ))) {
                if (empty($PHORUM['ref_thread_id']) && isset($PHORUM['args'][1]) &&
                    is_numeric($PHORUM['args'][1])) {
                    $PHORUM['ref_thread_id'] = $PHORUM['args'][1];
                }
                if (empty($PHORUM['ref_message_id']) && isset($PHORUM['args'][2]) &&
                    is_numeric($PHORUM['args'][2])) {
                    $PHORUM['ref_message_id'] = $PHORUM['args'][2];
                }
            }
        }

        // The ref_message_id can be set to the ref_thread_id, if no
        // ref_message_id was provided.
        if (!empty($PHORUM['ref_thread_id']) && empty($PHORUM['ref_message_id'])) {
            $PHORUM['ref_message_id'] = $PHORUM['ref_thread_id'];
        }
    }
}
// }}}

// {{{ Function: phorum_api_request_stripslashes()
/**
 * Recursively remove slashes from array elements.
 *
 * @param array $array
 *     The data array to modify.
 *
 * @return array
 *     The modified data array.
 */
function phorum_api_request_stripslashes($array)
{
    if (!is_array($array)) {
        return $array;
    } else {
        foreach($array as $key => $value) {
            if (!is_array($value)) {
                $array[$key] = stripslashes($value);
            } else {
                $array[$key] = phorum_api_request_stripslashes($value);
            }
        }
    }
    return $array;
}
// }}}

// {{{ Function: phorum_api_request_check_token()
/**
 * Setup and check posting tokens for form POST requests.
 *
 * For protecting forms against CSRF attacks, a signed posting token
 * is utilized. This posting token must be included in the POST request.
 * Without the token, Phorum will not accept the POST data.
 *
 * This function will check whether we are handling a POST request.
 * If yes, then check if an anti-CSRF token is provided in the POST data.
 * If no token is available or if the token does not match the expected
 * token, then the POST request is rejected.
 *
 * As a side effect, the required token is added to the {POST_VARS}
 * template variable. This facilitates protecting scripts. As
 * long as the template variable is added to the <form> for the
 * script, it will be automatically protected.
 *
 * @param string $target_page
 *     The page for which to check a posting token. When no target
 *     page is provided, then the constant "phorum_page" is used instead.
 *
 * @return string
 *     The expected posting token.
 */
function phorum_api_request_check_token($target_page = NULL)
{
    global $PHORUM;

    if ($target_page === NULL) $target_page = phorum_page;

    $variable = 'posting_token:' . $target_page;

    // Generate the posting token.
    $posting_token = md5(
        ($target_page !== NULL ? $target_page : phorum_page) . '/' .
        (
          $PHORUM['user']['user_id']
          ? $PHORUM['user']['password'].'/'.$PHORUM['user']['sessid_lt']
          : (
              isset($_SERVER['HTTP_USER_AGENT'])
              ? $_SERVER['HTTP_USER_AGENT']
              : 'unknown'
            )
        ) . '/' .
        $PHORUM['private_key']
    );

    // Add the posting token to the {POST_VARS}.
    $PHORUM['DATA']['POST_VARS'] .=
        "<input type=\"hidden\" name=\"$variable\" " .
        "value=\"$posting_token\"/>\n";

    // Check the posting token if a form post is done.
    if (!empty($_POST))
    {
        if (!isset($_POST[$variable]) ||
            $_POST[$variable] != $posting_token) {
            $PHORUM['DATA']['ERROR'] =
                'Possible hack attempt detected. ' .
                'The posted form data was rejected.';
            phorum_build_common_urls();
            phorum_api_output("message");
            exit();
        }
    }

    return $posting_token;
}
// }}}

// {{{ Function: phorum_api_request_require_login()
/**
 * Require that the user is logged in.
 *
 * A check is done to see if the user is logged in.
 * If not, then the user is redirected to the login page.
 *
 * @param bool $tight_security
 *     When this parameter has a true value (default is FALSE),
 *     then a tight security check is done. This means that a check
 *     is done to see if a short term session is active. An available
 *     long term session is not good enough in this case.
 *
 *     Tight Security is an option that can be enabled from Phorum's
 *     admin interface.
 */
function phorum_api_request_require_login($tight_security = FALSE)
{
    global $PHORUM;

    // Check if we have an authenticated user.
    if (!$PHORUM['user']['user_id']) {
        phorum_api_redirect(
            PHORUM_LOGIN_URL,
            'redir=' . urlencode(phorum_api_url_current())
        );
    }

    // Handle tight security.
    if ($tight_security && !$PHORUM['DATA']['FULLY_LOGGEDIN']) {
        phorum_api_redirect(
            PHORUM_LOGIN_URL,
            'redir=' . urlencode(phorum_api_url_current())
        );
    }
}
// }}}

?>
