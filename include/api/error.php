<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2009  Phorum Development Team                              //
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
 * This script implements error handling functionality for the Phorum API.
 *
 * @package    PhorumAPI
 * @subpackage ErrorHandling
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

if (!defined('PHORUM')) return;

// {{{ Variable definitions

global $PHORUM;

/**
 * A mapping of Phorum errno values to a human readable counter part.
 * These descriptions are used if no speicific error message was provided
 * to phorum_api_error().
 */
$PHORUM["API"]["errormessages"] = array(
    PHORUM_ERRNO_ERROR        => "An error occurred.",
    PHORUM_ERRNO_NOACCESS     => "Permisison denied.",
    PHORUM_ERRNO_NOTFOUND     => "Not found.",
    PHORUM_ERRNO_INTEGRITY    => "Database integrity problem detected.",
    PHORUM_ERRNO_INVALIDINPUT => "Invalid input.",
    PHORUM_ERRNO_DATABASE     => "A database error occurred."
);

// }}}

// {{{ Function: phorum_api_error()
/**
 * Set a Phorum API error.
 *
 * @param integer $errno
 *     The error code for the error that occurred. There are several
 *     errno constants available to indicate the type of error that occurred:
 *
 *     - {@link PHORUM_ERRNO_ERROR}: A generic all-purpose error code
 *     - {@link PHORUM_ERRNO_NOACCESS}: Permission denied
 *     - {@link PHORUM_ERRNO_NOTFOUND}: Resource not found
 *     - {@link PHORUM_ERRNO_INTEGRITY}: Database integrity problem
 *     - {@link PHORUM_ERRNO_INVALIDINPUT}: Data input error
 *     - {@link PHORUM_ERRNO_DATABASE}: A database error occurred
 *
 *     For {@link PHORUM_ERRNO_DATABASE}, the function will pass on the
 *     error to the {@link phorum_api_error_database()} function. For database
 *     errors, some special handling is implemented, to be able to warn the
 *     admin about the error. While code can call the API function
 *     {@link phorum_api_error_database()} directly too, calling this
 *     function using the {@link PHORUM_ERRNO_DATABASE} $errno allows for
 *     a more consistent way to do Phorum error handling.
 *
 * @param string $error
 *     This is the error message, describing the error that occurred.
 *     if this parameter is omitted or NULL, then the message will be
 *     set to a generic message, based on the {@link $errno} that was used.
 *
 * @return bool
 *     This function will always use FALSE as its return value,
 *     so a construction like "return phorum_api_error(...)" can
 *     be used for setting an error and returning FALSE at the same time.
 */ 
function phorum_api_error($errno, $error = NULL)
{
    global $PHORUM;

    if ($error === NULL) {
        if (isset($PHORUM["API"]["errormessages"][$errno])) {
            $error = $PHORUM["API"]["errormessages"][$errno];
        } else {
            $error = "Unknown errno value ($errno).";
        }
    }

    if ($errno == PHORUM_ERRNO_DATABASE) {
        return phorum_api_error_database($error);
    }

    $PHORUM["API"]["errno"] = $errno;
    $PHORUM["API"]["error"] = $error;

    return FALSE;
}
// }}}

// {{{ Function: phorum_api_error_code()
/**
 * Retrieve the error code for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error code or NULL if no error was set.
 */
function phorum_api_error_code()
{
    global $PHORUM;
    return $PHORUM["API"]["errno"];
}
// }}}

// {{{ Function: phorum_api_error_message()
/**
 * Retrieve the error message for the last Phorum API function that was called.
 *
 * @return mixed
 *     The error message or NULL if no error was set.
 */
function phorum_api_error_message()
{
    global $PHORUM;
    return $PHORUM["API"]["error"];
}
// }}}

// {{{ Function: phorum_api_error_backtrace()
/**
 * Generate a debug backtrace.
 *
 * @param integer $skip
 *     The amount of backtrace levels to skip. The call to this function
 *     is skipped by default, so you don't have to count that in.
 *
 * @param string|NULL $hidepath
 *     NULL to not hide paths or a string to replace the Phorum path with.
 *
 * @return string
 *     The backtrace in text format.
 */
