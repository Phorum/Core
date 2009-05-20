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
 * This script defines all the constants that are used by Phorum.
 *
 * @package PhorumAPI
 * @subpackage Core
 * @copyright  2009, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

global $PHORUM;

// ----------------------------------------------------------------------
// Basic definitions.
// ----------------------------------------------------------------------

/**
 * The Phorum version.
 */
define( 'PHORUM', '5.3-dev' );

/**
 * Our database schema version in the format of year-month-day-serial.
 */
define( 'PHORUM_SCHEMA_VERSION', '2009051900');

/**
 * Our database patch level in the format of year-month-day-serial.
 */
define( 'PHORUM_SCHEMA_PATCHLEVEL', '2009021901' );

/**
 * A reference to the Phorum installation directory.
 */
define('PHORUM_PATH', realpath(dirname(__FILE__).'/../..'));

// ----------------------------------------------------------------------
// Constants for settings that will not change very often. If there are
// settings that are often changed by admins, then these should go into
// the database to make them configurable through the admin interface.
// ----------------------------------------------------------------------

// put constants here that are configurable
// these should be things that will not be changed
// very often.  Things that are likely to be changed
// by most admins should go in the admin.

/**
 * The extension that is used for the front end Phorum scripts
 * (index, list, read, pm, etc.).
 */
define("PHORUM_FILE_EXTENSION", "php");

/**
 * The maximum number of messages that Phorum will remember as being
 * read, per forum per user. Phorum will trim off older read flags
 * if this limit is hit.
 */
define("PHORUM_MAX_READ_COUNT_PER_FORUM", 1000);

/**
 * Whether or not moderators can view email addresses.
 */
define("PHORUM_MOD_EMAIL_VIEW", true);

/**
 * Whether or not moderators can view user IP addresses.
 */
define("PHORUM_MOD_IP_VIEW", true);

/**
 * Change the author's name (to "Anonymous") on deleting the user.
 */
define("PHORUM_DELETE_CHANGE_AUTHOR", true);

/**
 * Enforce the use of only unregistered names for unregistered users.
 */
define("PHORUM_ENFORCE_UNREGISTERED_NAMES", true);

/**
 * The maximum time that a user can keep the message editor open.
 * This is used for determining stale attachment files.
 */
define("PHORUM_MAX_EDIT_TIME", 86400);

/**
 * A string used to separate things like items in the title tag.
 */
define("PHORUM_SEPARATOR", " :: ");

/**
 * The default TTL for cache-data if not specified otherwise.
 */
define('PHORUM_CACHE_DEFAULT_TTL', 3600);

/**
 * Split-variable for the file-based caching layer.
 */
define('PHORUM_CACHE_SPLIT',4);

/**
 * The replacement string for masking bad words.
 */
define('PHORUM_BADWORD_REPLACE', '@#$%&');

/**
 * Phorum's default template.
 */
define('PHORUM_DEFAULT_TEMPLATE', 'emerald');

/**
 * Phorum's default language.
 */
define('PHORUM_DEFAULT_LANGUAGE', 'english');

/**
 * The time in seconds for the admin token to timeout.
 */
define('PHORUM_ADMIN_TOKEN_TIMEOUT', 1800); // 30 minutes
 
// ----------------------------------------------------------------------
// Constants that should stay the same and which have not been
// assigned to an API layer (yet).
// ----------------------------------------------------------------------

define("PHORUM_UPLOADS_SELECT", 0);
define("PHORUM_UPLOADS_REG", 1);

// moderation
define("PHORUM_MODERATE_OFF", 0);
define("PHORUM_MODERATE_ON", 1);
define("PHORUM_EMAIL_MODERATOR_OFF", 0);
define("PHORUM_EMAIL_MODERATOR_ON", 1);

// message statuses
define("PHORUM_STATUS_APPROVED", 2);
define("PHORUM_STATUS_HOLD", -1);
define("PHORUM_STATUS_HIDDEN", -2);

// message sorting
define("PHORUM_SORT_STICKY", 1);
define("PHORUM_SORT_DEFAULT", 2);

// threaded on/off
define("PHORUM_THREADED_DEFAULT", 0);
define("PHORUM_THREADED_ON", 1);
define("PHORUM_THREADED_OFF", 2);
define("PHORUM_THREADED_HYBRID", 3);

