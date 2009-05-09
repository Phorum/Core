<?php
/**
 * @todo move all constants out of include/constants.php to this one.
 */

global $PHORUM;

// ----------------------------------------------------------------------
// Error handling related
// phorum_api_error_set(), phorum_api_errno(), phorum_api_strerror()
// ----------------------------------------------------------------------

/**
 * A general purpose errno value, mostly used for returning a generic
 * errno with a specific error message.
 */
define("PHORUM_ERRNO_ERROR",           1);

/**
 * An errno value, which indicates a permission problem.
 */
define("PHORUM_ERRNO_NOACCESS",        2);

/**
 * An errno value, which indicates that something was not found.
 */
define("PHORUM_ERRNO_NOTFOUND",        3);

/**
 * An errno value, which indicates a database integrity problem.
 */
define("PHORUM_ERRNO_INTEGRITY",       4);

/**
 * An errno value, which indicates invalid input data.
 */
define("PHORUM_ERRNO_INVALIDINPUT",    5);

// Error information is stored in these variables.
$PHORUM['API']['errno'] = NULL;
$PHORUM['API']['error'] = NULL;

// A mapping of Phorum errno values to a human readable counter part.
$PHORUM["API"]["errormessages"] = array(
    PHORUM_ERRNO_ERROR        => "An error occurred.",
    PHORUM_ERRNO_NOACCESS     => "Permisison denied.",
    PHORUM_ERRNO_NOTFOUND     => "Not found.",
    PHORUM_ERRNO_INTEGRITY    => "Database integrity problem detected.",
    PHORUM_ERRNO_INVALIDINPUT => "Invalid input.",
);

// ----------------------------------------------------------------------
// User API related
// phorum_api_user_*
// ----------------------------------------------------------------------

/**
 * If a user API is written as a replacement for the standard Phorum
 * user API, where the replacement API is incompatible with the
 * standard API, then this define should be set to FALSE. That will
 * disable the user management functions in the admin interface.
 */
define("PHORUM_ORIGINAL_USER_CODE", TRUE);

/**
 * Used for identifying long term sessions. The value is used as
 * the name for the session cookie for long term sessions.
 */
define( 'PHORUM_SESSION_LONG_TERM' ,   'phorum_session_v5' );

/**
 * Used for identifying short term sessions. The value is used as
 * the name for the session cookie for short term sessions
 * (this is used by the tighter authentication scheme).
 */
define( 'PHORUM_SESSION_SHORT_TERM',   'phorum_session_st' );

/**
 * Used for identifying admin sessions. The value is used as
 * the name for the session cookie for admin sessions.
 */
define( 'PHORUM_SESSION_ADMIN',        'phorum_admin_session' );

/**
 * Function call parameter, which tells various functions that
 * a front end forum session has to be handled.
 */
define('PHORUM_FORUM_SESSION',         1);

/**
 * Function call parameter, which tells various functions that
 * an admin back end session has to be handled.
 */
define('PHORUM_ADMIN_SESSION',         2);


/**
 * Function call flag that tells {@link phorum_api_user_set_active_user()}
 * that the short term forum session has to be activated.
 */
define('PHORUM_FLAG_SESSION_ST',       1);

/**
 * Function call flag, which tells {@link phorum_api_user_save()}
 * that the password field should be stored as is. This can be used
 * to feed Phorum MD5 encrypted passwords. Normally, the password
 * field would be MD5 encrypted by the function. This will keep the
 * phorum_api_user_save() function from double encrypting the password.
 */
define('PHORUM_FLAG_RAW_PASSWORD',     1);

/**
 * Function call flag that tells {@link phorum_api_user_get_display_name()}
 * that the returned display names have to be HTML formatted, so they can
 * be used for showing the name in HTML pages.
 */
define('PHORUM_FLAG_HTML',             1);

/**
 * Function call flag that tells {@link phorum_api_user_get_display_name()}
 * that the returned display names should be stripped down to plain text
 * format, so they can be used for showing the name in things like mail
 * messages and message quoting.
 */
define('PHORUM_FLAG_PLAINTEXT',        2);

/**
 * Function call parameter that tells
 * {@link phorum_api_user_session_create()} that session ids have to be
 * reset to new values as far as that is sensible for a newly
 * logged in user.
 */
define('PHORUM_SESSID_RESET_LOGIN',    1);

/**
 * Function call parameter, which tells
 * {@link phorum_api_user_session_create()} that all session ids have to
 * be reset to new values. This is for example appropriate after a user
 * changed the password (so active sessions on other computers or
 * browsers will be ended).
 */
