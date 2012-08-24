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

/**
 * This script implements a MySQL Phorum database layer.
 *
 * The other Phorum code does not care how data is stored.
 * The only requirement is that it is returned from these functions
 * in the right way. This means each database can use as many or as
 * few tables as it likes. It can store the fields anyway it wants.
 *
 * The only thing to worry about is the table_prefix for the tables.
 * all tables for a Phorum install should be prefixed with the
 * table_prefix that will be entered in include/db/config.php.  This
 * will allow multiple Phorum installations to use the same database.
 *
 * @todo
 *     phorum_api_user_check_access() is used in this layer, but the
 *     include file for that is not included here. Keep it like that
 *     or add the required include? Or is it functionality that doesn't
 *     belong here and could better go into the core maybe?
 *
 * @package    PhorumDBLayer
 * @copyright  2010, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// Bail out if we're not loaded from the Phorum code.
if (!defined('PHORUM')) return;

// ----------------------------------------------------------------------
// Definitions
// ----------------------------------------------------------------------

// {{{ Constant and variable definitions

// The table prefix, which allows for storing multiple Phorum data sets
// in one single database.
$prefix = $PHORUM['DBCONFIG']['table_prefix'];

/**
 * These are the table names that are used by this database system.
 */
$PHORUM['message_table']            = $prefix . '_messages';
$PHORUM['user_newflags_table']      = $prefix . '_user_newflags';
$PHORUM['subscribers_table']        = $prefix . '_subscribers';
$PHORUM['files_table']              = $prefix . '_files';
$PHORUM['search_table']             = $prefix . '_search';
$PHORUM['settings_table']           = $prefix . '_settings';
$PHORUM['forums_table']             = $prefix . '_forums';
$PHORUM['user_table']               = $prefix . '_users';
$PHORUM['user_permissions_table']   = $prefix . '_user_permissions';
$PHORUM['groups_table']             = $prefix . '_groups';
$PHORUM['forum_group_xref_table']   = $prefix . '_forum_group_xref';
$PHORUM['user_group_xref_table']    = $prefix . '_user_group_xref';
$PHORUM['user_custom_fields_table'] = $prefix . '_user_custom_fields';
$PHORUM['banlist_table']            = $prefix . '_banlists';
$PHORUM['pm_messages_table']        = $prefix . '_pm_messages';
$PHORUM['pm_folders_table']         = $prefix . '_pm_folders';
$PHORUM['pm_xref_table']            = $prefix . '_pm_xref';
$PHORUM['pm_buddies_table']         = $prefix . '_pm_buddies';
$PHORUM['message_tracking_table']   = $prefix . '_messages_edittrack';

/**
 * Message fields which are always strings, even if they contain numbers only.
 * Used in post-message and update-message, otherwise strange things happen.
 */
$PHORUM['string_fields_message'] = array('author', 'subject', 'body', 'email');

/**
 * Forum fields which are always strings, even if they contain numbers only.
 */
$PHORUM['string_fields_forum'] = array('name', 'description', 'template');

/**
 * User fields which are always strings, even if they contain numbers only.
 */
$PHORUM['string_fields_user'] = array('username', 'real_name', 'display_name',
   'password', 'password_temp', 'sessid_lt', 'sessid_st', 'email', 'email_temp',
   'signature', 'user_language', 'user_template', 'moderator_data', 'settings_data'
);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return a database connection handle.
 */
define('DB_RETURN_CONN',     0);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return a SQL quoted value.
 */
define('DB_RETURN_QUOTED',   1);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return the query statement handle for a SQL query.
 */
define('DB_RETURN_RES',      2);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return a single database row for a SQL query.
 */
define('DB_RETURN_ROW',      3);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return an array of rows for a SQL query.
 */
define('DB_RETURN_ROWS',     4);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return a single database row for a SQL query
 * as an associative array
 */
define('DB_RETURN_ASSOC',    5);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return an array of rows for a SQL query
 * as associative arrays.
 */
define('DB_RETURN_ASSOCS',   6);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return a single value for a SQL query.
 */
define('DB_RETURN_VALUE',    7);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return the number of selected rows for a SQL query.
 */
define('DB_RETURN_ROWCOUNT', 8);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return the new auto_increment id value for
 * an insert SQL query.
 */
define('DB_RETURN_NEWID',    9);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function return an error for a SQL query or NULL if there
 * was no error.
 */
define('DB_RETURN_ERROR',   10);

/**
 * Function call parameter $return for {@link phorum_db_interact()}.
 * Makes the function close the connection to the database.
 * The function will return no data.
 */
define('DB_CLOSE_CONN',     11);

/**#@+
 * Constant for the phorum_db_interact() function call $flags parameter.
 */
define('DB_NOCONNECTOK',     1);
define('DB_MISSINGTABLEOK',  2);
define('DB_DUPFIELDNAMEOK',  4);
define('DB_DUPKEYNAMEOK',    8);
define('DB_DUPKEYOK',       16);
define('DB_TABLEEXISTSOK',  32);
define('DB_GLOBALQUERY',    64);
define('DB_MASTERQUERY',   128);
/**#@-*/

/**#@+
 * Constant for the phorum_db_get_recent_messages() function call
 * $list_type parameter.
 */
define('LIST_RECENT_MESSAGES',   0);
define('LIST_RECENT_THREADS',    1);
define('LIST_UPDATED_THREADS',   2);
/**#@-*/

// }}}

// ----------------------------------------------------------------------
// Utility functions (not directly part of the Phorum db API)
// ----------------------------------------------------------------------

// {{{ Function: phorum_db_mysql_connect()
/**
 * A wrapper function for connecting to the database.
 *
 * This function should not be used from the db layer code. Instead the
 * phorum_db_interact() function should be used in combination with the
 * DB_RETURN_CONN return type. This function is only implemented for
 * module writers that use this function in their code.
 *
 * @return $conn - A database connection resource handle.
 * @deprecated
 */
function phorum_db_mysql_connect() {
    return phorum_db_interact(DB_RETURN_CONN, NULL, NULL, DB_MASTERQUERY);
}
// }}}

// {{{ Function: phorum_db_sanitize_mixed()
/**
 * This function will sanitize a mixed variable based on a given type
 * for safe use in SQL queries.
 *
 * @param mixed &$var
 *     The variable to be sanitized. Passed by reference, so the original
 *     variable will be updated. It can be either a single variable or an
 *     array containing multiple variables.
 *
 * @param string $type
 *     Either "int" or "string" (the default).
 */
function phorum_db_sanitize_mixed(&$var, $type)
{
    if (is_array($var)) {
        foreach ($var as $id => $val) {
            if ($type == 'int') {
                $var[$id] = (int)$val;
            } else {
                $var[$id] = phorum_db_interact(DB_RETURN_QUOTED, $val);
            }
        }
    } else {
        if ($type=='int') {
            $var = (int)$var;
        } else {
            $var = phorum_db_interact(DB_RETURN_QUOTED, $var);
        }
    }
}
// }}}

// {{{ Function: phorum_db_validate_field()
/**
 * Check if a value that will be used as a field name in a SQL query
 * contains only characters that would appear in a field name.
 *
 * @param string $field_name
 *     The field name to check.
 *
 * @return boolean
 *     Whether the field name is valid or not (TRUE or FALSE).
 */
function phorum_db_validate_field($field_name)
{
    $valid = preg_match('!^[a-zA-Z0-9_]+$!', $field_name);
    return (bool)$valid;
}
// }}}


// ----------------------------------------------------------------------
// API functions
// ----------------------------------------------------------------------

// {{{ Function: phorum_db_check_connection()
/**
 * @todo
 *     we can save a function call by directly calling
 *     phorum_db_interact(). I'm also not sure if we need
 *     to do this check from common.php. We could take care
 *     of this in the db layer error handling too. Have to
 *     think about this ...
 *
 * Checks if a database connection can be made.
 *
 * @return boolean
 *     TRUE if a connection can be made, FALSE otherwise.
 */
function phorum_db_check_connection()
{
    return phorum_db_interact(
        DB_RETURN_CONN,
        NULL, NULL,
        DB_NOCONNECTOK | DB_MASTERQUERY
    ) ? TRUE : FALSE;
}
// }}}

// {{{ Function: phorum_db_close_connection()
/**
 * Close the database connection.
 */
function phorum_db_close_connection()
{
    phorum_db_interact(DB_CLOSE_CONN);
}
// }}}

// {{{ Function: phorum_db_run_queries()
/**
 * Execute an array of queries.
 *
 * @param array $queries
 *     An array of SQL queries to execute.
 *
 * @return mixed
 *     NULL if all queries were executed successfully or an error
 *     message on failure.
 */
function phorum_db_run_queries($queries)
{
    $PHORUM = $GLOBALS['PHORUM'];

    $error = NULL;

    foreach ($queries as $sql)
    {
        // Because this function is used from the upgrade scripts,
        // we ignore errors about duplicate fields and keys. That
        // way running the same upgrade scripts twice (in case there
        // were problems during the first run) won't bring up fatal
        // errors in case fields or keys are created a second time.
        $error = phorum_db_interact(
            DB_RETURN_ERROR,
            $sql, NULL,
            DB_DUPFIELDNAMEOK | DB_DUPKEYNAMEOK | DB_TABLEEXISTSOK |
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        if ($error !== NULL) break;
    }

    return $error;
}
// }}}

// {{{ Function: phorum_db_load_settings()
/**
 * Load the Phorum settings in the $PHORUM array.
 *
 * These settings are key/value pairs that are read from the settings
 * table. In the settings table, a data type is provided for each setting.
 * The supported types are:
 *
 * - V = Value: the value of this field is used as is.
 * - S = Serialized: the value of this field is a serialzed PHP variable,
 *       which will be unserialized before storing it in $PHORUM
 */
function phorum_db_load_settings()
{
    global $PHORUM;

    // At install time, there is no settings table.
    // So we ignore errors if we do not see that table.
    $settings = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT name, data, type
         FROM {$PHORUM['settings_table']}",
        NULL,
        DB_MISSINGTABLEOK
    );

    foreach ($settings as $setting)
    {
        $val = $setting[2] == 'V'
             ? $setting[1]
             : unserialize($setting[1]);

        $PHORUM[$setting[0]] = $val;
    }
}
// }}}

// {{{ Function: phorum_db_update_settings()
/**
 * Store or update Phorum settings.
 *
 * @param array $settings
 *     An array containing key/value pairs that have to be stored in the
 *     settings table. Values can be either scalars or arrays. This
 *     function will automatically serialize the arrays before storing them.
 *
 * @return boolean
 *     TRUE if all settings were stored successfully. This function will
 *     always return TRUE, so we could do without a return value. The
 *     return value is here for backward compatibility.
 */
function phorum_db_update_settings($settings)
{
    global $PHORUM;

    if (count($settings) > 0)
    {
        foreach ($settings as $field => $value)
        {
            if (is_array($value)) {
                $value = serialize($value);
                $type = 'S';
            } else {
                $type = 'V';
            }

            $field = phorum_db_interact(DB_RETURN_QUOTED, $field);
            $value = phorum_db_interact(DB_RETURN_QUOTED, $value);

            // Try to insert a new settings record.
            $res = phorum_db_interact(
                DB_RETURN_RES,
                "INSERT INTO {$PHORUM['settings_table']}
                        (data, type, name)
                 VALUES ('$value', '$type', '$field')",
                NULL,
                DB_DUPKEYOK | DB_MASTERQUERY
            );
            // If no result was returned, then the query failed. This probably
            // means that we already have the settings record in the database.
            // So instead of inserting a record, we need to update one here.
            if (!$res) {
              phorum_db_interact(
                  DB_RETURN_RES,
                  "UPDATE {$PHORUM['settings_table']}
                   SET    data = '$value',
                          type = '$type'
                   WHERE  name = '$field'",
                  NULL,
                  DB_MASTERQUERY
              );
            }
        }
    }
    else trigger_error(
        'phorum_db_update_settings(): $settings cannot be empty',
        E_USER_ERROR
    );

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_get_thread_list()
/**
 * Retrieve a list of visible messages for a given page offset.
 *
 * By default, the message body is not included in the fetch queries.
 * To retrieve bodies as well, a true value has to be passed for the
 * $include_bodies parameter.
 *
 * NOTE: ALL dates must be returned as Unix timestamps
 *
 * @param integer $page
 *     The index of the page to return, starting with 0.
 *
 * @param boolean $include_bodies
 *     Whether to include the message bodies in the return data or not.
 *
 * @return array
 *     An array of messages, indexed by message id.
 */
function phorum_db_get_thread_list($page, $include_bodies=FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($page, 'int');

    // The messagefields that we want to fetch from the database.
    $messagefields =
       'author, datestamp, email, message_id, forum_id, meta,
        moderator_post, modifystamp, parent_id, msgid, sort, moved, status,
        subject, thread, thread_count, user_id, viewcount, threadviewcount,
        closed, ip, recent_message_id, recent_user_id, recent_author';

    // Include the message bodies in the thread list if requested.
    if ($include_bodies) {
        $messagefields .= ',body';
    }

    // The sort mechanism to use.
    if ($PHORUM['float_to_top']) {
        $sortfield = 'modifystamp';
        $index = 'list_page_float';
    } else {
        $sortfield = 'thread';
        $index = 'list_page_flat';
    }

    // Initialize the return array.
    $messages = array();

    // The groups of messages which we want to fetch from the database.
    // stickies : sticky messages (only on the first page)
    // threads  : thread starter messages (always)
    // replies  : thread reply messages (only in threaded list mode)
    $groups = array();
    if ($page == 0) $groups[] = 'stickies';
    $groups[] = 'threads';
    if ($PHORUM['threaded_list']) $groups[] = 'replies';

    // For remembering the message ids for which we want to fetch the replies.
    $replymsgids = array();

    // Process all groups.
    foreach ($groups as $group)
    {
        $sql = NULL;

        switch ($group)
        {
            // Stickies.
            case 'stickies':

                $sql = "SELECT $messagefields
                        FROM   {$PHORUM['message_table']}
                        WHERE  status=".PHORUM_STATUS_APPROVED." AND
                               parent_id=0 AND
                               sort=".PHORUM_SORT_STICKY." AND
                               forum_id={$PHORUM['forum_id']}
                        ORDER  BY sort, $sortfield desc";
                break;

            // Threads.
            case 'threads':

                if ($PHORUM['threaded_list']) {
                    $limit = $PHORUM['list_length_threaded'];
                } else {
                    $limit = $PHORUM['list_length_flat'];
                }
                $start = $page * $limit;

                $sql = "SELECT $messagefields
                        FROM   {$PHORUM['message_table']}
                        USE    INDEX ($index)
                        WHERE  $sortfield > 0 AND
                               forum_id = {$PHORUM['forum_id']} AND
                               status = ".PHORUM_STATUS_APPROVED." AND
                               parent_id = 0 AND
                               sort > 1
                        ORDER  BY $sortfield DESC
                        LIMIT  $start, $limit";
                break;

            // Reply messages.
            case 'replies':

                // We're done if we did not collect any messages with replies.
                if (! count($replymsgids)) break;

                $sortorder = "sort, $sortfield DESC, message_id";

                if (!empty($PHORUM['reverse_threading']))
                    $sortorder.=' DESC';

                $sql = "SELECT $messagefields
                        FROM   {$PHORUM['message_table']}
                        WHERE  status = ".PHORUM_STATUS_APPROVED." AND
                               thread in (" . implode(",",$replymsgids) .")
                        ORDER  BY $sortorder";
                break;

        } // End of switch ($group)

        // Continue with the next group if no SQL query was formulated.
        if ($sql === NULL) continue;

        // Query the messages for the current group.
        $rows = phorum_db_interact(DB_RETURN_ASSOCS, $sql, 'message_id');
        foreach ($rows as $id => $row)
        {
            // Unpack the thread message meta data.
            $row['meta'] = empty($row['meta'])
                         ? array()
                         : unserialize($row['meta']);

            // Add the row to the list of messages.
            $messages[$id] = $row;

            // We need the message ids for fetching reply messages.
            if ($group == 'threads' && $row['thread_count'] > 1) {
                $replymsgids[] = $id;
            }
        }
    }

    return $messages;
}
// }}}

// {{{ Function: phorum_db_get_recent_messages
/**
 * Retrieve a list of recent messages for all forums for which the user has
 * read permission, for a particular forum, for a list of forums or for a
 * particular thread. Optionally, only top level thread messages can be
 * retrieved.
 *
 * The original version of this function came from Jim Winstead of mysql.com
 *
 * @param integer $length
 *     Limit the number of returned messages to this number.
 *
 * @param integer $offset
 *     When using the $length parameter to limit the number of returned
 *     messages, this parameter can be used to specify the retrieval offset.
 *
 * @param integer $forum_id
 *     A forum_id, an array of forum_ids or 0 (zero) to retrieve messages
 *     from any forum.
 *
 * @param integer $thread
 *     A thread id or 0 (zero) to retrieve messages from any thread.
 *
 * @param integer $list_type
 *     This parameter determines the type of list that has to be returned.
 *     Options for this parameter are:
 *     - LIST_RECENT_MESSAGES: return a list of recent messages
 *     - LIST_RECENT_THREADS: return a list of recent threads
 *     - LIST_UPDATED_THREADS: return a list of recently updated threads
 *
 * @return array
 *     An array of recent messages, indexed by message_id. One special key
 *     "users" is set too. This one contains an array of all involved
 *     user_ids.
 */
function phorum_db_get_recent_messages($length, $offset = 0, $forum_id = 0, $thread = 0, $list_type = LIST_RECENT_MESSAGES)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Backward compatibility for the old $threads_only parameter.
    if (is_bool($list_type)) {
        $list_type = $list_type ? LIST_RECENT_THREADS : LIST_RECENT_MESSAGES;
    }

    settype($length,    'int');
    settype($offset,    'int');
    settype($thread,    'int');
    settype($list_type, 'int');
    phorum_db_sanitize_mixed($forum_id, 'int');

    // In case -1 is used as "any" value by the caller.
    if ($forum_id < 0) $forum_id = 0;
    if ($thread   < 0) $thread   = 0;

    // Parameter checking.
    if ($list_type < 0 || $list_type > 3) trigger_error(
        "phorum_db_get_recent_messages(): illegal \$list_type parameter used",
        E_USER_ERROR
    );
    if ($list_type != LIST_RECENT_MESSAGES && $thread) trigger_error(
        "phorum_db_get_recent_messages(): \$thread parameter can only be " .
        "used with \$list_type = LIST_RECENT_MESSAGES",
        E_USER_ERROR
    );

    // We have to check what forums the active Phorum user can read first.
    // Even if a $thread is passed, we have to make sure that the user
    // can read the containing forum. Here we convert the $forum_id argument
    // into an argument that is usable for phorum_api_user_check_access(),
    // in such way that it will always return an array of accessible forum_ids.
    if ($forum_id == 0) {
        $forum_id = PHORUM_ACCESS_LIST;
    } elseif(!is_array($forum_id)) {
        $forum_id = array($forum_id => $forum_id);
    }
    $allowed_forums = phorum_api_user_check_access(
        PHORUM_USER_ALLOW_READ, $forum_id
    );

    // If the user is not allowed to see any forum,
    // then return an empty array.
    if (empty($allowed_forums)) return array();

    // We need to differentiate on which key to use.
    // If selecting on a specific thread, then the best index
    // to use would be the thread_message index.
    if ($thread) {
        $use_key = 'thread_message';
    }
    // Indexes to use if we query exactly one forum.
    elseif (count($allowed_forums) == 1)
    {
        switch($list_type) {
            case LIST_RECENT_MESSAGES:
                $use_key = 'new_count';
                break;
            case LIST_RECENT_THREADS:
                $use_key = 'new_threads';
                break;
            case LIST_UPDATED_THREADS:
                $use_key = 'list_page_float';
                break;
        }
    }
    // Indexes to use if we query more than one forum.
    else
    {
        switch($list_type) {
            case LIST_RECENT_MESSAGES:
                $use_key = 'PRIMARY';
                break;
            case LIST_RECENT_THREADS:
                $use_key = 'recent_threads';
                break;
            case LIST_UPDATED_THREADS:
                $use_key = 'updated_threads';
                break;
        }
    }

    // Build the SQL query.
    $sql = "SELECT  *
            FROM    {$PHORUM['message_table']}
            USE     INDEX ($use_key)
            WHERE   status=".PHORUM_STATUS_APPROVED;

    if (count($allowed_forums) == 1) {
        $sql .= " AND forum_id = " . array_shift($allowed_forums);
    } else {
        $sql .= " AND forum_id IN (".implode(",", $allowed_forums).")";
    }

    if ($thread) {
        $sql.=" AND thread = $thread";
    }

    $sql .= " AND moved = 0";

    if ($list_type == LIST_RECENT_THREADS ||
        $list_type == LIST_UPDATED_THREADS) {
        $sql .= ' AND parent_id = 0';
    }

    if ($list_type == LIST_UPDATED_THREADS) {
        $sql .= ' ORDER BY modifystamp DESC';
    } else {
        $sql .= ' ORDER BY message_id DESC';
    }

    if ($length) {
        if ($offset > 0) {
            $sql .= " LIMIT $offset, $length";
        } else {
            $sql .= " LIMIT $length";
        }
    }

    // Retrieve matching messages from the database.
    $messages = phorum_db_interact(DB_RETURN_ASSOCS, $sql, 'message_id');

    // Post processing of received messages.
    $involved_users = array();
    foreach ($messages as $id => $message)
    {
        // Unpack the message meta data.
        $messages[$id]['meta'] = empty($message['meta'])
                               ? array()
                               : unserialize($message['meta']);

        // Collect all involved users.
        if (isset($message['user_id'])) {
            $involved_users[$message['user_id']] = $message['user_id'];
        }
    }

    // Store the involved users in the message array.
    $messages['users'] = $involved_users;

    return $messages;
}
// }}}

// {{{ Function: phorum_db_get_unapproved_list()
/**
 * Retrieve a list of messages which have not yet been approved by a moderator.
 *
 * NOTE: ALL dates must be returned as Unix timestamps
 *
 * @param $forum_id     - The forum id to work with or NULL in case all
 *                        forums have to be searched. You can also pass an
 *                        array of forum ids.
 * @param $on_hold_only - Only take into account messages which have to
 *                        be approved directly after posting. Do not include
 *                        messages which were hidden by a moderator.
 * @param $moddays      - Limit the search to the last $moddays number of days.
 *
 * @return              - An array of messages, indexed by message id.
 */
function phorum_db_get_unapproved_list($forum_id = NULL, $on_hold_only=FALSE, $moddays=0, $countonly = FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($on_hold_only, 'bool');
    settype($moddays, 'int');
    settype($countonly, 'bool');
    phorum_db_sanitize_mixed($forum_id, 'int');

    // Select a message count or full message records?
    $sql = 'SELECT ' . ($countonly ? 'count(*) ' : '* ') .
           'FROM ' . $PHORUM['message_table'] . ' WHERE ';

    if (is_array($forum_id)) {
        $sql .= 'forum_id IN (' . implode(', ', $forum_id) . ') AND ';
    } elseif ($forum_id !== NULL) {
        $sql .= "forum_id = $forum_id AND ";
    }

    if ($moddays > 0) {
        $checktime = time() - (86400*$moddays);
        $sql .= " datestamp > $checktime AND";
    }

    if ($on_hold_only) {
        $sql .= ' status = '.PHORUM_STATUS_HOLD;
    } else {
        // Use an UNION for speed. This is much faster than using
        // a (status=X or status=Y) query.
        $sql = "($sql status = ".PHORUM_STATUS_HOLD.") UNION " .
               "($sql status = ".PHORUM_STATUS_HIDDEN.")";
    }

    if (!$countonly) {
        $sql .= ' ORDER BY thread, message_id';
    }

    // Retrieve and return data for counting unapproved messages.
    if ($countonly) {
        $count_per_status = phorum_db_interact(DB_RETURN_ROWS, $sql);
        $sum = 0;
        foreach ($count_per_status as $count) $sum += $count[0];
        return $sum;
    }

    // Retrieve unapproved messages.
    $messages = phorum_db_interact(DB_RETURN_ASSOCS, $sql, 'message_id');

    // Post processing of received messages.
    foreach ($messages as $id => $message) {
        $messages[$id]['meta'] = empty($message['meta'])
                               ? array()
                               : unserialize($message['meta']);
    }

    return $messages;
}
// }}}

// {{{ Function: phorum_db_post_message()
/**
 * Store a new message in the database.
 *
 * The message will not be posted if it is a duplicate and if
 * $PHORUM['check_duplicate'] is set.
 *
 * The $message is passed by reference and in case the function completes
 * successfully, the "message_id" index will be set to the new value.
 * If the "thread" index is set to zero, a new thread will be started and the
 * "thread" index will be filled with the new thread id upon return.
 *
 * @param array &$message
 *     The message to post. This is an array, which should contain the
 *     following fields: forum_id, thread, parent_id, author, subject, email,
 *     ip, user_id, moderator_post, status, sort, msgid, body, closed.
 *     Additionally, the following optional fields can be set: meta,
 *     modifystamp, viewcount, threadviewcount.
 *
 * @param boolean $convert
 *     True in case the message is being inserted by a database conversion
 *     script. This will let you set the datestamp and message_id of the
 *     message from the $message data. Also, the duplicate message check
 *     will be fully skipped.
 *
 * @return integer
 *     The message_id that was assigned to the new message.
 */
function phorum_db_post_message(&$message, $convert=FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($convert, 'bool');

    foreach ($message as $key => $value) {
        if (is_numeric($value) &&
            !in_array($key,$PHORUM['string_fields_message'])) {
            $message[$key] = (int)$value;
        } elseif (is_array($value)) {
            $value = serialize($value);
            $message[$key] = phorum_db_interact(DB_RETURN_QUOTED, $value);
        } else {
            $message[$key] = phorum_db_interact(DB_RETURN_QUOTED, $value);
        }
    }

    // When converting messages, the post time should be in the message.
    $NOW = $convert ? $message['datestamp'] : time();

    // Check for duplicate posting of messages, unless we are converting a db.
    if (isset($PHORUM['check_duplicate']) && $PHORUM['check_duplicate'] && !$convert) {
        // Check for duplicate messages in the last hour.
        $check_timestamp = $NOW - 3600;
        $sql = "SELECT message_id
                FROM   {$PHORUM['message_table']}
                WHERE  forum_id  = {$message['forum_id']} AND
                       author    ='{$message['author']}' AND
                       subject   ='{$message['subject']}' AND
                       body      ='{$message['body']}' AND
                       datestamp > $check_timestamp";

        // Return 0 if at least one message can be found.
        if (phorum_db_interact(DB_RETURN_ROWCOUNT, $sql) > 0) return 0;
    }

    $insertfields = array(
        'forum_id'       => $message['forum_id'],
        'datestamp'      => $NOW,
        'thread'         => $message['thread'],
        'parent_id'      => $message['parent_id'],
        'author'         => "'" . $message['author'] . "'",
        'subject'        => "'" . $message['subject'] . "'",
        'email'          => "'" . $message['email'] . "'",
        'ip'             => "'" . $message['ip'] . "'",
        'user_id'        => $message['user_id'],
        'moderator_post' => $message['moderator_post'],
        'status'         => $message['status'],
        'sort'           => $message['sort'],
        'msgid'          => "'" . $message['msgid'] . "'",
        'body'           => "'" . $message['body'] . "'",
        'closed'         => $message['closed'],
        'moved'          => 0
    );

    // The meta field is optional.
    if (isset($message['meta'])) {
        $insertfields['meta'] = "'{$message['meta']}'";
    }

    // The moved field is optional.
    if (!empty($message['moved'])) {
        $insertfields['moved'] = 1;
    }

    // When handling a conversion, the message_id can be set.
    if ($convert && isset($message['message_id'])) {
        $insertfields['message_id'] = $message['message_id'];
    }

    if (isset($message['modifystamp'])) {
        $insertfields['modifystamp'] = $message['modifystamp'];
    }

    if (isset($message['viewcount'])) {
        $insertfields['viewcount'] = $message['viewcount'];
    }

    if (isset($message['threadviewcount'])) {
        $insertfields['threadviewcount'] = $message['threadviewcount'];
    }

    // Insert the message and get the new message_id.
    $message_id = phorum_db_interact(
        DB_RETURN_NEWID,
        "INSERT INTO {$PHORUM['message_table']}
                (".implode(', ', array_keys($insertfields)).")
         VALUES (".implode(', ', $insertfields).")",
        NULL,
        DB_MASTERQUERY
    );

    $message['message_id'] = $message_id;
    $message['datestamp']  = $NOW;

    // Updates for thread starter messages.
    if ($message['thread'] == 0)
    {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['message_table']}
             SET    thread     = $message_id
             WHERE  message_id = $message_id",
            NULL,
            DB_MASTERQUERY
        );

        $message['thread'] = $message_id;
    }

    if(empty($PHORUM['DBCONFIG']['empty_search_table'])) {

        // Full text searching updates.
        $search_text = $message['author']  .' | '.
        $message['subject'] .' | '.
        $message['body'];

        phorum_db_interact(
            DB_RETURN_RES,
            "INSERT DELAYED INTO {$PHORUM['search_table']}
            (message_id, forum_id,
            search_text)
            VALUES ({$message['message_id']}, {$message['forum_id']},
            '$search_text')",
            NULL,
            DB_MASTERQUERY
        );

    }

    return $message_id;
}
// }}}

