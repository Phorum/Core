<?php

// ----------------------------------------------------------------------
// Initialization code.
// ----------------------------------------------------------------------

// Check if we are loaded from the Phorum code.
// Direct access to this file is not allowed.
if (! defined("PHORUM")) return;

// A define that other scripts can use to see if event logging was loaded.
define('EVENT_LOGGING', TRUE);

// The chance (in %) that garbage collection is run on a user's request.
define('EVENT_LOGGING_GC_SPLAY', 5);

$GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"] = 0;
$GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"]  = 0;

require_once("./mods/event_logging/db.php");

require_once("./mods/event_logging/defaults.php");

function phorum_mod_event_logging_common_pre()
{
    global $PHORUM;

    // Check and handle automatic installation and upgrading
    // of the database structure. Do not continue running the
    // module in case the installation fails.
    if (! event_logging_db_install()) return;

    // Setup error handling, in case PHP errors or warnings need to be
    // logged by this module.
    if ($PHORUM["mod_event_logging"]["do_log_php_error"] ||
        $PHORUM["mod_event_logging"]["do_log_php_warning"]) {
        set_error_handler("phorum_mod_event_logging_error_handler");
    }
}


// ----------------------------------------------------------------------
// Utility functions.
// ----------------------------------------------------------------------

/**
 * This routine is used for automatically determining the source for an
 * event that has to be logged. It can determine the source by using either
 * a trace back depth level for examining the call stack or by using
 * the name of a file for which the event happened.
 *
 * The depth level is used to find the caller file by looking at a
 * debug_backtrace() array at the given level. The level might not be the
 * same for all requests, because some logged events will take multiple
 * steps before hitting the log writing functions (for example the database
 * error logging will run through an extra function call).
 *
 * The file that generated the event can also be passed as the $file
 * parameter. In that case, the code will directly use that parameter
 * and not investigate the call stack at all.
 *
 * @param $level   - The call stack depth at which the event generating
 *                   file can be found.
 * @param $file    - The file name of the file that generated the event or
 *                   NULL if this file name is unknown.
 *
 * @return $source - A string that can be used as the event source.
 */
function event_logging_find_source($level = 0, $file = NULL)
{
    $source = NULL;
    $from_module = FALSE;

    // Determine the file that generated the event.
    if ($file === NULL) {
        if (function_exists('debug_backtrace')) {
            $bt = debug_backtrace();
            if (isset($bt[$level]["file"])) {
                $file = $bt[$level]["file"];
            }
        }
    }
    // See if the file belongs to a module.
    if ($file !== NULL) {
        $moddir = preg_quote(dirname(dirname(__FILE__)), '!');
        if (preg_match("!^$moddir/([^/]+)/!", $file, $m)) {
            $source = $m[1];
            $from_module = TRUE;
        }
    }

    // No module found? Then the logging is probably done by a regular
    // Phorum page. We can use the phorum_page constant as the source here.
    if ($source === NULL) {
        if (defined("phorum_page")) {
            $source = phorum_page;
        } elseif (defined('PHORUM_ADMIN')) {
            $source = "admin";
        } else {
            $source = "unknown";
        }
    }

    return array($source, $from_module);
}

/**
 * This user will mask a password with "XXXX" if the hide passwords
 * module configuration option is enabled.
 *
 * @param string $password
 *     The password to mask.
 *
 * @return string
 *     The masked password if the hide passwords option is enabled,
 *     otherwise the original $password.
 */
function event_logging_mask_password($password)
{
    global $PHORUM;
    if (empty($PHORUM["mod_event_logging"]["hide_passwords"]) &&
        $password !== NULL) {
        return $password;
    } else {
        return str_repeat('X', strlen($password));
    }
}

// ------------------------------------------------------------------------
// API functions
// ------------------------------------------------------------------------

// These functions can be used by code which generates warnings that
// do not have to be logged. For example getimagesize() will warn in case
// an image file cannot be loaded, but this might happen a lot from modules
// like the in body attachments module (if users posted pictures hosted on
// other servers).
//
// Usage in other code:
//
// if (defined('EVENT_LOGGING')) phorum_mod_event_logging_suspend();
// ... warnings generating piece of code ...
// if (defined('EVENT_LOGGING')) phorum_mod_event_logging_resume();

function phorum_mod_event_logging_suspend() {
    $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"] ++;
}

