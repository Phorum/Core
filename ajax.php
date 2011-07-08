<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
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

// This script is used for handling Ajax calls to the Phorum system.
// Ajax calls can either be implemented as scripts files in
// "./include/ajax/call.<callname>.php" or through modules that implement
// the "ajax_call" hook.

define('phorum_page', 'ajax');

require_once('./common.php');
require_once('./include/api/json.php');

// ----------------------------------------------------------------------
// Client JavaScript library
// ----------------------------------------------------------------------

if (isset($PHORUM['args'][0]) && $PHORUM['args'][0] == 'client')
{
    header("Content-type: text/javascript");
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 28800) . ' GMT');
    header('Cache-control: must-revalidate');
    header('Pragma: cache');

    include('./include/ajax/client.js.php');
    exit;
}

// ----------------------------------------------------------------------
// Show examples page.
// ----------------------------------------------------------------------

if (isset($PHORUM['args'][0]) && $PHORUM['args'][0] == 'examples') {
    include('./include/ajax/examples.php');
    exit;
}

// ----------------------------------------------------------------------
// Dispatch Ajax calls
// ----------------------------------------------------------------------

$PHORUM['ajax_args'] = array();

// JSON data based request (stored in a POST or PUT request body)
if ($_SERVER['REQUEST_METHOD']=='POST' || $_SERVER['REQUEST_METHOD']=='PUT')
{
    // Get the POST body.
    $body = trim(file_get_contents('php://input'));
    if ($body == '') phorum_ajax_error('Ajax POST request misses body');

    // Set the Ajax arguments.
    $PHORUM["ajax_args"] = phorum_api_json_decode($body);
    if ($PHORUM["ajax_args"] == NULL) phorum_ajax_error(
        'Ajax POST request body does not seem to be JSON data'
    );
}
// GET based request.
elseif (isset($_GET['call']))
{
    // Set the Ajax arguments.
    $PHORUM['ajax_args'] = $_GET;
}
// Phorum argument based request.
elseif (isset($PHORUM['args']['call']))
{
    // Set the Ajax arguments.
    $PHORUM['ajax_args'] = $PHORUM['args'];
}

// Check if we got a valid Ajax request. The request should contain at
// least a "call" field in the Ajax arguments.
if (empty($PHORUM['ajax_args']) || !isset($PHORUM['ajax_args']['call'])) {
    phorum_ajax_error('Illegal Ajax request');
}

$ajax_call = basename($PHORUM['ajax_args']['call']);

// try to get some session-id if there isn't already a user loaded through
// the regular ways
if(empty($PHORUM['user']['user_id'])) {
    // check if we got a session-id in the ajax args and if we got one
    // try to load a user with that data
    $ajax_session_id = phorum_ajax_getarg(PHORUM_SESSION_LONG_TERM,'string',0);
    if(!empty($ajax_session_id)) {
        $PHORUM['use_cookies']=PHORUM_USE_COOKIES;
        $PHORUM['args'][PHORUM_SESSION_LONG_TERM]=$ajax_session_id;
        phorum_api_user_session_restore(PHORUM_FORUM_SESSION);
    }
}

/**
 * [hook]
 *     ajax_<call>
 *
 * [availability]
 *     Phorum 5 >= 5.2.8
 *
 * [description]
 *     This hook allows module writers to implement calls for the
 *     Phorum Ajax layer.<sbr/>
 *     <sbr/>
 *     The "call" argument from the Ajax argument array is used to
 *     construct the name of the hook that will be called. For example
 *     for the call "sayhello" the called hook will be
 *     <literal>call_sayhello</literal><sbr/>
 *     <sbr/>
 *     A call implementation should always be using
 *     the provided functions <literal>phorum_ajax_return()</literal>
 *     and <literal>phorum_ajax_error()</literal> to return data to the
 *     client. Because these functions will call <literal>exit</literal>
 *     after they are done, hook functions that implement an Ajax call stop
 *     page execution and do not return like other hook functions.
 *     Only if the hook function decides for some reason that the Ajax call
 *     is not to be handled by the module, it can return the Ajax argument
 *     array.
 *
 * [category]
 *     Miscellaneous
 *
 * [when]
 *     Just before ajax.php tries to find a built-in handler script
 *     for an Ajax call. Therefore, this hook can also be used to
 *     override core Ajax call implementations. We strongly
 *     discourage doing so though.
 *
 * [input]
 *     The Ajax argument array
 *
 * [output]
 *     The same array as the one that was used for the hook call argument.
 *
 * [example]
 * <hookcode>
 * function phorum_mod_foo_ajax_sayhello($ajax_args)
 * {
 *     // An optional name=.... argument can be used in the request.
 *     $name = phorum_ajax_getarg('name', 'string', 'Anonymous Person');
 *
 *     // This will return a JSON encoded string to the client.
 *     phorum_ajax_return("Hello, $name");
 * }
 * </hookcode>
 *
 * For this hook implementation, a GET based URL to fire this
 * Ajax call could look like
 * <literal>http://example.com/ajax.php?call=sayhello,name=JohnDoe</literal>.
 */