define('PHORUM_SESSID_RESET_ALL',      2);

/**
 * Function call parameter, which tells {@link phorum_api_user_get_list()}
 * that all users have to be returned.
 */
define('PHORUM_GET_ALL',               0);

/**
 * Function call parameter, which tells {@link phorum_api_user_get_list()}
 * that all active users have to be returned.
 */
define('PHORUM_GET_ACTIVE',            1);

/**
 * Function call parameter, which tells {@link phorum_api_user_get_list()}
 * that all inactive users have to be returned.
 */
define('PHORUM_GET_INACTIVE',          2);

/**
 * Function call parameter that tells {@link phorum_api_user_check_access()}
 * and {@link phorum_api_user_check_group_access()} to return an array
 * of respectively forums or groups for which a user is granted access.
 */
define('PHORUM_ACCESS_LIST', -1);

/**
 * Function call parameter that tells {@link phorum_api_user_check_access()}
 * and {@link phorum_api_user_check_group_access()} to check if the user
 * is granted access for respectively any forum or group.
 */
define('PHORUM_ACCESS_ANY', -2);

/**
 * User status, indicating that the user has not yet confirmed the
 * registration by email and that a user moderator will have to approve
 * the registration as well.
 */
define("PHORUM_USER_PENDING_BOTH", -3);

/**
 * User status, indicating that the user has not yet confirmed the
 * registration by email.
 */
define("PHORUM_USER_PENDING_EMAIL", -2);

/**
 * User status, indicating that the registration has not yet been approved
 * by a user moderator.
 */
define("PHORUM_USER_PENDING_MOD", -1);

/**
 * User status, indicating that the user has been deactivated.
 */
define("PHORUM_USER_INACTIVE", 0);

/**
 * User status, indicating that the registration has been completed
 * and that the user can access the forums.
 */
define("PHORUM_USER_ACTIVE", 1);

/**
 * Permission flag which allows users to read forum messages.
 */
define('PHORUM_USER_ALLOW_READ', 1);

/**
 * Permission flag which allows users to reply to forum messages.
 */
define('PHORUM_USER_ALLOW_REPLY', 2);

/**
 * Permission flag which allows users to edit their own forum messages.
 */
define('PHORUM_USER_ALLOW_EDIT', 4);

/**
 * Permission flag which allows users to start new forum topics.
 */
define('PHORUM_USER_ALLOW_NEW_TOPIC', 8);

/**
 * Permission flag which allows users to attach files
 * to their forum messages.
 */
define('PHORUM_USER_ALLOW_ATTACH', 32);

/**
 * Permission flag which allows users to edit other users' messages.
 */
define('PHORUM_USER_ALLOW_MODERATE_MESSAGES', 64);

/**
 * Permission flag which allows users to moderate user signup
 * requests within the vroot.
 */
define('PHORUM_USER_ALLOW_MODERATE_USERS', 128);

/**
 * Group permission flag for users which are suspended by a group moderator.
 */
define('PHORUM_USER_GROUP_SUSPENDED', -1);

/**
 * Group permission flag for users which are not yet approved by
 * a group moderator.
 */
define('PHORUM_USER_GROUP_UNAPPROVED', 0);

/**
 * Group permission flag for users which are active approved group members.
 */
define('PHORUM_USER_GROUP_APPROVED', 1);

/**
 * Group permission flag for users which are group moderator.
 */
define('PHORUM_USER_GROUP_MODERATOR', 2);

/**
 * Subscription type, which tells Phorum explicitly that the user
 * does not have a subscription of any kind for the forum or thread.
 */
define("PHORUM_SUBSCRIPTION_NONE", -1);

/**
 * Subscription type, which tells Phorum to send out a mail message for
 * every new forum or thread that a user is subscribed to.
 */
define("PHORUM_SUBSCRIPTION_MESSAGE", 0);

/**
 * Subscription type, which tells Phorum to periodially send a mail
 * message, containing a list of new messages in forums or threads
 * that a user is subscribed to. There is currently no support for
 * this type of subscription in the Phorum core code.
 */
define("PHORUM_SUBSCRIPTION_DIGEST", 1);

/**
 * Subscription type, which tells Phorum to make the forums or threads
 * that a user is subscribed to accessible from the followed threads
 * interface in the control center. No mail is sent for new messages,
 * but the user can check for new messages using that interface.
 */
define("PHORUM_SUBSCRIPTION_BOOKMARK", 2);
?>