function phorum_mod_event_logging_resume() {
    if ($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"] > 0) {
        $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"] --;
    }
}

// ------------------------------------------------------------------------
// Custom error handler, used for logging PHP notices, warnings and errors.
// ------------------------------------------------------------------------

function phorum_mod_event_logging_error_handler($errno, $errstr, $file, $line)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Check for the use of the silence operator @, in which case
    // this module should not bother with logging the error message.
    if (error_reporting() == 0) {
        return;
    }

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return;
    }

    // Prevention against recursive logging calls.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"])) {
        return;
    }
    $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"]++;

    // Prepare the event log data.
    $loglevel = NULL;
    $type     = NULL;
    switch ($errno)
    {
        case E_USER_NOTICE;
        case E_NOTICE:
          if ($PHORUM["mod_event_logging"]["do_log_php_notice"]) {
            $loglevel = EVENTLOG_LVL_DEBUG;
            $type     = "PHP notice";
          }
          break;

        case E_USER_WARNING:
        case E_WARNING:
          if ($PHORUM["mod_event_logging"]["do_log_php_warning"]) {
            $loglevel = EVENTLOG_LVL_WARNING;
            $type     = "PHP warning";
          }
          break;

        case E_USER_ERROR:
        case E_ERROR:
          if ($PHORUM["mod_event_logging"]["do_log_php_error"]) {
            $loglevel = EVENTLOG_LVL_ALERT;
            $type     = "PHP error";
          }
          break;
    }

    // Nothing to do? Then return and let PHP handle the problem
    // (works for PHP5, I don't know what PHP4 does here).
    if ($loglevel === NULL) {
        $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"]--;
        return FALSE;
    }

    // Create detailed info.
    $details = "$type generated at $file:$line\n";

    // Construct a back trace and add it to the details info.
    $backtrace = phorum_generate_backtrace(1);
    $details .= $backtrace === NULL ? "" : "\nBack trace:\n\n$backtrace\n";

    // Add request info to the details.
    $details .= "Request info:\n\n";
    foreach (array(
        "HTTP_HOST", "HTTP_REFERER",
        "REQUEST_URI", "REQUEST_PATH", "QUERY_STRING"
    ) as $k) {
        if (isset($_SERVER[$k]) and trim($_SERVER[$k]) != '') {
            $details .= "$k = {$_SERVER[$k]}\n";
        }
    }

    // Determine the source of the event.
    list ($source, $from_module) = event_logging_find_source(0, $file);

    // Because of the way in which the admin interface is programmed,
    // there are a lot of notices coming from that code. We ignore
    // those by default.
    if ($source == 'admin' && !$from_module && $loglevel == EVENTLOG_LVL_DEBUG) {
        if ($PHORUM["mod_event_logging"]["do_log_php_notice_ignore_in_admin"]) {
            $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"]--;
            return FALSE;
        }
    }

    // Log the event.
    if ($loglevel !== NULL) {
        event_logging_writelog(array(
            "message"   => "$type: $errstr",
            "loglevel"  => $loglevel,
            "category"  => $from_module
                           ? EVENTLOG_CAT_MODULE
                           : EVENTLOG_CAT_APPLICATION,
            "source"    => $source,
            "details"   => $details
        ));
    }

    // For fatal errors, we halt the application here and we tell the
    // user that the problem was logged.
    if ($loglevel == EVENTLOG_LVL_ALERT)
    {
        // Flush any buffered output so far.
        phorum_ob_clean();

        // Notify the user and exit.
        print "An error occurred in the application.<br/>" .
              "The error was logged to the Phorum event log.<br/>";
        exit(1);
    }

    // Let the normal error handler take over from here
    // (works for PHP5, I don't know what PHP4 does here).
    $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"]--;
    return FALSE;
}


// ----------------------------------------------------------------------
// Hooks for logging of Phorum events.
// ----------------------------------------------------------------------

function phorum_mod_event_logging_after_register($data)
{
    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_register"])
        return $data;

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"   => "User registered for an account: " .
                       "{$data["username"]} <{$data["email"]}>.",
        "source"    => $source,
        "loglevel"  => EVENTLOG_LVL_INFO,
        "category"  => EVENTLOG_CAT_SECURITY
    ));

    return $data;
}