// {{{ Function: phorum_db_update_message()
/**
 * Update a message in the database.
 *
 * Note: an update of the full text searching database is only handled
 * if all fields that we incorporate in full text searching (author,
 * subject and body) are in the update fields. If one of the fields is
 * provided, without providing the other two, then changes in the field
 * will not reflect in the full text searching info.
 *
 * @param $message_id - The message_id of the message to update.
 * @param $message    - An array containing the data for the message fields
 *                      that have to be updated. You can pass as many or
 *                      as few message fields as you wish to update.
 */
function phorum_db_update_message($message_id, $message)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($message_id, 'int');

    if (count($message) == 0) trigger_error(
        '$message cannot be empty in phorum_update_message()',
        E_USER_ERROR
    );

    foreach ($message as $field => $value)
    {
        if (phorum_db_validate_field($field))
        {
            if (is_numeric($value) &&
                !in_array($field, $PHORUM['string_fields_message'])) {
                $fields[] = "$field = $value";
            } elseif (is_array($value)) {
                $value = phorum_db_interact(DB_RETURN_QUOTED,serialize($value));
                $message[$field] = $value;
                $fields[] = "$field = '$value'";
            } else {
                $value = phorum_db_interact(DB_RETURN_QUOTED, $value);
                $message[$field] = $value;
                $fields[] = "$field = '$value'";
            }
        }
    }

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['message_table']}
         SET " . implode(', ', $fields) . "
         WHERE message_id = $message_id",
        NULL,
        DB_MASTERQUERY
    );

    // Full text searching updates.
    if (!empty($PHORUM['DBCONFIG']['mysql_use_ft']) &&
        isset($message['author']) &&
        isset($message['subject']) &&
        isset($message['body']) &&
        empty($PHORUM['DBCONFIG']['empty_search_table']) ) {


        $search_text = $message['author']  .' | '.
                       $message['subject'] .' | '.
                       $message['body'];

        phorum_db_interact(
            DB_RETURN_RES,
            "REPLACE DELAYED INTO {$PHORUM['search_table']}
             SET     message_id  = {$message_id},
                     forum_id    = {$message['forum_id']},
                     search_text = '$search_text'",
           NULL,
           DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_delete_message()
/**
 * Delete a message or a message tree from the database.
 *
 * @param integer $message_id
 *     The message_id of the message which should be deleted.
 *
 * @param integer $mode
 *     The mode of deletion. This is one of:
 *     - PHORUM_DELETE_MESSAGE: Delete a message and reconnect
 *       its reply messages to the parent of the deleted message.
 *     - PHORUM_DELETE_TREE: Delete a message and all its reply messages.
 */
function phorum_db_delete_message($message_id, $mode = PHORUM_DELETE_MESSAGE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($message_id, 'int');
    settype($mode, 'int');

    // Find the info for the message that has to be deleted.
    $msg = phorum_db_interact(
        DB_RETURN_ASSOC,
        "SELECT forum_id, message_id, thread, parent_id
         FROM   {$PHORUM['message_table']}
         WHERE  message_id = $message_id"
    );
    if (empty($msg)) trigger_error(
        "No message found for message_id $message_id", E_USER_ERROR
    );

    // Find all message_ids that have to be deleted, based on the mode.
    if ($mode == PHORUM_DELETE_TREE) {
        $mids = phorum_db_get_messagetree($message_id, $msg['forum_id']);
        $where = "message_id IN ($mids)";
        $mids = explode(',', $mids);
    } else {
        $mids = array($message_id);
        $where = "message_id = $message_id";
    }

    // First, the messages are unapproved, so replies will not get posted
    // during the time that we need for deleting them. There is still a
    // race condition here, but this already makes things quite reliable.
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['message_table']}
         SET    status=".PHORUM_STATUS_HOLD."
         WHERE  $where",
        NULL,
        DB_MASTERQUERY
    );

    $thread = $msg['thread'];

    // Change reply messages to point to the parent of the deleted message.
    if ($mode == PHORUM_DELETE_MESSAGE)
    {
        // The forum_id is in here for speeding up the query
        // (with the forum_id a lookup key will be used).
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['message_table']}
             SET    parent_id = {$msg['parent_id']}
             WHERE  forum_id  = {$msg['forum_id']} AND
                    parent_id = {$msg['message_id']}",
            NULL,
            DB_MASTERQUERY
        );
    }

    // Delete the messages.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['message_table']}
         WHERE $where",
        NULL,
        DB_MASTERQUERY
    );

    // Delete the read flags.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['user_newflags_table']}
         WHERE $where",
        NULL,
        DB_MASTERQUERY
    );

    // Delete the edit tracking.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['message_tracking_table']}
         WHERE $where",
        NULL,
        DB_MASTERQUERY
    );

    // Full text searching updates.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['search_table']}
         WHERE $where",
        NULL,
        DB_MASTERQUERY
    );

    // It kind of sucks to have this here, but it is the best way
    // to ensure that thread info gets updated if messages are deleted.
    // Leave this include down here, so it is included conditionally.
    include_once('./include/thread_info.php');
    phorum_update_thread_info($thread);

    // We need to delete the subscriptions for the thread too.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['subscribers_table']}
         WHERE forum_id > 0 AND thread = $thread",
        NULL,
        DB_MASTERQUERY
    );

    // This function will be slow with a lot of messages.
    phorum_db_update_forum_stats(TRUE);

    return $mids;
}
// }}}

// {{{ Function: phorum_db_get_messagetree()
/**
 * Build a tree of all child (reply) messages below a message_id.
 *
 * @param integer $message_id
 *     The message_id for which to build the message tree.
 *
 * @param integer $forum_id
 *     The forum_id for the message.
 *
 * @return string
 *     A string containing a comma separated list of child message_ids
 *     for the given message_id.
 */
function phorum_db_get_messagetree($message_id, $forum_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($message_id, 'int');
    settype($forum_id, 'int');

    // Find all children for the provided message_id.
    $child_ids = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT message_id
         FROM {$PHORUM['message_table']}
         WHERE forum_id  = $forum_id AND
               parent_id = $message_id"
    );

    // Recursively build the message tree.
    $tree = "$message_id";
    foreach ($child_ids as $child_id) {
        $tree .= ',' . phorum_db_get_messagetree($child_id[0], $forum_id);
    }

    return $tree;
}
// }}}

// {{{ Function: phorum_db_get_message()
/**
 * Retrieve message(s) from the messages table by comparing value(s)
 * for a specified field in that table.
 *
 * You can provide either a single value or an array of values to search
 * for. If a single value is provided, then the function will return the
 * first matching message in the table. If an array of values is provided,
 * the function will return all matching messages in an array.
 *
 * @param mixed $value
 *     The value that you want to search on in the messages table.
 *     This can be either a single value or an array of values.
 *
 * @param string $field
 *     The message field (database column) to search on.
 *
 * @param boolean $ignore_forum_id
 *     By default, this function will only search for messages within the
 *     active forum (as defined by $PHORUM["forum_id"). By setting this
 *     parameter to a true value, the function will search in any forum.
 *
 * @param boolean $write_server
 *     This value can be set to true to specify that the message should be
 *     retrieved from the master (aka write-server) in case replication
 *     is used.
 *
 * @return mixed
 *     Either a single message or an array of messages (indexed by
 *     message_id), depending on the $value parameter. If no message is
 *     found at all, then either an empty array or NULL is returned
 *     (also depending on the $value parameter).
 */
function phorum_db_get_message($value, $field='message_id', $ignore_forum_id=FALSE, $write_server=FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    phorum_db_sanitize_mixed($value, 'string');
    settype($ignore_forum_id, 'bool');
    if (!phorum_db_validate_field($field)) trigger_error(
        'phorum_db_get_message(): Illegal database field ' .
        '"' . htmlspecialchars($field) . '"', E_USER_ERROR
    );

    $forum_id_check = '';
    if (!$ignore_forum_id && !empty($PHORUM['forum_id'])) {
        $forum_id_check = "forum_id = {$PHORUM['forum_id']} AND ";
    }

    if (is_array($value)) {
        $multiple = TRUE;
        $checkvar = "$field IN ('".implode("','",$value)."')";
        $limit = '';
    } else {
        $multiple=FALSE;
        $checkvar = "$field = '$value'";
        $limit = 'LIMIT 1';
    }

    $return = $multiple ? array() : NULL;

    if($write_server) {
        $flags = DB_MASTERQUERY;
    } else {
        $flags = 0;
    }

    $messages = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM   {$PHORUM['message_table']}
         WHERE  $forum_id_check $checkvar
         $limit",
        NULL,
        $flags
    );

    foreach ($messages as $message)
    {
        $message['meta'] = empty($message['meta'])
                         ? array()
                         : unserialize($message['meta']);

        if (! $multiple) {
            $return = $message;
            break;
        }

        $return[$message['message_id']] = $message;
    }

    return $return;
}
// }}}

// {{{ Function: phorum_db_get_messages()
/**
 * Retrieve messages from a specific thread.
 *
 * @param integer $thread
 *     The id of the thread.
 *
 * @param integer $page
 *     A page offset (based on the configured read_length) starting with 1.
 *     All messages are returned in case $page is 0.
 *
 * @param boolean $ignore_mod_perms
 *     If this parameter is set to a true value, then the function will
 *     return hidden messages, even if the active Phorum user is not
 *     a moderator.
 *
 * @param boolean $write_server
 *     This value can be set to true to specify that the message should be retrieved
 *     from the master (aka write-server) in case replication is used
 *
 * @return array
 *     An array of messages, indexed by message_id. One special key "users"
 *     is set too. This one contains an array of all involved user_ids.
 */
function phorum_db_get_messages($thread, $page=0, $ignore_mod_perms=FALSE, $write_server = FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($thread, 'int');
    settype($page, 'int');
    settype($ignore_mod_perms, 'int');

    // Check if the forum_id has to be checked.
    $forum_id_check = '';
    if (!empty($PHORUM['forum_id'])) {
        $forum_id_check = "forum_id = {$PHORUM['forum_id']} AND";
    }

    // Determine if not approved messages should be displayed.
    $approvedval = '';
    if (!$ignore_mod_perms &&
        !phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
        $approvedval = 'AND status ='.PHORUM_STATUS_APPROVED;
    }

    $sql = "SELECT *
            FROM   {$PHORUM['message_table']}
            WHERE  $forum_id_check
                   thread = $thread
                   $approvedval
            ORDER  BY message_id";

    if ($page > 0) {
       // Handle the page offset.
       $start = $PHORUM['read_length'] * ($page-1);
       $sql .= " LIMIT $start,".$PHORUM['read_length'];
    } else {
       // Handle reverse threading. This is only done if $page is 0.
       // In that case, the messages for threaded read are retrieved.
       if (!empty($PHORUM['reverse_threading']))
           $sql.=' DESC';
    }

    if($write_server) {
        $flags = DB_MASTERQUERY;
    } else {
        $flags = 0;
    }

    $messages = phorum_db_interact(DB_RETURN_ASSOCS, $sql, 'message_id', $flags);
    $involved_users = array();

    foreach ($messages as $id => $message)
    {
        // Unpack the message meta data.
        $messages[$id]['meta'] = empty($message['meta'])
                               ? array()
                               : unserialize($message['meta']);

        // Collect all involved users.
        if ($message['user_id']) {
            $involved_users[$message['user_id']] = $message['user_id'];
        }
    }

    // Always include the thread starter message in the return data.
    // It might not be in the messagelist if a page offset is used
    // (since the thread starter is only on the first page).
    if (count($messages) && !isset($messages[$thread]))
    {
        $starter = phorum_db_interact(
            DB_RETURN_ASSOC,
            "SELECT *
             FROM   {$PHORUM['message_table']}
             WHERE  $forum_id_check
                    message_id = $thread
                    $approvedval",
            NULL,
            $flags
        );

        if ($starter)
        {
            // Unpack the message meta data.
            $starter['meta'] = empty($starter['meta'])
                             ? array()
                             : unserialize($starter['meta']);

            $messages[$thread] = $starter;

            // Add to involved users.
            if ($starter['user_id']) {
                $involved_users[$starter['user_id']] = $starter['user_id'];
            }
        }
    }

    // Store the involved users in the message array.
    $messages['users'] = $involved_users;

    return $messages;
}
// }}}

// {{{ Function: phorum_db_get_message_index()
/**
 * Retrieve the index of a message (the offset from the thread starter
 * message) within a thread.
 *
 * @param integer $thread
 *     The thread id.
 *
 * @param integer $message_id
 *     The message id for which to determine the index.
 *
 * @return integer
 *     The index of the message, starting with 0.
 */
function phorum_db_get_message_index($thread=0, $message_id=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // check for valid values
    if (empty($thread) || empty($message_id)) {
        return 0;
    }

    settype($thread, 'int');
    settype($message_id, 'int');

    $forum_id_check = '';
    if (!empty($PHORUM['forum_id'])) {
        $forum_id_check = "forum_id = {$PHORUM['forum_id']} AND";
    }

    $approvedval = '';
    if (!phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
        $approvedval='AND status ='.PHORUM_STATUS_APPROVED;
    }

    $index = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT count(*)
         FROM   {$PHORUM['message_table']}
         WHERE  $forum_id_check
                thread = $thread
                $approvedval AND
                message_id <= $message_id"
                );

    return $index;
}
// }}}

// {{{ Function: phorum_db_search()
/**
 * Search the database using the provided search criteria and return
 * an array containing the total count of matches and the visible
 * messages based on the page $offset and $length.
 *
 * @param string $search
 *     The query to search on in messages and subjects.
 *
 * @param mixed $author
 *     The query to search on in the message authors or a numerical user_id
 *     if searching for all messages for a certain user_id.
 *
 * @param boolean $return_threads
 *     Whether to return the results as threads (TRUE) or messages (FALSE).
 *     When searching for a user ($match_type = USER_ID), then only the
 *     thread starter messages that were posted by the user are returned.
 *
 * @param integer $offset
 *     The result page offset starting with 0.
 *
 * @param integer $length
 *     The result page length (nr. of results per page).
 *
 * @param string $match_type
 *     The type of search. This can be one of:
 *     - ALL:     search on all of the words (uses $search)
 *     - ANY:     search on any of the words (uses $search)
 *     - PHRASE:  search for an exact phrase (uses $search)
 *     - USER_ID: search for an author id (uses $author)
 *
 * @param integer $days
 *     The number of days to go back in the database for searching
 *     (last [x] days) or zero to search within all dates.
 *
 * @param string $match_forum
 *     The forum restriction. This can be either the string "ALL" to search
 *     in any of the readable forums or a comma separated list of forum_ids.
 *
 * @return array
 *     An array containing two fields:
 *     - "count" contains the total number of matching messages.
 *     - "rows" contains the messages that are visible, based on the page
 *       $offset and page $length. The messages are indexed by message_id.
 */
function phorum_db_search($search, $author, $return_threads, $offset, $length, $match_type, $days, $match_forum)
{
    $PHORUM = $GLOBALS['PHORUM'];

    $fulltext_mode = isset($PHORUM['DBCONFIG']['mysql_use_ft']) &&
                     $PHORUM['DBCONFIG']['mysql_use_ft'];

    $search = trim($search);
    $author = trim($author);
    settype($return_threads, 'bool');
    settype($offset, 'int');
    settype($length, 'int');
    settype($days, 'int');

    // For spreading search results over multiple pages.
    $start = $offset * $length;

    // Initialize the return data.
    $return = array('count' => 0, 'rows' => array());

    // Return immediately if the search queries are empty.
    if ($search == '' && $author == '') return $return;

    // Check what forums the active Phorum user can read.
    $allowed_forums = phorum_api_user_check_access(
        PHORUM_USER_ALLOW_READ, PHORUM_ACCESS_LIST
    );

    // If the user is not allowed to search any forum or the current
    // active forum, then return the emtpy search results array.
    if (empty($allowed_forums) ||
        ($PHORUM['forum_id']>0 &&
         !in_array($PHORUM['forum_id'], $allowed_forums))) return $return;

    // Prepare forum_id restriction.
    $match_forum_arr = explode(",", $match_forum);
    $search_forums = array();
    foreach ($match_forum_arr as $forum_id) {
        if ($forum_id == "ALL") {
            $search_forums = $allowed_forums;
            break;
        }
        if (isset($allowed_forums[$forum_id])) {
            $search_forums[] = $forum_id;
        }
    }
    if (count($search_forums)){
        $forum_where = "AND forum_id in (".implode(",", $search_forums).")";
    } else {
        // Hack attempt or error. Return empty search results.
        return $return;
    }

    // Prepare day limit restriction.
    if ($days > 0) {
        $ts = time() - 86400*$days;
        $datestamp_where = "AND datestamp >= $ts";
    } else {
        $datestamp_where = '';
    }

    // We make use of temporary tables for storing intermediate search
    // results. These tables are stored in $tables during processing.
    $tables = array();

    // ----------------------------------------------------------------------
    // Handle search for user_id only.
    // ----------------------------------------------------------------------

    if ($search == '' && $author != '' && $match_type == 'USER_ID')
    {
        $user_id = (int) $author;
        if (empty($user_id)) return $return;

        // Search for messages.
        $where = "user_id = $user_id AND
                  status=".PHORUM_STATUS_APPROVED." AND
                  moved=0";
        if ($return_threads) $where .= " AND parent_id = 0";
        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM   {$PHORUM['message_table']}
                USE    KEY(user_messages)
                WHERE  $where $forum_where
                ORDER  BY message_id DESC
                LIMIT  $start, $length";
        $rows = phorum_db_interact(DB_RETURN_ASSOCS, $sql,"message_id");

        // Retrieve the number of found messages.
        $count = phorum_db_interact(
            DB_RETURN_VALUE,
            "SELECT found_rows()"
        );

        // Fill the return data.
        $return = array("count" => $count, "rows"  => $rows);

        return $return;
    }

    // ----------------------------------------------------------------------
    // Handle search for message and subject.
    // ----------------------------------------------------------------------

    if ($search != '')
    {
        $match_str = '';
        $tokens = array();

        if ($match_type == "PHRASE")
        {
            $search = str_replace('"', '', $search);
            $match_str = '"'.phorum_db_interact(DB_RETURN_QUOTED, $search).'"';
        }
        else
        {
            // Surround with spaces so matching is easier.
            $search = " $search ";

            // Pull out all grouped terms, e.g. (nano mini).
            $paren_terms = array();
            if (strstr($search, '(')) {
                preg_match_all('/ ([+\-~]*\(.+?\)) /', $search, $m);
                $search = preg_replace('/ [+\-~]*\(.+?\) /', ' ', $search);
                $paren_terms = $m[1];
            }

            // Pull out all the double quoted strings,
            // e.g. '"iMac DV" or -"iMac DV".
            $quoted_terms = array();
            if (strstr( $search, '"')) {
                preg_match_all('/ ([+\-~]*".+?") /', $search, $m);
                $search = preg_replace('/ [+\-~]*".+?" /', ' ', $search);
                $quoted_terms = $m[1];
            }

            // Finally, pull out the rest words in the string.
            $norm_terms = preg_split("/\s+/", $search, 0, PREG_SPLIT_NO_EMPTY);

            // Merge all search terms together.
            $tokens =  array_merge($quoted_terms, $paren_terms, $norm_terms);
        }

        // Handle full text message / subject search.
        if ($fulltext_mode)
        {
            // Create a match string based on the parsed query tokens.
            if (count($tokens))
            {
                $match_str = '';

                foreach ($tokens as $term)
                {
                    if (!strstr("+-~", substr($term, 0, 1)))
                    {
                        if (strstr($term, ".") &&
                            !preg_match('!^".+"$!', $term) &&
                            substr($term, -1) != "*") {
                            $term = "\"$term\"";
                        }
                        if ($match_type == "ALL") {
                            $term = "+".$term;
                        }
                    }

                    $match_str .= "$term ";
                }

                $match_str = trim($match_str);
                $match_str = phorum_db_interact(DB_RETURN_QUOTED, $match_str);
            }

            $table_name = $PHORUM['search_table']."_ft_".md5(microtime());

            phorum_db_interact(
                DB_RETURN_RES,
                "CREATE TEMPORARY TABLE $table_name (
                     KEY (message_id)
                 ) ENGINE=HEAP
                   SELECT message_id
                   FROM   {$PHORUM['search_table']}
                   WHERE  MATCH (search_text)
                          AGAINST ('$match_str' IN BOOLEAN MODE)"
            );

            $tables[] = $table_name;
        }
        // Handle standard message / subject search.
        else
        {
            if (count($tokens))
            {
                $condition = ($match_type == "ALL") ? "AND" : "OR";

                foreach($tokens as $tid => $token) {
                     $tokens[$tid] = phorum_db_interact(DB_RETURN_QUOTED, $token);
                }

                $match_str = "('%".implode("%' $condition '%", $tokens)."%')";
            }

            $table_name = $PHORUM['search_table']."_like_".md5(microtime());

            phorum_db_interact(
                DB_RETURN_RES,
                "CREATE TEMPORARY TABLE $table_name (
                     KEY (message_id)
                 ) ENGINE=HEAP
                   SELECT message_id
                   FROM   {$PHORUM['search_table']}
                   WHERE  search_text LIKE $match_str"
            );

            $tables[] = $table_name;
        }
    }

    // ----------------------------------------------------------------------
    // Handle search for author.
    // ----------------------------------------------------------------------

    if ($author != '')
    {
        $table_name = $PHORUM['search_table']."_author_".md5(microtime());

        // Search either by user_id or by username.
        if ($match_type == "USER_ID") {
            $author = (int) $author;
            $author_where = "user_id = $author";
        } else {
            $author = phorum_db_interact(DB_RETURN_QUOTED, $author);
            $author_where = "author = '$author'";
        }

        phorum_db_interact(
            DB_RETURN_RES,
            "CREATE TEMPORARY TABLE $table_name (
               KEY (message_id)
             ) ENGINE=HEAP
               SELECT message_id
               FROM   {$PHORUM["message_table"]}
               WHERE  $author_where"
        );

        $tables[] = $table_name;
    }

    // ----------------------------------------------------------------------
    // Gather the results.
    // ----------------------------------------------------------------------

    if (count($tables))
    {
        // If we only have one temporary table, we can use it directly.
        if (count($tables) == 1) {
            $table = array_shift($tables);
        }
        // In case we have multiple temporary tables, we join them together
        // in a new temporary table for retrieving the results.
        else
        {
            $table = $PHORUM['search_table']."_final_".md5(microtime());

            $joined_tables = "";
            $main_table = array_shift($tables);
            foreach ($tables as $tbl) {
                $joined_tables.= "INNER JOIN $tbl USING (message_id)";
            }

            phorum_db_interact(
                DB_RETURN_RES,
                "CREATE TEMPORARY TABLE $table (
                   KEY (message_id)
                 ) ENGINE=HEAP
                   SELECT m.message_id
                   FROM   $main_table m $joined_tables"
            );
        }

        // When only threads need to be returned, then join the results
        // that we have so far with the message table into a result set
        // that only contains the threads for the results.
        if ($return_threads)
        {
            $threads_table = $PHORUM['search_table']."_final_threads_".md5(microtime());
            phorum_db_interact(
                DB_RETURN_RES,
                "CREATE TEMPORARY TABLE $threads_table (
                   KEY (message_id)
                 ) ENGINE=HEAP
                   SELECT distinct thread AS message_id
                   FROM   {$PHORUM['message_table']}
                          INNER JOIN $table
                          USING (message_id)"
            );

            $table = $threads_table;
        }

        // Retrieve the found messages.
        $rows = phorum_db_interact(
            DB_RETURN_ASSOCS,
            "SELECT SQL_CALC_FOUND_ROWS *
             FROM   {$PHORUM['message_table']}
                    INNER JOIN $table USING (message_id)
             WHERE  status=".PHORUM_STATUS_APPROVED."
                    $forum_where
                    $datestamp_where
             ORDER  BY datestamp DESC
             LIMIT  $start, $length",
             "message_id"
        );

        // Retrieve the number of found messages.
        $count = phorum_db_interact(
            DB_RETURN_VALUE,
            "SELECT found_rows()"
        );

        // Fill the return data.
        $return = array("count" => $count, "rows"  => $rows);
    }

    return $return;
}
// }}}

// {{{ Function: phorum_db_get_neighbour_thread()
/**
 * Retrieve the closest neighbour thread. What "neighbour" is, depends on the
 * float to top setting. If float to top is enabled, then the
 * modifystamp is used for comparison (so the time at which the last
 * message was posted to a thread). Otherwise, the thread id is used
 * (so the time at which a thread was started).
 *
 * @param integer $key
 *     The key value of the message for which the neighbour must be returned.
 *     The key value is either the modifystamp (if float to top is enabled)
 *     or the thread id.
 *
 * @param string $direction
 *     Either "older" or "newer".
 *
 * @return integer
 *     The thread id for the requested neigbour thread or 0 (zero) if there
 *     is no neighbour available.
 */
function phorum_db_get_neighbour_thread($key, $direction)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($key, 'int');

    $keyfield = $PHORUM['float_to_top'] ? 'modifystamp' : 'thread';

    switch ($direction) {
        case 'newer': $compare = '>'; $orderdir = 'ASC';  break;
        case 'older': $compare = '<'; $orderdir = 'DESC'; break;
        default:
            trigger_error(
                'phorum_db_get_neighbour_thread(): ' .
                'Illegal direction "'.htmlspecialchars($direction).'"',
                E_USER_ERROR
            );
    }

    // If the active Phorum user is not a moderator for the forum, then
    // the neighbour message should be approved.
    $approvedval = '';
    if (!phorum_api_user_check_access(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
        $approvedval = 'AND status = '.PHORUM_STATUS_APPROVED;
    }

    // Select the neighbour from the database.
    $thread = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT thread
         FROM   {$PHORUM['message_table']}
         WHERE  forum_id = {$PHORUM['forum_id']} AND
                parent_id = 0
                $approvedval AND
                $keyfield $compare $key
         ORDER  BY $keyfield $orderdir
         LIMIT  1"
    );

    return $thread;
}
// }}}