// index page styles
define("PHORUM_INDEX_DIRECTORY", 0);
define("PHORUM_INDEX_FLAT", 1);

// index new message count settings
define("PHORUM_NEWFLAGS_NOCOUNT", 0);
define("PHORUM_NEWFLAGS_COUNT", 1);
define("PHORUM_NEWFLAGS_CHECK", 2);

// edit tracking
define("PHORUM_EDIT_TRACK_OFF", 0);
define("PHORUM_EDIT_TRACK_ON", 1);
define("PHORUM_EDIT_TRACK_MODERATOR", 2);

// registration settings
define("PHORUM_REGISTER_INSTANT_ACCESS", 0);
define("PHORUM_REGISTER_VERIFY_EMAIL", 1);
define("PHORUM_REGISTER_VERIFY_MODERATOR", 2);
define("PHORUM_REGISTER_VERIFY_BOTH", 3);

// more group moderation stuff
define("PHORUM_GROUP_CLOSED", 0);
define("PHORUM_GROUP_OPEN", 1);
define("PHORUM_GROUP_REQUIRE_APPROVAL", 2);

// session cookie usage
define("PHORUM_NO_COOKIES", 0);
define("PHORUM_USE_COOKIES", 1);
define("PHORUM_REQUIRE_COOKIES", 2);

// attachment status flags
define("PHORUM_LINK_USER", "user");
define("PHORUM_LINK_MESSAGE", "message");
define("PHORUM_LINK_EDITOR", "editor");
define("PHORUM_LINK_TEMPFILE", "tempfile");

// Offsite file linking permissions
define("PHORUM_OFFSITE_FORUMONLY", 0);
define("PHORUM_OFFSITE_ANYSITE", 1);
define("PHORUM_OFFSITE_THISSITE", 2);

// PM Special folders
define("PHORUM_PM_INBOX", "inbox");
define("PHORUM_PM_OUTBOX", "outbox");
define("PHORUM_PM_ALLFOLDERS", "allfolder");

// PM Flag types
define("PHORUM_PM_READ_FLAG", "read_flag");
define("PHORUM_PM_REPLY_FLAG", "reply_flag");

// db values for ban list
define("PHORUM_BAD_IPS", 1);
define("PHORUM_BAD_NAMES", 2);
define("PHORUM_BAD_EMAILS", 3);
define("PHORUM_BAD_WORDS", 4);
define("PHORUM_BAD_USERID", 5);
define("PHORUM_BAD_SPAM_WORDS", 6);

// control center url page names
define("PHORUM_CC_SUMMARY", "summary");
define("PHORUM_CC_SUBSCRIPTION_THREADS", "subthreads");
define("PHORUM_CC_SUBSCRIPTION_FORUMS", "subforums");
define("PHORUM_CC_USERINFO", "user");
define("PHORUM_CC_SIGNATURE", "sig");
define("PHORUM_CC_MAIL", "email");
define("PHORUM_CC_BOARD", "forum");
define("PHORUM_CC_PASSWORD", "password");
define("PHORUM_CC_UNAPPROVED", "messages");
define("PHORUM_CC_FILES", "files");
define("PHORUM_CC_USERS", "users");
define("PHORUM_CC_PM", "pm");
define("PHORUM_CC_PRIVACY", "privacy");
define("PHORUM_CC_GROUP_MODERATION", "groupmod");
define("PHORUM_CC_GROUP_MEMBERSHIP", "groups");

// Phorum up/down status
define("PHORUM_MASTER_STATUS_NORMAL", "normal");
define("PHORUM_MASTER_STATUS_READ_ONLY", "read-only");
define("PHORUM_MASTER_STATUS_ADMIN_ONLY", "admin-only");
define("PHORUM_MASTER_STATUS_DISABLED", "disabled");