function phorum_api_error_backtrace($skip = 0, $hidepath = "{path to Phorum}")
{
    // Allthough Phorum 4.3.0 is the required PHP version
    // for Phorum at the time of writing, people might still be running
    // Phorum on older PHP versions. For those people, we'll skip
    // creation of a backtrace.

    $backtrace = NULL;
    $first = TRUE;
    if (function_exists("debug_backtrace"))
    {
        $bt = debug_backtrace();
        $backtrace = '';

        $step_count = 0;
        foreach ($bt as $id => $step)
        {
            // Don't include the call to this function. The $id 1, 2 and 3 are
            // checked for cases where this API call was called through the
            // Phorum::API()->error->backtrace() construction.
            if ($id == 0 ||
                ($id == 1 && $step['function'] == 'call_user_func_array') ||
                ($id == 2 && $step['function'] == '__call') ||
                ($id == 3 && $step['function'] == 'backtrace')) continue;

            // Skip the required number of steps.
            $step_count ++;
            if ($step_count <= $skip) continue;

            if ($hidepath !== NULL && isset($step["file"])) {
                $file = str_replace(PHORUM_PATH, $hidepath, $step["file"]);
            }
            if (!$first) $backtrace .= "\n----\n";
            $first = FALSE;
            $backtrace .= "Function " . $step["function"] . "() called" .
                          (!empty($step["line"])
                           ? " at\n" .  $file . ":" . $step["line"]
                           : "");
        }
    }
    else
    {
        $backtrace = 'PHP function "debug_backtrace" is not available ' .
                     'on this system. No backtrace could be generated.';
    }

    return $backtrace;
}

// }}}

// {{{ Function: phorum_api_error_database()
/**
 * Database error handling function.
 *
 * @param string $error
 *     The database error message.
 */
