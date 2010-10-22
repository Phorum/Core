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

// Create an empty message structure.
$message = array();

// If a checkbox with the name "sticky" is checked,
// then we map that field to an entry for the "special" message field.
if (isset($_POST["sticky"])) {
    $_POST["special"] = "sticky";
}

// For setting up following threads, either a "subscription" field 
// (containing one "bookmark" or "message" if a subscription is needed)
// or the checkboxes "subscription_follow" and "subscription_mail" can
// be used. If the checkboxes are used, then map them into a 
// correct subscription value here.
if (!isset($_POST["subscription"])) {
    $_POST["subscription"] = "";
    if (!empty($_POST["subscription_follow"])) {
        $_POST["subscription"] = empty($_POST["subscription_mail"]) 
                               ? "bookmark" : "message";
    }
}

// Inject form field data into the message structure. No checks
// are done on the data over here. Here we just take care of
// putting the data in the right format in the data structure.
foreach ($PHORUM["post_fields"] as $var => $spec)
{
    // Check the signature of signed fields.
    if ($spec[pf_SIGNED]) {
        $qvar = htmlspecialchars($var);
        if (! isset($_POST["$var:signature"])) trigger_error(
            "Data signing error: signature for field $qvar is missing " .
            "in the form data.", E_USER_ERROR
        );
        if (! isset($_POST["$var"])) trigger_error(
            "Data signing error: field $qvar is missing in the form data.",
            E_USER_ERROR
        );
        if (! phorum_check_data_signature($_POST["$var"], $_POST["$var:signature"]))
            trigger_error("Data signing error: signature for field $qvar " .
                          "is wrong; there was probably tampering with the " .
                          "form data", E_USER_ERROR);
    }

    // Format and store the data based on the configuration.
    switch ($spec[pf_TYPE])
    {
    	case "boolean":
    	    $message[$var] = isset($_POST[$var]) && $_POST[$var] ? 1 : 0;
    	    break;

    	case "integer":
    	    $message[$var] = isset($_POST[$var]) ? (int) $_POST[$var] : NULL;
    	    break;

        case "array":
            // Serialized arrays are base64 encoded, to prevent special
            // character (especially newline) mangling by the browser.
    	    $message[$var] = isset($_POST[$var])
                           ? unserialize(base64_decode($_POST[$var])) 
                           : array();
    	    break;

        case "string":
    	    $message[$var] = isset($_POST[$var]) ? trim($_POST[$var]) : '';
            // Prevent people from impersonating others by using
            // multiple spaces in the author name.
            if ($var == 'author') {
                $message[$var] = preg_replace('/\s+/', ' ', $message[$var]);
            }
    	    break;

    	default:
    	    trigger_error(
                "Illegal field type used for field $var: " . $spec[pf_TYPE],
                E_USER_ERROR
            );
    }
}

?>