// URL identifiers
define("PHORUM_LIST_URL", 1);
define("PHORUM_READ_URL", 2);
define("PHORUM_FOREIGN_READ_URL", 3);
define("PHORUM_REPLY_URL", 4);
define("PHORUM_POSTING_URL", 5);
define("PHORUM_REDIRECT_URL", 6);
define("PHORUM_SEARCH_URL", 7);
define("PHORUM_SEARCH_ACTION_URL", 8);
define("PHORUM_USER_URL", 9);
define("PHORUM_INDEX_URL", 10);
define("PHORUM_LOGIN_URL", 11);
define("PHORUM_LOGIN_ACTION_URL", 12);
define("PHORUM_REGISTER_URL", 13);
define("PHORUM_REGISTER_ACTION_URL", 14);
define("PHORUM_PROFILE_URL", 15);
define("PHORUM_SUBSCRIBE_URL", 16);
define("PHORUM_MODERATION_URL", 17);
define("PHORUM_MODERATION_ACTION_URL", 18);
define("PHORUM_CONTROLCENTER_URL", 19);
define("PHORUM_CONTROLCENTER_ACTION_URL", 20);
define("PHORUM_PM_URL", 21);
define("PHORUM_PM_ACTION_URL", 22);
define("PHORUM_FILE_URL", 23);
define("PHORUM_GROUP_MODERATION_URL", 24);
define("PHORUM_FOLLOW_URL", 25);
define("PHORUM_FOLLOW_ACTION_URL", 26);
define("PHORUM_REPORT_URL", 27);
define("PHORUM_FEED_URL", 28);
define("PHORUM_CUSTOM_URL", 29);
define("PHORUM_BASE_URL", 30);
define("PHORUM_ADDON_URL", 31);
define("PHORUM_CHANGES_URL", 32);
define("PHORUM_CSS_URL", 33);
define("PHORUM_POSTING_ACTION_URL", 34);
define("PHORUM_JAVASCRIPT_URL", 35);
define("PHORUM_AJAX_URL", 36);
define("PHORUM_OPENID_URL", 37);

// constants below here do not have to have a constant value,
// as long as each is unique.  They are used for enumeration.
// Add to them as you wish knowing that.

// URL forum_id option
$i=1;
define("PHORUM_URL_NO_FORUM_ID", $i++);
define("PHORUM_URL_ADD_FORUM_ID", $i++);
define("PHORUM_URL_COND_FORUM_ID", $i++);

// moderation actions
$i=1;
define("PHORUM_DELETE_MESSAGE", $i++);
define("PHORUM_DELETE_TREE", $i++);
define("PHORUM_MOVE_THREAD", $i++);
define("PHORUM_DO_THREAD_MOVE", $i++);
define("PHORUM_CLOSE_THREAD", $i++);
define("PHORUM_REOPEN_THREAD", $i++);
define("PHORUM_APPROVE_MESSAGE", $i++);
define("PHORUM_HIDE_POST", $i++);
define("PHORUM_APPROVE_MESSAGE_TREE", $i++);
define("PHORUM_MERGE_THREAD", $i++);
define("PHORUM_DO_THREAD_MERGE", $i++);
define("PHORUM_SPLIT_THREAD", $i++);
define("PHORUM_DO_THREAD_SPLIT", $i++);

// admin sanity checks
$i=1;
define("PHORUM_SANITY_OK", $i++);
define("PHORUM_SANITY_WARN", $i++);
define("PHORUM_SANITY_CRIT", $i++);
define("PHORUM_SANITY_SKIP", $i++);

// ----------------------------------------------------------------------
// Error handling related
// ----------------------------------------------------------------------

/**
 * A general purpose errno value, mostly used for returning a generic
 * errno with a specific error message.
 */
define("PHORUM_ERRNO_ERROR", 1);

/**
 * An errno value, which indicates a permission problem.
 */
define("PHORUM_ERRNO_NOACCESS", 2);

/**
 * An errno value, which indicates that something was not found.
 */
define("PHORUM_ERRNO_NOTFOUND", 3);

/**
 * An errno value, which indicates a database integrity problem.
 */
define("PHORUM_ERRNO_INTEGRITY", 4);

/**
 * An errno value, which indicates invalid input data.
 */
define("PHORUM_ERRNO_INVALIDINPUT", 5);

// ----------------------------------------------------------------------
// User API related
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
define( 'PHORUM_SESSION_LONG_TERM' , 'phorum_session_v5' );

/**
 * Used for identifying short term sessions. The value is used as
 * the name for the session cookie for short term sessions
 * (this is used by the tighter authentication scheme).
 */