// {{{ Function: phorum_db_get_forums()
/**
 * Retrieve a list of forums. The forums which are returned can be filtered
 * through the function parameters. Note that only one parameter is
 * effective at a time.
 *
 * @param mixed $forum_ids
 *     A single forum_id or an array of forum_ids for which to retrieve the
 *     forum data. If this parameter is 0 (zero), then the $parent_id
 *     parameter will be checked.
 *
 * @param mixed $parent_id
 *     Retrieve the forum data for all forums that have their parent_id set
 *     to $parent_id. If this parameter is NULL, then the $vroot parameter
 *     will be checked.
 *
 * @param mixed $vroot
 *     Retrieve the forum data for all forums that are in the given $vroot.
 *     If this parameter is NULL, then the $inherit_id parameter will be
 *     checked.
 *
 * @param mixed $inherit_id
 *     Retrieve the forum data for all forums that inherit their settings
 *     from the forum with id $inherit_id.
 *
 * @return array
 *     An array of forums, indexed by forum_id.
 */
function phorum_db_get_forums($forum_ids = 0, $parent_id = NULL, $vroot = NULL, $inherit_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    phorum_db_sanitize_mixed($forum_ids, 'int');
    if ($parent_id  !== NULL) settype($parent_id, 'int');
    if ($vroot      !== NULL) settype($vroot, 'int');
    if ($inherit_id !== NULL) settype($inherit_id, 'int');

    // Backward compatibility: previously, -1 was used for $parent_id
    // instead of NULL for indicating "any parent_id".
    if ($parent_id !== NULL && $parent_id == -1) $parent_id = NULL;

    $where = '';
    if (!empty($forum_ids)) {
        if (is_array($forum_ids)) {
            $where .= 'forum_id IN ('.implode(', ', $forum_ids).')';
        } else {
            $where .= "forum_id = $forum_ids";
        }
    } elseif ($inherit_id !== NULL) {
        $where .= "inherit_id = $inherit_id";
        if (!defined('PHORUM_ADMIN')) $where.=' AND active=1';
    } elseif ($parent_id !== NULL) {
        $where .= "parent_id = $parent_id";
        if (!defined('PHORUM_ADMIN')) $where.=' AND active=1';
    } elseif ($vroot !== NULL) {
        $where .= "vroot = $vroot";
    } else {
        $where .= 'forum_id <> 0';
    }

    $forums = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM   {$PHORUM['forums_table']}
         WHERE  $where
         ORDER  BY display_order ASC, name",
       'forum_id'
    );

    return $forums;
}
// }}}

// {{{ Function: phorum_db_update_forum_stats()
/**
 * Update the forums stats. This function will update the thread count,
 * message count, sticky message count and last post timestamp for a forum.
 * The function can either process delta values for the stats (this is
 * the most friendly way of updating) or fill the stats from scratch by
 * querying the database for the correct value.
 *
 * When the forum stats are updated, the cache_version for the forum
 * will be raised by one. This will flag the cache system that cached
 * data for the forum has to be refreshed.
 *
 * @param boolean $refresh
 *     If TRUE, the all stats will be filled from scratch by querying
 *     the database.
 *
 * @param integer $msg_count_change
 *     Delta for the message count or zero to query the value
 *     from the database.
 *
 * @param integer $timestamp
 *     The post time of the last message or zero to query the value from
 *     the database.
 *
 * @param integer $thread_count_change
 *     Delta for the thread count or zero to query the value
 *     from the database.

 * @param integer $sticky_count_change
 *     Delta for the sticky message count or zero to query the value
 *     from the database.
 */
function phorum_db_update_forum_stats($refresh=FALSE, $msg_count_change=0, $timestamp=0, $thread_count_change=0, $sticky_count_change=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($refresh, 'bool');
    settype($msg_count_change, 'int');
    settype($timestamp, 'int');
    settype($thread_count_change, 'int');
    settype($sticky_count_change, 'int');

    // Always do a full refresh on small forums.
    if (isset($PHORUM['message_count']) && $PHORUM['message_count']<1000) {
        $refresh = TRUE;
    }

    if ($refresh || empty($msg_count_change)) {
        $message_count = phorum_db_interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM   {$PHORUM['message_table']}
             WHERE  forum_id = {$PHORUM['forum_id']} AND
                    status   = ".PHORUM_STATUS_APPROVED
        );
    } else {
        $message_count = "message_count+$msg_count_change";
    }

    if ($refresh || empty($timestamp)) {
        $last_post_time = phorum_db_interact(
            DB_RETURN_VALUE,
            "SELECT max(modifystamp)
             FROM   {$PHORUM['message_table']}
             WHERE  status   = ".PHORUM_STATUS_APPROVED." AND
                    forum_id = {$PHORUM['forum_id']}"
        );
        // In case we're calling this function for an empty forum.
        if ($last_post_time === NULL) {
            $last_post_time = 0;
        }
    } else {
        $last_post_time = phorum_db_interact(
            DB_RETURN_VALUE,
            "SELECT last_post_time
             FROM   {$PHORUM['forums_table']}
             WHERE  forum_id = {$PHORUM['forum_id']}"
        );
        if ($timestamp > $last_post_time) {
            $last_post_time = $timestamp;
        }
    }

    if ($refresh || empty($thread_count_change)) {
        $thread_count = phorum_db_interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM   {$PHORUM['message_table']}
             WHERE  forum_id  = {$PHORUM['forum_id']} AND
                    parent_id = 0 AND
                    status    = ".PHORUM_STATUS_APPROVED
        );
    } else {
        $thread_count = "thread_count+$thread_count_change";
    }

    if ($refresh || empty($sticky_count_change)) {
        $sticky_count = phorum_db_interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM   {$PHORUM['message_table']}
             WHERE  forum_id  = {$PHORUM['forum_id']} AND
                    sort      = ".PHORUM_SORT_STICKY." AND
                    parent_id = 0 AND
                    status    = ".PHORUM_STATUS_APPROVED
        );
    } else {
        $sticky_count = "sticky_count+$sticky_count_change";
    }

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['forums_table']}
         SET    cache_version  = cache_version + 1,
                thread_count   = $thread_count,
                message_count  = $message_count,
                sticky_count   = $sticky_count,
                last_post_time = $last_post_time
         WHERE  forum_id = {$PHORUM['forum_id']}",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_move_thread()
/**
 * Move a thread to another forum.
 *
 * @param integer $thread_id
 *     The id of the thread that has to be moved.
 *
 * @param integer
 *     The id of the destination forum.
 */
function phorum_db_move_thread($thread_id, $toforum)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($thread_id, 'int');
    settype($toforum, 'int');

    if ($toforum > 0 && $thread_id > 0)
    {
        // Retrieve the messages from the thread, so we know for which
        // messages we have to update the newflags and search data below.
        $thread_messages = phorum_db_get_messages($thread_id);
        unset($thread_messages['users']);

        // All we have to do to move the thread to a different forum,
        // is update the forum_id for the messages in that thread.
        // Simple, isn't it?
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['message_table']}
             SET    forum_id = $toforum
             WHERE  thread   = $thread_id",
            NULL,
            DB_MASTERQUERY
        );

        // Update the stats for the source forum.
        phorum_db_update_forum_stats(TRUE);

        // Update the stats for the destination forum.
        $old_id = $GLOBALS['PHORUM']['forum_id'];
        $GLOBALS['PHORUM']['forum_id'] = $toforum;
        phorum_db_update_forum_stats(TRUE);
        $GLOBALS['PHORUM']['forum_id'] = $old_id;

        // Move the newflags and search data to the destination forum.

        /**
         * @todo In the move thread code, there are some flaws. The
         *       newflags for the user that is moving the message
         *       are used as the source for deciding what flags
         *       to delete or move for all other users. This results
         *       in strange newflag problems.
         *
         *       This main issue here is that the newflags should be
         *       handled separately for each user; no updates should be
         *       based on the newflags for the active user. The current
         *       algorithm will only make sure that the newflags will look
         *       correct for that specific user. The problem is that we
         *       do not yet have an idea on how to handle this with
         *       enough performance.
         */
        // First, gather information for doing the updates.
        $new_newflags = phorum_db_newflag_get_flags($toforum);
        $message_ids  = array();
        $delete_ids   = array();
        $search_ids   = array();
        foreach ($thread_messages as $mid => $data)
        {
            // Gather information for updating the newflags.
            // Moving the newflag is only useful if it is higher than the
            // min_id of the target forum.
            if (!empty($new_newflags['min_id'][$toforum]) &&
                $mid > $new_newflags['min_id'][$toforum]) {
                $message_ids[] = $mid;
            } else {
            // Other newflags can be deleted.
                $delete_ids[] = $mid;
            }

            // gather the information for updating the search table
            $search_ids[] = $mid;
        }

        // Move newflags.
        if (count($message_ids)) {
            phorum_db_newflag_update_forum($message_ids);
        }

        // Update subscriptions.
        if (count($message_ids)) {
            $ids_str = implode(', ',$message_ids);
            phorum_db_interact(
                DB_RETURN_RES,
                "UPDATE {$PHORUM['subscribers_table']}
                 SET    forum_id = $toforum
                 WHERE  thread IN ($ids_str)",
                NULL,
                DB_MASTERQUERY
            );
        }

        // Delete newflags.
        if (count($delete_ids)) {
            $ids_str = implode(', ',$delete_ids);
            phorum_db_interact(
                DB_RETURN_RES,
                "DELETE FROM {$PHORUM['user_newflags_table']}
                 WHERE  message_id IN($ids_str)",
                NULL,
                DB_MASTERQUERY
            );
        }

        // Update search data.
        if (count($search_ids)) {
            $ids_str = implode(', ',$search_ids);
            phorum_db_interact(
                DB_RETURN_RES,
                "UPDATE {$PHORUM['search_table']}
                 SET    forum_id = $toforum
                 WHERE  message_id in ($ids_str)",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
}
// }}}

// {{{ Function: phorum_db_close_thread()
/**
 * Close a thread for posting.
 *
 * @param integer
 *     The id of the thread that has to be closed.
 */
function phorum_db_close_thread($thread_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($thread_id, 'int');

    if ($thread_id > 0) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['message_table']}
             SET    closed = 1
             WHERE  thread = $thread_id",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_reopen_thread()
/**
 * (Re)open a thread for posting.
 *
 * @param integer
 *     The id of the thread that has to be opened.
 */
function phorum_db_reopen_thread($thread_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($thread_id, 'int');

    if ($thread_id > 0) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['message_table']}
             SET    closed = 0
             WHERE  thread = $thread_id",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_add_forum()
/**
 * Create a forum.
 *
 * @param array $forum
 *     The forum to create. This is an array, which should contain the
 *     following fields: name, active, description, template, folder_flag,
 *     parent_id, list_length_flat, list_length_threaded, read_length,
 *     moderation, threaded_list, threaded_read, float_to_top,
 *     display_ip_address, allow_email_notify, language, email_moderators,
 *     display_order, edit_post, pub_perms, reg_perms.
 *
 * @return integer
 *     The forum_id that was assigned to the new forum.
 */
function phorum_db_add_forum($forum)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // check for fields that must be set for mysql strict mode
    if(empty($forum["description"])) $forum["description"] = "";
    if(empty($forum["forum_path"])) $forum["forum_path"] = "";
    if(empty($forum["template_settings"])) $forum["template_settings"] = "";

    $insertfields = array();
    foreach ($forum as $key => $value)
    {
        if (phorum_db_validate_field($key))
        {
            if (is_numeric($value) &&
                !in_array($key,$PHORUM['string_fields_forum'])) {
                $value = (int)$value;
                $insertfields[$key] = $value;
            /**
             * @todo Wouldn't it be better to have this one set to a real
             *       NULL value from the script that calls this function?
             *       If for some reason somebody wants to use the string
             *       'NULL' for a value (a geek setting up a Phorum
             *       probably ;-), then strange things will happen.
             */
            } elseif ($value == 'NULL') {
                $insertfields[$key] = $value;
            } else {
                $value = phorum_db_interact(DB_RETURN_QUOTED, $value);
                $insertfields[$key] = "'$value'";
            }
        }
    }


    $forum_id = phorum_db_interact(
        DB_RETURN_NEWID,
        "INSERT INTO {$PHORUM['forums_table']}
                (".implode(', ', array_keys($insertfields)).")
         VALUES (".implode(', ', $insertfields).")",
        NULL,
        DB_MASTERQUERY
    );

    return $forum_id;
}
// }}}

// {{{ Function: phorum_db_add_message_edit()
/**
 * Add a message-edit item
 *
 * @param array $edit_data
 *     The edit_data to add. This is an array, which should contain the
 *     following fields: diff_body, diff_subject, time, message_id and user_id.
 *
 * @return integer
 *     The tracking_id that was assigned to that edit
 */
function phorum_db_add_message_edit($edit_data)
{
    $PHORUM = $GLOBALS['PHORUM'];


    foreach ($edit_data as $key => $value) {
        if (is_numeric($value)) {
            $edit_data[$key] = (int)$value;
        } elseif (is_array($value)) {
            $value = serialize($value);
            $edit_data[$key] = phorum_db_interact(DB_RETURN_QUOTED, $value);
        } else {
            $edit_data[$key] = phorum_db_interact(DB_RETURN_QUOTED, $value);
        }
    }

    $insertfields = array(
        'message_id'     => $edit_data['message_id'],
        'user_id'        => $edit_data['user_id'],
        'time'           => $edit_data['time'],
        'diff_body'      => "'" . $edit_data['diff_body'] . "'",
        'diff_subject'   => "'" . $edit_data['diff_subject'] . "'",
    );

    // Insert the tracking-entry and get the new tracking_id.
    $tracking_id = phorum_db_interact(
        DB_RETURN_NEWID,
        "INSERT INTO {$PHORUM['message_tracking_table']}
                (".implode(', ', array_keys($insertfields)).")
         VALUES (".implode(', ', $insertfields).")",
        NULL,
        DB_MASTERQUERY
    );

    return $tracking_id;
}
// }}}

// {{{ Function: phorum_db_get_message_edits()
/**
 * Retrieve a list of message-edits for a message
 *
 * @param integer $message_id
 *     The message id for which to retrieve the edits.
 *
 * @return array
 *     An array of message edits, indexed by track_id. The array elements
 *     are arrays containing the fields: user_id, time, diff_body
 *     and diff_subject.
 */
function phorum_db_get_message_edits($message_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($message_id, 'int');

    // Select the message files from the database.
    $edits = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT user_id,
                time,
                diff_body,
                diff_subject,
                track_id
         FROM   {$PHORUM['message_tracking_table']}
         WHERE  message_id = $message_id
         ORDER  BY track_id ASC",
        'track_id'
    );

    foreach ($edits as $id => $edit)
    {
        // Unpack the message meta data.
        $edits[$id]['diff_body'] = empty($edit['diff_body'])
                               ? array()
                               : unserialize($edit['diff_body']);

        // Unpack the message meta data.
        $edits[$id]['diff_subject'] = empty($edit['diff_subject'])
                               ? array()
                               : unserialize($edit['diff_subject']);

    }

    return $edits;
}
// }}}

// {{{ Function: phorum_db_drop_forum()
/**
 * Drop a forum and all of its messages.
 *
 * @param integer $forum_id
 *     The id of the forum to drop.
 */
function phorum_db_drop_forum($forum_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($forum_id, 'int');

    // These are the tables that hold forum related data.
    $tables = array (
        $PHORUM['message_table'],
        $PHORUM['user_permissions_table'],
        $PHORUM['user_newflags_table'],
        $PHORUM['subscribers_table'],
        $PHORUM['forum_group_xref_table'],
        $PHORUM['forums_table'],
        $PHORUM['banlist_table'],
        $PHORUM['search_table']
    );

    // Delete the data for the $forum_id from all those tables.
    foreach ($tables as $table) {
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE FROM $table
             WHERE forum_id = $forum_id",
            NULL,
            DB_MASTERQUERY
        );
    }

    // Collect all orphin message attachment files from the database.
    // These are all messages that are linked to a message, but for which
    // the message_id does not exist in the message table (anymore).
    // This might catch some more messages than only the ones for the
    // deleted forum alone. That should never be a problem.
    $files = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT file_id
         FROM   {$PHORUM['files_table']}
                LEFT JOIN {$PHORUM['message_table']}
                USING (message_id)
         WHERE  {$PHORUM['files_table']}.message_id > 0 AND
                link = '" . PHORUM_LINK_MESSAGE . "' AND
                {$PHORUM['message_table']}.message_id is NULL",
        0 // keyfield 0 is the file_id
    );

    // Delete all orphan message attachment files.
    if (!empty($files)) {
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE FROM {$PHORUM['files_table']}
             WHERE  file_id IN (".implode(",", array_keys($files)).")",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_drop_folder()
/**
 * Drop a forum folder. If the folder contains child forums or folders,
 * then the parent_id for those will be updated to point to the parent
 * of the folder that is being dropped.
 *
 * @param integer $forum_id
 *     The id of the folder to drop.
 */
function phorum_db_drop_folder($forum_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($forum_id, 'int');

    // See if the $forum_id is really a folder and find its
    // parent_id, which we can use to reattach children of the folder.
    $new_parent_id = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT parent_id
         FROM   {$PHORUM['forums_table']}
         WHERE  forum_id = $forum_id AND
                folder_flag = 1"
    );
    if ($new_parent_id === NULL) trigger_error(
        "phorum_db_drop_folder(): id $forum_id not found or not a folder",
        E_USER_ERROR
    );

    // Start with reattaching the folder's children to the new parent.
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['forums_table']}
         SET    parent_id = $new_parent_id
         WHERE  parent_id = $forum_id",
        NULL,
        DB_MASTERQUERY
    );

    // Now, drop the folder.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['forums_table']}
         WHERE  forum_id = $forum_id",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_update_forum()
/**
 * Update the settings for one or more forums.
 *
 * @param array $forum
 *     The forum to update. This is an array, which should contain at least
 *     the field "forum_id" to indicate what forum to update. Next to that,
 *     one or more of the other fields from phorum_db_add_forum() can be
 *     used to describe changed values. The "forum_id" field can also
 *     contain an array of forum_ids. By using that, the settings can be
 *     updated for all the forum_ids at once.
 *
 * @return boolean
 *     True if all settings were stored successfully. This function will
 *     always return TRUE, so we could do without a return value. The
 *     return value is here for backward compatibility.
 */
function phorum_db_update_forum($forum)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Check if the forum_id is set.
    if (!isset($forum['forum_id']) || empty($forum['forum_id'])) trigger_error(
        'phorum_db_update_forum(): $forum["forum_id"] cannot be empty',
        E_USER_ERROR
    );

    phorum_db_sanitize_mixed($forum['forum_id'], 'int');

    // See what forum(s) to update.
    if (is_array($forum['forum_id'])) {
        $forumwhere = 'forum_id IN ('.implode(', ',$forum['forum_id']).')';
    } else {
        $forumwhere = 'forum_id = ' . $forum['forum_id'];
    }
    unset($forum['forum_id']);

    // Prepare the SQL code for updating the fields.
    $fields = array();
    foreach ($forum as $key => $value)
    {
        if (phorum_db_validate_field($key))
        {
            if ($key == 'forum_path') {
                $value = serialize($value);
                $value = phorum_db_interact(DB_RETURN_QUOTED, $value);
                $fields[] = "$key = '$value'";
            } elseif (is_numeric($value) &&
                !in_array($key,$PHORUM['string_fields_forum'])) {
                $value = (int)$value;
                $fields[] = "$key = $value";
            } elseif ($value == 'NULL') {
                $fields[] = "$key = $value";
            } else {
                $value = phorum_db_interact(DB_RETURN_QUOTED, $value);
                $fields[] = "$key = '$value'";
            }
        }
    }

    // Run the update, if there are fields to update.
    if (count($fields)) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['forums_table']}
             SET "  .implode(', ', $fields) . "
             WHERE  $forumwhere",
            NULL,
            DB_MASTERQUERY
        );
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_get_groups()
/**
 * Retrieve all groups or one specific group.
 *
 * @param mixed $group_id
 *     A single group id or an array of group ids for which to retrieve
 *     the group data. If this parameter is 0 (zero), then all groups will
 *     be returned.
 *
 * @param boolean $sorted
 *     If this parameter has a true value, then the list of groups will
 *     be sorted by the group name field.
 *
 * @return array
 *     An array of groups, indexed by group_id.
 */
function phorum_db_get_groups($group_id = 0, $sorted = FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];


    phorum_db_sanitize_mixed($group_id,"int");

    if(is_array($group_id) && count($group_id)) {
        $group_str=implode(',',$group_id);
        $group_where=" where group_id IN($group_str)";
    } elseif(!is_array($group_id) && $group_id!=0) {
        $group_where=" where group_id=$group_id";
    } else {
        $group_where="";
    }


    // Retrieve the group(s) from the database.
    $groups = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM {$PHORUM['groups_table']}
         $group_where",
        'group_id'
    );

    // Retrieve the group permissions from the database.
    $perms = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM {$PHORUM['forum_group_xref_table']}
         $group_where"
    );

    // Add the permissions to the group(s).
    foreach ($groups as $id => $group) $groups[$id]['permissions'] = array();
    foreach ($perms as $perm)
    {
        // Little safety net against orphin records (shouldn't happen).
        if (!isset($groups[$perm['group_id']])) continue;

        $groups[$perm['group_id']]['permissions'][$perm['forum_id']]
            = $perm['permission'];
    }

    // Sort the list by group name.
    if ($sorted) {
        uasort($groups, 'phorum_db_sort_groups');
    }

    return $groups;
}

function phorum_db_sort_groups($a,$b) {
    return strcasecmp($a["name"], $b["name"]);
}
// }}}

// {{{ Function: phorum_db_get_group_members()
/**
 * Retrieve a list of members for a group or for a list of groups.
 *
 * If the member list for a list of groups is requested, any member matching
 * the specified status in any of the groups will be included in the return
 * array. There will be no group info in the return array however, so this
 * function cannot be used to retrieve a full group to member mapping. This
 * specific functionality is used from the Phorum scripts to see if there are
 * unapproved group members in any of the forums for which the active user
 * can moderate the group members.
 *
 * @param mixed $group_id
 *     A single group_id or an array of group_ids, for which to retrieve
 *     the members.
 *
 * @param integer $status
 *     A specific member status to look for. Defaults to all.
 *     Possible statuses are:
 *     - PHORUM_USER_GROUP_SUSPENDED:  (temporarily) deactivated
 *     - PHORUM_USER_GROUP_UNAPPROVED: on hold, not yet approved
 *     - PHORUM_USER_GROUP_APPROVED:   active in the group
 *     - PHORUM_USER_GROUP_MODERATOR:  active + member moderator
 *
 * @return array $members
 *     An array containing members for the specified group(s). The array
 *     contains a simple mapping from user_id to group permission. Note
 *     that the permission is only useful in case a single group was
 *     requested (see the function description).
 */
function phorum_db_get_group_members($group_id, $status = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    phorum_db_sanitize_mixed($group_id, 'int');
    if ($status !== NULL) settype($status, 'int');

    if (is_array($group_id)) {
        $group_where = 'AND group_id IN (' . implode(', ', $group_id) . ')';
    } else {
        $group_where = "AND group_id = $group_id";
    }

    if ($status !== NULL) {
        $status_where = "AND xref.status = $status";
    } else {
        $status_where = '';
    }

    // This join is only here so that the list of members comes out sorted.
    // If phorum_db_user_get() sorts results itself, this join can go away.
    $members = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT xref.user_id AS user_id,
                xref.status  AS status
         FROM   {$PHORUM['user_table']} AS users,
                {$PHORUM['user_group_xref_table']} AS xref
         WHERE  users.user_id = xref.user_id
                $group_where
                $status_where
         ORDER  BY username ASC",
        0
    );

    // The records are full rows, but we want a user_id -> status mapping.
    foreach ($members as $id => $member) $members[$id] = $member[1];

    return $members;
}
// }}}

// {{{ Function: phorum_db_add_group()
/**
 * Add a group. This will merely create the group in the database. For
 * changing settings for the group, the function phorum_db_update_group()
 * has to be used.
 *
 * @param string $group_name
 *     The name to assign to the group.
 *
 * @param integer $group_id
 *     The group id to assign to the group or 0 (zero) to assign a new
 *     group id. Assigning a specific group id is and should only be
 *     used by conversion scripts.
 *
 * @return integer
 *     The group id of the newly created group.
 */
function phorum_db_add_group($group_name, $group_id=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($group_id, 'int');
    $group_name = phorum_db_interact(DB_RETURN_QUOTED, $group_name);

    $fields = $group_id > 0 ? 'name, group_id' : 'name';
    $values = $group_id > 0 ? "'$group_name', $group_id" : "'$group_name'";

    $group_id = phorum_db_interact(
        DB_RETURN_NEWID,
        "INSERT INTO {$PHORUM['groups_table']}
                ($fields)
         VALUES ($values)",
         NULL,
         DB_MASTERQUERY
    );

    return $group_id;
}
// }}}

// {{{ Function: phorum_db_update_group()
/**
 * Update the settings for a group.
 *
 * @param array $group
 *     The group to update. This is an array, which should contain at least
 *     the field "group_id" to indicate what group to update. Next to that,
 *     one or more of the following fields can be used:
 *     - name:
 *       The name for the group.
 *     - open:
 *       This field determines how new members are added to the group.
 *       Available options are:
 *       - PHORUM_GROUP_CLOSED:
 *         Only the administrator can add users to this group.
 *       - PHORUM_GROUP_OPEN:
 *         The group is open for membership requests by users and
 *         membership is assigned on request immediately.
 *       - PHORUM_GROUP_REQUIRE_APPROVAL:
 *         The group is open for membership requests by users,
 *         but membership has to be approved by an administrator or
 *         a user moderator for the group.
 *     - permissions:
 *       An array containing forum permissions for the group
 *       (key = forum_id and value = permission value).
 *
 * @return boolean
 *     True if all settings were stored successfully. This function will
 *     always return TRUE, so we could do without a return value.
 *     The return value is here for backward compatibility.
 */
function phorum_db_update_group($group)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Check if the group_id is set.
    if (!isset($group['group_id']) || empty($group['group_id'])) trigger_error(
        'phorum_db_update_group(): $group["group_id"] cannot be empty',
        E_USER_ERROR
    );

    settype($group['group_id'], 'int');
    $group_where = 'group_id = ' . $group['group_id'];

    // See what group fields we have to update.
    $fields = array();
    if (isset($group['name'])) {
        $fields[] = "name = '" .
                    phorum_db_interact(DB_RETURN_QUOTED, $group['name']) ."'";
    }
    if (isset($group['open'])) {
        $fields[] = 'open = ' . (int)$group['open'];
    }

    // Update group fields.
    if (count($fields)) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['groups_table']}
             SET ". implode(', ', $fields) . "
             WHERE  $group_where",
            NULL,
            DB_MASTERQUERY
        );
    }

    // Update the group permissions if requested.
    if (isset($group['permissions']))
    {
        // First, all existing forum permissions for the group are deleted.
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE FROM {$PHORUM['forum_group_xref_table']}
             WHERE  $group_where",
            NULL,
            DB_MASTERQUERY
        );

        // Second, all new permissions are inserted.
        foreach ($group['permissions'] as $forum_id => $permission)
        {
            settype($forum_id, 'int');
            settype($permission, 'int');

            phorum_db_interact(
                DB_RETURN_RES,
                "INSERT INTO {$PHORUM['forum_group_xref_table']}
                        (group_id, permission, forum_id)
                 VALUES ({$group['group_id']}, $permission, $forum_id)",
                NULL,
                DB_MASTERQUERY
            );
        }
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_delete_group()
/**
 * Delete a group.
 *
 * @param integer $group_id
 *     The id of the group to delete.
 */