function phorum_api_error_database($error)
{
    global $PHORUM;
    $phorum = Phorum::API();
    $hcharset = $PHORUM['DATA']['HCHARSET'];

    // Clear any output that we buffered so far (e.g. in the admin interface,
    // we might already have sent the page header).
    $phorum->buffer->clear();

    /*
     * [hook]
     *     database_error
     *
     * [description]
     *     Give modules a chance to handle or process database errors.
     *     This can be useful to implement addional logging backends and/or
     *     alerting mechanisms. Another option is to fully override Phorum's
     *     default database error handling by handling the error and then
     *     calling exit() from the hook to prevent the default Phorum code
     *     from running.<sbr/>
     *     <sbr/>
     *     Note: If you decide to use the full override scenario, then
     *     it is best to make your module run the database_error hook
     *     last, so other modules can still run their hook handling
     *     before the script exits. To accomplish this, add this to your
     *     module info:
     *     <programlisting>
     *     priority: run hook database_error after *
     *     </programlisting>
     *
     * [category]
     *     Miscellaneous
     *
     * [when]
     *     At the start of the function
     *     <literal>phorum_api_error_database</literal> (which you can find in
     *     <filename>include/api/error.php</filename>). This function is called
     *     from the database layer when some database error occurs.
     *
     * [input]
     *     The error message that was returned from the database layer.
     *     This error is not HTML escaped, so if you send it to the browser,
     *     be sure to preprocess it using <phpfunc>htmlspecialchars</phpfunc>.
     *
     * [output]
     *     Same as input.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_database_error($error)
     *     {
     *         // Log database errors to syslog facility "LOCAL0".
     *         openlog("Phorum", LOG_PID | LOG_PERROR, LOG_LOCAL0);
     *         syslog(LOG_ERR, $error);
     *
     *         return $error;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["database_error"])) {
        $phorum->modules->hook("database_error", $error);
    }

    // Find out what type of error handling is configured.
    // If no type if set, then we use "screen" by default.
    $logopt = isset($PHORUM["error_logging"])
            ? $PHORUM["error_logging"]
            : 'screen';

    // Create a backtrace report, so it's easier to find out where
    // a problem is coming from.
    $backtrace = $phorum->error->backtrace(1);

    // Error page header.
    if (PHP_SAPI != "cli")
    {
        // Start the error page.
        ?><html><head><title>Phorum Database Error</title></head><body>
        <h1>Phorum Database Error</h1>
        Sorry, a Phorum database error occurred.<br/>
        <?php

        // In admin scripts, we will always include the
        // error message inside a comment in the page.
        if (defined("PHORUM_ADMIN")) {
            print "<!-- " .
                  htmlspecialchars($error, ENT_COMPAT, $hcharset) .
                  " -->";
        }
    }
    else
    {
        // In CLI mode, we always show the error message on screen.
        // No need to be hiding this info from a user that can run CLI code.
        print "Sorry, a Phorum database error occurred:\n";
        print "------------------------------------------------------\n";
        print "Error: $error\n";
        if ($backtrace !== NULL) {
            print "------------------------------------------------------\n";
            print "Backtrace:\n" . $backtrace . "\n";
        }
        print "------------------------------------------------------\n";
    }

    switch ($logopt)
    {
        // Log the database error to a logfile.
        case "file":

            $cache_dir  = $PHORUM["cache"];
            $logfile = $cache_dir . '/phorum-sql-errors.log';

            if ($fp = @fopen($logfile, "a")) {
                fputs($fp,
                    "Time: " . time() . "\n" .
                    "Error: $error\n" .
                    ($backtrace !== NULL ? "Back trace:\n$backtrace\n\n" : "")
                );
                fclose($fp);

                if (PHP_SAPI != 'cli') {
                    print "The error message has been logged<br/>" .
                          "to the phorum-sql-errors.log error log.<br/>" .
                          "Please, try again later!";
                } else {
                    print "The error message has been logged to the db error log:\n";
                    print "$logfile\n";
                }
            } else trigger_error(
                "phorum_api_error_database(): cannot write to $logfile",
                E_USER_ERROR
            );
            break;

        // Display the database error on screen.
        case "screen":

            // For CLI scripts, the error was already shown on screen.
            if (PHP_SAPI != 'cli')
            {
                $htmlbacktrace =
                    $backtrace === NULL
                    ? NULL
                    : nl2br(htmlspecialchars($backtrace, ENT_COMPAT, $hcharset));

                print "Please try again later!" .
                      "<h3>Error:</h3>" .
                      htmlspecialchars($error, ENT_COMPAT, $hcharset) .
                      ($backtrace !== NULL
                       ? "<h3>Backtrace:</h3>\n$htmlbacktrace"
                       : "");
            }
            break;

        // Send a mail to the administrator about the database error.
        case "mail":
        default:

            require_once PHORUM_PATH.'/include/email_functions.php';

            $data = array(
              'mailmessage' =>
                  "A database error occured in your Phorum installation\n" .
                  htmlspecialchars($PHORUM['http_path']) . ":\n" .
                  "\n" .
                  "Error message:\n" .
                  "--------------\n" .
                  "\n" .
                  "$error\n".
                  "\n" .
                  ($backtrace !== NULL
                   ? "Backtrace:\n" .
                     "----------\n" .
                     "\n" .
                     "$backtrace\n"
                   : ""),
              'mailsubject' =>
                  'Phorum: A database error occured'
            );

            $adminmail = $PHORUM['system_email_from_address'];
            phorum_email_user(array($adminmail), $data);

            if (PHP_SAPI != 'cli') {
                print "The administrator of this forum has been<br/>" .
                      "notified by email about the error.<br/>" .
                      "Please, try again later!";
            } else {
                print "The error message was sent by mail to $adminmail\n";
            }
            break;
    }

    // Error page footer.
    if (PHP_SAPI != "cli") {
        print '</body></html>';
    }

    exit();
}
// }}} 

?>