function phorum_mod_event_logging_failed_login($data)
{
    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_login_failure"])
        return $data;

    // Check if a user can be found for the used username.
    // If we can find a user, then we log the user_id, so the filter by
    // user option in the log viewer will include failed user logins as well.
    $user_id = phorum_db_user_search('username', $data['username']);

    $location = ucfirst($data["location"]);
    $password = event_logging_mask_password($data["password"]);
    event_logging_writelog(array(
        "source"    => $data["location"] . " login",
        "message"   => "$location login failure for user " .
                       '"' . $data["username"] . '".',
        "details"   => "The user tried to login using the password " .
                       '"' . $password . '".',
        "user_id"   => $user_id,
        "loglevel"  => EVENTLOG_LVL_WARNING,
        "category"  => EVENTLOG_CAT_SECURITY
    ));

    return $data;
}

function phorum_mod_event_logging_password_reset($data)
{
    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_password_reset"])
        return $data;

    $password = event_logging_mask_password($data['secret']);
    $log = 'Status: ' . $data['status'];

    switch ($data['status'])
    {
        case 'user_unknown':
            $log = 'A new password was requested for the user with email ' .
                   'address "'.$data['email'].'", but no matching user ' .
                   'was found in the database.';
            break;

        case 'unapproved':
            $log = 'A new password was requested for the user with email ' .
                   'address "'.$data['email'].'", but this user was not ' .
                   'yet approved by a moderator. No new password was ' .
                   'generated.';
            break;

        case 'new_verification':
            $log = 'A new password was requested for the user with email ' .
                   'address "'.$data['email'].'". This user was not yet ' .
                   'verified by email. A new verification code ' .
                   '"'.$password.'" was generated and mailed to the user.';
            break;

        case 'new_password':
            $log = 'A new password was requested for the user with email ' .
                   'address "'.$data['email'].'". A new password ' .
                   '"'.$password.'" was generated and mailed to the user.';
            break;
    }

    event_logging_writelog(array(
        "source"    => "forum login",
        "message"   => 'New password request for user "'.$data['email'].'"',
        "details"   => $log,
        "loglevel"  => EVENTLOG_LVL_INFO,
        "category"  => EVENTLOG_CAT_SECURITY
    ));
}

function phorum_mod_event_logging_after_login($data)
{
    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_login"])
        return $data;

    if (isset($GLOBALS["PHORUM"]["user"]["username"])) {
        $username = $GLOBALS["PHORUM"]["user"]["username"];

        event_logging_writelog(array(
            "source"    => "forum login",
            "message"   => "User $username logged in.",
            "loglevel"  => EVENTLOG_LVL_INFO,
            "category"  => EVENTLOG_CAT_SECURITY
        ));
    }

    return $data;
}

function phorum_mod_event_logging_before_logout()
{
    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return;
    }

    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_logout"])
        return;

    if (isset($GLOBALS["PHORUM"]["user"]["username"])) {
        $username = $GLOBALS["PHORUM"]["user"]["username"];

        event_logging_writelog(array(
            "source"    => "forum login",
            "message"   => "User $username logged out.",
            "loglevel"  => EVENTLOG_LVL_INFO,
            "category"  => EVENTLOG_CAT_SECURITY
        ));
    }
}

function phorum_mod_event_logging_after_post($data)
{
    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_post"])
        return $data;

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"    => "Message \"{$data["subject"]}\" posted by \"{$data["author"]}\".",
        "forum_id"   => $data["forum_id"],
        "thread_id"  => $data["thread"],
        "message_id" => $data["message_id"],
        "loglevel"   => EVENTLOG_LVL_INFO,
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $data;
}

function phorum_mod_event_logging_after_edit($data)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    // Check if this is a user or moderator edit.
    // Retrieve the data from the database, since the user_id
    // is not in the $data array.
    $dbmsg = phorum_db_get_message($data["message_id"]);
    $is_mod_edit = !isset($dbmsg["user_id"]) ||
                   $dbmsg["user_id"] != $PHORUM["user"]["user_id"];

    if ($is_mod_edit) {
        if (!$PHORUM["mod_event_logging"]["do_log_mod_edit_post"])
            return $data;
        $prefix = 'Moderation: ';
    } else {
        if (!$PHORUM["mod_event_logging"]["do_log_edit_post"])
            return $data;
        $prefix = '';
    }

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"    => $prefix . "Message \"{$data["subject"]}\" edited by \"{$GLOBALS["PHORUM"]["user"]["username"]}\".",
        "forum_id"   => $data["forum_id"],
        "thread_id"  => $data["thread"],
        "message_id" => $data["message_id"],
        "loglevel"   => EVENTLOG_LVL_INFO,
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $data;
}

