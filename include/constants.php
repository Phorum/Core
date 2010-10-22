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

    // put constants here that are configurable
    // these should be things that will not be changed
    // very often.  Things that are likely to be changed
    // by most admins should go in the admin.

    define("PHORUM_FILE_EXTENSION", "php");

    // The maximum number of messages that Phorum will remember as being
    // read, per forum per user. Phorum will trim off older read flags
    // if this limit is hit.
    define("PHORUM_MAX_READ_COUNT_PER_FORUM", 1000);

    // can moderators view email addresses
    define("PHORUM_MOD_EMAIL_VIEW", true);

    // can moderators view user's ip
    define("PHORUM_MOD_IP_VIEW", true);

    // change the author's name on deleting the user
    define("PHORUM_DELETE_CHANGE_AUTHOR", true);

    // enforce the use of only unregistered names for unregistered users
    define("PHORUM_ENFORCE_UNREGISTERED_NAMES",true);

    // maximum time in seconds for having the message editor open
    // this is used in determining stale attachment files
    define("PHORUM_MAX_EDIT_TIME", 86400);

    // string used to separate things like items in the title tag.
    define("PHORUM_SEPARATOR", " :: ");

    // default TTL for cache-data if not specified different
    define('PHORUM_CACHE_DEFAULT_TTL',3600);

    // split-variable for file-based cache
    define('PHORUM_CACHE_SPLIT',4);

    // the replace string for masking bad words.
    define('PHORUM_BADWORD_REPLACE', '@#$%&');

    // phorum's default template
    define('PHORUM_DEFAULT_TEMPLATE', 'emerald');

    // phorum's default language
    define('PHORUM_DEFAULT_LANGUAGE', 'english');
    
    // time in seconds for the admin token to timeout
    // 30 minutes default
    define('PHORUM_ADMIN_TOKEN_TIMEOUT', 1800);

    // The maximum length for message bodies.
    // If you upgrade this length, then note that you have to
    // change the storage type of the body field in the messages
    // table from TEXT to MEDIUMTEXT.
    define('MAX_MESSAGE_LENGTH', 65000);
    
    /////////////////////////////////////////
    //                                     //
    //     DO NOT EDIT BELOW THIS AREA     //
    //                                     //
    /////////////////////////////////////////

    // put constants here that need to stay the same value here.

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

?>
