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
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM")) return;

// For phorum_valid_email()
include_once("./include/email_functions.php");

$error = false;

// Post and reply checks for unregistered users.
if (! $PHORUM["DATA"]["LOGGEDIN"] &&
    ($mode == 'post' || $mode == 'reply'))
{
    if (empty($message["author"])) {
        $error = $PHORUM["DATA"]["LANG"]["ErrAuthor"];
    } elseif ((!defined('PHORUM_ENFORCE_UNREGISTERED_NAMES') || (defined('PHORUM_ENFORCE_UNREGISTERED_NAMES') && PHORUM_ENFORCE_UNREGISTERED_NAMES == true)) && phorum_api_user_search(array("username","display_name"),array($message["author"],$message["author"]), array("=","="), FALSE, "OR")) {
        $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdName"];
    } elseif (!empty($message["email"]) &&
              phorum_api_user_search("email", $message["email"])) {
        $error = $PHORUM["DATA"]["LANG"]["ErrRegisterdEmail"];
    }
}

/*
 * [hook]
 *     check_post
 *
 * [description]
 *     This hook can be used for modifying the message data and for running
 *     additional checks on the data. If an error is put in
 *     <literal>$error</literal>, Phorum will stop posting the message and show
 *     the error to the user in the post-form.<sbr/>
 *     <sbr/>
 *     Beware that <literal>$error</literal> can already contain an error on
 *     input, in case multiple modules are run for this hook. Therefore you
 *     might want to return immediately in your hook function in case
 *     <literal>$error</literal> is already set.<sbr/>
 *     <sbr/>
 *     Below is an example of how a function for this hook could look. This
 *     example will disallow the use of the word "bar" in the message body.
 *
 * [category]
 *     Message handling
 *
 * [when]
 *     In the <filename>include/posting/check_integrity.php</filename> file,
 *     right after performing preliminary posting checks, unless these checks
 *     have returned something bad.
 *
 * [input]
 *     An array containing:
 *     <ul>
 *         <li>An array of the message data.</li>
 *         <li><literal>$error</literal>, used to return an error message</li>
 *     </ul>
 *
 * [output]
 *     Same as input.
 *
 * [example]
 *     <hookcode>
 *     function phorum_mod_foo_check_post ($args) {
 *        list ($message, $error) = $args;
 *        if (!empty($error)) return $args;
 *
 *        if (stristr($message["body"], "bar") !== false) {
 *            return array($message, "The body may not contain 'bar'");
 *        }
 *
 *        return $args;
 *    }
 *     </hookcode>
 */
if (! $error && isset($PHORUM["hooks"]["check_post"]))
    list($message, $error) =
        phorum_hook("check_post", array($message, $error));

// Data integrity checks for all messages.
if (! $error)
{
    if (!isset($message["subject"]) || trim($message["subject"]) == '') {
        $error = $PHORUM["DATA"]["LANG"]["ErrSubject"];
    } elseif (!isset($message["body"]) || trim($message["body"]) == '') {
        $error = $PHORUM["DATA"]["LANG"]["ErrBody"];
    } elseif (!empty($message["email"]) &&
              !phorum_valid_email($message["email"])) {
        $error = $PHORUM["DATA"]["LANG"]["ErrEmail"];
    } elseif (strlen($message["body"]) > MAX_MESSAGE_LENGTH) {
        $error = $PHORUM["DATA"]["LANG"]["ErrBodyTooLarge"];
    }
}

if ($error) {
    $PHORUM["DATA"]["ERROR"] = $error;
}

?>