function phorum_db_delete_group($group_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($group_id, 'int');

    // These are the tables that hold group related data.
    $tables = array (
        $PHORUM['groups_table'],
        $PHORUM['user_group_xref_table'],
        $PHORUM['forum_group_xref_table']
    );

    // Delete the data for the $group_id from all those tables.
    foreach ($tables as $table) {
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE FROM $table
             WHERE group_id = $group_id",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_user_get_moderators()
/**
 * Retrieve a list of moderators for a particular forum.
 *
 * @param integer $forum_id
 *     The forum for which to retrieve the moderators.
 *
 * @param boolean $exclude_admin
 *     If this parameter has a true value, then the admin users are kept
 *     out of the list.
 *
 * @param boolean $for_email
 *     If this parameter has a true value, then a list of moderators is
 *     created for sending moderator mail messages. Moderators which
 *     have disabled the moderation_email option will be excluded from
 *     the list in this case.
 *
 * @return array
 *     An array of moderators. The keys are user_ids and
 *     the values are email addresses.
 */
function phorum_db_user_get_moderators($forum_id, $exclude_admin=FALSE, $for_email=FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($forum_id, 'int');
    settype($exclude_admin, 'bool');
    settype($for_email, 'bool');

    // If we are gathering email addresses for mailing the moderators,
    // then honour the moderation_email setting for the user.
    $where_moderation_mail = $for_email ? 'AND user.moderation_email = 1' : '';

    // Exclude admins from the list, if requested.
    $admin = $exclude_admin ? '' :
        "SELECT DISTINCT user.user_id AS user_id,
                user.email AS email
         FROM   {$PHORUM['user_table']} AS user
         WHERE  user.active=1 AND user.admin=1
                $where_moderation_mail
         UNION
        ";


    $moderators = array();

    // Look up moderators which are configured through user permissions.
    $usermods = phorum_db_interact(
        DB_RETURN_ROWS,
        $admin .
        "SELECT DISTINCT user.user_id AS user_id,
                user.email AS email
         FROM   {$PHORUM['user_permissions_table']} AS perm
                INNER JOIN {$PHORUM['user_table']} AS user
                ON perm.user_id = user.user_id
         WHERE  perm.forum_id = $forum_id AND user.active=1 AND
                perm.permission>=".PHORUM_USER_ALLOW_MODERATE_MESSAGES." AND
                (perm.permission & ".PHORUM_USER_ALLOW_MODERATE_MESSAGES.">0)
                $where_moderation_mail"
    );

    // Add them to the moderator list.
    foreach ($usermods as $mod) $moderators[$mod[0]] = $mod[1];
    unset($usermods);

    // Look up moderators which are configured through group permissions.
    $groupmods = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT DISTINCT user.user_id AS user_id,
                user.email AS email
         FROM   {$PHORUM['user_table']} AS user,
                {$PHORUM['groups_table']} AS groups,
                {$PHORUM['user_group_xref_table']} AS usergroup,
                {$PHORUM['forum_group_xref_table']} AS forumgroup
         WHERE  user.user_id       = usergroup.user_id AND
                usergroup.group_id = groups.group_id AND
                groups.group_id    = forumgroup.group_id AND
                forum_id           = $forum_id AND
                permission & ".PHORUM_USER_ALLOW_MODERATE_MESSAGES." > 0 AND
                usergroup.status  >= ".PHORUM_USER_GROUP_APPROVED."
                $where_moderation_mail"
    );

    // Add them to the moderator list.
    foreach ($groupmods as $mod) $moderators[$mod[0]] = $mod[1];
    unset($groupmods);

    return $moderators;
}
// }}}

// {{{ Function: phorum_db_user_count()
/**
 * Count the total number of users in the Phorum system.
 *
 * @return integer
 *     The number of users.
 */
function phorum_db_user_count()
{
    $PHORUM = $GLOBALS["PHORUM"];

    return phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT count(*)
         FROM   {$PHORUM['user_table']}"
    );
}
// }}}

// {{{ Function: phorum_db_user_get_all()
/**
 * Retrieve all users from the database.
 *
 * This function returns a query resource handle. This handle can be used
 * to retrieve the users from the database one-by-one, by calling the
 * phorum_db_fetch_row() function.
 *
 * @return resource
 *     A query resource handle is returned. This handle can be used
 *     to retrieve the users from the database one-by-one, by
 *     calling the phorum_db_fetch_row() function.
 *
 * @todo This function might be as well replaced with user search and get
 *       functionality from the user API, if search is extended with an
 *       option to return a resource handle.
 */
function phorum_db_user_get_all($offset = 0, $length = 0)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($offset, 'int');
    settype($length, 'int');

    $limit = '';
    if ($length > 0) {
        $limit = "LIMIT $offset, $length";
    }

    return phorum_db_interact(
        DB_RETURN_RES,
        "SELECT *
         FROM   {$PHORUM['user_table']}
        $limit"
    );
}
// }}}

// {{{ Function: phorum_db_user_get()
/**
 * Retrieve one or more users.
 *
 * @param mixed $user_id
 *     The user_id or an array of user_ids for which to
 *     retrieve the user data.
 *
 * @param boolean $detailed
 *     If this parameter has a true value, then the user's
 *     permissions and groups are included in the return data.
 *
 * @param boolean $write_server
 *     This value can be set to true to specify that the user should be
 *     retrieved from the master (aka write-server) in case replication
 *     is used
 *
 * @return mixed
 *     If $user_id is a single user_id, then either a single user or NULL
 *     (in case the user_id was not found in the database) is returned.
 *     If $user_id is an array of user_ids, then an array of users is
 *     returned, indexed by user_id. For user_ids that cannot be found,
 *     there will be no array element at all.
 */
function phorum_db_user_get($user_id, $detailed = FALSE, $write_server = FALSE, $raw_data = FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    phorum_db_sanitize_mixed($user_id, 'int');

    if (is_array($user_id)) {
        if (count($user_id)) {
            $user_where = 'user_id IN ('.implode(', ', $user_id).')';
        } else {
            return array();
        }
    } else {
        $user_where = "user_id = $user_id";
    }

    if($write_server) {
        $flags = DB_MASTERQUERY;
    } else {
        $flags = 0;
    }

    // Retrieve the requested user(s) from the database.
    $users = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM   {$PHORUM['user_table']}
         WHERE  $user_where",
        'user_id',
        $flags
    );

    // No users found?
    if (count($users) == 0) return array();

    // Unpack the settings_data.
    foreach ($users as $id => $user) {
        $users[$id]['settings_data'] = empty($user['settings_data'])
                                     ? array()
                                     : unserialize($user['settings_data']);
    }

    // Retrieve detailed group and permission information for the user(s).
    if ($detailed)
    {
        // Retrieve forum user permissions for the requested users.
        $forum_permissions = phorum_db_interact(
            DB_RETURN_ROWS,
            "SELECT user_id,
                    forum_id,
                    permission
             FROM   {$PHORUM['user_permissions_table']}
             WHERE  $user_where",
            NULL,
            $flags
        );

        // Add forum user permissions to the users.
        foreach ($forum_permissions as $perm) {
            $users[$perm[0]]['forum_permissions'][$perm[1]] = $perm[2];
        }

        // Retrieve forum group permissions and groups for the requested users.
        // "status >= ..." is used to retrieve both approved group users
        // and group moderators.
        $group_permissions = phorum_db_interact(
            DB_RETURN_ROWS,
            "SELECT user_id,
                    {$PHORUM['user_group_xref_table']}.group_id AS group_id,
                    forum_id,
                    permission
             FROM   {$PHORUM['user_group_xref_table']}
                    LEFT JOIN {$PHORUM['forum_group_xref_table']}
                    USING (group_id)
             WHERE  $user_where AND
                    status >= ".PHORUM_USER_GROUP_APPROVED,
            NULL,
            $flags
        );

        // Add groups and forum group permissions to the users.
        foreach ($group_permissions as $perm)
        {
            // Skip permissions for users which are not in our
            // $users array. This should not happen, but it could
            // happen in case some orphin group permissions are
            // lingering in the database.
            if (!isset($users[$perm[0]])) continue;

            // Add the group_id to the user data.
            $users[$perm[0]]['groups'][$perm[1]] = $perm[1];

            // Are we handling a group permissions record?
            if (!empty($perm[2]))
            {
                // Initialize group permissions for the forum_id in the
                // user data.
                if (!isset($users[$perm[0]]['group_permissions'][$perm[2]])) {
                    $users[$perm[0]]['group_permissions'][$perm[2]] = 0;
                }

                // Merge the group permission by logical OR-ing the permission
                // with the permissions that we've got so far for the forum_id
                // in the user data.
                $users[$perm[0]]['group_permissions'][$perm[2]] |= $perm[3];
            }
        }
    }

    // Retrieve custom user profile fields for the requested users.
    $custom_fields = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM   {$PHORUM['user_custom_fields_table']}
         WHERE  $user_where",
        NULL,
        $flags
    );

    // Add custom user profile fields to the users.
    foreach ($custom_fields as $fld)
    {
        // Skip profile fields for users which are not in our
        // $users array. This should not happen, but it could
        // happen in case some orphin custom user fields
        // are lingering in the database.
        if (!isset($users[$fld['user_id']])) continue;

        // Skip unknown custom profile fields.
        if (! isset($PHORUM['PROFILE_FIELDS'][$fld['type']])) continue;

        // Fetch the name for the custom profile field.
        $name = $PHORUM['PROFILE_FIELDS'][$fld['type']]['name'];

        // For "html_disabled" fields, the data is XSS protected by
        // replacing special HTML characters with their HTML entities.
        if ($PHORUM['PROFILE_FIELDS'][$fld['type']]['html_disabled'] && $raw_data === FALSE) {
            $users[$fld['user_id']][$name] = htmlspecialchars($fld['data']);
            continue;
        }

        // Other fields can either contain raw values or serialized
        // arrays. For serialized arrays, the field data is prefixed with
        // a magic "P_SER:" (Phorum serialized) marker.
        if (substr($fld['data'],0,6) == 'P_SER:') {
            $users[$fld['user_id']][$name]=unserialize(substr($fld['data'],6));
            continue;
        }

        // The rest of the fields contain raw field data.
        $users[$fld['user_id']][$name] = $fld['data'];
    }

    if (is_array($user_id)) {
        return $users;
    } else {
        return isset($users[$user_id]) ? $users[$user_id] : NULL;
    }
}
// }}}

// {{{ Function: phorum_db_user_get_fields()
/**
 * Retrieve the data for a couple of user table fields for one or more users.
 *
 * @param mixed $user_id
 *     The user_id or an array of user_ids for which to retrieve
 *     the field data.
 *
 * @param mixed $fields
 *     The field or an array of fields for which to retrieve the data.
 *
 * @return array $users
 *     An array of users (no matter what type of variable $user_id is),
 *     indexed by user_id. For user_ids that cannot be found, there
 *     will be no array element at all.
 */
function phorum_db_user_get_fields($user_id, $fields)
{
    $PHORUM = $GLOBALS['PHORUM'];

    phorum_db_sanitize_mixed($user_id, 'int');

    if (is_array($user_id)) {
        if (count($user_id)) {
            $user_where = 'user_id IN ('.implode(', ', $user_id).')';
        } else {
            return array();
        }
    } else {
        $user_where = "user_id = $user_id";
    }

    if (!is_array($fields)) {
        $fields = array($fields);
    }
    foreach ($fields as $key => $field) {
        if (!phorum_db_validate_field($field)) {
            unset($fields[$key]);
        }
    }

    $users = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT user_id, ".implode(', ', $fields)."
         FROM   {$PHORUM['user_table']}
         WHERE  $user_where",
        'user_id'
    );

    return $users;
}
// }}}

// {{{ Function: phorum_db_user_get_list()
/**
 * Retrieve a list of all users for a given type.
 *
 * @param integer $type
 *     The type of users to retrieve. Available options are:
 *     - 0 = all users
 *     - 1 = all active users
 *     - 2 = all inactive users
 *
 * @return array $users
 *     An array of users, indexed by user_id. The values are arrays
 *     containing the fields "user_id", "username" and "display_name".
 *
 * @todo This function might be as well replaced with user search and get
 *       functionality from the user API,
 */
function phorum_db_user_get_list($type = 0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($type, 'int');

    $where = '';
    if     ($type == 1) $where = 'WHERE active  = 1';
    elseif ($type == 2) $where = 'WHERE active != 1';

    $users = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT user_id,
                username,
                display_name
         FROM   {$PHORUM['user_table']}
                $where
         ORDER  BY username ASC",
        'user_id'
    );

    return $users;
}
// }}}

// {{{ Function: phorum_db_user_check_login()
/**
 * Check if a user's authentication credentials are correct.
 *
 * @param string $username
 *     The username for the user.
 *
 * @param string $password
 *     The password for the user.
 *
 * @param boolean $temp_password
 *     If this parameter has a true value, the password_temp field will
 *     be checked instead of the password field.
 *
 * @return integer $user_id
 *     The user_id if the password is correct or 0 (zero)
 *     if the password is wrong.
 */
function phorum_db_user_check_login($username, $password, $temp_password=FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($temp_password, 'bool');
    $username = phorum_db_interact(DB_RETURN_QUOTED, $username);
    $password = phorum_db_interact(DB_RETURN_QUOTED, $password);

    $pass_field = $temp_password ? 'password_temp' : 'password';

    $user_id = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT user_id
         FROM   {$PHORUM['user_table']}
         WHERE  username    = '$username' AND
                $pass_field = '$password'"
    );

    return $user_id ? $user_id : 0;
}
// }}}

// {{{ Function: phorum_db_user_search()
/**
 * Search for users, based on a simple search condition,
 * which can be used to search on any field in the user table.
 *
 * The parameters $field, $value and $operator (which are used for defining
 * the search condition) can be arrays or single values. If arrays are used,
 * then all three parameter arrays must contain the same number of elements
 * and the key values in the arrays must be the same.
 *
 * @param mixed $field
 *     The user table field (string) or fields (array) to search on.
 *
 * @param mixed $value
 *     The value (string) or values (array) to search for.
 *
 * @param mixed $operator
 *     The operator (string) or operators (array) to use. Valid operators are
 *     "=", "!=", "<>", "<", ">", ">=" and "<=", "*", "?*", "*?", "()". The
 *     "*" operator is for executing a "LIKE '%value%'" matching query. The
 *     "?*" and "*?" operators are for executing a "LIKE 'value%'" or a
 *     "LIKE '%value' matching query. The "()" operator is for executing a
 *     "IN ('value[0]',value[1]')" matching query.  The "()" operator requires
 *     its $value to be an array.
 *
 * @param boolean $return_array
 *     If this parameter has a true value, then an array of all matching
 *     user_ids will be returned. Else, a single user_id will be returned.
 *
 * @param string $type
 *     The type of search to perform. This can be one of:
 *     - AND  match against all fields
 *     - OR   match against any of the fields
 *
 * @param mixed $sort
 *     The field (string) or fields (array) to sort the results by. For
 *     ascending sort, "fieldname" or "+fieldname" can be used. For
 *     descending sort, "-fieldname" can be used. By default, the results
 *     will be sorted by user_id.
 *
 * @param integer $offset
 *     The result page offset starting with 0.
 *
 * @param integer $length
 *     The result page length (nr. of results per page)
 *     or 0 (zero, the default) to return all results.
 *
 * @param boolean $count_only
 *     Tells the function to just return the count of results for this
 *     search query.
 *
 * @return mixed
 *     An array of matching user_ids or a single user_id (based on the
 *     $return_array parameter) or a count of results (based on $count_only).
 *     If no user_ids can be found at all, then 0 (zero) will be returned.
 */
function phorum_db_user_search($field, $value, $operator='=', $return_array=FALSE, $type='AND', $sort=NULL, $offset=0, $length=0, $count_only = false)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($return_array, 'bool');
    settype($offset, 'int');
    settype($length, 'int');

    // Convert all search condition parameters to arrays.
    if (!is_array($field))    $field    = array($field);
    if (!is_array($value))    $value    = array($value);
    if (!is_array($operator)) $operator = array($operator);
    if (!is_array($sort) && $sort!==NULL) $sort = array($sort);

    // Basic check to see if all condition arrays contain the
    // same number of elements.
    if (count($field) != count($value) ||
        count($field) != count($operator)) trigger_error(
        'phorum_db_user_search(): array parameters $field, $value, ' .
        'and $operator do not contain the same number of elements',
        E_USER_ERROR
    );

    $type = strtoupper($type);
    if ($type != 'AND' && $type != 'OR') trigger_error(
        'phorum_db_user_search(): Illegal search type parameter (must ' .
        'be either AND" or "OR")',
        E_USER_ERROR
    );

    $valid_operators = array('=', '<>', '!=', '>', '<', '>=', '<=', '*', '?*', '*?','()');

    // Construct the required "WHERE" clause.
    $clauses = array();
    foreach ($field as $key => $name) {
        if (in_array($operator[$key], $valid_operators) &&
            phorum_db_validate_field($name)) {
            if ($operator[$key] != '()') $value[$key] = phorum_db_interact(DB_RETURN_QUOTED, $value[$key]);
            if ($operator[$key] == '*') {
                $clauses[] = "$name LIKE '%$value[$key]%'";
            } else if ($operator[$key] == '?*') {
                $clauses[] = "$name LIKE '$value[$key]%'";
            } else if ($operator[$key] == '*?') {
                $clauses[] = "$name LIKE '%$value[$key]'";
            } else if ($operator[$key] == '()') {
                foreach ($value[$key] as $in_key => $in_value) {
                    $value[$key][$in_key] = phorum_db_interact(DB_RETURN_QUOTED, $value[$key][$in_key]);
                }
                $clauses[] = "$name IN ('" . implode("','",$value[$key]) ."')";
            } else {
                $clauses[] = "$name $operator[$key] '$value[$key]'";
            }
        }
    }

    if (!empty($clauses)) {
        $where = 'WHERE ' . implode(" $type ", $clauses);
    } else {
        $where = '';
    }

    // Construct the required "ORDER BY" clause.
    if (!empty($sort)) {
        foreach ($sort as $id => $spec) {
            if (substr($spec, 0, 1) == '+') {
                $fld = substr($spec, 1);
                $dir = 'ASC';
            } elseif (substr($spec, 0, 1) == '-') {
                $fld = substr($spec, 1);
                $dir = 'DESC';
            } else {
                $fld = $spec;
                $dir = 'ASC';
            }

            if (!phorum_db_validate_field($fld)) trigger_error(
                'phorum_db_user_search(): Illegal sort field: ' .
                htmlspecialchars($spec),
                E_USER_ERROR
            );

            $sort[$id] = "$fld $dir";
        }
        $order = 'ORDER BY ' . implode(', ', $sort);
    } else {
        $order = '';
    }

    // Construct the required "LIMIT" clause.
    if (!empty($length)) {
        $limit = "LIMIT $offset, $length";
    } else {
        // If we do not need to return an array, the we can limit the
        // query results to only one record.
        $limit = $return_array ? '' : 'LIMIT 1';
    }

    if($count_only) {
	    // Retrieve the number of matching user_ids from the database.
	    $user_count = phorum_db_interact(
	        DB_RETURN_VALUE,
	        "SELECT count(*)
	         FROM   {$PHORUM['user_table']}
	         $where $order $limit",
	        0 // keyfield 0 is the user_id
	    );

	    $ret = $user_count;

    } else {
	    // Retrieve the matching user_ids from the database.
	    $user_ids = phorum_db_interact(
	        DB_RETURN_ROWS,
	        "SELECT user_id
	         FROM   {$PHORUM['user_table']}
	         $where $order $limit",
	        0 // keyfield 0 is the user_id
	    );

	    // No user_ids found at all?
	    if (count($user_ids) == 0) return 0;

	    // Return an array of user_ids.
	    if ($return_array) {
	        foreach ($user_ids as $id => $user_id) $user_ids[$id] = $user_id[0];
	        $ret = $user_ids;
	    } else {
		    // Return a single user_id.
		    list ($user_id, $dummy) = each($user_ids);

		    $ret = $user_id;
	    }
    }
    return $ret;
}
// }}}

// {{{ Function: phorum_db_user_add()
/**
 * Add a user.
 *
 * @param array $userdata
 *     An array containing the fields to insert into the user table.
 *     This array should contain at least a "username" field. See
 *     phorum_db_user_save() for some more info on the other data
 *     in this array.
 *
 * @return integer $user_id
 *     The user_id that was assigned to the new user.
 */
function phorum_db_user_add($userdata)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // We need at least the username for the user.
    if (! isset($userdata['username'])) trigger_error(
        'phorum_db_user_add: Missing field in userdata: username',
        E_USER_ERROR
    );
    $username = phorum_db_interact(DB_RETURN_QUOTED, $userdata['username']);

    // We can set the user_id. If not, then we'll create a new user_id.
    if (isset($userdata['user_id'])) {
        $user_id = (int)$userdata['user_id'];
        $fields = 'user_id, username, signature, moderator_data, settings_data';
        $values = "$user_id, '$username', '', '', ''";
    } else {
        $fields = 'username, signature, moderator_data, settings_data';
        $values = "'$username', '', '', ''";
    }

    // Insert a bare bone user in the database.
    $user_id = phorum_db_interact(
        DB_RETURN_NEWID,
        "INSERT INTO {$PHORUM['user_table']}
                ($fields)
         VALUES ($values)",
        NULL,
        DB_MASTERQUERY
    );

    // Set the rest of the data using the phorum_db_user_save() function.
    $userdata['user_id'] = $user_id;
    phorum_db_user_save($userdata);

    return $user_id;
}
// }}}

// {{{ Function: phorum_db_user_save()
/**
 * Update a user.
 *
 * @param array $userdata
 *     An array containing the fields to update in the user table.
 *     The array should contain at least the user_id field to identify
 *     the user for which to update the data. The array can contain two
 *     special fields:
 *     - forum_permissions:
 *       This field can contain an array with forum permissions for the user.
 *       The keys are forum_ids and the values are permission values.
 *     - user_data:
 *       This field can contain an array of key/value pairs which will be
 *       inserted in the database as custom profile fields. The keys are
 *       profile type ids (as defined by $PHORUM["PROFILE_FIELDS"]).
 *
 * @return boolean
 *     True if all settings were stored successfully. This function will
 *     always return TRUE, so we could do without a return value.
 *     The return value is here for backward compatibility.
 */
function phorum_db_user_save($userdata)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Pull some non user table fields from the userdata. These can be
    // set in case the $userdata parameter that is used is coming from
    // phorum_api_user_get() or phorum_db_user_get().
    if (isset($userdata['permissions'])) {
        unset($userdata['permissions']);
    }
    if (isset($userdata['groups'])) {
        unset($userdata['groups']);
    }
    if (isset($userdata['group_permissions'])) {
        unset($userdata['group_permissions']);
    }

    // Forum permissions and custom profile fields are handled by this
    // function too, but they need to be extracted from the userdata, so
    // they won't be used for updating the standard user fields.
    if (isset($userdata['forum_permissions'])) {
        if (is_array($userdata['forum_permissions'])) {
            $forum_perms = $userdata['forum_permissions'];
        }
        unset($userdata['forum_permissions']);
    }
    if (isset($userdata['user_data'])) {
        $custom_profile_data = $userdata['user_data'];
        unset($userdata['user_data']);
    }

    // The user_id is required for doing the update.
    if (!isset($userdata['user_id'])) trigger_error(
        'phorum_db_user_save(): the user_id field is missing in the ' .
        '$userdata argument',
        E_USER_ERROR
    );
    $user_id = $userdata['user_id'];
    unset($userdata['user_id']);

    // If there are standard user table fields in the userdata then
    // update the user table for the user.
    if (count($userdata))
    {
        // Prepare the user table fields.
        $values = array();
        foreach ($userdata as $key => $value) {
            if (!phorum_db_validate_field($key)) continue;
            if ($key === 'settings_data') {
                if (is_array($value)) {
                    $value = serialize($value);
                } else trigger_error(
                    'Internal error: settings_data field for ' .
                    'phorum_db_user_save() must be an array', E_USER_ERROR
                );
            }
            $value = phorum_db_interact(DB_RETURN_QUOTED, $value);

            if( in_array($key, $PHORUM['string_fields_user'] ) ) {
                $values[] = "$key = '$value'";
            } else {
                $values[] = "$key = $value";
            }
        }

        // Update the fields in the database.
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['user_table']}
             SET    ".implode(', ', $values)."
             WHERE  user_id = $user_id",
            NULL,
            DB_MASTERQUERY
        );
    }

    // Update forum permissions for the user.
    if (isset($forum_perms))
    {
        // Delete all the existing forum permissions.
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE FROM {$PHORUM['user_permissions_table']}
             WHERE  user_id = $user_id",
            NULL,
            DB_MASTERQUERY
        );

        // Add new forum permissions.
        foreach ($forum_perms as $forum_id => $permission) {
            phorum_db_interact(
                DB_RETURN_RES,
                "INSERT INTO {$PHORUM['user_permissions_table']}
                        (user_id, forum_id, permission)
                 VALUES ($user_id, $forum_id, $permission)",
                NULL,
                DB_MASTERQUERY
            );
        }
    }

    // Update custom user fields for the user.
    if (isset($custom_profile_data))
    {
        // Insert new custom profile fields.
        foreach ($custom_profile_data as $key => $val)
        {
            settype($key, "int");

            // Arrays need to be serialized. The serialized data is prefixed
            // with "P_SER:" as a marker for serialization.
            if (is_array($val)) $val = 'P_SER:'.serialize($val);

            $val = phorum_db_interact(DB_RETURN_QUOTED, $val);

            // Try to insert a new record.
            $res = phorum_db_interact(
                DB_RETURN_RES,
                "INSERT INTO {$PHORUM['user_custom_fields_table']}
                        (user_id, type, data)
                 VALUES ($user_id, $key, '$val')",
                NULL,
                DB_DUPKEYOK | DB_MASTERQUERY
            );
            // If no result was returned, then the query failed. This probably
            // means that we already have a record in the database.
            // So instead of inserting a record, we need to update one here.
            if (!$res) {
              phorum_db_interact(
                  DB_RETURN_RES,
                  "UPDATE {$PHORUM['user_custom_fields_table']}
                   SET    data = '$val'
                   WHERE  user_id = $user_id AND type = $key",
                  NULL,
                  DB_MASTERQUERY
              );
            }
        }
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_user_display_name_updates()
/**
 * Run the updates that are needed after changing the display_name for a user.
 *
 * The display_name for users is stored redundant at several places
 * in the database (this improves the speed of the system, because joins
 * with the user table do not have to be made). This function will update
 * that redundant information to match the active display_name field in
 * the user data.
 *
 * @param array $userdata
 *     A userdata array containing at least the fields "user_id" and
 *     "display_name".
 */
function phorum_db_user_display_name_updates($userdata)
{
    $PHORUM = $GLOBALS['PHORUM'];
    if (!isset($userdata['user_id'])) trigger_error(
        'phorum_db_user_display_name_updates(): Missing user_id field in ' .
        'the $userdata parameter',
        E_USER_ERROR
    );
    if (!isset($userdata['display_name'])) trigger_error(
        'phorum_db_user_display_name_updates(): Missing display_name field ' .
        'in the $userdata parameter',
        E_USER_ERROR
    );

    $author = phorum_db_interact(DB_RETURN_QUOTED, $userdata['display_name']);
    $user_id = (int) $userdata['user_id'];

    // Update forum message authors.
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['message_table']}
         SET    author = '$author'
         WHERE  user_id = $user_id",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );

    // Update recent forum reply authors.
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['message_table']}
         SET    recent_author = '$author'
         WHERE  recent_user_id = $user_id",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );

    // Update PM author data.
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['pm_messages_table']}
         SET    author = '$author'
         WHERE  user_id = $user_id",
        NULL,
        DB_MASTERQUERY
    );

    // Update PM recipient data.
    $res = phorum_db_interact(
        DB_RETURN_RES,
        "SELECT m.pm_message_id AS pm_message_id, meta
         FROM   {$PHORUM['pm_messages_table']} AS m,
                {$PHORUM['pm_xref_table']} AS x
         WHERE  m.pm_message_id = x.pm_message_id AND
                x.user_id = $user_id AND
                special_folder != 'outbox'",
         NULL,
         DB_MASTERQUERY
    );
    while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC)) {
        $meta = unserialize($row['meta']);
        $meta['recipients'][$user_id]['display_name'] = $author;
        $meta = phorum_db_interact(DB_RETURN_QUOTED, serialize($meta));
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['pm_messages_table']}
             SET    meta='$meta'
             WHERE  pm_message_id = {$row['pm_message_id']}",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_user_save_groups()