$call_hook = 'ajax_' . $ajax_call;
if (isset($PHORUM['hooks'][$call_hook])) {
    phorum_hook($call_hook, $PHORUM['ajax_args']);
}

// Check if the Ajax call has a core handler script.
if (file_exists("./include/ajax/call.$ajax_call.php")) {
    include("./include/ajax/call.$ajax_call.php");
    exit();
}

// No handler script available. Bail out.
phorum_ajax_error('Unknown call "'.$ajax_call.'" in Ajax POST request');


// ----------------------------------------------------------------------
// Utility functions that can be used by Ajax call implementations
// ----------------------------------------------------------------------

/**
 * Return an Ajax error to the caller.
 *
 * This will send an error (500 HTTP status code) message to the client,
 * using UTF-8 as the character set.
 *
 * @param string $message
 *     The error message to return.
 */
function phorum_ajax_error($message)
{
    $message = phorum_api_json_convert_to_utf8($message);

    header("HTTP/1.1 500 Phorum Ajax error");
    header("Status: 500 Phorum Ajax error");
    header("Content-Type: text/plain; charset=UTF-8");
    print $message;
    exit(1);
}

/**
 * Return an Ajax result to the caller.
 *
 * The data will be sent to the client in the JSON encoding format,
 * using UTF-8 as the character set.
 *
 * @param mixed $data
 *     The data to return in the body.
 */
function phorum_ajax_return($data)
{
    header("Content-Type: text/plain; charset=UTF-8");
    print phorum_api_json_encode($data);
    exit(0);
}

/**
 * Retrieve an argument from the call arguments.
 *
 * The Ajax call arguments are stored in the array $PHORUM['ajax_args'].
 * his function can be used to retrieve a single argument from this array.
 *
 * It can check the argument type by providing the $type parameter.
 * Also, a default value can be supplied, which will be used in case the
 * requested argument wasn't included in the call data.
 *
 * If an argument is invalid or missing without providing a default value,
 * then an Ajax error will be returned.
 *
 * @param string $arg
 *     The name of the argument to retrieve.
 *
 * @param mixed $type
 *
 *     The type of data to retrieve. Options are: "int", "int>0", "string",
 *     "boolean". These types can be prefixed with "array:" to indicate
 *     that an array of those types has to be returned. If the input
 *     argument is not an array in this case, then this function will
 *     convert it to a single item array.
 *
 *     The type can also be NULL, in which case the argument is not checked
 *     at all. This is useful for more complex data types, which need to be
 *     checked by the function that uses them.
 *
 * @param mixed $default
 *     The default value for the argument or NULL if no default is available.
 *
 * @return $value - The value for the argument.
 */
function phorum_ajax_getarg($arg, $type = NULL, $default = NULL)
{
    global $PHORUM;

    // Fetch the argument from the Ajax request data.
    if (! isset($PHORUM["ajax_args"][$arg])) {
        if (! is_null($default)) {
            return $default;
        } else phorum_ajax_error(
            "missing Ajax argument: ".htmlspecialchars($arg)
        );
    }
    $value = $PHORUM["ajax_args"][$arg];

    // Return immediately, if we don't need to do type checking.
    if (is_null($type)) {
        return $value;
    }

    // Check if an array should be returned.
    $array = FALSE;
    if (substr($type, 0, 6) == 'array:') {
        $type = substr($type, 6);
        $array = TRUE;
    }
    if ($array && !is_array($value)) phorum_ajax_error(
        "illegal argument: argument \"$arg\" should be an array ($type)");
    if (!$array && is_array($value)) phorum_ajax_error(
        "illegal argument: argument \"$arg\" should not be an array ($type)");

    if (!is_array($value)) $value = array(0 => $value);

    // Check the argument's data type.
    switch ($type)
    {
        case 'string':
            break;

        case 'int>0':

        case 'int':
            foreach ($value as $k => $v) {
                if (!preg_match('/^[-+]?\d+$/', $v)) phorum_ajax_error(
                    "illegal argument: argument \"$arg\" must contain " .
                    ($array ? "only integer values" : "an integer value"));

                if ($type == 'int>0' && $v <= 0) phorum_ajax_error(
                    "illegal argument: argument \"$arg\" must contain " .
                    ($array ? "only integer values" : "an integer value") .
                    ", larger than zero");
            }
            break;

        case 'boolean':
            foreach ($value as $k => $v) {
                $val = strtolower($v);
                if ($v == '1' || $v == 'yes' || $v == 'true') {
                    $value[$k] = TRUE;
                } elseif ($v == '0' || $v == 'no' || $v == 'false') {
                    $value[$k] = FALSE;
                } else phorum_ajax_error(
                    "illegal argument: argument \"$arg\" must contain " .
                    ($array ? "only boolean values" : "a boolean value") .
                    " (0, 1, \"yes\", \"no\", \"true\" or \"false\")");
            }
            break;

        default:
            phorum_ajax_error("Internal error: illegal argument type: " .
                       ($array ? "array:" : "") . $type);
    }

    return $array ? $value : $value[0];
}

?>