define( 'PHORUM_SESSION_SHORT_TERM', 'phorum_session_st' );

/**
 * Used for identifying admin sessions. The value is used as
 * the name for the session cookie for admin sessions.
 */
define( 'PHORUM_SESSION_ADMIN', 'phorum_admin_session' );

/**
 * Function call parameter, which tells various functions that
 * a front end forum session has to be handled.
 */
define('PHORUM_FORUM_SESSION', 1);

/**
 * Function call parameter, which tells various functions that
 * an admin back end session has to be handled.
 */
define('PHORUM_ADMIN_SESSION', 2);


/**
 * Function call flag that tells {@link phorum_api_user_set_active_user()}
 * that the short term forum session has to be activated.
 */
define('PHORUM_FLAG_SESSION_ST', 1);

/**
 * Function call flag, which tells {@link phorum_api_user_save()}
 * that the password field should be stored as is. This can be used
 * to feed Phorum MD5 encrypted passwords. Normally, the password
 * field would be MD5 encrypted by the function. This will keep the
 * phorum_api_user_save() function from double encrypting the password.
 */
define('PHORUM_FLAG_RAW_PASSWORD', 1);

/**
 * Function call flag that tells {@link phorum_api_user_get_display_name()}
 * that the returned display names have to be HTML formatted, so they can
 * be used for showing the name in HTML pages.
 */
define('PHORUM_FLAG_HTML', 1);

/**
 * Function call flag that tells {@link phorum_api_user_get_display_name()}
 * that the returned display names should be stripped down to plain text
 * format, so they can be used for showing the name in things like mail
 * messages and message quoting.
 */
define('PHORUM_FLAG_PLAINTEXT', 2);

/**
 * Function call parameter that tells
 * {@link phorum_api_user_session_create()} that session ids have to be
 * reset to new values as far as that is sensible for a newly
 * logged in user.
 */
define('PHORUM_SESSID_RESET_LOGIN', 1);

/**
 * Function call parameter, which tells
 * {@link phorum_api_user_session_create()} that all session ids have to
 * be reset to new values. This is for example appropriate after a user
 * changed the password (so active sessions on other computers or
 * browsers will be ended).
 */
define('PHORUM_SESSID_RESET_ALL', 2);

/**
 * Function call parameter, which tells {@link phorum_api_user_get_list()}
 * that all users have to be returned.
 */
define('PHORUM_GET_ALL', 0);

/**
 * Function call parameter, which tells {@link phorum_api_user_get_list()}
 * that all active users have to be returned.
 */
define('PHORUM_GET_ACTIVE', 1);

/**
 * Function call parameter, which tells {@link phorum_api_user_get_list()}
 * that all inactive users have to be returned.
 */
define('PHORUM_GET_INACTIVE', 2);

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

// ----------------------------------------------------------------------
// Custom field API related
// ----------------------------------------------------------------------

/**
 * The maximum size that can be used for storing data for a single
 * custom field. This value depends on the type of field that is used
 * in the database for storing custom field data. If you need a higher
 * value for this, then mind that the custom fields table needs to be
 * altered as wel.
 */
define('PHORUM_MAX_CPLENGTH', 65000);

/**
 * The custom field type that indicates that a custom field
 * is linked to the users.
 */
define('PHORUM_CUSTOM_FIELD_USER', 1);

/**
 * The custom field type that indicates that a custom field
 * is linked to the forums.
 */
define('PHORUM_CUSTOM_FIELD_FORUM', 2);

/**
 * The custom field type that indicates that a custom field
 * is linked to the messages.
 */
define('PHORUM_CUSTOM_FIELD_MESSAGE', 3);

// ----------------------------------------------------------------------
// File API related
// ----------------------------------------------------------------------

/**
 * Function call flag, which tells {@link phorum_api_file_retrieve()}
 * that the retrieved Phorum file data has to be returned to the caller.
 */
define("PHORUM_FLAG_GET", 1);

/**
 * Function call flag, which tells {@link phorum_api_file_retrieve()}
 * that the retrieved Phorum file can be sent to the browser directly.
 */
define("PHORUM_FLAG_SEND", 2);

/**
 * Function call flag, which tells the function to skip any
 * permission checks.
 */
define("PHORUM_FLAG_IGNORE_PERMS", 4);