/**
 * Save the group memberships for a user.
 *
 * @param integer $user_id
 *     The user_id for which to save the group memberships.
 *
 * @param array $groups
 *     The group memberships to save. This is an array in which the keys
 *     are group_ids and the values are group statuses.
 *
 * @return boolean
 *     True if all settings were stored successfully. This function will
 *     always return TRUE, so we could do without a return value.
 *     The return value is here for backward compatibility.
 */
function phorum_db_user_save_groups($user_id, $groups)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');

    // Delete all existing group memberships.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['user_group_xref_table']}
         WHERE  user_id = $user_id",
        NULL,
        DB_MASTERQUERY
    );

    // Insert new group memberships.
    foreach ($groups as $group_id => $group_status) {
        $group_id = (int)$group_id;
        $group_status = (int)$group_status;
        phorum_db_interact(
            DB_RETURN_RES,
            "INSERT INTO {$PHORUM['user_group_xref_table']}
                    (user_id, group_id, status)
             VALUES ($user_id, $group_id, $group_status)",
            NULL,
            DB_MASTERQUERY
        );
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_user_subscribe()
/**
 * Subscribe a user to a forum or thread.
 *
 * Remark: Currently, there is no active support for subscription type
 * PHORUM_SUBSCRIPTION_DIGEST in the Phorum core.
 *
 * @param integer $user_id
 *     The id of the user to create the subscription for.
 *
 * @param integer $thread
 *     The id of the thread to describe to.
 *
 * @param integer $forum_id
 *     The if of the forum to subscribe to.
 *
 * @param integer $type
 *     The type of subscription. Available types are:
 *     - {@link PHORUM_SUBSCRIPTION_NONE}
 *       Explicitly note that the user has no subscription at all.
 *     - {@link PHORUM_SUBSCRIPTION_MESSAGE}
 *       Send a mail message for every new message.
 *     - {@link PHORUM_SUBSCRIPTION_BOOKMARK}
 *       Make new messages visible from the followed threads interface.
 *     - {@link PHORUM_SUBSCRIPTION_DIGEST}
 *       Periodically, send a mail message containing a list of new messages.
 *
 * @return boolean
 *     True if the subscription was stored successfully.
 */
function phorum_db_user_subscribe($user_id, $thread, $forum_id, $type)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');
    settype($forum_id, 'int');
    settype($thread, 'int');
    settype($type, 'int');

    // Try to insert a new record.
    $res = phorum_db_interact(
        DB_RETURN_RES,
        "INSERT INTO {$PHORUM['subscribers_table']}
                (user_id, forum_id, thread, sub_type)
         VALUES ($user_id, $forum_id, $thread, $type)",
        NULL,
        DB_DUPKEYOK | DB_MASTERQUERY
    );
    // If no result was returned, then the query failed. This probably
    // means that we already have the record in the database.
    // So instead of inserting a record, we need to update one here.
    if (!$res) {
      phorum_db_interact(
      DB_RETURN_RES,
          "UPDATE {$PHORUM['subscribers_table']}
           SET    sub_type = $type
           WHERE  user_id  = $user_id AND
                  forum_id = $forum_id AND
                  thread   = $thread",
      NULL,
      DB_MASTERQUERY
      );
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_user_unsubscribe()
/**
 * Unsubscribe a user from a forum/thread.
 *
 * @param integer $user_id
 *     The id of the user to remove a suscription for.
 *
 * @param integer $thread
 *     The id of the thread to unsubscribe from.
 *
 * @param integer $forum_id
 *     The id of the forum to unsubscribe from (or 0 (zero)
 *     to simply unsubscribe by the thread id alone).
 *
 * @return boolean
 *     True if the subscription was stored successfully.
 */
function phorum_db_user_unsubscribe($user_id, $thread, $forum_id=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');
    settype($forum_id, 'int');
    settype($thread, 'int');

    $forum_where = $forum_id ? "AND forum_id = $forum_id" : '';

    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['subscribers_table']}
         WHERE  user_id = $user_id AND
                thread  = $thread
                $forum_where",
        NULL,
        DB_MASTERQUERY
    );

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_user_increment_posts()
/**
 * Increment the posts counter for a user.
 *
 * @param integer $user_id
 *     The user_id for which to increment the posts counter.
 */
function phorum_db_user_increment_posts($user_id)
{
    settype($user_id, 'int');

    if (!empty($user_id)) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$GLOBALS['PHORUM']['user_table']}
             SET    posts = posts + 1
             WHERE  user_id = $user_id",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_user_get_groups()
/**
 * Retrieve a list of group memberships and their statuses for a user.
 *
 * @param integer $user_id
 *     The user id for which to retrieve the groups.
 *
 * @return array
 *     An array of groups for the user. The keys are group_ids and the
 *     values are the membership statuses.
 */
function phorum_db_user_get_groups($user_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');

    // Retrieve the groups for the user_id from the database.
    $groups = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT group_id,
                status
         FROM   {$PHORUM['user_group_xref_table']}
         WHERE  user_id = $user_id
         ORDER  BY status DESC",
        0 // keyfield 0 is the group_id
    );

    // The records are full rows, but we want a group_id -> status mapping.
    foreach ($groups as $id => $group) $groups[$id] = $group[1];

    return $groups;
}
// }}}

// {{{ Function: phorum_db_user_get_unapproved()
/**
 * Retrieve the users that await signup approval.
 *
 * @return $users
 *     An array or users, indexed by user_id, that await approval.
 *     The elements of the array are arrays containing the fields:
 *     user_id, username and email.
 *
 * @todo This function might be as well replaced with user search and get
 *       functionality from the user API.
 */
function phorum_db_user_get_unapproved()
{
    $PHORUM = $GLOBALS['PHORUM'];

    $users = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT user_id,
                username,
                email
         FROM   {$PHORUM['user_table']}
         WHERE  active in (".PHORUM_USER_PENDING_BOTH.",
                           ".PHORUM_USER_PENDING_MOD.")
         ORDER  BY username",
        'user_id'
    );

    return $users;
}
// }}}

// {{{ Function: phorum_db_user_delete
/**
 * Delete a user completely. Messages that were posted by the user in the
 * forums, will be changed into anonymous messages (user_id = 0). If the
 * constant PHORUM_DELETE_CHANGE_AUTHOR is set to a true value, then the
 * author name of all postings will be set to {LANG->AnonymousUser}. If
 * it is set to a false value, then the original author name will be kept.
 *
 * @param integer $user_id
 *     The id of the user to delete.
 *
 * @return boolean
 *     True if the user was deleted successfully.
 */
function phorum_db_user_delete($user_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');

    // Retrieve a list of private mesage xrefs for this user. After we delete
    // the pm xrefs for this user in the code afterwards, we might have
    // created orphin PM messages (messages with no xrefs linked to them),
    // so we'll have to check for that later on.
    $pmxrefs = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT pm_message_id
         FROM   {$PHORUM['pm_xref_table']}
         WHERE  user_id = $user_id",
        NULL,
        DB_MASTERQUERY
    );

    // These are tables that hold user related data.
    $tables = array (
        $PHORUM['user_table'],
        $PHORUM['user_permissions_table'],
        $PHORUM['user_newflags_table'],
        $PHORUM['subscribers_table'],
        $PHORUM['user_group_xref_table'],
        $PHORUM['pm_buddies_table'],
        $PHORUM['pm_folders_table'],
        $PHORUM['pm_xref_table'],
        $PHORUM['user_custom_fields_table']
    );

    // Delete the data for the $user_id from all those tables.
    foreach ($tables as $table) {
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE FROM $table
             WHERE user_id = $user_id",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );
    }

    // See if we created any orphin private messages. We do this in
    // a loop using the standard phorum_db_pm_update_message_info()
    // function and not a single SQL statement with something like
    // pm_message_id IN (...) in it, because MySQL won't use an index
    // for that, making the full lookup very slow on large PM tables.
    foreach ($pmxrefs as $row) {
        phorum_db_pm_update_message_info($row[0]);
    }

    // Change the forum postings into anonymous postings.
    // If PHORUM_DELETE_CHANGE_AUTHOR is set, then the author field is
    // updated to {LANG->AnonymousUser}.
    $author = 'author';

    if (defined('PHORUM_DELETE_CHANGE_AUTHOR') && PHORUM_DELETE_CHANGE_AUTHOR) {
        $anonymous = $PHORUM['DATA']['LANG']['AnonymousUser'];
        $author = "'".phorum_db_interact(DB_RETURN_QUOTED, $anonymous)."'";
    }

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['message_table']}
         SET    user_id = 0,
                email   = '',
                author  = $author
         WHERE  user_id = $user_id",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['message_table']}
         SET    recent_user_id = 0,
                recent_author  = $author
         WHERE  recent_user_id = $user_id",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_get_file_list()
/**
 * Retrieve a list of files from the database.
 *
 * @param string $link_type
 *     The type of link to retrieve from the database. Normally this is one
 *     of the Phorum built-in link types, but it can also be a custom
 *     link type (e.g. if a module uses the file storage on its own).
 *     This parameter can be NULL to retrieve any link type.
 *
 * @param integer $user_id
 *     The user_id to retrieve files for or NULL to retrieve files for
 *     any user_id.
 *
 * @param integer $message_id
 *     The message_id to retrieve files for or NULL to retrieve files for
 *     any message_id.
 *
 * @return array
 *     An array of files, indexed by file_id.
 *     The array elements are arrays containing the fields:
 *     file_id, filename, filesize and add_datetime.
 */
function phorum_db_get_file_list($link_type = NULL, $user_id = NULL, $message_id = NULL)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $where = '';
    $clauses = array();
    if ($link_type !== NULL) {
        $qtype = phorum_db_interact(DB_RETURN_QUOTED, $link_type);
        $clauses[] = "link = '$qtype'";
    }
    if ($user_id !== NULL) {
        $clauses[] = 'user_id = ' . (int) $user_id;
    }
    if ($message_id !== NULL) {
        $clauses[] = 'message_id = ' . (int) $message_id;
    }
    if (count($clauses)) {
        $where = 'WHERE ' . implode(' AND ', $clauses);
    }

    return phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT file_id,
                filename,
                filesize,
                add_datetime
         FROM   {$PHORUM['files_table']}
         $where
         ORDER  BY file_id",
        'file_id'
    );
}
// }}}

// {{{ Function: phorum_db_get_user_file_list()
/**
 * Retrieve a list of personal files for a user.
 *
 * @param integer $user_id
 *     The user id for which to retrieve the file list.
 *
 * @return array
 *     An array of personal user files, indexed by file_id.
 *     The array elements are arrays containing the fields:
 *     file_id, filename, filesize and add_datetime.
 */
function phorum_db_get_user_file_list($user_id)
{
    return phorum_db_get_file_list(PHORUM_LINK_USER, $user_id, 0);
}
// }}}

// {{{ Function: phorum_db_get_message_file_list()
/**
 * Retrieve a list of files for a message (a.k.a. attachments).
 *
 * @param integer $message_id
 *     The message id for which to retrieve the file list.
 *
 * @return array
 *     An array of message files, indexed by file_id.
 *     The array elements are arrays containing the fields:
 *     file_id, filename, filesize and add_datetime.
 */
function phorum_db_get_message_file_list($message_id)
{
    return phorum_db_get_file_list(PHORUM_LINK_MESSAGE, NULL, $message_id);
}
// }}}

// {{{ Function: phorum_db_file_get()
/**
 * Retrieve a file.
 *
 * @param integer $file_id
 *     The file id to retrieve from the database.
 *
 * @param boolean $include_file_data
 *     If this parameter is set to a false value (TRUE is the default),
 *     the file data will not be included in the return data.
 *
 * @return array
 *     An array, containing the data for all file table fields.
 *     If the file id cannot be found in the database, an empty
 *     array will be returned instead.
 */
function phorum_db_file_get($file_id, $include_file_data = TRUE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($file_id, 'int');

    $fields = "file_id, user_id, filename, filesize, " .
              "add_datetime, message_id, link";
    if ($include_file_data) $fields .= ",file_data";

    // Select the file from the database.
    $files = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT $fields
         FROM   {$PHORUM['files_table']}
         WHERE  file_id = $file_id"
    );

    if (count($files) == 0) {
        return array();
    } else {
        return $files[0];
    }
}
// }}}

// {{{ Function: phorum_db_file_save()
/**
 * Add or updates a file.
 *
 * @todo Update docs, because it now is based on separate parameters,
 *       while the function itself requires an array now.
 *
 * @param integer $user_id
 *     The id of the user for which to add the file.
 *     If this file is linked to a message by an anonymous
 *     user, then this value can be 0 (zero).
 *
 * @param string $filename
 *     The name of the file.
 *
 * @param integer $filesize
 *     The size of the file in bytes.
 *
 * @param string $file_data
 *     The file contents. This should be data which is safe to store in a
 *     TEXT field in the database. The calling application has to take
 *     care of this. The database layer will simply store and retrieve
 *     the file data as provided by the caller.
 *
 * @param integer $message_id
 *     The message_id to link the file to. If this file is not linked to
 *     a posted message (the link type PHORUM_LINK_MESSAGE) then this value
 *     can be 0 (zero).
 *
 * @param string $link
 *     A file can be linked to a number of different types of objects.
 *     The available link types are:
 *     - PHORUM_LINK_USER:
 *       The file is linked to a user. This means that the file is
 *       available from within the files section in the user's Control Center.
 *     - PHORUM_LINK_MESSAGE:
 *       The file is linked to a message. This means that the file is
 *       available as an attachment on a posted forum message.
 *     - PHORUM_LINK_EDITOR
 *       The file is linked to the editor. This means that the file was
 *       uploaded as part of editing a forum message. This message was
 *       not yet posted. As soon as the message is posted for final, the
 *       link type for the message will be updated to
 *       PHORUM_LINK_MESSAGE.
 *       Note: these are the official link types. Calling functions are
 *       allowed to provide different custom link types if they need to.
 *
 * @param integer $file_id
 *     If the $file_id is set, then this will be used for updating the
 *     existing file data for the given $file_id.
 *
 * @return integer
 *     The file_id that was assigned to the new file or the file_id of
 *     the existing file if the $file_id parameter was used.
 */
function phorum_db_file_save($file)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // If a link type is not provided, we'll guess for the type of link.
    // This is done to provide some backward compatibility.
    if ($file["link"] === NULL) {
        if     ($file["message_id"]) $file["link"] = PHORUM_LINK_MESSAGE;
        elseif ($file["user_id"])    $file["link"] = PHORUM_LINK_USER;
        else trigger_error(
            'phorum_db_file_save(): Missing link field in the $file parameter',
            E_USER_ERROR
        );
    }

    $user_id    = (int)$file["user_id"];
    $message_id = (int)$file["message_id"];
    $filesize   = (int)$file["filesize"];
    $file_id    = !isset($file["file_id"]) || $file["file_id"] === NULL
                ? NULL : (int)$file["file_id"];
    $link       = phorum_db_interact(DB_RETURN_QUOTED, $file["link"]);
    $filename   = phorum_db_interact(DB_RETURN_QUOTED, $file["filename"]);
    $file_data  = phorum_db_interact(DB_RETURN_QUOTED, $file["file_data"]);
    $datetime   = empty($file['add_datetime'])
                ? time() : (int)$file['add_datetime'];

    // Create a new file record.
    if ($file_id === NULL) {
        $file_id = phorum_db_interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$PHORUM['files_table']}
                    (user_id, message_id, link,
                     filename, filesize, file_data, add_datetime)
             VALUES ($user_id, $message_id, '$link',
                     '$filename', $filesize, '$file_data', $datetime)",
            NULL,
            DB_MASTERQUERY
        );
    }
    // Update an existing file record.
    else {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['files_table']}
             SET    user_id      = $user_id,
                    message_id   = $message_id,
                    link         = '$link',
                    filename     = '$filename',
                    filesize     = $filesize,
                    file_data    = '$file_data'
             WHERE  file_id      = $file_id",
            NULL,
            DB_MASTERQUERY
        );
    }

    return $file_id;
}
// }}}

// {{{ Function: phorum_db_file_delete()
/**
 * Delete a file.
 *
 * @param integer $file_id
 *     The id of the file to delete.
 *
 * @return boolean
 *     True if the file was deleted successfully. This function will
 *     always return TRUE, so we could do without a return value.
 *     The return value is here for backward compatibility.
 */
function phorum_db_file_delete($file_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($file_id, 'int');

    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['files_table']}
         WHERE  file_id = $file_id",
        NULL,
        DB_MASTERQUERY
    );

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_file_link()
/**
 * Update the message to which a file is linked and/or the link type.
 *
 * @param integer $file_id
 *     The id of the file to update.
 *
 * @param integer $message_id
 *     The id of the message to link the file to.
 *
 * @param string $link
 *     A file can be linked to a number of different types of objects.
 *     See phorum_db_file_save() for the possible link types.
 *
 * @return boolean
 *     True if the file link was updated successfully. This function will
 *     always return TRUE, so we could do without a return value.
 *     The return value is here for backward compatibility.
 */
function phorum_db_file_link($file_id, $message_id, $link = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($file_id, 'int');
    settype($message_id, 'int');

    $link = $link === NULL
          ? PHORUM_LINK_MESSAGE
          : phorum_db_interact(DB_RETURN_QUOTED, $link);

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['files_table']}
         SET    message_id = $message_id,
                link       = '$link'
         WHERE  file_id    = $file_id",
        NULL,
        DB_MASTERQUERY
    );

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_get_user_filesize_total()
/**
 * Retrieve the total size for all personal files for a user.
 *
 * @param integer $user_id
 *     The user to compute the total size for.
 *
 * @return integer
 *     The total size in bytes.
 */
function phorum_db_get_user_filesize_total($user_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');

    $size = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT SUM(filesize)
         FROM   {$PHORUM['files_table']}
         WHERE  user_id    = $user_id AND
                message_id = 0 AND
                link       = '".PHORUM_LINK_USER."'"
    );

    return $size;
}
// }}}

// {{{ Function: phorum_db_list_stale_files()
/**
 * Retrieve a list of stale files from the database.
 *
 * Stale files are files that are not linked to anything anymore.'
 * These can for example be caused by users that are writing a message
 * with attachments, but never post it.
 *
 * @return array
 *     An array of stale Phorum files, indexed by file_id. Every item in
 *     this array is an array on its own, containing the fields:
 *     - file_id: the file id of the stale file
 *     - filename: the name of the stale file
 *     - filesize: the size of the file
 *     - add_datetime: the time at which the file was added
 *     - reason: the reason why it's a stale file
 */
function phorum_db_list_stale_files()
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Select orphin editor files.
    // These are files that are linked to the editor and that were added
    // a while ago. These are from posts that were abandoned before posting.
    $stale_files = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT file_id,
                filename,
                filesize,
                add_datetime,
                'Attachments, left behind by unposted messages' AS reason
         FROM   {$PHORUM['files_table']}
         WHERE  link = '".PHORUM_LINK_EDITOR."'
                AND
                add_datetime < ". (time()-PHORUM_MAX_EDIT_TIME),
        'file_id',
        DB_GLOBALQUERY
    );

    return $stale_files;
}
// }}}

// {{{ Function: phorum_db_newflag_allread()
/**
 * Mark all messages for a forum read for the active Phorum user.
 *
 * @param integer $forum_id
 *     The forum to mark read or 0 (zero) to mark the current forum read.
 */
function phorum_db_newflag_allread($forum_id=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if (empty($forum_id)) $forum_id = $PHORUM['forum_id'];
    settype($forum_id, 'int');

    // Delete all the existing newflags for this user for this forum.
    phorum_db_newflag_delete(0, $forum_id);

    // Retrieve the maximum message_id in this forum.
    $max_id = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT max(message_id)
         FROM   {$PHORUM['message_table']}
         WHERE  forum_id = $forum_id"
    );

    // Set this message_id as the min-id for the forum.
    if ($max_id) {
        phorum_db_newflag_add_read(array(
            0 => array(
              'id'    => $max_id,
              'forum' => $forum_id
            )
        ));
    }
}
// }}}

// {{{ Function: phorum_db_newflag_get_flags()
/**
 * Retrieve the read messages for a forum for the active Phorum user.
 *
 * @param integer $forum_id
 *     The forum to retrieve the read messages for or 0 (zero) to
 *     retrieve them for the current forum.
 *
 * @return array
 *     An array containing the message_ids that have been read for the
 *     forum (key and value are both the message_id). Also an element
 *     for the key "min_id", which holds the minimum read message_id. All
 *     message_ids lower than the min_id are also considered to be read.
 */
function phorum_db_newflag_get_flags($forum_id=NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($forum_id === NULL) $forum_id = $PHORUM['forum_id'];
    settype($forum_id, 'int');

    // Initialize the read messages array.
    $read_msgs = array('min_id' => 0);

    // Select the read messages from the newflags table.
    $newflags = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT message_id
         FROM   {$PHORUM['user_newflags_table']}
         WHERE  user_id  = {$PHORUM['user']['user_id']} AND
                forum_id = $forum_id
         ORDER  BY message_id ASC"
    );

    // Add the newflags to the $read_msgs.
    // The first newflags element also determines the min_id.
    foreach ($newflags as $index => $newflag) {
        if ($index == 0) $read_msgs['min_id'] = $newflag[0];
        $read_msgs[$newflag[0]] = $newflag[0];
    }

    return $read_msgs;
}
// }}}

// {{{ Function: phorum_db_newflag_check()
/**
 * Checks if there are new messages in the forums given in forum_ids
 *
 * @param array $forum_ids
 *     The forums to check for new messages
 *
 * @return array
 *     An array containing forum_ids as the key and a boolean for
 *     the values.
 */
function phorum_db_newflag_check($forum_ids)
{
    $PHORUM = $GLOBALS['PHORUM'];

    phorum_db_sanitize_mixed($forum_ids, 'int');

    $sql = "select forum_id, min(message_id) as message_id
            from {$PHORUM['user_newflags_table']}
            where user_id=".$PHORUM["user"]["user_id"]."
            group by forum_id";

    $list = phorum_db_interact(DB_RETURN_ASSOCS, $sql, "forum_id");

    $sql = "select forum_id, count(*) as count
            from {$PHORUM['user_newflags_table']}
            where user_id=".$PHORUM["user"]["user_id"]."
            group by forum_id";

    $counts = phorum_db_interact(DB_RETURN_ASSOCS, $sql, "forum_id");

    $new_checks = array();

    foreach($forum_ids as $forum_id){

        if(empty($list[$forum_id]) || empty($counts[$forum_id])){

            $new_checks[$forum_id] = FALSE;

        } else {

            // check for new messages
            $sql = "select count(*) as count from {$PHORUM['message_table']}
                    where forum_id=".$forum_id." and
                    message_id>=".$list[$forum_id]["message_id"]." and
                    status=".PHORUM_STATUS_APPROVED." and
                    moved=0";

            list($count) = phorum_db_interact(DB_RETURN_ROW, $sql);

            $new_checks[$forum_id] = ($count > $counts[$forum_id]["count"]);

        }
    }

    return $new_checks;

}
// }}}

// {{{ Function: phorum_db_newflag_count()
/**
 * Gets a count of new messages and threads for the forum ids given
 *
 * @param array $forum_ids
 *     The forums to check for new messages.
 *
 * @return array
 *     An array containing forum_ids as the key and a two element array
 *     for each entry with messages and threads counts.
 */
function phorum_db_newflag_count($forum_ids)
{
    $PHORUM = $GLOBALS['PHORUM'];

    phorum_db_sanitize_mixed($forum_ids, 'int');

    // get a list of forum_ids and minimum message ids from the newflags table
    $sql = "select forum_id, min(message_id) as message_id
            from {$PHORUM['user_newflags_table']}
            where user_id=".$PHORUM["user"]["user_id"]."
            group by forum_id";

    $list = phorum_db_interact(DB_RETURN_ASSOCS, $sql, "forum_id");

    // get the total number of messages the user has read in each forum
    $sql = "select {$PHORUM['user_newflags_table']}.forum_id, count(*) as count
            from {$PHORUM['user_newflags_table']}
            inner join {$PHORUM['message_table']} using (message_id, forum_id)
            where {$PHORUM['user_newflags_table']}.user_id=".$PHORUM["user"]["user_id"]." and
            status=".PHORUM_STATUS_APPROVED."
            group by forum_id";

    $message_counts = phorum_db_interact(DB_RETURN_ASSOCS, $sql, "forum_id");


    // get the number of threads the user has read in each forum
    $sql = "select {$PHORUM['user_newflags_table']}.forum_id, count(*) as count
            from {$PHORUM['user_newflags_table']}
            inner join {$PHORUM['message_table']} using (message_id, forum_id)
            where {$PHORUM['user_newflags_table']}.user_id=".$PHORUM["user"]["user_id"]." and
            parent_id=0 and
            status=".PHORUM_STATUS_APPROVED."
            group by forum_id";

    $thread_counts = phorum_db_interact(DB_RETURN_ASSOCS, $sql, "forum_id");

    $new_checks = array();

    foreach($forum_ids as $forum_id){

        if(empty($list[$forum_id])){

            $new_checks[$forum_id] = array("messages"=>0, "threads"=>0);

        } else {

            if(empty($message_counts[$forum_id])){

                $new_checks[$forum_id]["messages"] = 0;

            } else {

                // check for new messages
                $sql = "select count(*) as count from {$PHORUM['message_table']}
                        where forum_id=".$forum_id." and
                        message_id>=".$list[$forum_id]["message_id"]." and
                        status=".PHORUM_STATUS_APPROVED." and
                        moved=0";

                list($count) = phorum_db_interact(DB_RETURN_ROW, $sql);

                $new_checks[$forum_id]["messages"] = max(0, $count - $message_counts[$forum_id]["count"]);
            }

            // no this is not a typo
            // we need to calculate their thread count if they have ANY read messages
            // in the table.  the only way to do that is to see if message count was set
            if(empty($message_counts[$forum_id])){

                $new_checks[$forum_id]["threads"] = 0;

            } else {

                // check for new threads
                $sql = "select count(*) as count from {$PHORUM['message_table']}
                        where forum_id=".$forum_id." and
                        message_id>=".$list[$forum_id]["message_id"]." and
                        parent_id=0 and
                        status=".PHORUM_STATUS_APPROVED." and
                        moved=0";

                list($count) = phorum_db_interact(DB_RETURN_ROW, $sql);

                if(isset($thread_counts[$forum_id]["count"])){
                    $new_checks[$forum_id]["threads"] = max(0, $count - $thread_counts[$forum_id]["count"]);
                } else {
                    $new_checks[$forum_id]["threads"] = max(0, $count);
                }
            }
        }
    }

    return $new_checks;

}
// }}}