function phorum_mod_event_logging_pm_sent($message, $recipients)
{
    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_post_pm"])
        return $message;

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"    => "Private message posted by \"{$message["from_username"]}\".",
        "details"    => "Number of recipients: " . count($recipients),
        "loglevel"   => EVENTLOG_LVL_INFO,
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $message;
}

function phorum_mod_event_logging_database_error($error)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_database_error"])
        return $error;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $error;
    }

    // Prevention against recursive logging calls.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"])) {
        return $error;
    }
    $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"]++;

    // Construct a back trace.
    $backtrace = phorum_generate_backtrace(3);

    list ($source, $from_module) = event_logging_find_source(4);

    // Log the event.
    event_logging_writelog(array(
        "message"   => "Database error: $error",
        "details"   => ($backtrace === NULL
                        ? NULL : "\nBack trace:\n\n$backtrace"),
        "loglevel"  => EVENTLOG_LVL_ALERT,
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    $GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["LOOPLOCK"]--;

    return $error;
}

function phorum_mod_event_logging_before_delete($data)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_mod_delete"])
        return $data;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    $message = $data[3];
    $delete_mode = $data[4];

    $suffix = $delete_mode == PHORUM_DELETE_TREE
          ? " and replies"
          : "";

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"   => "Moderation: Deleted message \"{$message["subject"]}\"$suffix.",
        "details"   => "Message contents:\n----\n{$message["body"]}\n----\n",
        "loglevel"  => EVENTLOG_LVL_INFO,
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $data;
}

function phorum_mod_event_logging_report($data)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_report"])
        return $data;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    list ($source, $from_module) = event_logging_find_source(1);

    // The "message" field isn't available in Phorum versions prior to 5.1.22.
    $message_id = NULL;
    $forum_id   = NULL;
    $thread_id  = NULL;
    if (isset($data["message"])) {
        $message_id = $data["message"]["message_id"];
        $forum_id   = $data["message"]["forum_id"];
        $thread_id  = $data["message"]["thread"];
    }

    event_logging_writelog(array(
        "message"    => "Message reported: \"{$data["subject"]}\" for forum \"{$data["forumname"]}\".",
        "details"    => "Report reason:\n----\n{$data["explanation"]}\n----\n",
        "loglevel"   => EVENTLOG_LVL_INFO,
        "message_id" => $message_id,
        "thread_id"  => $thread_id,
        "forum_id"   => $forum_id,
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $data;
}

function phorum_mod_event_logging_move_thread($message_id)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_mod_move"])
        return $message_id;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $message_id;
    }

    $dbmsg = phorum_db_get_message($message_id, "message_id", TRUE);
    if ($dbmsg === NULL || !is_array($dbmsg)) return $message_id;

    // Update the log entries in which the message id was used.
    event_logging_update_message_id_info(
        $message_id,
        $dbmsg["forum_id"],
        $dbmsg["thread"]
    );

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"    => "Moderation: Moved message \"{$dbmsg["subject"]}\" to a different forum.",
        "loglevel"   => EVENTLOG_LVL_INFO,
        "message_id" => $message_id,
        "thread_id"  => $dbmsg["thread"],
        "forum_id"   => $dbmsg["forum_id"],
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $message_id;
}

function phorum_mod_event_logging_close_thread($message_id)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_mod_close"])
        return $message_id;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $message_id;
    }

    $dbmsg = phorum_db_get_message($message_id, "message_id", TRUE);
    if ($dbmsg === NULL || !is_array($dbmsg)) return $message_id;

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"    => "Moderation: Closed thread \"{$dbmsg["subject"]}\".",
        "loglevel"   => EVENTLOG_LVL_INFO,
        "message_id" => $message_id,
        "thread_id"  => $dbmsg["thread"],
        "forum_id"   => $dbmsg["forum_id"],
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $message_id;
}