/**
 * Function call flag, which tells {@link phorum_api_file_retrieve()}
 * to force a download by the browser by sending an application/octet-stream
 * Content-Type header. This flag will only have effect if the
 * {@link PHORUM_FLAG_SEND} flag is set as well.
 */
define("PHORUM_FLAG_FORCE_DOWNLOAD", 8);

// ----------------------------------------------------------------------
// Forum API related
// ----------------------------------------------------------------------

/**
 * Function call flag, which tells {@link phorum_api_forums_save()}
 * that it should not save the settings to the database, but only prepare
 * the data and return the prepared data array.
 */
define('PHORUM_FLAG_PREPARE', 1);

/**
 * Function call flag, which tells {@link phorum_api_forums_save()}
 * that the provided data have to be stored in the default settings.
 */
define('PHORUM_FLAG_DEFAULTS', 2);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should only contain forums from which the settings
 * can be inherited by another forum or folder.
 */
define('PHORUM_FLAG_INHERIT_MASTERS', 4);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should only contain folders.
 */
define('PHORUM_FLAG_FOLDERS', 8);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should only contain forums.
 */
define('PHORUM_FLAG_FORUMS', 16);

/**
 * Function call flag, which tells {@link phorum_api_forums_get()}
 * that the return data should contain inactive forums as well
 * (for these the "active" field is set to zero).
 */
define('PHORUM_FLAG_INCLUDE_INACTIVE', 32);

/**
 * Function call flag, which tells {@link phorum_api_forums_tree()}
 * to include empty folders in the tree.
 */
define('PHORUM_FLAG_INCLUDE_EMPTY_FOLDERS', 64);

/**
 * Function call flag, which tells {@link phorum_api_forums_format()}
 * to add information about unread messages to the formatted data.
 */
define('PHORUM_FLAG_ADD_UNREAD_INFO', 128);

// ----------------------------------------------------------------------
// Newflags API related
// ----------------------------------------------------------------------

/**
 * Function call flag, which tells {@link phorum_api_newflags_apply_to_messages}
 * that the newflags have to be processed in threaded mode. This means that the
 * newflag will be set for thread starter messages in the message list that
 * have at least one new message in their thread.
 */
define('PHORUM_NEWFLAGS_BY_THREAD', 1);

/**
 * Function call flag, which tells {@link phorum_api_newflags_apply_to_messages}
 * that the newflags have to be processed in single message mode. This means
 * that the newflag will be set for all messages that are new.
 */
define('PHORUM_NEWFLAGS_BY_MESSAGE', 2);

/**
 * Function call flag, which tells {@link phorum_api_newflags_apply_to_messages}
 * that the newflags have to be added in single message mode (see
 * {@link PHORUM_NEWFLAGS_MESSAGE}, except for sticky messages, which have
 * to be added in threaded mode. This mode is useful for the list page,
 * where sticky threads are always displayed collapsed, even if the list page
 * view is threaded.
 */
define('PHORUM_NEWFLAGS_BY_MESSAGE_EXSTICKY', 3);

/**
 * Function call flag, which tells {@link phorum_api_newflags_markread()}
 * that a single messages have to be marked read.
 */
define('PHORUM_MARKREAD_MESSAGES', 1);

/**
 * Function call flag, which tells {@link phorum_api_newflags_markread()}
 * that threads have to be marked read.
 */
define('PHORUM_MARKREAD_THREADS', 2);

/**
 * Function call flag, which tells {@link phorum_api_newflags_markread()}
 * that full forums have to be marked read.
 */
define('PHORUM_MARKREAD_FORUMS', 3);

// ----------------------------------------------------------------------
// Feed API related
// ----------------------------------------------------------------------

/**
 * Function call flag, which tells {@link phorum_api_feed()} that 
 * a feed has to be generated for all (readable) forums in the
 * current (v)root.
 */
define('PHORUM_FEED_VROOT', 0);

/**
 * Function call flag, which tells {@link phorum_api_feed()} that 
 * a feed has to be generated for a single forum.
 */
define('PHORUM_FEED_FORUM', 1);

/**
 * Function call flag, which tells {@link phorum_api_feed()} that 
 * a feed has to be generated for a single thread.
 */
define('PHORUM_FEED_THREAD', 2);

?>
