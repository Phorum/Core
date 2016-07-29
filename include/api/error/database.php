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
 * This script implements the Error API function phorum_api_error_database().
 *
 * @package    PhorumAPI
 * @subpackage ErrorHandling
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

require_once PHORUM_PATH.'/include/api/error/backtrace.php';

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

    // Clear any output that we buffered so far (e.g. in the admin interface,
    // we might already have sent the page header).
    phorum_api_buffer_clear();

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
        phorum_api_hook("database_error", $error);
    }

    // Find out what type of error handling is configured.
    // If no type if set, then we use "screen" by default.
    $logopt = isset($PHORUM["error_logging"])
            ? $PHORUM["error_logging"]
            : 'screen';

    // Create a backtrace report, so it's easier to find out where
    // a problem is coming from.
    $backtrace = phorum_api_error_backtrace(2);

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
                  phorum_api_format_htmlspecialchars($error) .
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

            $cache_dir  = $PHORUM['CACHECONFIG']['directory'];
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
                    : nl2br(phorum_api_format_htmlspecialchars($backtrace));

                print "Please try again later!" .
                      "<h3>Error:</h3>" .
                      phorum_api_format_htmlspecialchars($error) .
                      ($backtrace !== NULL
                       ? "<h3>Backtrace:</h3>\n$htmlbacktrace"
                       : "");
            }
            break;

        // Send a mail to the administrator about the database error.
        case "mail":
        default:
            require_once PHORUM_PATH.'/include/api/mail.php';
            $data = array(
              'mailmessage' =>
                  "A database error occured in your Phorum installation\n" .
                  phorum_api_format_htmlspecialchars($PHORUM['http_path']) . ":\n" .
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
            phorum_api_mail($adminmail, $data);

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