// {{{ Function: phorum_db_newflag_get_unread_count()
/**
 * Retrieve the number of new threads and messages for a forum for the
 * active Phorum user.
 *
 * @param integer $forum_id
 *     The forum to retrieve the new counts for or
 *     0 (zero) to retrieve them for the current forum.
 *
 * @return array
 *     An array containing two elements. The first element is the number
 *     of new messages. The second one is the number of new threads.
 */
function phorum_db_newflag_get_unread_count($forum_id=NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($forum_id === NULL) $forum_id = $PHORUM['forum_id'];
    settype($forum_id, 'int');

    // Retrieve the minimum message_id from newflags for the forum.
    $min_message_id = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT  min(message_id)
         FROM    {$PHORUM['user_newflags_table']}
         WHERE   user_id  = {$PHORUM['user']['user_id']} AND
                 forum_id = {$forum_id}"
    );

    // No result found? Then we know that the user never read a
    // message from this forum. We won't count the new messages
    // in that case. Return an empty result.
    if (!$min_message_id) return array(0,0);

    // Retrieve the unread thread count.
    $new_threads = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT count(*)
         FROM {$PHORUM['message_table']} AS m
              LEFT JOIN {$PHORUM['user_newflags_table']} AS n ON
               m.message_id = n.message_id AND
               n.user_id    = {$PHORUM['user']['user_id']}
         WHERE m.forum_id   = {$forum_id} AND
               m.message_id > $min_message_id AND
               n.message_id IS NULL AND
               m.parent_id  = 0 AND
               m.status     = ".PHORUM_STATUS_APPROVED." AND
               m.thread     = m.message_id"
    );

    // Retrieve the unread message count.
    $new_messages = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT count(*)
         FROM   {$PHORUM['message_table']} AS m
                LEFT JOIN {$PHORUM['user_newflags_table']} AS n ON
                m.message_id = n.message_id AND
                m.forum_id   = n.forum_id AND
                n.user_id    = {$PHORUM['user']['user_id']}
         WHERE  m.forum_id   = {$forum_id} AND
                m.message_id > $min_message_id AND
                n.message_id IS NULL AND
                m.status = ".PHORUM_STATUS_APPROVED
    );

    $counts = array(
        $new_messages,
        $new_threads
    );

    return $counts;
}
// }}}

// {{{ Function: phorum_db_newflag_add_read()
/**
 * Mark a message as read for the active Phorum user.
 *
 * @param mixed $message_ids
 *     The message_id of the message to mark read in the active forum or an
 *     array description of messages to mark read. Elements in this array
 *     can be:
 *     - Simple message_id values, to mark messages read in the active forum.
 *     - An array containing two fields: "forum" containing a forum_id and
 *       "id" containing a message_id. This notation can be used to mark
 *       messages read in other forums than te active one.
 */
function phorum_db_newflag_add_read($message_ids)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Find the number of newflags for the user
    $num_newflags = phorum_db_newflag_get_count();

    if (!is_array($message_ids)) {
        $message_ids = array(0 => $message_ids);
    }

    // Delete newflags which would exceed the maximum number of
    // newflags that are allowed in the database per user.
    $num_end = $num_newflags + count($message_ids);
    if ($num_end > PHORUM_MAX_READ_COUNT_PER_FORUM) {
        phorum_db_newflag_delete($num_end - PHORUM_MAX_READ_COUNT_PER_FORUM);
    }

    // Insert newflags.
    $inserts = array();
    foreach ($message_ids as $id => $data)
    {
        if (is_array($data)) {
            $user_id    = $PHORUM['user']['user_id'];
            $forum_id   = (int)$data['forum'];
            $message_id = (int)$data['id'];
        } else {
            $user_id    = $PHORUM['user']['user_id'];
            $forum_id   = $PHORUM['forum_id'];
            $message_id = (int)$data;
        }
        $values = "($user_id,$forum_id,$message_id)";
	$inserts[$values] = $values;
    }

    if(count($inserts)) {

        $inserts_str = implode(",",$inserts);

        // Try to insert the values.
        $res = phorum_db_interact(
            DB_RETURN_RES,
            "INSERT INTO {$PHORUM['user_newflags_table']}
                    (user_id, forum_id, message_id)
             VALUES $inserts_str",
            NULL,
            DB_DUPKEYOK | DB_MASTERQUERY
        );

	// If inserting the values failed, then this most probably means
	// that one of the values already existed in the database, causing
	// a duplicate key error. In this case, fallback to one-by-one
	// insertion, so the other records in the list will be created.
	if (!$res && count($inserts) > 1)
	{
	    foreach ($inserts as $values)
	    {
                $res = phorum_db_interact(
                    DB_RETURN_RES,
                    "INSERT INTO {$PHORUM['user_newflags_table']}
                            (user_id, forum_id, message_id)
                     VALUES $values",
                    NULL,
                    DB_DUPKEYOK | DB_MASTERQUERY
                );
	    }
	}
    }
}
// }}}

// {{{ Function: phorum_db_newflag_get_count()
/**
 * Retrieve the total number of newflags for a forum for the active
 * Phorum user.
 *
 * @param integer $forum_id
 *     The forum to retrieve the count for or 0 (zero) to retrieve it
 *     for the current forum.
 *
 * @return integer
 *     The total number of newflags.
 */
function phorum_db_newflag_get_count($forum_id=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if (empty($forum_id)) $forum_id = $PHORUM['forum_id'];
    settype($forum_id, 'int');

    $count = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT count(*)
         FROM   {$PHORUM['user_newflags_table']}
         WHERE  user_id  = {$PHORUM['user']['user_id']} AND
                forum_id = {$forum_id}"
    );

    return $count;
}
// }}}

// {{{ Function: phorum_db_newflag_delete()
/**
* Remove newflags for a forum for the active Phorum user.
*
* @param integer $numdelete
*     The number of newflags to delete or 0 (zero) to delete them all.
*
* @param integer $forum_id
*     The forum for which to delete the newflags or 0 (zero) to
*     delete them for the current forum.
*/
function phorum_db_newflag_delete($numdelete=0,$forum_id=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if (empty($forum_id)) $forum_id = $PHORUM['forum_id'];
    settype($numdelete, 'int');
    settype($forum_id, 'int');

    $limit = $numdelete > 0 ? "ORDER BY message_id ASC LIMIT $numdelete" : '';

    // Delete the provided amount of newflags.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['user_newflags_table']}
         WHERE  user_id  = {$PHORUM['user']['user_id']} AND
                forum_id = {$forum_id}
         $limit",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_newflag_update_forum()
/**
 * Update the forum_id for the newflags. The newsflags are updated by setting
 * their forum_ids to the forum_ids of the referenced message table records.
 *
 * @param array $message_ids
 *     An array of message_ids which should be updated.
 */
function phorum_db_newflag_update_forum($message_ids)
{
    phorum_db_sanitize_mixed($message_ids, 'int');
    $ids_str = implode(', ', $message_ids);

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE IGNORE {$GLOBALS['PHORUM']['user_newflags_table']} AS flags,
                       {$GLOBALS['PHORUM']['message_table']} AS msg
         SET    flags.forum_id   = msg.forum_id
         WHERE  flags.message_id = msg.message_id AND
                flags.message_id IN ($ids_str)",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_user_list_subscribers()
/**
 * Retrieve the email addresses of the active users that are subscribed to a
 * forum/thread, grouped by the preferred language for these users.
 *
 * @param integer $forum_id
 *     The forum_id to check on.
 *
 * @param integer $thread
 *     The thread id to check on.
 *
 * @param integer $type
 *     The type of subscription to check on. See the documentation for the
 *     function {@link phorum_db_user_subscribe()} for available
 *     subscription types.
 *
 * @param boolean $ignore_active_user
 *     If this parameter is set to FALSE (it is TRUE by default), then the
 *     active Phorum user will be excluded from the list.
 *
 * @return array $addresses
 *     An array containing the subscriber email addresses. The keys in the
 *     result array are language names. The values are arrays. Each array
 *     contains a list of email addresses of users which are using the
 *     language from the key field.
 */
function phorum_db_user_list_subscribers($forum_id, $thread, $type, $ignore_active_user=TRUE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($forum_id, 'int');
    settype($thread, 'int');
    settype($type, 'int');
    settype($ignore_active_user, 'bool');

    $userignore = '';
    if ($ignore_active_user && $PHORUM['DATA']['LOGGEDIN'])
       $userignore = "AND u.user_id != {$PHORUM['user']['user_id']}";

    // Select all subscriptions for the requested thread.
    // This query also checks on s.thread = 0. This is for
    // subscriptions that are set on a full forum. This is a
    // feature which never really made it to the core (because the
    // posting performance would get too low with a lot of forum
    // subscribers, but we'll leave it in the query here, in case
    // somebody wants to write a module for handling this functionality.
    $users = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT DISTINCT(u.email) AS email,
                user_language
         FROM   {$PHORUM['subscribers_table']} AS s,
                {$PHORUM['user_table']} AS u
         WHERE  s.forum_id = $forum_id AND
                (s.thread = $thread or s.thread = 0) AND
                s.sub_type = $type AND
                u.user_id = s.user_id AND
                u.active = ".PHORUM_USER_ACTIVE."
                $userignore"
    );

    $addresses = array();

    // Add the subscriptions to the addresses array.
    foreach ($users as $user)
    {
        // Determine what language to use for this user.
        $lang = empty($user[1]) ? $PHORUM['language'] : $user[1];

        $addresses[$lang][] = $user[0];
    }

    return $addresses;
}
// }}}

// {{{ Function: phorum_db_user_list_subscriptions()
/**
 * Retrieve a list of threads to which a user is subscribed. The list can be
 * limited to those threads which did receive contributions recently.
 *
 * @param integer $user_id
 *     The id of the user for which to retrieve the subscribed threads.
 *
 * @param integer $days
 *     If set to 0 (zero), then all subscriptions will be returned. If set to
 *     a different value, then only threads which have received contributions
 *     within the last $days days will be included in the list.
 *
 * @param integer $forum_ids
 *     If this parameter is NULL, then subscriptions from all forums will
 *     be included. This parameter can also be an array of forum_ids, in
 *     which case the search will be limited to the forums in this array.
 *
 * @return array $threads
 *     An array of matching threads, indexed by thread id. One special key
 *     "forum_ids" is set too. This one contains an array of all involved
 *     forum_ids.
 */
function phorum_db_user_list_subscriptions($user_id, $days=0, $forum_ids=NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');
    settype($days, 'int');
    if ($forum_ids !== NULL) phorum_db_sanitize_mixed($forums_ids, 'int');

    $time_where = $days > 0
                ? " AND (".time()." - m.modifystamp) <= ($days * 86400)"
                : '';

    $forum_where = ($forum_ids !== NULL and is_array($forum_ids))
                 ? " AND s.forum_id IN (" . implode(",", $forum_ids) . ")"
                 : '';

    // Retrieve all subscribed threads from the database for which the
    // latest message in the thread was posted within the provided time limit.
    $threads = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT s.thread         AS thread,
                s.forum_id       AS forum_id,
                s.sub_type       AS sub_type,
                m.subject        AS subject,
                m.modifystamp    AS modifystamp,
                m.author         AS author,
                m.user_id        AS user_id,
                m.email          AS email,
                m.recent_author  AS recent_author,
                m.recent_user_id AS recent_user_id,
                m.meta           AS meta
         FROM   {$PHORUM['subscribers_table']} AS s,
                {$PHORUM['message_table']} AS m
         WHERE  s.user_id    = $user_id AND
                m.message_id = s.thread AND
                (s.sub_type  = ".PHORUM_SUBSCRIPTION_MESSAGE." OR
                 s.sub_type  = ".PHORUM_SUBSCRIPTION_BOOKMARK.")
                $time_where
                $forum_where
         ORDER  BY m.modifystamp DESC",
        'thread'
    );

    // An array for keeping track of all involved forum ids.
    $forum_ids = array();

    foreach ($threads as $id => $thread)
    {
        // Unpack the thread's meta data.
        $threads[$id]['meta'] = empty($thread['meta'])
                              ? array() : unserialize($thread['meta']);

        $forum_ids[$thread['forum_id']] = $thread['forum_id'];
    }

    // Store the involved forum_ids in the thread array.
    $threads['forum_ids'] = $forum_ids;

    return $threads;
}
// }}}

// {{{ Function: phorum_db_user_get_subscription()
/**
 * Retrieve the subscription of a user for a thread.
 *
 * @param integer $user_id
 *     The user_id to retrieve the subscription for.
 *
 * @param integer $forum_id
 *     The forum_id to retrieve the subscription for.
 *
 * @param integer $thread
 *     The thread id to retrieve the subscription for.
 *
 * @return integer
 *     The type of subscription if there is a subscription available or
 *     NULL in case no subscription was found. For a list of available
 *     subscription types see the documentation for function
 *     phorum_db_user_subscribe().
 */
function phorum_db_user_get_subscription($user_id, $forum_id, $thread)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($user_id, 'int');
    settype($forum_id, 'int');
    settype($thread, 'int');
    settype($type, 'int');

    $type = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT sub_type
         FROM   {$PHORUM['subscribers_table']}
         WHERE  forum_id = $forum_id AND
                thread   = $thread AND
                user_id  = $user_id"
    );

    return $type;
}
// }}}

// {{{ Function: phorum_db_get_banlists()
/**
 * Retrieve the ban lists for the active forum.
 *
 * @param boolean $ordered
 *     If this parameter has a true value (default is FALSE),
 *     then the results will be ordered by ban type and string.
 *
 * @return array
 *     An array of active bans, indexed by the type of ban. For available
 *     ban types, see the documentation for the function
 *     phorum_db_mod_banlists(). Each value for a ban type is an array of
 *     bans. Each ban in those arrays is an array containing the fields:
 *     prce, string and forum_id.
 */
function phorum_db_get_banlists($ordered=FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($ordered, 'bool');

    $forum_where = '';
    if (isset($PHORUM['forum_id']) && !empty($PHORUM['forum_id']))
    {
        $forum_where = "WHERE forum_id = {$PHORUM['forum_id']} " .
                       // forum_id = 0 is used for GLOBAL ban items
                       'OR forum_id = 0';

        // If we're inside a vroot, then retrieve the ban items that apply
        // to this vroot as well.
        if (isset($PHORUM['vroot']) && !empty($PHORUM['vroot'])) {
            $forum_where .= " OR forum_id = {$PHORUM['vroot']}";
        }
    }

    $order = $ordered ? 'ORDER BY type, string' : '';

    $bans = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM {$PHORUM['banlist_table']}
         $forum_where
         $order"
    );

    $banlists = array();
    foreach ($bans as $ban) {
        $banlists[$ban['type']][$ban['id']] = array (
            'pcre'     => $ban['pcre'],
            'string'   => $ban['string'],
            'comments' => $ban['comments'],
            'forum_id' => $ban['forum_id']
        );
    }

    return $banlists;
}
// }}}

// {{{ Function: phorum_db_get_banitem
/**
 * Retrieve a single ban item from the ban lists.
 *
 * @param integer $banid
 *     The id of the ban item to retrieve.
 *
 * @return array
 *     A ban item array, containing the fields: pcre, string, forum_id,
 *     type. If no ban can be found for the $banid, then an empty array
 *     is returned instead.
 */
function phorum_db_get_banitem($banid)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($banid, 'int');

    $bans = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM   {$PHORUM['banlist_table']}
         WHERE  id = $banid"
    );

    if (count($bans)) {
        $ban = array(
            'pcre'     => $bans[0]['pcre'],
            'string'   => $bans[0]['string'],
            'forum_id' => $bans[0]['forum_id'],
            'type'     => $bans[0]['type'],
            'comments' => $bans[0]['comments']
        );
    } else {
        $ban = array();
    }

    return $ban;
}
// }}}

// {{{ Function: phorum_db_del_banitem
/**
 * Delete a single ban item from the ban lists.
 *
 * @param integer $banid
 *     The id of the ban item to delete.
 */
function phorum_db_del_banitem($banid)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($banid, 'int');

    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['banlist_table']}
         WHERE  id = $banid",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_mod_banlists()
/**
 * Add or modify a single ban list item.
 *
 * @param $type
 *     The type of ban list item. Available ban types are:
 *     - PHORUM_BAD_IPS:        Match IP address or hostname.
 *     - PHORUM_BAD_NAMES:      Mach name or username.
 *     - PHORUM_BAD_EMAILS:     Match the email address.
 *     - PHORUM_BAD_USERID:     Match the user_id.
 *     - PHORUM_BAD_SPAM_WORDS: Match for spam words.
 *
 * @param boolean $pcre
 *      Whether the ban string has to be handled as a standard match
 *      string or as a pcre (Perl Compatible Regular Expression).
 *
 * @param string $string
 *     The ban string for performing the match.
 *
 * @param integer $forum_id
 *     The forum_id to link the ban to. This can be a real forum_id, a
 *     vroot id or 0 (zero) to indicate a GLOBAL ban item.
 *
 * @param string $comments
 *     Comments to add to the ban item. This can be used for documenting the
 *     ban item (why was the ban created, when was this done or generally
 *     any info that an administrator finds useful).
 *
 * @param integer $banid
 *     This parameter can be set to the id of a ban item to let the
 *     function update an existing ban. If set to 0 (zero), a new ban
 *     item will be created.
 *
 * @return boolean
 *     True if the ban item was created or updated successfully.
 */
function phorum_db_mod_banlists($type, $pcre, $string, $forum_id, $comments, $banid=0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    $retarr = array();

    settype($type, 'int');
    settype($pcre, 'int');
    settype($forum_id, 'int');
    settype($banid, 'int');

    $string = phorum_db_interact(DB_RETURN_QUOTED, $string);
    $comments = phorum_db_interact(DB_RETURN_QUOTED, $comments);

    // Update an existing ban item.
    if ($banid > 0) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['banlist_table']}
             SET    forum_id = $forum_id,
                    type     = $type,
                    pcre     = $pcre,
                    string   = '$string',
                    comments = '$comments'
             WHERE  id = $banid",
            NULL,
            DB_MASTERQUERY
        );
    }
    // Create a new ban item.
    else {
        phorum_db_interact(
            DB_RETURN_RES,
            "INSERT INTO {$PHORUM['banlist_table']}
                    (forum_id, type, pcre, string, comments)
             VALUES ($forum_id, $type, $pcre, '$string', '$comments')",
            NULL,
            DB_MASTERQUERY
        );
    }

    return TRUE;
}
// }}}

// {{{ Function: phorum_db_pm_list()
/**
 * Retrieve all private messages for a user in a folder.
 *
 * @param mixed $folder
 *     The folder to use. Either a special folder (PHORUM_PM_INBOX or
 *     PHORUM_PM_OUTBOX) or the id of a custom user folder.
 *
 * @param integer $user_id
 *     The user to retrieve messages for or NULL to use the active
 *     Phorum user (default).
 *
 * @param boolean $reverse
 *     If set to a true value (default), sorting of messages is done
 *     in reverse (newest first).
 *
 * @return array
 *     An array of private messages for the folder.
 */
function phorum_db_pm_list($folder, $user_id = NULL, $reverse = TRUE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');
    settype($reverse, 'bool');

    if (is_numeric($folder)) {
        $folder_where = "pm_folder_id = $folder";
    } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
        $folder_where = "(pm_folder_id = 0 AND special_folder = '$folder')";
    } else trigger_error(
        'phorum_db_pm_list(): Illegal folder "'.htmlspecialchars($folder).'" '.
        'requested for user id "'.$user_id.'"',
        E_USER_ERROR
    );

    // Retrieve the messages from the folder.
    $messages = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT m.pm_message_id AS pm_message_id,
                m.user_id,       author,
                subject,         datestamp,
                meta,            pm_xref_id,
                pm_folder_id,    special_folder,
                read_flag,       reply_flag
         FROM   {$PHORUM['pm_messages_table']} AS m,
                {$PHORUM['pm_xref_table']} AS x
         WHERE  x.user_id = $user_id AND
                $folder_where AND
                x.pm_message_id = m.pm_message_id
         ORDER  BY x.pm_message_id " . ($reverse ? 'DESC' : 'ASC'),
        'pm_message_id'
    );

    // Add the recipient information unserialized to the messages.
    foreach ($messages as $id => $message) {
        $meta = unserialize($message['meta']);
        $messages[$id]['recipients'] = $meta['recipients'];
    }

    return $messages;
}
// }}}

// {{{ Function: phorum_db_pm_get()
/**
 * Retrieve a private message from the database.
 *
 * @param integer $pm_id
 *     The id for the private message to retrieve.
 *
 * @param mixed $folder
 *     The folder to retrieve the message from or NULL if the folder
 *     does not matter.
 *
 * @param integer $user_id
 *     The user to retrieve the message for or NULL to use the active
 *     Phorum user (default).
 *
 * @return array
 *     Return the private message on success or NULL if the message
 *     could not be found.
 */
function phorum_db_pm_get($pm_id, $folder = NULL, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');
    settype($pm_id, 'int');

    if ($folder === NULL) {
        $folder_where = '';
    } elseif (is_numeric($folder)) {
        $folder_where = "pm_folder_id = $folder AND ";
    } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
        $folder_where = "pm_folder_id = 0 AND special_folder = '$folder' AND ";
    } else trigger_error(
        'phorum_db_pm_get(): Illegal folder "'.htmlspecialchars($folder).'" '.
        'requested for user id "'.$user_id.'"',
        E_USER_ERROR
    );

    // Retrieve the private message.
    $messages = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT m.pm_message_id  AS pm_message_id,
                m.user_id        AS user_id,
                m.author         AS author,
                m.subject        AS subject,
                m.message        AS message,
                m.datestamp      AS datestamp,
                m.meta           AS meta,
                x.pm_xref_id     AS pm_xref_id,
                x.pm_folder_id   AS pm_folder_id,
                x.special_folder AS special_folder,
                x.pm_message_id  AS pm_message_id,
                x.read_flag      AS read_flag,
                x.reply_flag     AS reply_flag
         FROM {$PHORUM['pm_messages_table']} AS m,
              {$PHORUM['pm_xref_table']} AS x
         WHERE $folder_where
               x.pm_message_id = $pm_id AND
               x.user_id       = $user_id AND
               x.pm_message_id = m.pm_message_id"
    );

    // Prepare the return data.
    if (count($messages) == 0) {
        $message = NULL;
    } else {
        $message = $messages[0];

        // Add the recipient information unserialized to the message..
        $meta = unserialize($message['meta']);
        $message['recipients'] = $meta['recipients'];
    }

    return $message;
}
// }}}

// {{{ Function: phorum_db_pm_create_folder()
/**
 * Create a new private messages folder for a user.
 *
 * @param string $foldername
 *     The name of the folder to create.
 *
 * @param mixed $user_id
 *     The id of the user to create the folder for or NULL to use the
 *     active Phorum user (default).
 *
 * @return integer $pm_folder_id
 *     The id that was assigned to the new folder.
 */
function phorum_db_pm_create_folder($foldername, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');
    $foldername = phorum_db_interact(DB_RETURN_QUOTED, $foldername);

    $pm_folder_id = phorum_db_interact(
        DB_RETURN_NEWID,
        "INSERT INTO {$PHORUM['pm_folders_table']}
                (user_id, foldername)
         VALUES ($user_id, '$foldername')",
        NULL,
        DB_MASTERQUERY
    );

    return $pm_folder_id;
}
// }}}

// {{{ Function: phorum_db_pm_rename_folder()
/**
 * Rename a private message folder for a user.
 *
 * @param integer $folder_id
 *     The id of the folder to rename.
 *
 * @param string $newname
 *     The new name for the folder.
 *
 * @param mixed $user_id
 *     The user to rename the folder for or NULL to use the active
 *     Phorum user (default).
 */
function phorum_db_pm_rename_folder($folder_id, $newname, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');
    settype($folder_id, 'int');
    $newname = phorum_db_interact(DB_RETURN_QUOTED, $newname);

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['pm_folders_table']}
         SET    foldername = '$newname'
         WHERE  pm_folder_id = $folder_id AND
                user_id = $user_id",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_pm_delete_folder()
/**
 * Delete a private message folder for a user. Along with the folder,
 * all contained messages are deleted as well.
 *
 * @param integer $folder_id
 *     The id of the folder to delete.
 *
 * @param mixed $user_id
 *     The user to delete the folder for or NULL to use the active
 *     Phorum user (default).
 */
function phorum_db_pm_delete_folder($folder_id, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');
    settype($folder_id, 'int');

    // Retrieve the private messages in this folder and delete them.
    $list = phorum_db_pm_list($folder_id, $user_id);
    foreach ($list as $id => $data) {
        phorum_db_pm_delete($id, $folder_id, $user_id);
    }

    // Delete the folder itself.
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['pm_folders_table']}
         WHERE pm_folder_id = $folder_id AND
               user_id      = $user_id",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_pm_getfolders()
/**
 * Retrieve a list of private message folders for a user.
 *
 * @param mixed $user_id
 *     The user to retrieve folders for or NULL to use the active
 *     Phorum user (default).
 *
 * @param boolean $count
 *     If this parameter is set to a true value, the number of messages
 *     for each folder is included in the return data. By default,
 *     this is not done.
 *
 * @return array
 *     An array of private message folders, indexed by the folder id.
 *     The values are arrays, containing the fields "id" and "name".
 *     If $count is enabled, the  keys "total" and "new" will be added
 *     to the forum info.
 */
function phorum_db_pm_getfolders($user_id = NULL, $count = FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');
    settype($count, 'bool');

    // Initialize the list of folders. Our special folders are
    // not in the database, so these are added hard-coded to the list.
    // Add the incoming folder.
    $folders = array(
        PHORUM_PM_INBOX => array(
            'id'   => PHORUM_PM_INBOX,
            'name' => $PHORUM['DATA']['LANG']['INBOX'],
        ),
    );

    // Select all custom folders for the user.
    $customfolders = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT pm_folder_id AS id,
                foldername   AS name
         FROM   {$PHORUM['pm_folders_table']}
         WHERE  user_id = $user_id
         ORDER  BY foldername",
        'id'
    );

    // Add them to the folder list.
    foreach ($customfolders as $id => $customfolder) {
        $folders[$id] = $customfolder;
    }

    // Add the outgoing folder.
    $folders[PHORUM_PM_OUTBOX] = array(
        'id'   => PHORUM_PM_OUTBOX,
        'name' => $PHORUM['DATA']['LANG']['SentItems'],
    );

    // Count the number of messages in the folders if requested.
    if ($count)
    {
        // Initialize counters.
        foreach ($folders as $id => $data) {
            $folders[$id]['total'] = 0;
            $folders[$id]['new']   = 0;
        }

        // Collect count information.
        $countinfo = phorum_db_interact(
            DB_RETURN_ASSOCS,
            "SELECT pm_folder_id,
                    special_folder,
                    count(*) AS total,
                    (count(*) - sum(read_flag)) AS new
             FROM   {$PHORUM['pm_xref_table']}
             WHERE  user_id = $user_id
             GROUP  BY pm_folder_id, special_folder"
        );

        // Merge the count information with the folders.
        foreach ($countinfo as $info)
        {
            $folder_id = $info['pm_folder_id']
                       ? $info['pm_folder_id']
                       : $info['special_folder'];

            // If there are stale messages for no longer existing folders
            // (shouldn't happen), we do not want them to create non-existent
            // mailboxes in the list.
            if (isset($folders[$folder_id])) {
                $folders[$folder_id]['total'] = $info['total'];
                $folders[$folder_id]['new']   = $info['new'];
            }
        }
    }

    return $folders;
}
// }}}

