<?php

/**
 * Log categories.
 */
define('EVENTLOG_CAT_APPLICATION', 0);
define('EVENTLOG_CAT_DATABASE',    1);
define('EVENTLOG_CAT_SECURITY',    2);
define('EVENTLOG_CAT_SYSTEM',      3);
define('EVENTLOG_CAT_MODULE',      4);

/**
 * Log levels.
 */
define('EVENTLOG_LVL_DEBUG',       0);
define('EVENTLOG_LVL_INFO',        1);
define('EVENTLOG_LVL_WARNING',     2);
define('EVENTLOG_LVL_ERROR',       3);
define('EVENTLOG_LVL_ALERT',       4);

/**
 * Log category descriptions.
 */
$GLOBALS["PHORUM"]["DATA"]["MOD_EVENT_LOGGING"]["CATEGORIES"] = array(
    EVENTLOG_CAT_APPLICATION => "Application",
    EVENTLOG_CAT_DATABASE    => "Database",
    EVENTLOG_CAT_SECURITY    => "Security",
    EVENTLOG_CAT_SYSTEM      => "System",
    EVENTLOG_CAT_MODULE      => "Module",
);

/**
 * Log level descriptions.
 */
$GLOBALS["PHORUM"]["DATA"]["MOD_EVENT_LOGGING"]["LOGLEVELS"] = array(
    EVENTLOG_LVL_DEBUG       => "Debug",
    EVENTLOG_LVL_INFO        => "Info",
    EVENTLOG_LVL_WARNING     => "Warning",
    EVENTLOG_LVL_ERROR       => "Error",
    EVENTLOG_LVL_ALERT       => "Alert",
);

/**
 * A list of available built-in event log types. The types that have
 * NULL as the value are used as separators in the interface, so these
 * aren't real event types.
 */
$GLOBALS["PHORUM"]["DATA"]["MOD_EVENT_LOGGING"]["EVENT_TYPES"] = array(
    "Forum user events" => NULL,
        "register"       => "A user registers for an account",
        "login_failure"  => "A user fails to login",
        "password_reset" => "A user requests a new password",
        "login"          => "A user logs in",
        "logout"         => "A user logs out",
        "post"           => "A user posts a message in a forum",
        "edit_post"      => "A user edits a message in a forum",
        "post_pm"        => "A user sends a private message",
        "report"         => "A user reports a message",
        "user_delete"    => "A user is deleted", 
    "Forum moderator events" => NULL,
        "mod_edit_post"  => "A moderator edits a message in a forum",
        "mod_delete"     => "A moderator deletes message(s) from a forum",
        "mod_move"       => "A moderator moves a thread to a different forum",
        "mod_close"      => "A moderator closes a thread for posting",
        "mod_reopen"     => "A moderator reopens a thread for posting",
        "mod_hide"       => "A moderator disapproves and hides message(s)",
        "mod_approve"    => "A moderator approves messages(s)",
    "Software events (especially interesting for developers)" => NULL,
        "php_notice"     => "A PHP notice is triggered",
        "php_notice_ignore_in_admin" => "Ignore PHP notices that are triggered from the admin interface",
        "php_warning"    => "A PHP warning is triggered",
        "php_error"      => "A PHP error is triggered",
        "database_error" => "A database error occurred",
);

?>