function phorum_mod_event_logging_reopen_thread($message_id)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_mod_reopen"])
        return $message_id;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $message_id;
    }

    $dbmsg = phorum_db_get_message($message_id, "message_id", TRUE);
    if ($dbmsg === NULL || !is_array($dbmsg)) return $message_id;

    list ($source, $from_module) = event_logging_find_source(1);

    event_logging_writelog(array(
        "message"    => "Moderation: Reopened thread \"{$dbmsg["subject"]}\".",
        "loglevel"   => EVENTLOG_LVL_INFO,
        "message_id" => $message_id,
        "thread_id"  => $dbmsg["thread"],
        "forum_id"   => $dbmsg["forum_id"],
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $message_id;
}

function phorum_mod_event_logging_hide_thread($message_id)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_mod_hide"])
        return $message_id;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $message_id;
    }

    $dbmsg = phorum_db_get_message($message_id, "message_id", TRUE);
    if ($dbmsg === NULL || !is_array($dbmsg)) return $message_id;

    list ($source, $from_module) = event_logging_find_source(1);

    $what = $dbmsg["parent_id"] == 0 ? "thread" : "message";

    event_logging_writelog(array(
        "message"    => "Moderation: Disapproved and hid $what \"{$dbmsg["subject"]}\".",
        "loglevel"   => EVENTLOG_LVL_INFO,
        "message_id" => $message_id,
        "thread_id"  => $dbmsg["thread"],
        "forum_id"   => $dbmsg["forum_id"],
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $message_id;
}

function phorum_mod_event_logging_after_approve($data)
{
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_mod_approve"])
        return $data;

    // Check for suspended logging.
    if (!empty($GLOBALS["PHORUM"]["MOD_EVENT_LOGGING"]["SUSPEND"])) {
        return $data;
    }

    list ($source, $from_module) = event_logging_find_source(1);

    $suffix = $data[1] == PHORUM_APPROVE_MESSAGE_TREE
            ? " and replies" : "";

    event_logging_writelog(array(
        "message"    => "Moderation: Approved message \"{$data[0]["subject"]}\"$suffix.",
        "loglevel"   => EVENTLOG_LVL_INFO,
        "message_id" => $data[0]["message_id"],
        "thread_id"  => $data[0]["thread"],
        "forum_id"   => $data[0]["forum_id"],
        "source"     => $source,
        "category"   => $from_module
                        ? EVENTLOG_CAT_MODULE
                        : EVENTLOG_CAT_APPLICATION
    ));

    return $data;
}

// ----------------------------------------------------------------------
// Automatic log cleanup
// ----------------------------------------------------------------------

// To prevent log cleanup from blocking the main page, we use this
// addon implementation to put the processing behind a 1x1 transparent
// image that we can include in the page. This way, the user's request
// won't be stalling because of the cleanup process.
//
// Whether or not the image in included on the page is determined from
// the before_footer hook.

function phorum_mod_event_logging_addon()
{
    // This function takes care of cleaning up the garbage.
    event_logging_countlogs();

    // Display a 1x1 transparent gif image.
    header("Content-type: image/gif");
    print "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x91\x00\x00" .
          "\x00\x66\x99\x00\x00\x00\x00\x00\x00\x00\x00\x00\x21" .
          "\xf9\x04\x09\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01" .
          "\x00\x01\x00\x00\x08\x04\x00\x01\x04\x04\x00\x3b";
}

// Garbage collection is started by including the pixel from the
// addon hook in the page. We do not want to run garbage collection
// on every request, so here we spread those runs randomly.
function phorum_mod_event_logging_before_footer()
{
    $rand = rand(1,100);
    if ($rand <= EVENT_LOGGING_GC_SPLAY) {
        $img_url = phorum_get_url(PHORUM_ADDON_URL, 'module=event_logging'); 
        print '<img src="'.$img_url.'" width="1" height="1" />';
    }
}


function phorum_mod_event_logging_user_delete($userid) {
    if (!$GLOBALS["PHORUM"]["mod_event_logging"]["do_log_user_delete"])
        return $userid;

    list ($source, $from_module) = event_logging_find_source(1);

    $user = phorum_api_user_get($userid);

    event_logging_writelog(array(
        "message"    => "User deleted: {$user['username']} <{$user['email']}> ID: $userid .",
        "loglevel"   => EVENTLOG_LVL_INFO,
        "source"     => $source,
        "category"   => EVENTLOG_CAT_SECURITY,
    ));

    return $userid;
}


?>