// {{{ Function: phorum_db_pm_messagecount
/**
 * Compute the total number of private messages a user has and return
 * both the total number of messages and the number of unread messages.
 *
 * @param mixed $folder
 *     The id of the folder to use. Either a special folder
 *     (PHORUM_PM_INBOX or PHORUM_PM_OUTBOX), the id of a user's custom
 *     folder or PHORUM_PM_ALLFOLDERS for looking at all folders.
 *
 * @param mixed $user_id
 *     The user to retrieve messages for or NULL to use the
 *     active Phorum user (default).
 *
 * @return array
 *     An array containing the elements "total" and "new".
 */
function phorum_db_pm_messagecount($folder, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    if (is_numeric($folder)) {
        $folder_where = "pm_folder_id = $folder AND";
    } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
        $folder_where = "pm_folder_id = 0 AND special_folder = '$folder' AND";
    } elseif ($folder == PHORUM_PM_ALLFOLDERS) {
        $folder_where = '';
    } else trigger_error(
        'phorum_db_pm_messagecount(): Illegal folder "' .
        htmlspecialchars($folder).'" requested for user id "'.$user_id.'"',
        E_USER_ERROR
    );

    $counters = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT count(*) AS total,
                (count(*) - sum(read_flag)) AS new
         FROM   {$PHORUM['pm_xref_table']}
         WHERE  $folder_where user_id = $user_id"
     );

     $count = array( 'total' => 0, 'new' => 0 );
     if (count($counters) > 0) {
        $count['total'] = $counters[0]['total'];
        $count['new']   = ($counters[0]['new'] >= 1) ? $counters[0]['new'] : 0;
    }

    return $count;
}
// }}}

// {{{ Function: phorum_db_pm_checknew()
/**
 * Check if the user has any new private messages. This is useful in case
 * you only want to know whether the user has new messages or not and when
 * you are not interested in the exact amount of new messages.
 *
 * @param mixed $user_id
 *     The user to check for or NULL to use the active Phorum user (default).
 *
 * @return boolean
 *     TRUE in case there are new messages, FALSE otherwise.
 */
function phorum_db_pm_checknew($user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    $new = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT user_id
         FROM   {$PHORUM['pm_xref_table']}
         WHERE  user_id   = $user_id AND
                read_flag = 0 LIMIT 1"
    );

    return (bool)$new;
}
// }}}

// {{{ Function: phorum_db_pm_send
/**
 * Send a private message.
 *
 * @param string $subject
 *     The subject for the private message.
 *
 * @param string $message
 *     The message text for the private message.
 *
 * @param mixed $to
 *     A single user_id or an array of user_ids for specifying the
 *     recipient(s).
 *
 * @param mixed $from
 *     The id of the sending user or NULL to use the active Phorum user
 *     (default).
 *
 * @param $keepcopy
 *     If set to a true value, a copy of the mail will be kept in the
 *     outbox of the user. Default value is FALSE.
 *
 * @return integer
 *     The id that was assigned to the new message.
 */
function phorum_db_pm_send($subject, $message, $to, $from=NULL, $keepcopy=FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Prepare the sender.
    if ($from === NULL) $from = $PHORUM['user']['user_id'];
    settype($from, 'int');
    $fromuser = phorum_db_user_get($from, FALSE);
    if (! $fromuser) trigger_error(
        "phorum_db_pm_send(): Unknown sender user_id '$from'",
        E_USER_ERROR
    );
    $fromuser = phorum_db_interact(DB_RETURN_QUOTED, $fromuser['display_name']);
    $subject = phorum_db_interact(DB_RETURN_QUOTED, $subject);
    $message = phorum_db_interact(DB_RETURN_QUOTED, $message);

    // Prepare the list of recipients and xref entries.
    $xref_entries = array();
    $rcpts = array();
    if (! is_array($to)) $to = array($to);
    foreach ($to as $user_id)
    {
        settype($user_id, 'int');

        $user = phorum_db_user_get($user_id, FALSE);
        if (! $user) trigger_error(
            "phorum_db_pm_send(): Unknown recipient user_id '$user_id'",
            E_USER_ERROR
        );
        $rcpts[$user_id] = array(
            'user_id'        => $user_id,
            'display_name'   => $user['display_name'],
            'read_flag'      => 0,
        );
        $xref_entries[] = array(
            'user_id'        => $user_id,
            'pm_folder_id'   => 0,
            'special_folder' => PHORUM_PM_INBOX,
            'read_flag'      => 0,
        );
    }

    // Keep copy of this message in outbox?
    if ($keepcopy) {
        $xref_entries[] = array(
            'user_id'        => $from,
            'pm_folder_id'   => 0,
            'special_folder' => PHORUM_PM_OUTBOX,
            'read_flag'      => 1,
        );
    }
    // Prepare message meta data.
    $meta = phorum_db_interact(DB_RETURN_QUOTED, serialize(array(
        'recipients' => $rcpts
    )));

    // Create the message.
    $pm_id = phorum_db_interact(
        DB_RETURN_NEWID,
        "INSERT INTO {$PHORUM['pm_messages_table']}
                (user_id, author, subject,
                 message, datestamp, meta)
         VALUES ($from, '$fromuser', '$subject',
                 '$message', '".time()."', '$meta')",
        NULL,
        DB_MASTERQUERY
    );

    // Put the message in the recipient inboxes.
    foreach ($xref_entries as $xref)
    {
        phorum_db_interact(
            DB_RETURN_RES,
            "INSERT INTO {$PHORUM['pm_xref_table']}
                    (user_id, pm_folder_id,
                     special_folder, pm_message_id,
                     read_flag, reply_flag)
             VALUES ({$xref['user_id']}, {$xref['pm_folder_id']},
                     '{$xref['special_folder']}', $pm_id,
                     {$xref['read_flag']}, 0)",
            NULL,
            DB_MASTERQUERY
        );
    }

    return $pm_id;
}
// }}}

// {{{ Function: phorum_db_pm_setflag()
/**
 * Update a flag for a private message.
 *
 * @param integer $pm_id
 *     The id of the message to update.
 *
 * @param integer $flag
 *     The flag to update. Possible flags are: PHORUM_PM_READ_FLAG and
 *     PHORUM_PM_REPLY_FLAG.
 *
 * @param boolean $value
 *     The value for the flag (either TRUE or FALSE).
 *
 * @param $user_id
 *     The user to set a flag for or NULL to use the active Phorum
 *     user (default).
 */
function phorum_db_pm_setflag($pm_id, $flag, $value, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($pm_id, 'int');

    if ($flag != PHORUM_PM_READ_FLAG &&
        $flag != PHORUM_PM_REPLY_FLAG) trigger_error(
        'phorum_db_pm_setflag(): Illegal value "' . htmlspecialchars($flag) .
        '" for parameter $flag',
        E_USER_WARNING
    );

    $value = $value ? 1 : 0;

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    // Update the flag in the database.
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['pm_xref_table']}
         SET    $flag = $value
         WHERE  pm_message_id = $pm_id AND
                user_id       = $user_id",
        NULL,
        DB_MASTERQUERY
    );

    // Update message counters.
    if ($flag == PHORUM_PM_READ_FLAG) {
        phorum_db_pm_update_message_info($pm_id);
    }
}
// }}}

// {{{ Function: phorum_db_pm_delete()
/**
 * Delete a private message from a folder.
 *
 * @param integer $pm_id
 *     The id of the private message to delete
 *
 * @param mixed $folder
 *     The folder from which to delete the message
 *
 * @param integer $user_id
 *     The id of the user to delete the message for
 *     or NULL to use the active Phorum user (default).
 */
function phorum_db_pm_delete($pm_id, $folder, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($pm_id, 'int');

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    if (is_numeric($folder)) {
        $folder_where = "pm_folder_id = $folder";
    } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
        $folder_where = "(pm_folder_id = 0 AND special_folder = '$folder')";
    } else trigger_error(
        'phorum_db_pm_delete(): Illegal folder "' .
        htmlspecialchars($folder).'" requested for user id "'.$user_id.'"',
        E_USER_ERROR
    );

    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['pm_xref_table']}
         WHERE user_id       = $user_id AND
               pm_message_id = $pm_id AND
               $folder_where",
        NULL,
        DB_MASTERQUERY
    );

    // Update message counters.
    phorum_db_pm_update_message_info($pm_id);
}
// }}}

// {{{ Function: phorum_db_pm_move()
/**
 * Move a private message to a different folder.
 *
 * @param integer $pm_id
 *     The id of the private message to move.
 *
 * @param mixed $from
 *     The folder to move the message from.
 *
 * @param mixed $to
 *     The folder to move the message to.
 *
 * @param mixed $user_id
 *     The id or the user to move the message for
 *     or NULL to use the active Phorum user (default).
 */
function phorum_db_pm_move($pm_id, $from, $to, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($pm_id, 'int');

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    if (is_numeric($from)) {
        $folder_where = "pm_folder_id = $from";
    } elseif ($from == PHORUM_PM_INBOX || $from == PHORUM_PM_OUTBOX) {
        $folder_where = "(pm_folder_id = 0 AND special_folder = '$from')";
    } else trigger_error(
        'phorum_db_pm_move(): Illegal source folder "' .
        htmlspecialchars($from).'" requested for user id "'.$user_id.'"',
        E_USER_ERROR
    );

    if (is_numeric($to)) {
        $pm_folder_id = $to;
        $special_folder = 'NULL';
    } elseif ($to == PHORUM_PM_INBOX || $to == PHORUM_PM_OUTBOX) {
        $pm_folder_id = 0;
        $special_folder = "'$to'";
    } else trigger_error(
        'phorum_db_pm_move(): Illegal target folder "' .
        htmlspecialchars($to).'" requested for user_id "'.$user_id.'"',
        E_USER_ERROR
    );

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['pm_xref_table']}
         SET    pm_folder_id   = $pm_folder_id,
                special_folder = $special_folder
         WHERE  user_id        = $user_id AND
                pm_message_id  = $pm_id AND
                $folder_where",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_pm_update_message_info()
/**
 * Update the meta information for a message.
 *
 * This function will update the meta information using the information
 * from the xrefs table. If we find that no xrefs are available for the
 * message anymore, the message will be deleted from the database.
 *
 * @param integer $pm_id
 *     The id of the private message for which to update the meta information.
 */
function phorum_db_pm_update_message_info($pm_id)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($pm_id, 'int');

    // Retrieve the meta data for the private message.
    $pm = phorum_db_interact(
        DB_RETURN_ASSOC,
        "SELECT meta
         FROM   {$PHORUM['pm_messages_table']}
         WHERE  pm_message_id = $pm_id",
        NULL,
        DB_MASTERQUERY
    );

    # Return immediately if no message was found.
    if (empty($pm)) return;

    // Find the xrefs for this message.
    $xrefs = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT user_id, read_flag
         FROM   {$PHORUM['pm_xref_table']}
         WHERE  pm_message_id = $pm_id",
        NULL,
        DB_MASTERQUERY
    );

    // No xrefs left? Then the message can be fully deleted.
    if (count($xrefs) == 0) {
        phorum_db_interact(
            DB_RETURN_RES,
            "DELETE FROM {$PHORUM['pm_messages_table']}
             WHERE  pm_message_id = $pm_id",
            NULL,
            DB_MASTERQUERY
        );
        return;
    }

    // Update the read flags for the recipients in the meta data.
    $meta = unserialize($pm['meta']);
    $rcpts = $meta['recipients'];
    foreach ($xrefs as $xref) {
        // Only update if available. A copy that is kept in the outbox will
        // not be in the meta list, so if the copy is read, the meta data
        // does not have to be updated here.
        if (isset($rcpts[$xref[0]])) {
            $rcpts[$xref[0]]['read_flag'] = $xref[1];
        }
    }
    $meta['recipients'] = $rcpts;

    // Store the new meta data.
    $meta = phorum_db_interact(DB_RETURN_QUOTED, serialize($meta));
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['pm_messages_table']}
         SET    meta = '$meta'
         WHERE  pm_message_id = $pm_id",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_pm_is_buddy()
/**
 * Check if a user is buddy of another user.
 *
 * @param integer $buddy_user_id
 *     The user_id for which to check if the user is a buddy.
 *
 * @param mixed $user_id
 *     The user_id for which the buddy list must be checked
 *     or NULL to use the active Phorum user (default).
 *
 * @return mixed
 *     If the user is a buddy, then the pm_buddy_id for the buddy will be
 *     returned. If not, then NULL will be returned.
 */
function phorum_db_pm_is_buddy($buddy_user_id, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($buddy_user_id, 'int');

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    $pm_buddy_id = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT pm_buddy_id
         FROM   {$PHORUM['pm_buddies_table']}
         WHERE  user_id       = $user_id AND
                buddy_user_id = $buddy_user_id"
    );

    return $pm_buddy_id;
}
// }}}

// {{{ Function: phorum_db_pm_buddy_add()
/**
 * Add a buddy for a user.
 *
 * @param integer $buddy_user_id
 *     The user_id that has to be added as a buddy.
 *
 * @param mixed $user_id
 *     The user_id the buddy has to be added for
 *     or NULL to use the active Phorum user (default).
 *
 * @return mixed
 *     The id that was assigned to the new buddy or the existing id if
 *     the buddy already existed. If no user can be found for the
 *     $buddy_user_id parameter, then NULL will be returned.
 */
function phorum_db_pm_buddy_add($buddy_user_id, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($buddy_user_id, 'int');

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    // Check if the buddy_user_id is a valid user_id.
    $valid = phorum_db_user_get($buddy_user_id, FALSE);
    if (! $valid) return NULL;

    // See if the user is already a buddy.
    $pm_buddy_id = phorum_db_pm_is_buddy($buddy_user_id);

    // If not, then create insert a new buddy relation.
    if ($pm_buddy_id === NULL) {
        $pm_buddy_id = phorum_db_interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$PHORUM['pm_buddies_table']}
                    (user_id, buddy_user_id)
             VALUES ($user_id, $buddy_user_id)",
            NULL,
            DB_MASTERQUERY
        );
    }

    return $pm_buddy_id;
}
// }}}

// {{{ Function: phorum_db_pm_buddy_delete()
/**
 * Delete a buddy for a user.
 *
 * @param integer $buddy_user_id
 *     The user_id that has to be deleted as a buddy.
 *
 * @param mixed $user_id
 *     The user_id the buddy has to be delete for
 *     or NULL to use the active Phorum user (default).
 */
function phorum_db_pm_buddy_delete($buddy_user_id, $user_id = NULL)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($buddy_user_id, 'int');

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['pm_buddies_table']}
         WHERE buddy_user_id = $buddy_user_id AND
               user_id       = $user_id",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_pm_buddy_list()
/**
 * Retrieve a list of buddies for a user.
 *
 * @param mixed $user_id
 *     The user_id for which to retrieve the buddies or NULL to user the
 *     active Phorum user (default).
 *
 * @param boolean $find_mutual
 *     Whether to find mutual buddies or not (default FALSE).
 *
 * @return array
 *     An array of buddies. The keys in this array are user_ids. The values
 *     are arrays, which contain the field "user_id" and possibly the
 *     boolean field "mutual" if the $find_mutual parameter was set to
 *     a true value.
 */
function phorum_db_pm_buddy_list($user_id = NULL, $find_mutual = FALSE)
{
    $PHORUM = $GLOBALS['PHORUM'];

    if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
    settype($user_id, 'int');

    settype($find_mutual, 'bool');

    // Retrieve all buddies for this user.
    $buddies = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT buddy_user_id AS user_id
         FROM {$PHORUM['pm_buddies_table']}
         WHERE user_id = $user_id",
        'user_id'
    );

    // If we do not have to lookup mutual buddies, we're done.
    if (! $find_mutual) return $buddies;

    // Initialize mutual buddy value.
    foreach ($buddies as $id => $data) {
        $buddies[$id]['mutual'] = FALSE;
    }

    // Retrieve all mutual buddies.
    $mutuals = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT DISTINCT a.buddy_user_id AS buddy_user_id
         FROM {$PHORUM['pm_buddies_table']} AS a,
              {$PHORUM['pm_buddies_table']} AS b
         WHERE a.user_id       = $user_id AND
               b.user_id       = a.buddy_user_id AND
               b.buddy_user_id = $user_id"
    );

    // Merge the mutual buddies with the buddies array.
    foreach ($mutuals as $mutual) {
        $buddies[$mutual[0]]['mutual'] = TRUE;
    }

    return $buddies;
}
// }}}

// {{{ Function: phorum_db_split_thread()
/**
 * Split a thread.
 *
 * @param integer $message_id
 *     The id of the message at which to split a thread.
 *
 * @param integer $forum_id
 *     The id of the forum in which the message can be found.
 */
function phorum_db_split_thread($message_id, $forum_id)
{
    settype($message_id, 'int');
    settype($forum_id, 'int');

    if ($message_id > 0 && $forum_id > 0)
    {
        // Retrieve the message tree for all messages below the split message.
        // This tree is used for updating the thread ids of the children
        // below the split message.
        $tree = phorum_db_get_messagetree($message_id, $forum_id);

        // Turn the message into a thread starter message.
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$GLOBALS['PHORUM']['message_table']}
             SET    thread     = $message_id,
                    parent_id  = 0
             WHERE  message_id = $message_id",
            NULL,
            DB_MASTERQUERY
        );

        // Link the messages below the split message to the split off thread.
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$GLOBALS['PHORUM']['message_table']}
             SET    thread = $message_id
             WHERE  message_id IN ($tree)",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_get_max_messageid()
/**
 * Retrieve the maximum message_id in the database.
 *
 * @return integer $max_id
 *     The maximum available message_id or 0 (zero)
 *     if no message was found at all.
 */
function phorum_db_get_max_messageid()
{
    $PHORUM = $GLOBALS['PHORUM'];

    $maxid = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT max(message_id)
         FROM   {$PHORUM["message_table"]}"
    );

    return $maxid === NULL ? 0 : $maxid;
}
// }}}

// {{{ Function: phorum_db_increment_viewcount()
/**
 * Increment the viewcount field for a post.
 *
 * @param integer $message_id
 *     The id of the message for which to increment the viewcount.
 *
 * @param boolean $thread_id
 *     If this parameter is set to a thread_id, then the threadviewcount
 *     for that thread will be incremented as well.
 */
function phorum_db_increment_viewcount($message_id, $thread_id = NULL)
{
    settype($message_id, 'int');
    if ($thread_id !== NULL) settype($thread_id, 'int');

    // Check if the message is the thread starter, in which case we can
    // handle the increment with only one SQL query later on in this function.
    $tvc = '';
    if ($thread_id !== NULL) {
        if ($thread_id == $message_id) {
            $tvc = ',threadviewcount = threadviewcount + 1';
        } else {
            phorum_db_interact(
                DB_RETURN_RES,
                "UPDATE {$GLOBALS['PHORUM']['message_table']}
                 SET    threadviewcount = threadviewcount + 1
                 WHERE  message_id = $thread_id",
                NULL,
                DB_MASTERQUERY
            );
        }
    }

    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$GLOBALS['PHORUM']['message_table']}
         SET    viewcount = viewcount + 1
                $tvc
         WHERE  message_id = $message_id",
        NULL,
        DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_rebuild_search_data()
/**
 * Rebuild the search table data from scratch.
 */
function phorum_db_rebuild_search_data()
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Delete all records from the search table.
    phorum_db_interact(
        DB_RETURN_RES,
        "TRUNCATE TABLE {$PHORUM['search_table']}",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );

    // Rebuild all search data from scratch.
    phorum_db_interact(
        DB_RETURN_RES,
        "INSERT INTO {$PHORUM['search_table']}
                (message_id, search_text, forum_id)
         SELECT message_id,
                concat(author, ' | ', subject, ' | ', body),
                forum_id
         FROM   {$PHORUM['message_table']}",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );
}
// }}}

// {{{ Function: phorum_db_rebuild_user_posts()
/**
 * Rebuild the user post counts from scratch.
 */
function phorum_db_rebuild_user_posts()
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Reset the post count for all users.
    phorum_db_interact(
        DB_RETURN_RES,
        "UPDATE {$PHORUM['user_table']}
         SET posts = 0",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );

    // Retrieve the post counts for all user_ids in the message table.
    $postcounts = phorum_db_interact(
        DB_RETURN_ROWS,
        "SELECT user_id, count(*)
         FROM   {$PHORUM['message_table']}
         GROUP  BY user_id",
        NULL,
        DB_GLOBALQUERY | DB_MASTERQUERY
    );

    // Set the post counts for the users to their correct values.
    foreach ($postcounts as $postcount) {
        phorum_db_interact(
            DB_RETURN_RES,
            "UPDATE {$PHORUM['user_table']}
             SET    posts   = {$postcount[1]}
             WHERE  user_id = {$postcount[0]}",
            NULL,
            DB_MASTERQUERY
        );
    }
}
// }}}

// {{{ Function: phorum_db_user_search_custom_profile_field()
/**
 * Search for users, based on a simple search condition,
 * which can be used to search on custom profile fields.
 *
 * The parameters $field_id, $value and $operator (which are used for defining
 * the search condition) can be arrays or single values. If arrays are used,
 * then all three parameter arrays must contain the same number of elements
 * and the key values in the arrays must be the same.
 *
 * @param mixed $field_id
 *     The custom profile field id (integer) or ids (array) to search on.
 *
 * @param mixed $value
 *     The value (string) or values (array) to search for.
 *
 * @param mixed $operator
 *     The operator (string) or operators (array) to use. Valid operators are
 *     "=", "!=", "<>", "<", ">", ">=" and "<=", "*", '?*', '*?'. The
 *     "*" operator is for executing a "LIKE '%value%'" matching query. The
 *     "?*" and "*?" operators are for executing a "LIKE 'value%'" or a
 *     "LIKE '%value' matching query.
 *
 * @param boolean $return_array
 *     If this parameter has a true value, then an array of all matching
 *     user_ids will be returned. Else, a single user_id will be returned.
 *
 * @param string $type
 *     The type of search to perform. This can be one of:
 *     - AND  match against all fields
 *     - OR   match against any of the fields
 *
 * @param integer $offset
 *     The result page offset starting with 0.
 *
 * @param integer $length
 *     The result page length (nr. of results per page)
 *     or 0 (zero, the default) to return all results.
 *
 * @return mixed
 *     An array of matching user_ids or a single user_id (based on the
 *     $return_array parameter). If no user_ids can be found at all,
 *     then 0 (zero) will be returned.
 */
function phorum_db_user_search_custom_profile_field($field_id, $value, $operator = '=', $return_array = FALSE, $type = 'AND', $offset = 0, $length = 0)
{
    $PHORUM = $GLOBALS['PHORUM'];

    settype($return_array, 'bool');
    settype($offset, 'int');
    settype($length, 'int');

    // Convert all search condition parameters to arrays.
    if (!is_array($field_id)) $field_id = array($field_id);
    if (!is_array($value))    $value    = array($value);
    if (!is_array($operator)) $operator = array($operator);

    // Basic check to see if all condition arrays contain the
    // same number of elements.
    if (count($field_id) != count($value) ||
        count($field_id) != count($operator)) trigger_error(
        'phorum_db_user_search_custom_profile_field(): ' .
        'array parameters $field_id, $value, and $operator do not contain ' .
        'the same number of elements',
        E_USER_ERROR
    );

    $type = strtoupper($type);
    if ($type != 'AND' && $type != 'OR') trigger_error(
        'phorum_db_user_search_custom_profile_field(): ' .
        'Illegal search type parameter (must be either AND" or "OR")',
        E_USER_ERROR
    );

    $valid_operators = array('=', '<>', '!=', '>', '<', '>=', '<=', '*', '?*', '*?');

    // Construct the required "WHERE" clause.
    $clauses = array();
    foreach ($field_id as $key => $id) {
        if (in_array($operator[$key], $valid_operators)) {
            settype($id, 'int');
            $value[$key] = phorum_db_interact(DB_RETURN_QUOTED, $value[$key]);
            if ($operator[$key] == '*') {
                $clauses[] = "(type = $id AND data LIKE '%$value[$key]%')";
            } else if ($operator[$key] == '?*') {
                $clauses[] = "(type = $id AND data LIKE '$value[$key]%')";
            } else if ($operator[$key] == '*?') {
                $clauses[] = "(type = $id AND data LIKE '%$value[$key]')";
            } else {
                $clauses[] = "(type = $id AND data $operator[$key] '$value[$key]')";
            }
        }
    }
    if (!empty($clauses)) {
        $where = 'WHERE ' . implode(" OR ", $clauses);
    } else {
        $where = '';
    }

    // Construct the required "LIMIT" clause.
    if (!empty($length)) {
        $limit = "LIMIT $offset, $length";
    } else {
        // If we do not need to return an array, the we can limit the
        // query results to only one record.
        $limit = $return_array ? '' : 'LIMIT 1';
    }

    // Build the final query.
    if ($type == 'OR' || count($clauses) == 1)
    {
        $sql = "SELECT DISTINCT(user_id)
                FROM   {$PHORUM['user_custom_fields_table']}
                $where
                $limit";
    } else {
        $sql = "SELECT user_id
                FROM   {$PHORUM['user_custom_fields_table']}
                $where
                GROUP  BY user_id
                HAVING count(*) = " . count($clauses) . " " .
                $limit;
    }

    // Retrieve the matching user_ids from the database.
    $user_ids = phorum_db_interact(
        DB_RETURN_ROWS, $sql, 0 // keyfield 0 is the user_id
    );

    // No user_ids found at all?
    if (count($user_ids) == 0) return 0;

    // Return an array of user_ids.
    if ($return_array) {
        foreach ($user_ids as $id => $user_id) $user_ids[$id] = $user_id[0];
        return $user_ids;
    }

    // Return a single user_id.
    list ($user_id, $dummy) = each($user_ids);
    return $user_id;
}
// }}}

// {{{ Function: phorum_db_metaquery_compile()
/**
 * Translates a message searching meta query into a real SQL WHERE
 * statement for this database backend. The meta query can be used to
 * define extended SQL queries, based on a meta description of the
 * search that has to be performed on the database.
 *
 * The meta query is an array, containing:
 * - query conditions
 * - grouping using "(" and ")"
 * - AND/OR specifications using "AND" and "OR".
 *
 * The query conditions are arrays, containing the following elements:
 * <ul>
 * <li>condition<br>
 *   <br>
 *   A description of a condition. The syntax for this is:
 *   <field name to query> <operator> <match specification><br>
 *   <br>
 *   The <field name to query> is a field in the message query that
 *   we are running in this function.<br>
 *   <br>
 *   The <operator> can be one of "=", "!=", "<", "<=", ">", ">=".
 *   Note that there is nothing like "LIKE" or "NOT LIKE". If a "LIKE"
 *   query has to be done, then that is setup through the
 *   <match specification> (see below).<br>
 *   <br>
 *   The <match specification> tells us with what the field should be
 *   matched. The string "QUERY" inside the specification is preserved to
 *   specify at which spot in the query the "query" element from the
 *   condition array should be inserted. If "QUERY" is not available in
 *   the specification, then a match is made on the exact value in the
 *   specification. To perform "LIKE" searches (case insensitive wildcard
 *   searches), you can use the "*" wildcard character in the specification
 *   to do so.<br><br>
 * </li>
 * <li>query<br>
 *   <br>
 *   The data to use in the query, in case the condition element has a
 *   <match specification> that uses "QUERY" in it.
 * </li>
 * </ul>
 * Example:
 * <code>
 * $metaquery = array(
 *     array(
 *         "condition"  =>  "field1 = *QUERY*",
 *         "query"      =>  "test data"
 *     ),
 *     "AND",
 *     "(",
 *     array("condition"  => "field2 = whatever"),
 *     "OR",
 *     array("condition"  => "field2 = something else"),
 *     ")"
 * );
 * </code>
 *
 * For MySQL, this would be turned into the MySQL WHERE statement:
 * <code>
 * ... WHERE field1 LIKE '%test data%'
 *     AND (field2 = 'whatever' OR field2 = 'something else')
 * </code>
 *
 * @param array $metaquery
 *     A meta query description array.
 *
 * @return array
 *     An array containing two elements. The first element is either
 *     TRUE or FALSE, based on the success state of the function call
 *     (FALSE means that there was an error). The second argument contains
 *     either a WHERE statement or an error message.
 */
function phorum_db_metaquery_compile($metaquery)
{
    $where = '';

    $expect_condition  = TRUE;
    $expect_groupstart = TRUE;
    $expect_groupend   = FALSE;
    $expect_combine    = FALSE;
    $in_group          = 0;

    foreach ($metaquery as $part)
    {
        // Found a new condition.
        if ($expect_condition && is_array($part))
        {
            $cond = trim($part['condition']);
            if (preg_match('/^([\w_\.]+)\s+(!?=|<=?|>=?)\s+(\S*)$/', $cond, $m))
            {
                $field = $m[1];
                $comp  = $m[2];
                $match = $m[3];

                $matchtokens = preg_split(
                    '/(\*|QUERY|NULL)/',
                    $match, -1,
                    PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                );

                $matchsql = "'";
                $is_like_query = FALSE;
                foreach ($matchtokens as $m) {
                    if ($m == '*') {
                        $is_like_query = TRUE;
                        $matchsql .= '%';
                    } elseif ($m == 'QUERY') {
                        $p = $part['query'];
                        $matchsql .= phorum_db_interact(DB_RETURN_QUOTED, $p);
                    } else {
                        $matchsql .= phorum_db_interact(DB_RETURN_QUOTED, $m);
                    }
                }
                $matchsql .= "'";

                if ($is_like_query)
                {
                    if ($comp == '=') { $comp = ' LIKE '; }
                    elseif ($comp == '!=') { $comp = ' NOT LIKE '; }
                    else return array(
                        FALSE,
                        'Illegal metaquery token ' . htmlspecialchars($cond) .
                        ": wildcard match does not combine with $comp operator"
                    );
                }

                $where .= "$field $comp $matchsql ";
            } else {
                return array(
                    FALSE,
                    'Illegal metaquery token ' . htmlspecialchars($cond) .
                    ': condition does not match the required format'
                );
            }

            $expect_condition   = FALSE;
            $expect_groupstart  = FALSE;
            $expect_groupend    = $in_group;
            $expect_combine     = TRUE;
        }
        // Found a new group start.
        elseif ($expect_groupstart && $part == '(')
        {
            $where .= '(';
            $in_group ++;

            $expect_condition   = TRUE;
            $expect_groupstart  = FALSE;
            $expect_groupend    = FALSE;
            $expect_combine     = FALSE;
        }
        // Found a new group end.
        elseif ($expect_groupend && $part == ')')
        {
            $where .= ') ';
            $in_group --;

            $expect_condition   = FALSE;
            $expect_groupstart  = FALSE;
            $expect_groupend    = $in_group;
            $expect_combine     = TRUE;
        }
        // Found a combine token (AND or OR).
        elseif ($expect_combine && preg_match('/^(OR|AND)$/i', $part, $m))
        {
            $where .= strtoupper($m[1]) . ' ';

            $expect_condition   = TRUE;
            $expect_groupstart  = TRUE;
            $expect_groupend    = FALSE;
            $expect_combine     = FALSE;
        }
        // Unexpected or illegal token.
        else trigger_error(
            'Internal error: unexpected token in metaquery description: ' .
            (is_array($part) ? 'condition' : htmlspecialchars($part)),
            E_USER_ERROR
        );
    }

    if ($expect_groupend) die ('Internal error: unclosed group in metaquery');

    // If the metaquery is empty, then provide a safe true WHERE statement.
    if ($where == '') { $where = '1 = 1'; }

    return array(TRUE, $where);
}
// }}}

// {{{ Function: phorum_db_metaquery_messagesearch()
/**
 * Run a search on the messages, using a metaquery. See the documentation
 * for the phorum_db_metaquery_compile() function for more info on the
 * metaquery syntax.
 *
 * The query that is run here, does create a view on the messages, which
 * includes some thread and user info. This is used so these can also
 * be taken into account when selecting messages. For the condition elements
 * in the meta query, you can use fully qualified field names for the
 * <field name to query>. You can use message.*, user.* and thread.* for this.
 *
 * The primary goal for this function is to provide a backend for the
 * message pruning interface.
 *
 * @param array $metaquery
 *     A metaquery array. See {@link phorum_db_metaquery_compile()} for
 *     more information about the metaquery syntax.
 *
 * @return array
 *     An array of message records.
 */
function phorum_db_metaquery_messagesearch($metaquery)
{
    $PHORUM = $GLOBALS['PHORUM'];

    // Compile the metaquery into a where statement.
    list($success, $where) = phorum_db_metaquery_compile($metaquery);
    if (!$success) trigger_error($where, E_USER_ERROR);

    // Retrieve matching messages.
    $messages = phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT message.message_id  AS message_id,
                message.thread      AS thread,
                message.parent_id   AS parent_id,
                message.forum_id    AS forum_id,
                message.subject     AS subject,
                message.author      AS author,
                message.datestamp   AS datestamp,
                message.body        AS body,
                message.ip          AS ip,
                message.status      AS status,
                message.user_id     AS user_id,
                user.username       AS user_username,
                thread.closed       AS thread_closed,
                thread.modifystamp  AS thread_modifystamp,
                thread.thread_count AS thread_count
         FROM   {$PHORUM['message_table']} AS thread,
                {$PHORUM['message_table']} AS message
                    LEFT JOIN {$PHORUM['user_table']} AS user
                    ON message.user_id = user.user_id
         WHERE  message.thread  = thread.message_id AND
                ($where)
         ORDER BY message_id ASC",
        'message_id'
    );

    return $messages;
}
// }}}

// {{{ Function: phorum_db_create_tables()
/**
 * Create the tables that are needed in the database. This function will
 * only be called at install time. After installation, changes in the
 * database schema will be handled by the database upgrade system.
 *
 * @return mixed
 *     NULL on success or an error message on failure.
 *
 * @todo It might be nice to have some feedback mechanism through a
 *       callback. Using that, table create status can be provided
 *       to the interface which is creating the tables. This is especially
 *       useful in case the create process fails at some point, in which
 *       case you currently have no good feedback about the create
 *       table progress.
 */
function phorum_db_create_tables()
{
    $PHORUM = $GLOBALS['PHORUM'];

    $lang = PHORUM_DEFAULT_LANGUAGE;

    $charset = empty($PHORUM['DBCONFIG']['charset'])
             ? ''
             : "DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";

    $create_table_queries = array(

      "CREATE TABLE {$PHORUM['forums_table']} (
           forum_id                 int unsigned   NOT NULL auto_increment,
           name                     varchar(50)    NOT NULL default '',
           active                   tinyint(1)     NOT NULL default '0',
           description              text           NOT NULL,
           template                 varchar(50)    NOT NULL default '',
           folder_flag              tinyint(1)     NOT NULL default '0',
           parent_id                int unsigned   NOT NULL default '0',
           list_length_flat         int unsigned   NOT NULL default '0',
           list_length_threaded     int unsigned   NOT NULL default '0',
           moderation               int unsigned   NOT NULL default '0',
           threaded_list            tinyint(1)     NOT NULL default '0',
           threaded_read            tinyint(1)     NOT NULL default '0',
           float_to_top             tinyint(1)     NOT NULL default '0',
           check_duplicate          tinyint(1)     NOT NULL default '0',
           allow_attachment_types   varchar(100)   NOT NULL default '',
           max_attachment_size      int unsigned   NOT NULL default '0',
           max_totalattachment_size int unsigned   NOT NULL default '0',
           max_attachments          int unsigned   NOT NULL default '0',
           pub_perms                int unsigned   NOT NULL default '0',
           reg_perms                int unsigned   NOT NULL default '0',
           display_ip_address       tinyint(1)     NOT NULL default '1',
           allow_email_notify       tinyint(1)     NOT NULL default '1',
           language                 varchar(100)   NOT NULL default '$lang',
           email_moderators         tinyint(1)     NOT NULL default '0',
           message_count            int unsigned   NOT NULL default '0',
           sticky_count             int unsigned   NOT NULL default '0',
           thread_count             int unsigned   NOT NULL default '0',
           last_post_time           int unsigned   NOT NULL default '0',
           display_order            int unsigned   NOT NULL default '0',
           read_length              int unsigned   NOT NULL default '0',
           vroot                    int unsigned   NOT NULL default '0',
           edit_post                tinyint(1)     NOT NULL default '1',
           template_settings        text           NOT NULL,
           forum_path               text           NOT NULL,
           count_views              tinyint(1)     NOT NULL default '0',
           count_views_per_thread   tinyint(1)     NOT NULL default '0',
           display_fixed            tinyint(1)     NOT NULL default '0',
           reverse_threading        tinyint(1)     NOT NULL default '0',
           inherit_id               int unsigned       NULL default NULL,
           cache_version            int unsigned   NOT NULL default '0',

           PRIMARY KEY (forum_id),
           KEY name (name),
           KEY active (active, parent_id),
           KEY group_id (parent_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['message_table']} (
           message_id               int unsigned   NOT NULL auto_increment,
           forum_id                 int unsigned   NOT NULL default '0',
           thread                   int unsigned   NOT NULL default '0',
           parent_id                int unsigned   NOT NULL default '0',
           user_id                  int unsigned   NOT NULL default '0',
           author                   varchar(255)   NOT NULL default '',
           subject                  varchar(255)   NOT NULL default '',
           body                     text           NOT NULL,
           email                    varchar(100)   NOT NULL default '',
           ip                       varchar(255)   NOT NULL default '',
           status                   tinyint(4)     NOT NULL default '2',
           msgid                    varchar(100)   NOT NULL default '',
           modifystamp              int unsigned   NOT NULL default '0',
           thread_count             int unsigned   NOT NULL default '0',
           moderator_post           tinyint(1)     NOT NULL default '0',
           sort                     tinyint(4)     NOT NULL default '2',
           datestamp                int unsigned   NOT NULL default '0',
           meta                     mediumtext         NULL,
           viewcount                int unsigned   NOT NULL default '0',
           threadviewcount          int unsigned   NOT NULL default '0',
           closed                   tinyint(1)     NOT NULL default '0',
           recent_message_id        int unsigned   NOT NULL default '0',
           recent_user_id           int unsigned   NOT NULL default '0',
           recent_author            varchar(255)   NOT NULL default '',
           moved                    tinyint(1)     NOT NULL default '0',

           PRIMARY KEY (message_id),
           KEY thread_message (thread,message_id),
           KEY thread_forum (thread,forum_id),
           KEY special_threads (sort,forum_id),
           KEY status_forum (status,forum_id),
           KEY list_page_float (forum_id,parent_id,modifystamp),
           KEY list_page_flat (forum_id,parent_id,thread),
           KEY new_count (forum_id,status,moved,message_id),
           KEY new_threads (forum_id,status,parent_id,moved,message_id),
           KEY recent_threads (status, parent_id, message_id, forum_id),
           KEY updated_threads (status, parent_id, modifystamp),
           KEY dup_check (forum_id,author(50),subject,datestamp),
           KEY forum_max_message (forum_id,message_id,status,parent_id),
           KEY last_post_time (forum_id,status,modifystamp),
           KEY next_prev_thread (forum_id,status,thread),
           KEY recent_user_id (recent_user_id),
           KEY user_messages (user_id, message_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['settings_table']} (
           name                     varchar(255)   NOT NULL default '',
           type                     enum('V','S')  NOT NULL default 'V',
           data                     text           NOT NULL,

           PRIMARY KEY (name)
       ) $charset",

      "CREATE TABLE {$PHORUM['subscribers_table']} (
           user_id                  int unsigned   NOT NULL default '0',
           forum_id                 int unsigned   NOT NULL default '0',
           sub_type                 tinyint(4)     NOT NULL default '0',
           thread                   int unsigned   NOT NULL default '0',

           PRIMARY KEY (user_id,forum_id,thread),
           KEY forum_id (forum_id,thread,sub_type)
       ) $charset",

      "CREATE TABLE {$PHORUM['user_permissions_table']} (
           user_id                  int unsigned   NOT NULL default '0',
           forum_id                 int unsigned   NOT NULL default '0',
           permission               int unsigned   NOT NULL default '0',

           PRIMARY KEY  (user_id,forum_id),
           KEY forum_id (forum_id,permission)
       ) $charset",

      // When creating extra fields, then mind to update the file
      // include/api/custom_profile_fields.php script too (it contains a
      // list of reserved names for custom profile fields).
      "CREATE TABLE {$PHORUM['user_table']} (
           user_id                  int unsigned   NOT NULL auto_increment,
           username                 varchar(50)    NOT NULL default '',
           real_name                varchar(255)   NOT NULL default '',
           display_name             varchar(255)   NOT NULL default '',
           password                 varchar(50)    NOT NULL default '',
           password_temp            varchar(50)    NOT NULL default '',
           sessid_lt                varchar(50)    NOT NULL default '',
           sessid_st                varchar(50)    NOT NULL default '',
           sessid_st_timeout        int unsigned   NOT NULL default '0',
           email                    varchar(100)   NOT NULL default '',
           email_temp               varchar(110)   NOT NULL default '',
           hide_email               tinyint(1)     NOT NULL default '0',
           active                   tinyint(1)     NOT NULL default '0',
           signature                text           NOT NULL,
           threaded_list            tinyint(1)     NOT NULL default '0',
           posts                    int(10)        NOT NULL default '0',
           admin                    tinyint(1)     NOT NULL default '0',
           threaded_read            tinyint(1)     NOT NULL default '0',
           date_added               int unsigned   NOT NULL default '0',
           date_last_active         int unsigned   NOT NULL default '0',
           last_active_forum        int unsigned   NOT NULL default '0',
           hide_activity            tinyint(1)     NOT NULL default '0',
           show_signature           tinyint(1)     NOT NULL default '0',
           email_notify             tinyint(1)     NOT NULL default '0',
           pm_email_notify          tinyint(1)     NOT NULL default '1',
           tz_offset                float(4,2)     NOT NULL default '-99.00',
           is_dst                   tinyint(1)     NOT NULL default '0',
           user_language            varchar(100)   NOT NULL default '',
           user_template            varchar(100)   NOT NULL default '',
           moderator_data           text           NOT NULL,
           moderation_email         tinyint(1)     NOT NULL default '1',
           settings_data            mediumtext     NOT NULL,

           PRIMARY KEY (user_id),
           UNIQUE KEY username (username),
           KEY active (active),
           KEY userpass (username,password),
           KEY sessid_st (sessid_st),
           KEY sessid_lt (sessid_lt),
           KEY activity (date_last_active,hide_activity,last_active_forum),
           KEY date_added (date_added),
           KEY email_temp (email_temp)
       ) $charset",

      "CREATE TABLE {$PHORUM['user_newflags_table']} (
           user_id                  int unsigned   NOT NULL default '0',
           forum_id                 int unsigned   NOT NULL default '0',
           message_id               int unsigned   NOT NULL default '0',

           PRIMARY KEY  (user_id,forum_id,message_id),
           KEY move (message_id, forum_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['groups_table']} (
           group_id                 int unsigned   NOT NULL auto_increment,
           name                     varchar(255)   NOT NULL default '',
           open                     tinyint(1)     NOT NULL default '0',

           PRIMARY KEY  (group_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['forum_group_xref_table']} (
           forum_id                 int unsigned   NOT NULL default '0',
           group_id                 int unsigned   NOT NULL default '0',
           permission               int unsigned   NOT NULL default '0',

           PRIMARY KEY  (forum_id,group_id),
           KEY group_id (group_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['user_group_xref_table']} (
           user_id                  int unsigned   NOT NULL default '0',
           group_id                 int unsigned   NOT NULL default '0',
           status                   tinyint(4)     NOT NULL default '1',

           PRIMARY KEY  (user_id,group_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['files_table']} (
           file_id                  int unsigned   NOT NULL auto_increment,
           user_id                  int unsigned   NOT NULL default '0',
           filename                 varchar(255)   NOT NULL default '',
           filesize                 int unsigned   NOT NULL default '0',
           file_data                mediumtext     NOT NULL,
           add_datetime             int unsigned   NOT NULL default '0',
           message_id               int unsigned   NOT NULL default '0',
           link                     varchar(10)    NOT NULL default '',

           PRIMARY KEY (file_id),
           KEY add_datetime (add_datetime),
           KEY message_id_link (message_id,link),
           KEY user_id_link (user_id,link)
       ) $charset",

      "CREATE TABLE {$PHORUM['banlist_table']} (
           id                       int unsigned   NOT NULL auto_increment,
           forum_id                 int unsigned   NOT NULL default '0',
           type                     tinyint(4)     NOT NULL default '0',
           pcre                     tinyint(1)     NOT NULL default '0',
           string                   varchar(255)   NOT NULL default '',
           comments                 text           NOT NULL,

           PRIMARY KEY (id),
           KEY forum_id (forum_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['search_table']} (
           message_id               int unsigned   NOT NULL default '0',
           forum_id                 int unsigned   NOT NULL default '0',
           search_text              mediumtext     NOT NULL,

           PRIMARY KEY (message_id),
           KEY forum_id (forum_id),
           FULLTEXT KEY search_text (search_text)
       ) ENGINE=MyISAM $charset",

      "CREATE TABLE {$PHORUM['user_custom_fields_table']} (
           user_id                  int unsigned   NOT NULL default '0',
           type                     int unsigned   NOT NULL default '0',
           data                     text           NOT NULL,

           PRIMARY KEY (user_id, type)
       ) $charset",

      "CREATE TABLE {$PHORUM['pm_messages_table']} (
           pm_message_id            int unsigned   NOT NULL auto_increment,
           user_id                  int unsigned   NOT NULL default '0',
           author                   varchar(255)   NOT NULL default '',
           subject                  varchar(100)   NOT NULL default '',
           message                  text           NOT NULL,
           datestamp                int unsigned   NOT NULL default '0',
           meta                     mediumtext     NOT NULL,

           PRIMARY KEY (pm_message_id),
           KEY user_id (user_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['pm_folders_table']} (
           pm_folder_id             int unsigned   NOT NULL auto_increment,
           user_id                  int unsigned   NOT NULL default '0',
           foldername               varchar(20)    NOT NULL default '',

           PRIMARY KEY (pm_folder_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['pm_xref_table']} (
           pm_xref_id               int unsigned   NOT NULL auto_increment,
           user_id                  int unsigned   NOT NULL default '0',
           pm_folder_id             int unsigned   NOT NULL default '0',
           special_folder           varchar(10)        NULL default NULL,
           pm_message_id            int unsigned   NOT NULL default '0',
           read_flag                tinyint(1)     NOT NULL default '0',
           reply_flag               tinyint(1)     NOT NULL default '0',

           PRIMARY KEY (pm_xref_id),
           KEY xref (user_id,pm_folder_id,pm_message_id),
           KEY read_flag (read_flag)
       ) $charset",

      "CREATE TABLE {$PHORUM['pm_buddies_table']} (
           pm_buddy_id              int unsigned   NOT NULL auto_increment,
           user_id                  int unsigned   NOT NULL default '0',
           buddy_user_id            int unsigned   NOT NULL default '0',

           PRIMARY KEY pm_buddy_id (pm_buddy_id),
           UNIQUE KEY userids (user_id, buddy_user_id),
           KEY buddy_user_id (buddy_user_id)
       ) $charset",

      "CREATE TABLE {$PHORUM['message_tracking_table']} (
           track_id                 int unsigned   NOT NULL auto_increment,
           message_id               int unsigned   NOT NULL default '0',
           user_id                  int unsigned   NOT NULL default '0',
           time                     int unsigned   NOT NULL default '0',
           diff_body                text               NULL ,
           diff_subject             text               NULL ,

           PRIMARY KEY track_id (track_id),
           KEY message_id (message_id)
       ) $charset",
    );

    foreach ($create_table_queries as $sql) {
        $error = phorum_db_interact(DB_RETURN_ERROR, $sql, NULL, DB_MASTERQUERY);
        if ($error !== NULL) {
            return $error;
        }
    }

    return NULL;
}
// }}}

// {{{ Function: phorum_db_maxpacketsize()
/**
 * This function is used by the sanity checking system in the admin
 * interface to determine how much data can be transferred in one query.
 * This is used to detect problems with uploads that are larger than the
 * database server can handle. The function returns the size in bytes.
 * For database implementations which do not have this kind of limit,
 * NULL can be returned.
 *
 * @return integer
 *     The maximum packet size in bytes.
 */
function phorum_db_maxpacketsize()
{
    $maxsize = phorum_db_interact(
        DB_RETURN_VALUE,
        'SELECT @@global.max_allowed_packet',
        NULL,
        DB_MASTERQUERY
    );

    return $maxsize;
}
// }}}

// {{{ Function: phorum_db_sanitychecks()
/**
 * This function is used by the sanity checking system to let the
 * database layer do sanity checks of its own. This function can
 * be used by every database layer to implement specific checks.
 *
 * The return value for this function should be exactly the same
 * as the return value expected for regular sanity checking
 * function (see include/admin/sanity_checks.php for information).
 *
 * There's no need to load the sanity_check.php file for the needed
 * constants, because this function should only be called from the
 * sanity checking system.
 *
 * @return array
 *     A return value as expected by Phorum's sanity checking system.
 */
function phorum_db_sanitychecks()
{
    global $PHORUM;

    // For Phorum 5.2+, we need the "charset" option to be set
    // in the config.php.
    if (!isset($PHORUM['DBCONFIG']['charset'])) return array(
        PHORUM_SANITY_CRIT,
        "Database configuration parameter \"charset\" missing.",
        "The option \"charset\" is missing in your database configuration.
         This might indicate that you are using a config.php from an
         older Phorum version, which does not yet contain this option.
         Please, copy include/db/config.php.sample to
         include/db/config.php and edit this new config.php. Read
         Phorum's install.txt for installation instructions."
    );

    // Retrieve the MySQL server version.
    $version = phorum_db_interact(
        DB_RETURN_VALUE,
        'SELECT @@global.version',
        NULL,
        DB_MASTERQUERY
    );
    if (!$version) return array(
        PHORUM_SANITY_WARN,
        "The database layer could not retrieve the version of the
         running MySQL server",
        "This probably means that you are running a really old MySQL
         server, which does not support \"SELECT @@global.version\"
         as a SQL command. If you are not running a MySQL server
         with version 4.0.18 or higher, then please upgrade your
         MySQL server. Else, contact the Phorum developers to see
         where this warning is coming from"
    );

    // See if we recognize the version numbering.
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)/', $version, $ver)) return array(
        PHORUM_SANITY_WARN,
        "The database layer was unable to recognize the MySQL server's
         version number \"" . htmlspecialchars($version) . "\". Therefore,
         checking if the right version of MySQL is used is not possible.",
        "Contact the Phorum developers and report this specific
         version number, so the checking scripts can be updated."
    );

    // MySQL before version 4.
    if ($ver[1] < 5) return array(
        PHORUM_SANITY_CRIT,
        "The MySQL database server that is used is too old. The
         running version is \"" . htmlspecialchars($version) . "\",
         while MySQL version 5.0.x or higher is required.",
        "Upgrade your MySQL server to a newer version. If your
         website is hosted with a service provider, please contact
         the service provider to upgrade your MySQL database."
    );

    // THE FOLLOWING TWO CHECKS ARE NO LONGER NEEDED WITH THE ABOVE CHECK
    // MAKING MYSQL5 A REQUIREMENT

    // MySQL before version 4.0.18, with full text search enabled.
    /*
    if (isset($PHORUM['DBCONFIG']['mysql_use_ft']) &&
        $PHORUM['DBCONFIG']['mysql_use_ft'] &&
        $ver[2] == 4 && $ver[2] == 0 && $ver[3] < 18) return array(
        PHORUM_SANITY_WARN,
        "The MySQL database server that is used does not
         support all Phorum features. The running version is
         \"" . htmlspecialchars($version) . "\", while MySQL version
         4.0.18 or higher is recommended.",
        "Upgrade your MySQL server to a newer version. If your
         website is hosted with a service provider, please contact
         the service provider to upgrade your MySQL database."
    );

    // MySQL before version 5.0
    if ($ver[1] < 5) return array(
        PHORUM_SANITY_WARN,
        "The MySQL database server that is used does not
         support all Phorum features. The running version is
         \"" . htmlspecialchars($version) . "\", while MySQL version
         5.0 or higher is recommended. MySQL has discontinued active development
         for all versions below 5.0. The Phorum teams uses 5.0 for all
         development. Phorum has been known to work with MySQL 4.1 and some
         later 4.0 versions. However, there is no testing with these versions.
         It is recommended that all users upgrade to 5.0 as soon as possible
         to get the most out of MySQL and Phorum.",
        "Upgrade your MySQL server to a newer version. If your
         website is hosted with a service provider, please contact
         the service provider to upgrade your MySQL database."
    );
    */

    // All checks are okay.
    return array (PHORUM_SANITY_OK, NULL);
}
// }}}

// ----------------------------------------------------------------------
// Load specific code for the required PHP database module.
// ----------------------------------------------------------------------

// PHP has support for MySQL connections through multiple extensions.
// If the config.php specifies a PHP database extension, then this one is
// used for loading the specific PHP database extension code. Otherwise,
// we try to auto-detect which one is available.

$ext = NULL;
// could be unset in Phorum < 5.2.7
if(!isset($PHORUM['DBCONFIG']['socket'])) {
    $PHORUM['DBCONFIG']['socket']=NULL;
}
if(!isset($PHORUM['DBCONFIG']['port'])) {
    $PHORUM['DBCONFIG']['port']=NULL;
}

if (isset($PHORUM['DBCONFIG']['mysql_php_extension'])) {
   $ext = basename($PHORUM['DBCONFIG']['mysql_php_extension']);
} elseif (function_exists('mysqli_connect')) {
   $ext = "mysqli";
} elseif (function_exists('mysql_connect')) {
   $ext = "mysql";

   // build the right hostname for the mysql extension
   // not having separate args for port and socket
   if(!empty($PHORUM['DBCONFIG']['socket'])) {
       $PHORUM['DBCONFIG']['server'].=":".$PHORUM['DBCONFIG']['socket'];
   } elseif(!empty($PHORUM['DBCONFIG']['port'])) {
       $PHORUM['DBCONFIG']['server'].=":".$PHORUM['DBCONFIG']['port'];
   }
} else {
   // Up to here, no PHP extension was found. This probably means that no
   // MySQL extension is loaded. Here we'll try to dynamically load an
   // extension ourselves.
   if(function_exists('dl')) {
	   @dl("mysqli.so");
	   if (function_exists('mysqli_connect')) {
	       $ext = "mysqli";
	   } else {
	       @dl("mysql.so");
	       if (function_exists('mysql_connect')) {
	           $ext = "mysql";
	       }
	   }
   }
}

// If we have no extension by now, we are very much out of luck.
if ($ext === NULL) trigger_error(
   "The Phorum MySQL database layer is unable to determine the PHP " .
   "MySQL extension to use. This might indicate that there is no " .
   "extension loaded from the php.ini.",
   E_USER_ERROR
);

// Load the specific code for the PHP extension that we use.
$extfile = "./include/db/mysql/{$ext}.php";
if (!file_exists($extfile)) trigger_error(
   "The Phorum MySQL database layer is unable to find the extension " .
   "file $extfile on the system. Check if all Phorum files are uploaded " .
   "and if you did specify the correct \"mysql_php_extension\" in the file " .
   "include/db/config.php (valid options are \"mysql\" and \"mysqli\").",
   E_USER_ERROR
);
include($extfile);

?>
