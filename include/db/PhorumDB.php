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
 * This script implements the base class for the Phorum database layers.
 *
 * The other Phorum code does not care how data is stored.
 * The only requirement is that it is returned from these methods
 * in the right way. This means each database-specific implementation
 * can use as many or as few tables as it likes. It can store the
 * fields anyway it wants.
 *
 * The only thing to worry about is the table_prefix for the tables.
 * All tables for a Phorum install should be prefixed with the
 * table_prefix that will be entered in include/config/database.php.
 * This will allow multiple Phorum installations to use the same database
 * and it will prevent table name colissions with other software packages.
 *
 * Derived layers must at least implement the methods interact() and
 * fetch_row(), which define the core of the database interaction.
 * Other methods can be overridden to accommodate for the the specifics
 * of the database system (e.g. to implement speed optimized queries.)
 *
 * @todo
 *     phorum_api_user_check_access() is used in this layer, but the
 *     include file for that is not included here. Keep it like that
 *     or add the required include? Or is it functionality that doesn't
 *     belong here and could better go into the core maybe?
 *
 * @package    PhorumDBLayer
 * @copyright  2016, Phorum Development Team
 * @license    Phorum License, http://www.phorum.org/license.txt
 */

// Bail out if we're not loaded from the Phorum code.
if (!defined('PHORUM')) return;

// Make sure that PHORUM_PATH is defined (in case the db layer is
// included separately from the Phorum core code.)
defined('PHORUM_PATH') or define('PHORUM_PATH', dirname(__FILE__).'/../..');

// ----------------------------------------------------------------------
// Definitions
// ----------------------------------------------------------------------

// {{{ Constant and variable definitions

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return a database connection handle.
 */
define('DB_RETURN_CONN',     0);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return a SQL quoted value.
 */
define('DB_RETURN_QUOTED',   1);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return the query statement handle for a SQL query.
 */
define('DB_RETURN_RES',      2);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return a single database row for a SQL query.
 */
define('DB_RETURN_ROW',      3);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return an array of rows for a SQL query.
 */
define('DB_RETURN_ROWS',     4);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return a single database row for a SQL query
 * as an associative array
 */
define('DB_RETURN_ASSOC',    5);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return an array of rows for a SQL query
 * as associative arrays.
 */
define('DB_RETURN_ASSOCS',   6);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return a single value for a SQL query.
 */
define('DB_RETURN_VALUE',    7);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return the number of selected rows for a SQL query.
 */
define('DB_RETURN_ROWCOUNT', 8);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return the new auto_increment id value for
 * an insert SQL query.
 */
define('DB_RETURN_NEWID',    9);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function return an error for a SQL query or NULL if there
 * was no error.
 */
define('DB_RETURN_ERROR',   10);

/**
 * Function call parameter $return for {@link PhorumDBLayer::interact()}.
 * Makes the function close the connection to the database.
 * The function will return no data.
 */
define('DB_CLOSE_CONN',     11);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter
 * that indicates that a connection failure is not a fatal error. Instead,
 * the function will return FALSE on error.
 */
define('DB_NOCONNECTOK',     1);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter
 * that indicates that missing table errors are not fatal errors. Instead,
 * the function will return FALSE on error.
 */
define('DB_MISSINGTABLEOK',  2);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter
 * that indicates that duplicate field errors are not fatal errors. Instead,
 * the function will return FALSE on error.
 * Duplicate field errors occur when a field is added to a table, when
 * that field already exists in the table schema.
 */
define('DB_DUPFIELDNAMEOK',  4);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter
 * that indicates that duplicate key name errors are not fatal errors. Instead,
 * the function will return FALSE on error.
 * Duplicate key name errors occur when an index is added to a table, when
 * an index by the same name is already defined for the table schema.
 */
define('DB_DUPKEYNAMEOK',    8);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter
 * that indicates that duplicate key errors are not fatal errors. Instead,
 * the function will return FALSE on error.
 */
define('DB_DUPKEYOK',       16);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter
 * that indicates that table exist errors are not fatal errors. Instead,
 * the function will return FALSE on error.
 */
define('DB_TABLEEXISTSOK',  32);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter.
 * This flag is not in use by the Phorum core code. It is in use for
 * some specialized proprietary db setup.
 */
define('DB_GLOBALQUERY',    64);

/**
 * Constant for the PhorumDBLayer::interact() function call $flags parameter
 * that indicates that this query has to be run on a cluster master
 * server. This is used by the include/db/mysqli_replication.php database
 * backend.
 */
define('DB_MASTERQUERY',   128);

/**
 * Constant for the PhorumDBLayer::interact() function call
 * $list_type parameter that indicates that recent message have to
 * be returned.
 */
define('LIST_RECENT_MESSAGES',   0);

/**
 * Constant for the PhorumDBLayer::interact() function call
 * $list_type parameter that indicates that recent new threads have to
 * be returned.
 */
define('LIST_RECENT_THREADS',    1);

/**
 * Constant for the PhorumDBLayer::interact() function call
 * $list_type parameter that indicates that recently updated threads
 * (i.e. where new replies have been posted) have to be returned.
 */
define('LIST_UPDATED_THREADS',   2);

/**
 * Constant for the PhorumDBLayer::interact() function call
 * $list_type parameter that indicates that unread messages have
 * to be returned.
 */
define('LIST_UNREAD_MESSAGES',   3);

// }}}

abstract class PhorumDB
{
    // {{{ Properties

    /**
     * The prefix to use for the table names.
     * This one is filled from the db layer configuration at construction time.
     * @var string
     */
    public $prefix;

    /**
     * Fields from the messags table that must be treated as strings
     * (even if they contain numbers only.)
     * @var array
     */
    protected $_string_fields_message = array(
        'author', 'subject', 'body', 'email'
    );

    /**
     * Fields from the forums table that must be treated as strings
     * (even if they contain numbers only.)
     * @var array
     */
    protected $_string_fields_forum = array(
        'name', 'description', 'template'
    );

    /**
     * Fields from the users table that must be treated as strings
     * (even if they contain numbers only.)
     * @var array
     */
    protected $_string_fields_user = array(
        'username', 'real_name', 'display_name', 'password', 'password_temp',
        'sessid_lt', 'sessid_st', 'email', 'email_temp', 'signature',
        'user_language', 'user_template', 'settings_data'
    );

    /**
     * Whether or not the database system supports "USE INDEX" in a SELECT
     * @var boolean
     */
    protected $_can_USE_INDEX = FALSE;

    /**
     * Whether or not the database system supports "INSERT DELAYED".
     * @var boolean
     */
    protected $_can_INSERT_DELAYED = FALSE;

    /**
     * Whether or not the database system supports "UPDATE IGNORE".
     * @var boolean
     */
    protected $_can_UPDATE_IGNORE = FALSE;

    /**
     * Whether or not the database system supports "INSERT IGNORE".
     * @var boolean
     */
    protected $_can_INSERT_IGNORE = FALSE;

    /**
     * Whether or not the database system supports "TRUNCATE".
     * @var boolean
     */
    protected $_can_TRUNCATE = FALSE;

    /**
     * Whether or not the database system supports multiple inserts
     * in one command like INSERT INTO .. VALUES (set 1), (set 2), .., (set n).
     * @var boolean
     */
    protected $_can_insert_multiple = FALSE;

    /**
     * The method to use for string concatenation. Options are:
     * - "pipes"  : PostgreSQL style
     * - "concat" : MySQL style, using the concat() function
     * @var string
     */
    protected $_concat_method = 'pipes';

    // }}}

    // ----------------------------------------------------------------------
    // Methods that are part of the db layer API
    // ----------------------------------------------------------------------

    // {{{ Method: check_connection()
    /**
     * Checks if a database connection can be made.
     *
     * @return boolean
     *     TRUE if a connection can be made, FALSE otherwise.
     */
    public function check_connection()
    {
        return $this->interact(
          DB_RETURN_CONN,
          NULL, NULL,
          DB_NOCONNECTOK | DB_MASTERQUERY
        ) ? TRUE : FALSE;
    }
    // }}}

    // {{{ Method: close_connection()
    /**
     * Close the database connection.
     */
    public function close_connection()
    {
        $this->interact(DB_CLOSE_CONN);
    }
    // }}}

    // {{{ Method: load_settings()
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
    public function load_settings()
    {
        global $PHORUM;

        // At install time, there is no settings table.
        // So we ignore errors if we do not see that table.
        $settings = $this->interact(
            DB_RETURN_ROWS,
            "SELECT name, data, type
             FROM {$this->settings_table}",
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

    // {{{ Method: update_settings()
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
    public function update_settings($settings)
    {

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

                $field = $this->interact(DB_RETURN_QUOTED, $field);
                $value = $this->interact(DB_RETURN_QUOTED, $value);

                // Try to insert a new settings record.
                $res = $this->interact(
                    DB_RETURN_RES,
                    "INSERT INTO {$this->settings_table}
                            (data, type, name)
                     VALUES ('$value', '$type', '$field')",
                    NULL,
                    DB_DUPKEYOK | DB_MASTERQUERY
                );

                // If no result was returned, then the query failed.
                // This probably means that we already have the settings
                // record in the database. So instead of inserting a record,
                // we need to update one here.
                if (!$res) {
                  $this->interact(
                      DB_RETURN_RES,
                      "UPDATE {$this->settings_table}
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
            __METHOD__ . ': $settings cannot be empty',
            E_USER_ERROR
        );

        return TRUE;
    }
    // }}}

    // {{{ Method: sanitize_mixed()
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
    public function sanitize_mixed(&$var, $type)
    {
        if (is_array($var)) {
            foreach ($var as $id => $val) {
                if ($type == 'int') {
                    $var[$id] = (int)$val;
                } else {
                    $var[$id] = $this->interact(DB_RETURN_QUOTED, $val);
                }
            }
        } else {
            if ($type=='int') {
                $var = (int)$var;
            } else {
                $var = $this->interact(DB_RETURN_QUOTED, $var);
            }
        }
    }
    // }}}

    // {{{ Method: validate_field()
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
    function validate_field($field_name)
    {
        $valid = preg_match('!^[a-zA-Z0-9_]+$!', $field_name);
        return (bool)$valid;
    }
    // }}}

    // {{{ Method: run_queries()
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
    public function run_queries($queries)
    {

        $error = NULL;

        foreach ($queries as $sql)
        {
            // Because this function is used from the upgrade scripts,
            // we ignore errors about duplicate fields and keys. That
            // way running the same upgrade scripts twice (in case there
            // were problems during the first run) won't bring up fatal
            // errors in case fields or keys are created a second time.
            $error = $this->interact(
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

    // {{{ Method: get_thread_list()
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
     *     When bodies are included, then custom fields are included as well.
     *
     * @return array
     *     An array of messages, indexed by message id.
     */
    public function get_thread_list($page, $include_bodies=FALSE)
    {
        global $PHORUM;

        settype($page, 'int');

        // The messagefields that we want to fetch from the database.
        $messagefields =
           'author, datestamp, email, message_id, forum_id, meta,
            moderator_post, modifystamp, parent_id, msgid, sort, status,
            subject, thread, thread_count, user_id, viewcount, threadviewcount,
            closed, ip, recent_message_id, recent_user_id, recent_author,
            moved, hide_period';

        // Include the message bodies in the thread list if requested.
        if ($include_bodies) {
            $messagefields .= ',body';
        }

        // The sort mechanism to use.
        if ($PHORUM['float_to_top']) {
            $sortfield = 'modifystamp';
            $index = 'list_page_float';
        } else {
            $sortfield = 'datestamp';
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
            $sql    = NULL;
            $limit  = 0;
            $offset = 0;

            switch ($group)
            {
                // Stickies.
                case 'stickies':

                    $sql = "SELECT $messagefields
                            FROM   {$this->message_table}
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
                    $offset = $page * $limit;

                    $sql = "SELECT $messagefields
                            FROM   {$this->message_table} " .
                            ($this->_can_USE_INDEX ? "USE INDEX ($index)" : "").
                           "WHERE  $sortfield > 0 AND
                                   forum_id = {$PHORUM['forum_id']} AND
                                   status = ".PHORUM_STATUS_APPROVED." AND
                                   parent_id = 0 AND
                                   sort > 1
                            ORDER  BY $sortfield DESC";
                    break;

                // Reply messages.
                case 'replies':

                    // We're done if we did not collect any messages
                    // with replies.
                    if (! count($replymsgids)) break;

                    // replies are always sorted by datestamp and ascending by default
                    $sortorder = "sort, datestamp ASC, message_id";

                    $sql = "SELECT $messagefields
                            FROM   {$this->message_table}
                            WHERE  status = ".PHORUM_STATUS_APPROVED." AND
                                   thread in (" . implode(",",$replymsgids) .")
                            ORDER  BY $sortorder";
                    break;

            } // End of switch ($group)

            // Continue with the next group if no SQL query was formulated.
            if ($sql === NULL) continue;

            // Query the messages for the current group.
            $rows = $this->interact(
                DB_RETURN_ASSOCS, $sql, 'message_id', 0, $limit, $offset);
            $now  = time();
            foreach ($rows as $id => $row)
            {
                // Skip the message if the hide_period has passed.
                if (!empty($row['hide_period']) &&
                    ($row['datestamp'] + $row['hide_period']) < $now) continue;

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

        // Add custom fields to the messages.
        if ($include_bodies && count($messages))
        {
            $custom_fields = $this->get_custom_fields(
                PHORUM_CUSTOM_FIELD_MESSAGE, array_keys($messages));
            foreach ($custom_fields as $message_id => $fields) {
                foreach($fields as $fieldname => $fielddata) {
                    $messages[$message_id][$fieldname] = $fielddata;
                }
            }
        }

        return $messages;
    }
    // }}}

    // {{{ Method: get_recent_messages
    /**
     * Retrieve a list of recent messages for all forums for which the user has
     * read permission, for a particular forum, for a list of forums or for a
     * particular thread. Optionally, only top level thread messages can be
     * retrieved.
     *
     * The original version of this function came from Jim Winstead of mysql.com
     *
     * @param integer $limit
     *     Limit the number of returned messages to this number.
     *
     * @param integer $offset
     *     When using the $limit parameter to limit the number of returned
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
     *     - LIST_UNREAD_MESSAGES: return a list of unread messages
     *
     * @return array
     *     An array of recent messages, indexed by message_id. One special key
     *     "users" is set too. This one contains an array of all involved
     *     user_ids.
     */
    public function get_recent_messages(
        $limit, $offset = 0, $forum_id = 0, $thread = 0,
        $list_type = LIST_RECENT_MESSAGES)
    {
        global $PHORUM;

        // Backward compatibility for the old $threads_only parameter.
        if (is_bool($list_type)) {
            $list_type = $list_type
                       ? LIST_RECENT_THREADS : LIST_RECENT_MESSAGES;
        }

        settype($limit,     'int');
        settype($offset,    'int');
        settype($thread,    'int');
        settype($list_type, 'int');
        $this->sanitize_mixed($forum_id, 'int');

        if ($list_type == LIST_UNREAD_MESSAGES) {
            if (empty($PHORUM['user']['user_id'])) trigger_error(
                __METHOD__ . ": \$list_type parameter LIST_UNREAD_MESSAGES " .
                "used, but no authenticated user available; this feature " .
                "can only be used for authenticated users",
                E_USER_ERROR
            );
        }

        // In case -1 is used as "any" value by the caller.
        if ($forum_id < 0) $forum_id = 0;
        if ($thread   < 0) $thread   = 0;

        // Parameter checking.
        if ($list_type < 0 || $list_type > 3) trigger_error(
            __METHOD__ . ": illegal \$list_type parameter used",
            E_USER_ERROR
        );
        if ($list_type != LIST_RECENT_MESSAGES && $thread) trigger_error(
            __METHOD__ . ":\$thread parameter can only be " .
            "used with \$list_type = LIST_RECENT_MESSAGES",
            E_USER_ERROR
        );

        // We have to check what forums the active Phorum user can read first.
        // Even if a $thread is passed, we have to make sure that the user
        // can read the containing forum. Here we convert the $forum_id
        // argument into an argument that is usable for
        // phorum_api_user_check_access(), in such way that it will always
        // return an array of accessible forum_ids.
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

        // Keep track of the database index that we want to force
        // in order to optimize the query.
        $use_key = NULL;

        // We need to differentiate on which key to use.
        // If selecting on a specific thread, then the best index
        // to use would be the thread_message index.
        if ($thread) {
            $use_key = 'thread_message';
        }
        // Indexes to use if we query exactly one forum.
        elseif (count($allowed_forums) == 1)
        {
            switch ($list_type) {
                case LIST_RECENT_MESSAGES:
                case LIST_UNREAD_MESSAGES:
                    $use_key = 'forum_recent_messages';
                    break;
                case LIST_RECENT_THREADS:
                    $use_key = 'list_page_flat';
                    break;
                case LIST_UPDATED_THREADS:
                    $use_key = 'list_page_float';
                    break;
            }
        }
        // Indexes to use if we query more than one forum.
        else
        {
            switch ($list_type) {
                case LIST_RECENT_MESSAGES:
                case LIST_UNREAD_MESSAGES:
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
        $sql = "SELECT msg.* FROM {$this->message_table} msg";

        if ($this->_can_USE_INDEX && $use_key !== NULL) {
            $sql .= " USE INDEX ($use_key)";
        }

        if ($list_type == LIST_UNREAD_MESSAGES) {
            $sql .=
                " LEFT JOIN {$this->user_newflags_min_id_table} min
                  ON msg.forum_id = min.forum_id AND
                  min.user_id = " . (int) $PHORUM['user']['user_id'] .
                " LEFT JOIN {$this->user_newflags_table} new
                  ON msg.message_id = new.message_id AND
                  new.user_id = " . (int) $PHORUM['user']['user_id'];

        }

        $sql .= " WHERE msg.status = ".PHORUM_STATUS_APPROVED;

        // When we are retrieving unread messages, we need to find out
        // how many new messages we have and what forums contain those
        // new messages. This is a relatively light query, which' output
        // can be used to greatly improve the query that we need to
        // run here.
        if ($list_type == LIST_UNREAD_MESSAGES)
        {
            $tmp = $this->get_forums(
                $allowed_forums, NULL, NULL, NULL, NULL, 2);
            $tmp = phorum_api_newflags_apply_to_forums(
                $tmp, PHORUM_NEWFLAGS_COUNT);

            $unread_count = 0;
            $unread_forums = array();
            foreach ($tmp as $f) {
                if (!empty($f['new_messages'])) {
                    $unread_count += $f['new_messages'];
                    $unread_forums[$f['forum_id']] =  $f['forum_id'];
                }
            }

            // No new messages? Then we're done here.
            if ($unread_count == 0) {
                return array();
            }

            // Otherwise, update the query parameters to improve
            // the unread messages query.
            if ($unread_count < $limit) $limit = $unread_count;
            $allowed_forums = $unread_forums;
        }

        if (count($allowed_forums) == 1) {
            $sql .= " AND msg.forum_id = " . array_shift($allowed_forums);
        } else {
            $sql .= " AND msg.forum_id IN (".implode(",", $allowed_forums).")";
        }

        if ($thread) {
            $sql .= " AND msg.thread = $thread";
        }

        $sql .= " AND msg.moved = 0";

        if ($list_type == LIST_UNREAD_MESSAGES) {
            $sql .= " AND (min_id IS NULL OR msg.message_id > min_id)
                      AND new.message_id IS NULL";
        }

        if ($list_type == LIST_RECENT_THREADS ||
            $list_type == LIST_UPDATED_THREADS) {
            $sql .= ' AND msg.parent_id = 0';
        }

        if ($list_type == LIST_UPDATED_THREADS) {
            $sql .= ' ORDER BY msg.modifystamp DESC';
        } else {
            $sql .= ' ORDER BY msg.datestamp DESC';
        }

        // Retrieve matching messages from the database.
        $messages = $this->interact(
            DB_RETURN_ASSOCS, $sql, 'message_id', NULL, $limit, $offset);

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

    // {{{ Method: get_unapproved_list()
    /**
     * Retrieve a list of messages which have not yet been approved by a moderator.
     *
     * NOTE: ALL dates must be returned as Unix timestamps
     *
     * @param integer $forum_id     - The forum id to work with or NULL in case all
     *                                forums have to be searched. You can also pass an
     *                                array of forum ids.
     * @param boolean $on_hold_only - Only take into account messages which have to
     *                                be approved directly after posting. Do not include
     *                                messages which were hidden by a moderator.
     * @param integer $moddays      - Limit the search to the last $moddays number of days.
     * @param boolean $countonly    - Return only a count of the possible results
     *
     * @return                      - An array of messages, indexed by message id.
     */
    public function get_unapproved_list($forum_id = NULL, $on_hold_only=FALSE, $moddays=0, $countonly = FALSE)
    {

        settype($on_hold_only, 'bool');
        settype($moddays, 'int');
        settype($countonly, 'bool');
        $this->sanitize_mixed($forum_id, 'int');

        // Select a message count or full message records?
        $sql = 'SELECT ' . ($countonly ? 'count(*) ' : '* ') .
               'FROM ' . $this->message_table . ' WHERE ';

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
            $sql = "$sql status = ".PHORUM_STATUS_HOLD." UNION " .
                   "$sql status = ".PHORUM_STATUS_HIDDEN;
        }

        if (!$countonly) {
            $sql .= ' ORDER BY thread, datestamp';
        }

        // Retrieve and return data for counting unapproved messages.
        if ($countonly) {
            $count_per_status = $this->interact(DB_RETURN_ROWS, $sql);
            $sum = 0;
            foreach ($count_per_status as $count) $sum += $count[0];
            return $sum;
        }

        // Retrieve unapproved messages.
        $messages = $this->interact(DB_RETURN_ASSOCS, $sql, 'message_id');

        // Post processing of received messages.
        foreach ($messages as $id => $message) {
            $messages[$id]['meta'] = empty($message['meta'])
                                   ? array()
                                   : unserialize($message['meta']);
        }

        // Add custom fields to the messages.
        if (!$countonly && count($messages))
        {
            $custom_fields = $this->get_custom_fields(
                PHORUM_CUSTOM_FIELD_MESSAGE, array_keys($messages));
            foreach ($custom_fields as $message_id => $fields) {
                foreach($fields as $fieldname => $fielddata) {
                    $messages[$message_id][$fieldname] = $fielddata;
                }
            }
        }

        return $messages;
    }
    // }}}

    // {{{ Method: post_message()
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
    public function post_message(&$message, $convert=FALSE)
    {
        global $PHORUM;

        settype($convert, 'bool');

        foreach ($message as $key => $value) {
            if (is_numeric($value) &&
                !in_array($key, $this->_string_fields_message)) {
                $message[$key] = (int)$value;
            } elseif (is_array($value)) {
                $value = serialize($value);
                $message[$key] = $this->interact(DB_RETURN_QUOTED, $value);
            } else {
                $message[$key] = $this->interact(DB_RETURN_QUOTED, $value);
            }
        }

        // When converting messages, the post time should be in the message.
        $NOW = $convert ? $message['datestamp'] : time();

        // Check for duplicate posting of messages, unless we are converting a db.
        if (
            isset($PHORUM['check_duplicate']) &&
            $PHORUM['check_duplicate'] &&
            !$convert
        ) {
            // Check for duplicate messages in the last hour.
            $check_timestamp = $NOW - 3600;
            $sql = "SELECT message_id
                    FROM   {$this->message_table}
                    WHERE  forum_id  = {$message['forum_id']} AND
                           author    ='{$message['author']}' AND
                           subject   ='{$message['subject']}' AND
                           body      ='{$message['body']}' AND
                           datestamp > $check_timestamp";

            // Return 0 if at least one message can be found.
            if ($this->interact(DB_RETURN_ROWCOUNT, $sql) > 0) return 0;
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
            'moved'          => 0,
            'hide_period'    => 0
        );

        // The meta field is optional.
        if (isset($message['meta'])) {
            $insertfields['meta'] = "'{$message['meta']}'";
        }

        // The moved field is optional.
        if (!empty($message['moved'])) {
            $insertfields['moved'] = 1;
            $insertfields['hide_period'] = (int)$message['hide_period'];
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

        $customfields=array();
        foreach ($message as $key => $value) {
            if (!isset($insertfields[$key])) {
                $customfields[$key] = $value;
            }
        }

        // Insert the message and get the new message_id.
        $message_id = $this->interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$this->message_table}
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
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET    thread     = $message_id
                 WHERE  message_id = $message_id",
                NULL,
                DB_MASTERQUERY
            );

            $message['thread'] = $message_id;
        }

        if (count($customfields)) {
            $this->save_custom_fields(
                $message_id,PHORUM_CUSTOM_FIELD_MESSAGE,$customfields);
        }

        if (empty($PHORUM['DBCONFIG']['empty_search_table']))
        {
            // Full text searching updates.
            $search_text = $message['author']  .' | '.
                           $message['subject'] .' | '.
                           $message['body'];

            $INSERT = $this->_can_INSERT_DELAYED ? 'INSERT DELAYED' : 'INSERT';
            $this->interact(
                DB_RETURN_RES,
                "$INSERT INTO {$this->search_table}
                        (message_id, forum_id, search_text)
                 VALUES ({$message['message_id']}, {$message['forum_id']},
                         '$search_text')",
                NULL,
                DB_MASTERQUERY
            );
        }

        return $message_id;
    }
    // }}}

    // {{{ Method: update_message()
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
    public function update_message($message_id, $message)
    {
        global $PHORUM;

        settype($message_id, 'int');

        if (count($message) == 0) trigger_error(
            '$message cannot be empty in phorum_update_message()',
            E_USER_ERROR
        );

        $customfields=array();
        $fields = array();

        foreach ($message as $field => $value)
        {
            if ($this->validate_field($field))
            {
                require_once PHORUM_PATH . '/include/api/custom_field.php';
                $custom = phorum_api_custom_field_byname(
                    $field, PHORUM_CUSTOM_FIELD_MESSAGE);

                if ($custom === null)
                {
                    if (is_numeric($value) &&
                        !in_array($field, $this->_string_fields_message)) {
                        $fields[] = "$field = $value";
                    } elseif (is_array($value)) {
                        $value = $this->interact(
                            DB_RETURN_QUOTED, serialize($value));
                        $message[$field] = $value;
                        $fields[] = "$field = '$value'";
                    } else {
                        $value = $this->interact(DB_RETURN_QUOTED, $value);
                        $message[$field] = $value;
                        $fields[] = "$field = '$value'";
                    }
                }
                else {
                    $customfields[$field] = $value;
                }
            }
        }

        if(count($fields)) {
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET " . implode(', ', $fields) . "
                 WHERE message_id = $message_id",
                NULL,
                DB_MASTERQUERY
            );
        }

        if (count($customfields)) {
            $this->save_custom_fields($message_id, PHORUM_CUSTOM_FIELD_MESSAGE, $customfields);
        }

        // Full text searching updates.
        if (!empty($PHORUM['DBCONFIG']['mysql_use_ft']) &&
            isset($message['author']) &&
            isset($message['subject']) &&
            isset($message['body']) &&
            empty($PHORUM['DBCONFIG']['empty_search_table'])) {

            $search_text = $message['author']  .' | '.
                           $message['subject'] .' | '.
                           $message['body'];

            // Try to insert a new record.
            $INSERT = $this->_can_INSERT_DELAYED ? 'INSERT DELAYED' : 'INSERT';
            $res = $this->interact(
                DB_RETURN_RES,
                "$INSERT INTO {$this->search_table}
                         (message_id, forum_id, search_text)
                 VALUES  ($message_id, {$message['forum_id']}, '$search_text')",
                NULL,
                DB_DUPKEYOK | DB_MASTERQUERY
            );
            // If no result was returned, then the query failed. This probably
            // means that we already have a record in the database.
            // So instead of inserting a record, we need to update one here.
            if (!$res) {
                $this->interact(
                    DB_RETURN_RES,
                    "UPDATE {$this->search_table}
                     SET    search_text = '$search_text'
                     WHERE  forum_id    = {$message['forum_id']} AND
                            message_id  = $message_id",
                    NULL,
                    DB_MASTERQUERY
                );
            }
        }
    }
    // }}}

    // {{{ Method: delete_message()
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
     *
     * @return array - An array of the message-ids deleted
     */
    public function delete_message($message_id, $mode = PHORUM_DELETE_MESSAGE)
    {

        settype($message_id, 'int');
        settype($mode, 'int');

        // Find the info for the message that has to be deleted.
        $msg = $this->interact(
            DB_RETURN_ASSOC,
            "SELECT forum_id, message_id, thread, parent_id
             FROM   {$this->message_table}
             WHERE  message_id = $message_id"
        );

        // The message was not found in the database. Since this is the
        // situation that we want to end up with, consider this an okay
        // situation. If we would trigger an error here, moderators that
        // accidentally try to remove the same message twice (or two
        // moderators that try to delete the same message) would get
        // a nasty error message as a result.
        if (empty($msg)) return array();

        // Find all message_ids that have to be deleted, based on the mode.
        if ($mode == PHORUM_DELETE_TREE) {
            $mids = $this->get_messagetree($message_id, $msg['forum_id']);
            $where = "message_id IN ($mids)";
            $mids = explode(',', $mids);
        } else {
            $mids = array($message_id);
            $where = "message_id = $message_id";
        }

        // First, the messages are unapproved, so replies will not get posted
        // during the time that we need for deleting them. There is still a
        // race condition here, but this already makes things quite reliable.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->message_table}
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
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET    parent_id = {$msg['parent_id']}
                 WHERE  forum_id  = {$msg['forum_id']} AND
                        parent_id = {$msg['message_id']}",
                NULL,
                DB_MASTERQUERY
            );
        }

        // Delete the messages.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->message_table}
             WHERE $where",
            NULL,
            DB_MASTERQUERY
        );

        // Delete the read flags.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->user_newflags_table}
             WHERE $where",
            NULL,
            DB_MASTERQUERY
        );

        // Delete the edit tracking.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->message_tracking_table}
             WHERE $where",
            NULL,
            DB_MASTERQUERY
        );

        // Full text searching updates.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->search_table}
             WHERE $where",
            NULL,
            DB_MASTERQUERY
        );
        if ($mode != PHORUM_DELETE_TREE) {
            $mids = array($message_id);
        }
        // delete custom profile fields
        if (count($mids)) {
            $this->delete_custom_fields(PHORUM_CUSTOM_FIELD_MESSAGE,$mids);
        }

        // It kind of sucks to have this here, but it is the best way
        // to ensure that thread info gets updated if messages are deleted.
        // Leave this include down here, so it is included conditionally.
        require_once PHORUM_PATH.'/include/api/thread.php';
        phorum_api_thread_update_metadata($thread);

        // We need to delete the subscriptions for the thread too.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->subscribers_table}
             WHERE forum_id > 0 AND thread = $thread",
            NULL,
            DB_MASTERQUERY
        );

        // This function will be slow with a lot of messages.
        $this->update_forum_stats(TRUE);

        return $mids;
    }
    // }}}

    // {{{ Method: get_messagetree()
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
    public function get_messagetree($message_id, $forum_id)
    {
        settype($message_id, 'int');
        settype($forum_id, 'int');

        // Find all children for the provided message_id.
        $child_ids = $this->interact(
            DB_RETURN_ROWS,
            "SELECT message_id
             FROM {$this->message_table}
             WHERE forum_id  = $forum_id AND
                   parent_id = $message_id"
        );

        // Recursively build the message tree.
        $tree = "$message_id";
        foreach ($child_ids as $child_id) {
            $tree .= ',' . $this->get_messagetree($child_id[0], $forum_id);
        }

        return $tree;
    }
    // }}}

    // {{{ Method: get_message()
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
    public function get_message(
        $value, $field = 'message_id', $ignore_forum_id = FALSE,
        $write_server = FALSE)
    {
        global $PHORUM;

        $limit  = 0;

        $this->sanitize_mixed($value, 'string');
        settype($ignore_forum_id, 'bool');
        if (!$this->validate_field($field)) trigger_error(
            __METHOD__ . ': Illegal database field ' .
            '"' . htmlspecialchars($field) . '"', E_USER_ERROR
        );

        $forum_id_check = '';
        if (!$ignore_forum_id && !empty($PHORUM['forum_id'])) {
            $forum_id_check = "forum_id = {$PHORUM['forum_id']} AND ";
        }

        if (is_array($value)) {
            $multiple = TRUE;
            $checkvar = "$field IN ('".implode("','",$value)."')";
        } else {
            $multiple = FALSE;
            $checkvar = "$field = '$value'";
            $limit = 1;
        }

        $return = $multiple ? array() : NULL;

        if ($write_server) {
            $flags = DB_MASTERQUERY;
        } else {
            $flags = 0;
        }

        $messages = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM   {$this->message_table}
             WHERE  $forum_id_check $checkvar",
            NULL,
            $flags, $limit
        );

        foreach ($messages as $message)
        {
            $message['meta'] = empty($message['meta'])
                             ? array()
                             : unserialize($message['meta']);

            if (! $multiple) {
                $return[$message['message_id']] = $message;
                break;
            }

            $return[$message['message_id']] = $message;
        }

        // Add custom fields to the messages.
        if (count($return))
        {
            $custom_fields = $this->get_custom_fields(
                PHORUM_CUSTOM_FIELD_MESSAGE, array_keys($return), $flags);
            foreach ($custom_fields as $message_id => $fields) {
                foreach($fields as $fieldname => $fielddata) {
                    $return[$message_id][$fieldname] = $fielddata;
                }
            }
        }

        if (! $multiple) {
            $return = empty($return) ? NULL : array_shift($return);
        }

        return $return;
    }
    // }}}

    // {{{ Method: get_messages()
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
     *     This value can be set to true to specify that the message should be
     *     retrieved from the master (aka write-server) in case replication
     *     is used
     *
     * @param boolean $get_custom_fields
     *     This value can be set to false to specify that no custom fields
     *     should be retrieved for the message, avoids another query and
     *     therefore speed *     up things
     *
     * @return array
     *     An array of messages, indexed by message_id. One special key "users"
     *     is set too. This one contains an array of all involved user_ids.
     */
    public function get_messages($thread, $page = 0, $ignore_mod_perms = FALSE, $write_server = FALSE, $get_custom_fields = TRUE)
    {
        global $PHORUM;

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
                FROM   {$this->message_table}
                WHERE  $forum_id_check
                       thread = $thread
                       $approvedval
                ORDER  BY datestamp";

        // Handle the page offset.
        if ($page > 0) {
            $offset = $PHORUM['read_length'] * ($page-1);
            $limit  = $PHORUM['read_length'];
        } else {
            $offset = 0;
            $limit  = 0;
        }

        if ($write_server) {
            $flags = DB_MASTERQUERY;
        } else {
            $flags = 0;
        }

        $messages = $this->interact(
            DB_RETURN_ASSOCS, $sql, 'message_id', $flags, $limit, $offset);
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
            $starter = $this->interact(
                DB_RETURN_ASSOC,
                "SELECT *
                 FROM   {$this->message_table}
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

        // Add custom fields to the messages.
        if (count($messages) && $get_custom_fields)
        {
            $custom_fields = $this->get_custom_fields(
                PHORUM_CUSTOM_FIELD_MESSAGE, array_keys($messages), $flags);
            foreach ($custom_fields as $message_id => $fields) {
                foreach($fields as $fieldname => $fielddata) {
                    $messages[$message_id][$fieldname] = $fielddata;
                }
            }
        }

        // Store the involved users in the message array.
        $messages['users'] = $involved_users;

        return $messages;
    }
    // }}}

    // {{{ Method: get_message_index()
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
    public function get_message_index($thread=0, $message_id=0)
    {
        global $PHORUM;

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

        $index = $this->interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM   {$this->message_table}
             WHERE  $forum_id_check
                    thread = $thread
                    $approvedval AND
                    message_id <= $message_id"
        );

        return $index;
    }
    // }}}

    // {{{ Method: search()
    //
    // Note: This is a basic implementation of the search method for Phorum.
    // We tried to create a method that works for a wide range of
    // database systems.
    //
    // When the database system is capable of more sophisticated
    // search mechanisms (e.g. by applying full text search indexing),
    // this method can be overridden in derived database layers.
    //
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
     * @param integer $page
     *     The result page offset starting with 0.
     *
     * @param integer $limit
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
     *       $page and page $limit. The messages are indexed by message_id.
     */
    public function search(
        $search, $author, $return_threads, $page, $limit,
        $match_type, $days, $match_forum)
    {
        global $PHORUM;

        $search = trim($search);
        $author = trim($author);
        settype($return_threads, 'bool');
        settype($page, 'int');
        settype($limit, 'int');
        settype($days, 'int');

        // For spreading search results over multiple pages.
        $offset = $page * $limit;

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

        // -------------------------------------------------------------------
        // Handle search for user_id only.
        // -------------------------------------------------------------------

        if ($search == '' && $author != '' && $match_type == 'USER_ID')
        {
            $user_id = (int) $author;
            if (empty($user_id)) return $return;

            // Search for messages.
            $where = "user_id = $user_id AND
                      status=".PHORUM_STATUS_APPROVED." AND
                      moved=0";
            if ($return_threads) $where .= " AND parent_id = 0";

            $from_and_where =
                "FROM   {$this->message_table} " .
                ($this->_can_USE_INDEX ? "USE INDEX (user_messages)" : "") .
                "WHERE  $where $forum_where";

            // Retrieve the message rows.
            $rows = $this->interact(
                DB_RETURN_ASSOCS,
                "SELECT *
                        $from_and_where
                 ORDER  BY datestamp DESC",
                "message_id", 0, $limit, $offset
            );

            // Retrieve the number of found messages.
            $count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT count(*) $from_and_where"
            );

            // Fill the return data.
            $return = array("count" => $count, "rows"  => $rows);

            return $return;
        }

        // -------------------------------------------------------------------
        // Handle search for message and subject.
        // -------------------------------------------------------------------

        if ($search != '')
        {
            $match_str = '';
            $tokens = array();

            if ($match_type == "PHRASE")
            {
                $search = str_replace('"', '', $search);
                $match_str = '"'.$this->interact(DB_RETURN_QUOTED, $search).'"';
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
                $norm_terms = preg_split(
                    "/\s+/", $search, 0, PREG_SPLIT_NO_EMPTY);

                // Merge all search terms together.
                $tokens =  array_merge(
                    $quoted_terms, $paren_terms, $norm_terms);
            }

            if (count($tokens))
            {
                $condition = ($match_type == "ALL") ? "AND" : "OR";

                foreach($tokens as $tid => $token) {
                    $tokens[$tid] =
                        $this->interact(DB_RETURN_QUOTED, $token);
                }

                $match_str =
                    "search_text LIKE " .
                    "('%".implode("%' $condition '%", $tokens)."%')";
            }

            $table_name = $this->search_table . "_like_" . md5(microtime());

            $this->interact(
                DB_RETURN_RES,
                "CREATE TEMPORARY TABLE $table_name AS
                   SELECT message_id
                   FROM   {$this->search_table}
                   WHERE  $match_str"
            );
            $this->interact(
                DB_RETURN_RES,
                "CREATE INDEX {$table_name}_idx ON $table_name (message_id)"
            );

            $tables[] = $table_name;
        }

        // -------------------------------------------------------------------
        // Handle search for author.
        // -------------------------------------------------------------------

        if ($author != '')
        {
            $table_name = $this->search_table . "_author_" . md5(microtime());

            // Search either by user_id or by username.
            if ($match_type == "USER_ID") {
                $author = (int) $author;
                $author_where = "user_id = $author";
            } else {
                $author = $this->interact(DB_RETURN_QUOTED, $author);
                $author_where = "author = '$author'";
            }

            $this->interact(
                DB_RETURN_RES,
                "CREATE TEMPORARY TABLE $table_name AS
                   SELECT message_id
                   FROM   {$this->message_table}
                   WHERE  $author_where"
            );
            $this->interact(
                DB_RETURN_RES,
                "CREATE INDEX {$table_name}_idx ON $table_name (message_id)"
            );

            $tables[] = $table_name;
        }

        // -------------------------------------------------------------------
        // Gather the results.
        // -------------------------------------------------------------------

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
                $table = $this->search_table . "_final_" . md5(microtime());

                $joined_tables = "";
                $main_table = array_shift($tables);
                foreach ($tables as $tbl) {
                    $joined_tables .=
                        "INNER JOIN $tbl " .
                        "ON $main_table.message_id = $tbl.message_id";
                }

                $this->interact(
                    DB_RETURN_RES,
                    "CREATE TEMPORARY TABLE $table AS
                       SELECT m.message_id
                       FROM   $main_table m $joined_tables"
                );
                $this->interact(
                    DB_RETURN_RES,
                    "CREATE INDEX {$table}_idx ON $table (message_id)"
                );
            }

            // When only threads need to be returned, then join the results
            // that we have so far with the message table into a result set
            // that only contains the threads for the results.
            if ($return_threads)
            {
                $threads_table = $this->search_table .
                                 "_final_threads_" . md5(microtime());
                $this->interact(
                    DB_RETURN_RES,
                    "CREATE TEMPORARY TABLE $threads_table AS
                       SELECT distinct thread AS message_id
                       FROM   {$this->message_table}
                              INNER JOIN $table
                              ON {$this->message_table}.message_id =
                                 $table.message_id"
                );
                $this->interact(
                    DB_RETURN_RES,
                    "CREATE INDEX {$threads_table}_idx
                     ON $threads_table (message_id)"
                );

                $table = $threads_table;
            }

            $from_and_where =
                "FROM   {$this->message_table}
                        INNER JOIN $table
                        ON {$this->message_table}.message_id = $table.message_id
                 WHERE  status=".PHORUM_STATUS_APPROVED."
                        $forum_where
                        $datestamp_where";

            // Retrieve the found messages.
            $rows = $this->interact(
                DB_RETURN_ASSOCS,
                "SELECT * $from_and_where
                 ORDER  BY datestamp DESC",
                 "message_id", 0, $limit, $offset
            );

            // Retrieve the number of found messages.
            $count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT count(*) $from_and_where"
            );

            // Fill the return data.
            $return = array("count" => $count, "rows"  => $rows);
        }

        return $return;
    }
    // }}}

    // {{{ Method: get_neighbour_thread()
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
    public function get_neighbour_thread($key, $direction)
    {
        global $PHORUM;

        settype($key, 'int');

        $keyfield = $PHORUM['float_to_top'] ? 'modifystamp' : 'datestamp';

        $compare  = "";
        $orderdir = "";

        switch ($direction) {
            case 'newer': $compare = '>'; $orderdir = 'ASC';  break;
            case 'older': $compare = '<'; $orderdir = 'DESC'; break;
            default:
                trigger_error(
                    __METHOD__ . ': Illegal direction ' .
                    '"'.htmlspecialchars($direction).'"',
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
        $thread = $this->interact(
            DB_RETURN_VALUE,
            "SELECT thread
             FROM   {$this->message_table}
             WHERE  forum_id = {$PHORUM['forum_id']} AND
                    parent_id = 0
                    $approvedval AND
                    $keyfield $compare $key
             ORDER  BY $keyfield $orderdir",
             NULL, 0, 1
        );

        return $thread;
    }
    // }}}

    // {{{ Method: get_forums()
    /**
     * Retrieve a list of forums. The forums which are returned can be filtered
     * through the function parameters. Note that only one parameter of
     * $forum_ids, $parent_id and $inherit_id is effective at a time.
     *
     * @param mixed $forum_ids
     *     A single forum_id or an array of forum_ids for which to retrieve the
     *     forum data. If this parameter is NULL, then the $parent_id
     *     parameter will be checked.
     *
     * @param mixed $parent_id
     *     Retrieve the forum data for all forums that have their parent_id set
     *     to $parent_id. If this parameter is NULL, then the $inherit_id
     *     will be checked.
     *
     * @param mixed $vroot
     *     Retrieve only forum data for forums that are in the given $vroot.
     *     This parameter can be used in combination with one of the parameters
     *     $forum_ids, $parent_id and $inherit_id as well.
     *
     * @param mixed $inherit_id
     *     Retrieve the forum data for all forums that inherit their settings
     *     from the forum with id $inherit_id.
     *
     * @param boolean $only_inherit_masters
     *     If this parameter has a true value (default is FALSE), then only forums
     *     that can act as a settings inheritance master will be returned (these
     *     are the forums for which customized settings are used, which means
     *     that inherit_id is NULL).
     *
     * @param integer $return_type
     *     0 to return both forums and folders.
     *     1 to return only folders.
     *     2 to return only forums.
     *
     * @param boolean $include_inactive
     *     If this parameter has a true value (default is FALSE), then both
     *     active and inactive forums and folders will be returned. Returning
     *     inactive ones is useful for administrative interfaces, which need to
     *     be able to access all forums and folders.
     *
     * @return array
     *     An array of forums, indexed by forum_id.
     */
    public function get_forums($forum_ids = NULL, $parent_id = NULL, $vroot = NULL, $inherit_id = NULL, $only_inherit_masters = FALSE, $return_type = 0, $include_inactive = FALSE)
    {

        $this->sanitize_mixed($forum_ids, 'int');
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
        } elseif ($parent_id !== NULL) {
            $where .= "parent_id = $parent_id";
        } else {
            $where .= 'forum_id <> 0';
        }

        if (!$include_inactive) {
            $where .= ' AND active = 1';
        }

        if ($vroot !== NULL) {
            $where .= " AND vroot = $vroot";
        }

        if ($only_inherit_masters) {
            $where .= ' AND inherit_id IS NULL AND folder_flag = 0';
        }

        if ($return_type == 1) {
            $where .= ' AND folder_flag = 1';
        } elseif ($return_type == 2) {
            $where .= ' AND folder_flag = 0';
        }

        $forums = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM   {$this->forums_table}
             WHERE  $where
             ORDER  BY display_order ASC, name",
           'forum_id'
        );

        return $forums;
    }
    // }}}

    // {{{ Method: update_forum_stats()
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
    public function update_forum_stats($refresh=FALSE, $msg_count_change=0, $timestamp=0, $thread_count_change=0, $sticky_count_change=0)
    {
        global $PHORUM;

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
            $message_count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT count(*)
                 FROM   {$this->message_table}
                 WHERE  forum_id = {$PHORUM['forum_id']} AND
                        status   = ".PHORUM_STATUS_APPROVED
            );
        } else {
            $message_count = "message_count+$msg_count_change";
        }

        if ($refresh || empty($timestamp)) {
            $last_post_time = $this->interact(
                DB_RETURN_VALUE,
                "SELECT max(modifystamp)
                 FROM   {$this->message_table}
                 WHERE  status   = ".PHORUM_STATUS_APPROVED." AND
                        forum_id = {$PHORUM['forum_id']}"
            );
            // In case we're calling this function for an empty forum.
            if ($last_post_time === NULL) {
                $last_post_time = 0;
            }
        } else {
            $last_post_time = $this->interact(
                DB_RETURN_VALUE,
                "SELECT last_post_time
                 FROM   {$this->forums_table}
                 WHERE  forum_id = {$PHORUM['forum_id']}"
            );
            if ($timestamp > $last_post_time) {
                $last_post_time = $timestamp;
            }
        }

        if ($refresh || empty($thread_count_change)) {
            $thread_count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT count(*)
                 FROM   {$this->message_table}
                 WHERE  forum_id  = {$PHORUM['forum_id']} AND
                        parent_id = 0 AND
                        status    = ".PHORUM_STATUS_APPROVED
            );
        } else {
            $thread_count = "thread_count+$thread_count_change";
        }

        if ($refresh || empty($sticky_count_change)) {
            $sticky_count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT count(*)
                 FROM   {$this->message_table}
                 WHERE  forum_id  = {$PHORUM['forum_id']} AND
                        sort      = ".PHORUM_SORT_STICKY." AND
                        parent_id = 0 AND
                        status    = ".PHORUM_STATUS_APPROVED
            );
        } else {
            $sticky_count = "sticky_count+$sticky_count_change";
        }

        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->forums_table}
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

    // {{{ Method: move_thread()
    /**
     * Move a thread to another forum.
     *
     * @param integer $thread_id
     *     The id of the thread that has to be moved.
     *
     * @param integer $toforum
     *     The id of the destination forum.
     */
    public function move_thread($thread_id, $toforum)
    {
        global $PHORUM;

        settype($thread_id, 'int');
        settype($toforum, 'int');

        // Check if the thread starter message exists.
        $message = $this->get_message($thread_id);
        if ($message['parent_id']) trigger_error(
            __METHOD__ . ": thread $thread_id does not exist",
            E_USER_ERROR
        );

        // Check if the target forum exists.
        $forums = $this->get_forums($toforum);
        if (empty($forums)) trigger_error(
            __METHOD__ . ": forum $toforum does not exist",
            E_USER_ERROR
        );

        if ($toforum > 0 && $thread_id > 0)
        {
            // Retrieve the messages from the thread, so we know for which
            // messages we have to update the newflags and search data below.
            $thread_messages = $this->get_messages($thread_id);
            unset($thread_messages['users']);

            // All we have to do to move the thread to a different forum,
            // is update the forum_id for the messages in that thread.
            // Simple, isn't it?
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET    forum_id = $toforum
                 WHERE  thread   = $thread_id",
                NULL,
                DB_MASTERQUERY
            );

            // Update the stats for the source forum.
            $this->update_forum_stats(TRUE);

            // Update the stats for the destination forum.
            $old_id = $PHORUM['forum_id'];
            $PHORUM['forum_id'] = $toforum;
            $this->update_forum_stats(TRUE);
            $PHORUM['forum_id'] = $old_id;

            // Handle updates for the data that is related to the
            // messages in the moved thread.
            $message_ids = array_keys($thread_messages);
            $ids_str     = implode(', ',$message_ids);

            // Move the newflags to the destination forum.
            $this->newflag_update_forum($message_ids);

            // Move the subscriptions to the destination forum.
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->subscribers_table}
                 SET    forum_id = $toforum
                 WHERE  thread IN ($ids_str)",
                NULL,
                DB_MASTERQUERY
            );

            // Move the search data to the destination forum.
            $ids_str = implode(', ',$message_ids);
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->search_table}
                 SET    forum_id = $toforum
                 WHERE  message_id in ($ids_str)",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
    // }}}

    // {{{ Method: close_thread()
    /**
     * Close a thread for posting.
     *
     * @param integer
     *     The id of the thread that has to be closed.
     */
    public function close_thread($thread_id)
    {

        settype($thread_id, 'int');

        if ($thread_id > 0) {
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET    closed = 1
                 WHERE  thread = $thread_id",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
    // }}}

    // {{{ Method: reopen_thread()
    /**
     * (Re)open a thread for posting.
     *
     * @param integer
     *     The id of the thread that has to be opened.
     */
    public function reopen_thread($thread_id)
    {
        settype($thread_id, 'int');

        if ($thread_id > 0) {
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET    closed = 0
                 WHERE  thread = $thread_id",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
    // }}}

    // {{{ Method: add_forum()
    /**
     * Create a forum.
     *
     * @param array $forum
     *     The forum to create. This is an array, which should contain the
     *     following fields: name, active, description, template, folder_flag,
     *     parent_id, list_length_flat, list_length_threaded, read_length,
     *     moderation, threaded_list, threaded_read, float_to_top,
     *     display_ip_address, allow_email_notify, language, email_moderators,
     *     display_order, pub_perms, reg_perms.
     *
     * @return integer
     *     The forum_id that was assigned to the new forum.
     */
    public function add_forum($forum)
    {
        // check for fields that must be set for mysql strict mode
        if(empty($forum["description"])) $forum["description"] = "";
        if(empty($forum["forum_path"])) $forum["forum_path"] = "";

        $insertfields = array();
        $customfields = array();

        foreach ($forum as $key => $value)
        {
            // If the forum_id field is set to 0, then we will not include
            // it in the create query, so the database will generate one
            // for us. If a specific forum_id is provided, we keep that
            // one, since the caller might be doing something like a
            // migration or so, wanting to keep forum ids the same.
            if ($key == 'forum_id' && empty($value)) {
                continue;
            }

            if ($this->validate_field($key))
            {
                // find out if this field is a custom field
                /**
                 * @todo duplicated work. the same is done in
                 *       save_custom_fields.
                 *       find out how to find the custom fields differently
                 *       (define the real fields like for the users?)
                 */
                require_once PHORUM_PATH.'/include/api/custom_field.php';
                $custom = phorum_api_custom_field_byname($key,PHORUM_CUSTOM_FIELD_FORUM);

                if($custom === NULL) {
                    if (is_numeric($value) &&
                        !in_array($key, $this->_string_fields_forum)) {
                        $value = (int)$value;
                        $insertfields[$key] = $value;
                    } elseif ($value === NULL) {
                        $insertfields[$key] = 'NULL';
                    } else {
                        $value = $this->interact(DB_RETURN_QUOTED, $value);
                        $insertfields[$key] = "'$value'";
                    }
                } else {
                    $customfields[$key]=$value;
                }
            }
        }

        $forum_id = $this->interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$this->forums_table}
                    (".implode(', ', array_keys($insertfields)).")
             VALUES (".implode(', ', $insertfields).")",
            NULL,
            DB_MASTERQUERY
        );

        if (is_array($customfields) && count($customfields)) {
            $this->save_custom_fields(
                $forum_id,PHORUM_CUSTOM_FIELD_FORUM,$customfields);
        }

        return $forum_id;
    }
    // }}}

    // {{{ Method: update_forum()
    /**
     * Update the settings for one or more forums.
     *
     * @param array $forum
     *     The forum to update. This is an array, which should contain at least
     *     the field "forum_id" to indicate what forum to update. Next to that,
     *     one or more of the other fields from add_forum() can be
     *     used to describe changed values. The "forum_id" field can also
     *     contain an array of forum_ids. By using that, the settings can be
     *     updated for all the forum_ids at once.
     *
     * @return boolean
     *     True if all settings were stored successfully. This function will
     *     always return TRUE, so we could do without a return value. The
     *     return value is here for backward compatibility.
     */
    public function update_forum($forum)
    {
        // Check if the forum_id is set.
        if (!isset($forum['forum_id']) || empty($forum['forum_id'])) trigger_error(
            __METHOD__ . ': $forum["forum_id"] cannot be empty',
            E_USER_ERROR
        );

        $this->sanitize_mixed($forum['forum_id'], 'int');

        // See what forum(s) to update.
        if (is_array($forum['forum_id'])) {
            $forumwhere = 'forum_id IN ('.implode(', ',$forum['forum_id']).')';
            $forum_ids = $forum['forum_id'];
        } else {
            $forumwhere = 'forum_id = ' . $forum['forum_id'];
            $forum_ids = array($forum['forum_id']);
        }
        unset($forum['forum_id']);

        // Prepare the SQL code for updating the fields.
        $fields = array();
        $customfields = array();
        foreach ($forum as $key => $value)
        {
            if ($this->validate_field($key))
            {
                require_once PHORUM_PATH.'/include/api/custom_field.php';
                $custom = phorum_api_custom_field_byname($key,PHORUM_CUSTOM_FIELD_FORUM);

                if($custom === null) {
                    if ($key == 'forum_path') {
                        $value = serialize($value);
                        $value = $this->interact(DB_RETURN_QUOTED, $value);
                        $fields[] = "$key = '$value'";
                    } elseif (is_numeric($value) &&
                        !in_array($key, $this->_string_fields_forum)) {
                        $value = (int)$value;
                        $fields[] = "$key = $value";
                    } elseif ($value === NULL) {
                        $fields[] = "$key = NULL";
                    } else {
                        $value = $this->interact(DB_RETURN_QUOTED, $value);
                        $fields[] = "$key = '$value'";
                    }
                } else {
                    $customfields[$key] = $value;
                }
            }
        }

        // Run the update, if there are fields to update.
        if (count($fields)) {
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->forums_table}
                 SET "  .implode(', ', $fields) . "
                 WHERE  $forumwhere",
                NULL,
                DB_MASTERQUERY
            );
        }

        if (is_array($customfields) && count($customfields)) {
            foreach($forum_ids as $forum_id) {
                $this->save_custom_fields(
                    $forum_id,PHORUM_CUSTOM_FIELD_FORUM,$customfields);
            }
        }

        return TRUE;
    }
    // }}}

    // {{{ Method: drop_forum()
    /**
     * Drop a forum and all of its messages.
     *
     * @param integer $forum_id
     *     The id of the forum to drop.
     */
    public function drop_forum($forum_id)
    {
        settype($forum_id, 'int');

        // These are the tables that hold forum related data.
        $tables = array (
            $this->message_table,
            $this->user_permissions_table,
            $this->user_newflags_table,
            $this->subscribers_table,
            $this->forum_group_xref_table,
            $this->forums_table,
            $this->banlist_table,
            $this->search_table
        );

        // Delete the data for the $forum_id from all those tables.
        foreach ($tables as $table) {
            $this->interact(
                DB_RETURN_RES,
                "DELETE FROM $table
                 WHERE forum_id = $forum_id",
                NULL,
                DB_MASTERQUERY
            );
        }

        // now delete its custom fields
        $this->delete_custom_fields(PHORUM_CUSTOM_FIELD_FORUM,$forum_id);

        // Collect all orphan message attachment files from the database.
        // These are all files that are linked to a message, but for which
        // the message_id does not exist in the message table (anymore).
        // This might catch some more messages than only the ones for the
        // deleted forum alone. That should never be a problem.
        $files = $this->interact(
            DB_RETURN_ROWS,
            "SELECT file_id
             FROM   {$this->files_table}
                    LEFT JOIN {$this->message_table}
                    ON {$this->files_table}.message_id =
                       {$this->message_table}.message_id
             WHERE  {$this->files_table}.message_id > 0 AND
                    link = '" . PHORUM_LINK_MESSAGE . "' AND
                    {$this->message_table}.message_id is NULL",
            0 // keyfield 0 is the file_id
        );

        // Delete all orphan message attachment files.
        if (!empty($files)) {
            $this->interact(
                DB_RETURN_RES,
                "DELETE FROM {$this->files_table}
                 WHERE  file_id IN (".implode(",", array_keys($files)).")",
                NULL,
                DB_MASTERQUERY
            );
        }


        // Collect all orphan message custom fields from the database.
        // These are all custom fields that are linked to a message, but for which
        // the message_id does not exist in the message table (anymore).
        // This might catch some more custom fields than only the ones for the
        // deleted forum alone. That should never be a problem.
        $customfields = $this->interact(
            DB_RETURN_ROWS,
            "SELECT DISTINCT(a.relation_id)
             FROM   {$this->custom_fields_table} as a
                    LEFT JOIN {$this->message_table} as b
                    ON b.message_id = a.relation_id
             WHERE  a.relation_id > 0 AND
                    a.field_type = '" . PHORUM_CUSTOM_FIELD_MESSAGE . "' AND
                    b.message_id is NULL",
            0 // keyfield 0 is the relation_id
        );

        if(is_array($customfields) && count($customfields)) {
            $this->delete_custom_fields(PHORUM_CUSTOM_FIELD_MESSAGE,$customfields);
        }

    }
    // }}}

    // {{{ Method: drop_folder()
    /**
     * Drop a forum folder. If the folder contains child forums or folders,
     * then the parent_id for those will be updated to point to the parent
     * of the folder that is being dropped.
     *
     * @param integer $forum_id
     *     The id of the folder to drop.
     */
    public function drop_folder($forum_id)
    {

        settype($forum_id, 'int');

        // See if the $forum_id is really a folder and find its
        // parent_id, which we can use to reattach children of the folder.
        $new_parent_id = $this->interact(
            DB_RETURN_VALUE,
            "SELECT parent_id
             FROM   {$this->forums_table}
             WHERE  forum_id = $forum_id AND
                    folder_flag = 1"
        );
        if ($new_parent_id === NULL) trigger_error(
            __METHOD__ . ": id $forum_id not found or not a folder",
            E_USER_ERROR
        );

        // Start with reattaching the folder's children to the new parent.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->forums_table}
             SET    parent_id = $new_parent_id
             WHERE  parent_id = $forum_id",
            NULL,
            DB_MASTERQUERY
        );

        // Now, drop the folder.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->forums_table}
             WHERE  forum_id = $forum_id",
            NULL,
            DB_MASTERQUERY
        );

        // now delete its custom fields
        $this->delete_custom_fields(PHORUM_CUSTOM_FIELD_FORUM,$forum_id);
    }
    // }}}

    // {{{ Method: add_message_edit()
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
    public function add_message_edit($edit_data)
    {

        foreach ($edit_data as $key => $value) {
            if (is_numeric($value)) {
                $edit_data[$key] = (int)$value;
            } elseif (is_array($value)) {
                $value = serialize($value);
                $edit_data[$key] = $this->interact(DB_RETURN_QUOTED, $value);
            } else {
                $edit_data[$key] = $this->interact(DB_RETURN_QUOTED, $value);
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
        $tracking_id = $this->interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$this->message_tracking_table}
                    (".implode(', ', array_keys($insertfields)).")
             VALUES (".implode(', ', $insertfields).")",
            NULL,
            DB_MASTERQUERY
        );

        return $tracking_id;
    }
    // }}}

    // {{{ Method: get_message_edits()
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
    public function get_message_edits($message_id)
    {
        settype($message_id, 'int');

        // Select the message files from the database.
        $edits = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT user_id,
                    time,
                    diff_body,
                    diff_subject,
                    track_id
             FROM   {$this->message_tracking_table}
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

    // {{{ Method: get_groups()
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
    public function get_groups($group_id = 0, $sorted = FALSE)
    {
        global $PHORUM;


        $this->sanitize_mixed($group_id,"int");

        if(is_array($group_id) && count($group_id)) {
            $group_str=implode(',',$group_id);
            $group_where=" where group_id IN($group_str)";
        } elseif(!is_array($group_id) && $group_id!=0) {
            $group_where=" where group_id=$group_id";
        } else {
            $group_where="";
        }


        // Retrieve the group(s) from the database.
        $groups = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM {$this->groups_table}
             $group_where",
            'group_id'
        );

        // Retrieve the group permissions from the database.
        $perms = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM {$this->forum_group_xref_table}
             $group_where"
        );

        // Add the permissions to the group(s).
        foreach ($groups as $id => $group) $groups[$id]['permissions'] = array();
        foreach ($perms as $perm)
        {
            // Little safety net against orphan records (shouldn't happen).
            if (!isset($groups[$perm['group_id']])) continue;

            $groups[$perm['group_id']]['permissions'][$perm['forum_id']]
                = $perm['permission'];
        }

        // Sort the list by group name.
        if ($sorted) {
            uasort($groups, array($PHORUM['DB'], 'sort_groups'));
        }

        return $groups;
    }

    public function sort_groups($a,$b) {
        return strcasecmp($a["name"], $b["name"]);
    }
    // }}}

    // {{{ Method: get_group_members()
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
    public function get_group_members($group_id, $status = NULL)
    {
        $this->sanitize_mixed($group_id, 'int');
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
        // If user_get() sorts results itself, this join can go away.
        $members = $this->interact(
            DB_RETURN_ROWS,
            "SELECT xref.user_id AS user_id,
                    xref.status  AS status
             FROM   {$this->user_table} AS users,
                    {$this->user_group_xref_table} AS xref
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

    // {{{ Method: add_group()
    /**
     * Add a group. This will merely create the group in the database. For
     * changing settings for the group, the public function update_group()
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
    public function add_group($group_name, $group_id=0)
    {
        settype($group_id, 'int');
        $group_name = $this->interact(DB_RETURN_QUOTED, $group_name);

        $fields = $group_id > 0 ? 'name, group_id' : 'name';
        $values = $group_id > 0 ? "'$group_name', $group_id" : "'$group_name'";

        $group_id = $this->interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$this->groups_table}
                    ($fields)
             VALUES ($values)",
             NULL,
             DB_MASTERQUERY
        );

        return $group_id;
    }
    // }}}

    // {{{ Method: update_group()
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
    public function update_group($group)
    {
        // Check if the group_id is set.
        if (!isset($group['group_id']) || empty($group['group_id'])) trigger_error(
            __METHOD__ . ': $group["group_id"] cannot be empty',
            E_USER_ERROR
        );

        settype($group['group_id'], 'int');
        $group_where = 'group_id = ' . $group['group_id'];

        // See what group fields we have to update.
        $fields = array();
        if (isset($group['name'])) {
            $fields[] = "name = '" .
                        $this->interact(DB_RETURN_QUOTED, $group['name']) ."'";
        }
        if (isset($group['open'])) {
            $fields[] = 'open = ' . (int)$group['open'];
        }

        // Update group fields.
        if (count($fields)) {
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->groups_table}
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
            $this->interact(
                DB_RETURN_RES,
                "DELETE FROM {$this->forum_group_xref_table}
                 WHERE  $group_where",
                NULL,
                DB_MASTERQUERY
            );

            // Second, all new permissions are inserted.
            foreach ($group['permissions'] as $forum_id => $permission)
            {
                settype($forum_id, 'int');
                settype($permission, 'int');

                $this->interact(
                    DB_RETURN_RES,
                    "INSERT INTO {$this->forum_group_xref_table}
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

    // {{{ Method: delete_group()
    /**
     * Delete a group.
     *
     * @param integer $group_id
     *     The id of the group to delete.
     */
    public function delete_group($group_id)
    {
        settype($group_id, 'int');

        // These are the tables that hold group related data.
        $tables = array (
            $this->groups_table,
            $this->user_group_xref_table,
            $this->forum_group_xref_table
        );

        // Delete the data for the $group_id from all those tables.
        foreach ($tables as $table) {
            $this->interact(
                DB_RETURN_RES,
                "DELETE FROM $table
                 WHERE group_id = $group_id",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
    // }}}

    // {{{ Method: user_get_moderators()
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
    public function user_get_moderators($forum_id, $exclude_admin=FALSE, $for_email=FALSE)
    {
        settype($forum_id, 'int');
        settype($exclude_admin, 'bool');
        settype($for_email, 'bool');

        // If we are gathering email addresses for mailing the moderators,
        // then honour the moderation_email setting for the user.
        $where_moderation_mail = $for_email ? 'AND u.moderation_email = 1' : '';

        // Exclude admins from the list, if requested.
        $admin = $exclude_admin ? '' :
                    "SELECT DISTINCT u.user_id AS user_id,
                u.email AS email
         FROM   {$this->user_table} AS u
         WHERE  u.active=1 AND u.admin=1
                $where_moderation_mail
         UNION
        ";


        $moderators = array();

        // Look up moderators which are configured through user permissions.
        $usermods = $this->interact(
            DB_RETURN_ROWS,
            $admin .
            "SELECT DISTINCT u.user_id AS user_id,
                    u.email AS email
             FROM   {$this->user_permissions_table} AS perm
                    INNER JOIN {$this->user_table} AS u
                    ON perm.user_id = u.user_id
             WHERE  perm.forum_id = $forum_id AND u.active = 1 AND
                    perm.permission>=".PHORUM_USER_ALLOW_MODERATE_MESSAGES." AND
                    (perm.permission & ".PHORUM_USER_ALLOW_MODERATE_MESSAGES.">0)
                    $where_moderation_mail"
        );

        // Add them to the moderator list.
        foreach ($usermods as $mod) $moderators[$mod[0]] = $mod[1];
        unset($usermods);

        // Look up moderators which are configured through group permissions.
        $groupmods = $this->interact(
            DB_RETURN_ROWS,
            "SELECT DISTINCT u.user_id AS user_id,
                    u.email AS email
             FROM   {$this->user_table} AS u,
                    {$this->groups_table} AS groups,
                    {$this->user_group_xref_table} AS usergroup,
                    {$this->forum_group_xref_table} AS forumgroup
             WHERE  u.user_id   = usergroup.user_id AND
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

    // {{{ Method: user_count()
    /**
     * Count the total number of users in the Phorum system.
     *
     * @return integer
     *     The number of users.
     */
    public function user_count()
    {
        return $this->interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM   {$this->user_table}"
        );
    }
    // }}}

    // {{{ Method: user_get_all()
    /**
     * Retrieve all users from the database.
     *
     * This function returns a query resource handle. This handle can be used
     * to retrieve the users from the database one-by-one, by calling the
     * fetch_row() method.
     *
     * @return resource
     *     A query resource handle is returned. This handle can be used
     *     to retrieve the users from the database one-by-one, by
     *     calling the fetch_row() method.
     *
     * @todo This function might be as well replaced with user search and get
     *       functionality from the user API, if search is extended with an
     *       option to return a resource handle.
     */
    public function user_get_all($offset = 0, $limit = 0)
    {
        settype($offset, 'int');
        settype($limit, 'int');

        return $this->interact(
            DB_RETURN_RES,
            "SELECT *
             FROM   {$this->user_table}",
            NULL, NULL, $limit, $offset
        );
    }
    // }}}

    // {{{ Method: user_get()
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
    public function user_get($user_id, $detailed = FALSE, $write_server = FALSE)
    {

        $this->sanitize_mixed($user_id, 'int');

        if (is_array($user_id)) {
            if (count($user_id)) {
                $user_ids = 'IN ('.implode(', ', $user_id).')';
            } else {
                return array();
            }
        } else {
            $user_ids = "= $user_id";
        }

        if($write_server) {
            $flags = DB_MASTERQUERY;
        } else {
            $flags = 0;
        }

        // Retrieve the requested user(s) from the database.
        $users = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM   {$this->user_table}
             WHERE  user_id $user_ids",
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
            $forum_permissions = $this->interact(
                DB_RETURN_ROWS,
                "SELECT user_id,
                        forum_id,
                        permission
                 FROM   {$this->user_permissions_table}
                 WHERE  user_id $user_ids",
                NULL,
                $flags
            );

            // Add forum user permissions to the users.
            foreach ($forum_permissions as $perm) {
                $users[$perm[0]]['forum_permissions'][$perm[1]] = $perm[2];
            }

            // Retrieve forum group permissions and groups for the requested
            // users. "status >= ..." is used to retrieve both approved group
            // users and group moderators.
            $group_permissions = $this->interact(
                DB_RETURN_ROWS,
                "SELECT user_id,
                        {$this->user_group_xref_table}.group_id AS group_id,
                        forum_id,
                        permission
                 FROM   {$this->user_group_xref_table}
                        LEFT JOIN {$this->forum_group_xref_table}
                        ON {$this->user_group_xref_table}.group_id =
                           {$this->forum_group_xref_table}.group_id
                 WHERE  user_id $user_ids AND
                        status >= ".PHORUM_USER_GROUP_APPROVED,
                NULL,
                $flags
            );

            // Add groups and forum group permissions to the users.
            foreach ($group_permissions as $perm)
            {
                // Skip permissions for users which are not in our
                // $users array. This should not happen, but it could
                // happen in case some orphan group permissions are
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

        if (is_array($user_id)) {
            return $users;
        } else {
            return isset($users[$user_id]) ? $users[$user_id] : NULL;
        }
    }
    // }}}

    // {{{ Method: get_custom_fields()
    /**
     * Retrieve custom fields for one or more objects of the given type
     *
     * @param integer $type
     *     The type of the fields to be retrieved, which can currently be:
     *         PHORUM_CUSTOM_FIELD_USER
     *         PHORUM_CUSTOM_FIELD_FORUM
     *         PHORUM_CUSTOM_FIELD_MESSAGE
     *
     * @param mixed $relation_id
     *     The id of the object the custom fields belong to
     *
     * @param integer $db_flags
     *     Database flags needed to be sent to the database functions
     *
     * @param boolean $raw_data
     *     When this parameter is TRUE (default is FALSE), then custom fields
     *     that are configured with html_disabled will not be HTML encoded in
     *     the return data.
     *
     * @return mixed
     *     An array of custom fields is returned, indexed by relation_id.
     *     For relation_ids that cannot be found, there will be no
     *     array element at all.
     */
    public function get_custom_fields(
        $type, $relation_id, $db_flags = 0, $raw_data = FALSE)
    {
        $this->sanitize_mixed($relation_id, 'int');
        $this->sanitize_mixed($type, 'int');

        if (is_array($relation_id)) {
            if (count($relation_id)) {
                $relation_ids = 'IN ('.implode(', ', $relation_id).')';
            } else {
                return array();
            }
        } else {
            $relation_ids = "= $relation_id";
        }

        $custom_fields = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT f.*, c.name, c.html_disabled
             FROM   {$this->custom_fields_config_table} c
                    INNER JOIN {$this->custom_fields_table} f
                    ON c.id = f.type
             WHERE  c.field_type = $type AND
                    c.deleted = 0 AND
                    f.relation_id $relation_ids",
            NULL,
            $db_flags
        );

        // Format the custom field data.
        $requested_data = array();
        foreach ($custom_fields as $fld)
        {
            $name   = $fld['name'];
            $rel_id = $fld['relation_id'];
            $data   = $fld['data'];

            // For "html_disabled" fields, the data is XSS protected by
            // replacing special HTML characters with their HTML entities.
            if ($fld['html_disabled'] && !$raw_data)
            {
                $requested_data[$rel_id][$name] = htmlspecialchars($data);
                continue;
            }

            // Other fields can either contain raw values or serialized
            // arrays. For serialized arrays, the field data is prefixed with
            // a magic "P_SER:" (Phorum serialized) marker.
            if (substr($data, 0, 6) == 'P_SER:') {
                $requested_data[$rel_id][$name] = unserialize(substr($data, 6));
                continue;
            }

            // The rest of the fields contain raw field data.
            $requested_data[$rel_id][$name] = $data;
        }

        return $requested_data;
    }
    // }}}

    // {{{ Method: user_get_fields()
    /**
     * Retrieve the data for a couple of provided user table fields for
     * one or more users.
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
    public function user_get_fields($user_id, $fields)
    {
        $this->sanitize_mixed($user_id, 'int');

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
            if (!$this->validate_field($field)) {
                unset($fields[$key]);
            }
        }

        $users = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT user_id, ".implode(', ', $fields)."
             FROM   {$this->user_table}
             WHERE  $user_where",
            'user_id'
        );

        return $users;
    }
    // }}}

    // {{{ Method: user_get_list()
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
    public function user_get_list($type = 0)
    {
        settype($type, 'int');

        $where = '';
        if     ($type == 1) $where = 'WHERE active  = 1';
        elseif ($type == 2) $where = 'WHERE active != 1';

        $users = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT user_id,
                    username,
                    display_name
             FROM   {$this->user_table}
                    $where
             ORDER  BY username ASC",
            'user_id'
        );

        return $users;
    }
    // }}}

    // {{{ Method: user_check_login()
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
    public function user_check_login($username, $password, $temp_password=FALSE)
    {

        settype($temp_password, 'bool');
        $username = $this->interact(DB_RETURN_QUOTED, $username);
        $password = $this->interact(DB_RETURN_QUOTED, $password);

        $pass_field = $temp_password ? 'password_temp' : 'password';

        $user_id = $this->interact(
            DB_RETURN_VALUE,
            "SELECT user_id
             FROM   {$this->user_table}
             WHERE  username    = '$username' AND
                    $pass_field = '$password'"
        );

        return $user_id ? $user_id : 0;
    }
    // }}}

    // {{{ Method: user_search()
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
     * @param integer $limit
     *     The result page limit (nr. of results per page)
     *     or 0 (zero, the default) to return all results.
     *
     * @param boolean $count_only
     *     Tells the function to just return the count of results for this
     *     search query.
     *
     * @return mixed
     *     An array of matching user_ids or a single user_id (based on the
     *     $return_array parameter). If no user_ids can be found at all,
     *     then 0 (zero) will be returned.
     */
    public function user_search(
        $field, $value, $operator = '=', $return_array = FALSE,
        $type = 'AND', $sort = NULL, $offset = 0, $limit = 0, $count_only = false)
    {

        settype($return_array, 'bool');
        settype($offset, 'int');
        settype($limit, 'int');

        // Convert all search condition parameters to arrays.
        if (!is_array($field))    $field    = array($field);
        if (!is_array($value))    $value    = array($value);
        if (!is_array($operator)) $operator = array($operator);
        if (!is_array($sort) && $sort!==NULL) $sort = array($sort);

        // Basic check to see if all condition arrays contain the
        // same number of elements.
        if (count($field) != count($value) ||
            count($field) != count($operator)) trigger_error(
            __METHOD__ . ': array parameters $field, $value, ' .
            'and $operator do not contain the same number of elements',
            E_USER_ERROR
        );

        $type = strtoupper($type);
        if ($type != 'AND' && $type != 'OR') trigger_error(
            __METHOD__ . ': Illegal search type parameter (must ' .
            'be either AND" or "OR")',
            E_USER_ERROR
        );

        $valid_operators = array('=', '<>', '!=', '>', '<', '>=', '<=', '*', '?*', '*?','()');

        // Construct the required "WHERE" clause.
        $clauses = array();
        foreach ($field as $key => $name) {
            if (in_array($operator[$key], $valid_operators) &&
                $this->validate_field($name)) {
                if ($operator[$key] != '()') $value[$key] = $this->interact(DB_RETURN_QUOTED, $value[$key]);
                if ($operator[$key] == '*') {
                    $clauses[] = "$name LIKE '%$value[$key]%'";
                } else if ($operator[$key] == '?*') {
                    $clauses[] = "$name LIKE '$value[$key]%'";
                } else if ($operator[$key] == '*?') {
                    $clauses[] = "$name LIKE '%$value[$key]'";
                } else if ($operator[$key] == '()') {
                    foreach ($value[$key] as $in_key => $in_value) {
                        $value[$key][$in_key] = $this->interact(DB_RETURN_QUOTED, $value[$key][$in_key]);
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

                if (!$this->validate_field($fld)) trigger_error(
                    __METHOD__ . ': Illegal sort field: ' .
                    htmlspecialchars($spec),
                    E_USER_ERROR
                );

                $sort[$id] = "$fld $dir";
            }
            $order = 'ORDER BY ' . implode(', ', $sort);
        } else {
            $order = '';
        }

        // If we do not need to return an array, the we can limit the
        // query results to only one record.
        $limit = $return_array ? $limit : 1;

        $ret = null;

        if($count_only) {
            // Retrieve the number of matching user_ids from the database.
            $user_count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT count(*)
                 FROM   {$this->user_table}
                 $where $order",
                0, // keyfield 0 is the user_id
                0,
                $limit,
                $offset
            );

            $ret = $user_count;

        } else {

            // Retrieve the matching user_ids from the database.
            $user_ids = $this->interact(
                DB_RETURN_ROWS,
                "SELECT user_id
                 FROM   {$this->user_table}
                 $where $order",
                0, // keyfield 0 is the user_id
                0, // no flags
                $limit,
                $offset
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

    // {{{ Method: user_add()
    /**
     * Add a user.
     *
     * @param array $userdata
     *     An array containing the fields to insert into the user table.
     *     This array should contain at least a "username" field. See
     *     user_save() for some more info on the other data
     *     in this array.
     *
     * @return integer $user_id
     *     The user_id that was assigned to the new user.
     */
    public function user_add($userdata)
    {

        // We need at least the username for the user.
        if (! isset($userdata['username'])) trigger_error(
            __METHOD__ . ': Missing field in userdata: username',
            E_USER_ERROR
        );
        $username = $this->interact(DB_RETURN_QUOTED, $userdata['username']);

        // We can set the user_id. If not, then we'll create a new user_id.
        if (isset($userdata['user_id'])) {
            $user_id = (int)$userdata['user_id'];
            $fields = 'user_id, username, signature, settings_data';
            $values = "$user_id, '$username', '', ''";
        } else {
            $fields = 'username, signature, settings_data';
            $values = "'$username', '', ''";
        }

        // Insert a bare bone user in the database.
        $user_id = $this->interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$this->user_table}
                    ($fields)
             VALUES ($values)",
            NULL,
            DB_MASTERQUERY
        );

        // Set the rest of the data using the user_save() function.
        $userdata['user_id'] = $user_id;
        $this->user_save($userdata);

        return $user_id;
    }
    // }}}

    // {{{ Method: user_save()
    /**
     * Update a user.
     *
     * @param array $userdata
     *     An array containing the fields to update in the user table.
     *     The array should contain at least the user_id field to identify
     *     the user for which to update the data. The array can contain two
     *     special fields:
     *     - forum_permissions:
     *       This field can contain an array with forum permissions for the
     *       user. The keys are forum_ids and the values are permission values.
     *     - user_data:
     *       This field can contain an array of key/value pairs which will be
     *       inserted in the database as custom profile fields. The keys are
     *       profile type ids (ids from the custom field config table).
     *
     * @return boolean
     *     True if all settings were stored successfully. This function will
     *     always return TRUE, so we could do without a return value.
     *     The return value is here for backward compatibility.
     */
    public function user_save($userdata)
    {
        // Pull some non user table fields from the userdata. These can be
        // set in case the $userdata parameter that is used is coming from
        // phorum_api_user_get() or user_get().
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
        // The user_id is required for doing the update.
        if (!isset($userdata['user_id'])) trigger_error(
            __METHOD__ . ': the user_id field is missing in the ' .
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
                if (!$this->validate_field($key)) continue;
                if ($key === 'settings_data') {
                    if (is_array($value)) {
                        $value = serialize($value);
                    } else trigger_error(
                        'Internal error: settings_data field for ' .
                        'user_save() must be an array', E_USER_ERROR
                    );
                }
                $value = $this->interact(DB_RETURN_QUOTED, $value);

                if (in_array($key, $this->_string_fields_user)) {
                    $values[] = "$key = '$value'";
                } else {
                    $values[] = "$key = $value";
                }
            }

            // Update the fields in the database.
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->user_table}
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
            $this->interact(
                DB_RETURN_RES,
                "DELETE FROM {$this->user_permissions_table}
                 WHERE  user_id = $user_id",
                NULL,
                DB_MASTERQUERY
            );

            // Add new forum permissions.
            foreach ($forum_perms as $forum_id => $permission) {
                $this->interact(
                    DB_RETURN_RES,
                    "INSERT INTO {$this->user_permissions_table}
                            (user_id, forum_id, permission)
                     VALUES ($user_id, $forum_id, $permission)",
                    NULL,
                    DB_MASTERQUERY
                );
            }
        }


        return TRUE;
    }
    // }}}

    // {{{ Method: save_custom_fields()
    public function save_custom_fields($relation_id, $field_type, $customfield_data)
    {

        // Update custom fields for the object.
        if (isset($customfield_data))
        {
            // Insert new custom profile fields.
            foreach ($customfield_data as $name => $val)
            {
                require_once PHORUM_PATH . '/include/api/custom_field.php';
                $custom = phorum_api_custom_field_byname($name, $field_type);

                // Arrays and NULL values are left untouched.
                // Other values are truncated to their configured field length.
                if ($val !== NULL && !is_array($val)) {
                    $val = substr($val, 0, $custom['length']);
                }
                if ($custom !== null)
                {
                    $key = $custom['id'];

                    // Arrays need to be serialized. The serialized data is prefixed
                    // with "P_SER:" as a marker for serialization.
                    if (is_array($val)) $val = 'P_SER:'.serialize($val);

                    $val = $this->interact(DB_RETURN_QUOTED, $val);

                    // Try to insert a new record.
                    $res = $this->interact(
                        DB_RETURN_RES,
                        "INSERT INTO {$this->custom_fields_table}
                                (relation_id, field_type, type, data)
                         VALUES ($relation_id, $field_type , $key, '$val')",
                        NULL,
                        DB_DUPKEYOK | DB_MASTERQUERY
                    );
                    // If no result was returned, then the query failed. This probably
                    // means that we already have a record in the database.
                    // So instead of inserting a record, we need to update one here.
                    if (!$res) {
                      $this->interact(
                          DB_RETURN_RES,
                          "UPDATE {$this->custom_fields_table}
                           SET    data = '$val'
                           WHERE  relation_id = $relation_id AND
                                  field_type = $field_type AND
                                  type = $key",
                                NULL,
                                DB_MASTERQUERY
                        );
                    }
                }
            }
        }
    }
    // }}}

    // {{{ Method: user_display_name_updates()
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
    public function user_display_name_updates($userdata)
    {

        if (!isset($userdata['user_id'])) trigger_error(
            __METHOD__ . ': Missing user_id field in ' .
            'the $userdata parameter',
            E_USER_ERROR
        );
        if (!isset($userdata['display_name'])) trigger_error(
            __METHOD__ . ': Missing display_name field ' .
            'in the $userdata parameter',
            E_USER_ERROR
        );

        $author = $this->interact(DB_RETURN_QUOTED, $userdata['display_name']);
        $user_id = (int) $userdata['user_id'];

        // Update forum message authors.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->message_table}
             SET    author = '$author'
             WHERE  user_id = $user_id",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        // Update recent forum reply authors.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->message_table}
             SET    recent_author = '$author'
             WHERE  recent_user_id = $user_id",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        // Update PM author data.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->pm_messages_table}
             SET    author = '$author'
             WHERE  user_id = $user_id",
            NULL,
            DB_MASTERQUERY
        );

        // Update PM recipient data.
        $res = $this->interact(
            DB_RETURN_RES,
            "SELECT m.pm_message_id AS pm_message_id, meta
             FROM   {$this->pm_messages_table} AS m,
                    {$this->pm_xref_table} AS x
             WHERE  m.pm_message_id = x.pm_message_id AND
                    x.user_id = $user_id AND
                    special_folder != 'outbox'",
             NULL,
             DB_MASTERQUERY
        );
        while ($row = $this->fetch_row($res, DB_RETURN_ASSOC)) {
            $meta = unserialize($row['meta']);
            $meta['recipients'][$user_id]['display_name'] = $author;
            $meta = $this->interact(DB_RETURN_QUOTED, serialize($meta));
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->pm_messages_table}
                 SET    meta='$meta'
                 WHERE  pm_message_id = {$row['pm_message_id']}",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
    // }}}

    // {{{ Method: user_save_groups()
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
    public function user_save_groups($user_id, $groups)
    {
        settype($user_id, 'int');

        // Delete all existing group memberships.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->user_group_xref_table}
             WHERE  user_id = $user_id",
            NULL,
            DB_MASTERQUERY
        );

        // Insert new group memberships.
        foreach ($groups as $group_id => $group_status) {
            $group_id = (int)$group_id;
            $group_status = (int)$group_status;
            $this->interact(
                DB_RETURN_RES,
                "INSERT INTO {$this->user_group_xref_table}
                        (user_id, group_id, status)
                 VALUES ($user_id, $group_id, $group_status)",
                NULL,
                DB_MASTERQUERY
            );
        }

        return TRUE;
    }
    // }}}

    // {{{ Method: user_subscribe()
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
    public function user_subscribe($user_id, $thread, $forum_id, $type)
    {
        settype($user_id, 'int');
        settype($forum_id, 'int');
        settype($thread, 'int');
        settype($type, 'int');

        // Try to insert a new record.
        $res = $this->interact(
            DB_RETURN_RES,
            "INSERT INTO {$this->subscribers_table}
                    (user_id, forum_id, thread, sub_type)
             VALUES ($user_id, $forum_id, $thread, $type)",
            NULL,
            DB_DUPKEYOK | DB_MASTERQUERY
        );
        // If no result was returned, then the query failed. This probably
        // means that we already have the record in the database.
        // So instead of inserting a record, we need to update one here.
        if (!$res) {
          $this->interact(
          DB_RETURN_RES,
              "UPDATE {$this->subscribers_table}
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

    // {{{ Method: user_unsubscribe()
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
    public function user_unsubscribe($user_id, $thread, $forum_id=0)
    {
        settype($user_id, 'int');
        settype($forum_id, 'int');
        settype($thread, 'int');

        $forum_where = $forum_id ? "AND forum_id = $forum_id" : '';

        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->subscribers_table}
             WHERE  user_id = $user_id AND
                    thread  = $thread
                    $forum_where",
            NULL,
            DB_MASTERQUERY
        );

        return TRUE;
    }
    // }}}

    // {{{ Method: user_increment_posts()
    /**
     * Increment the posts counter for a user.
     *
     * @param integer $user_id
     *     The user_id for which to increment the posts counter.
     */
    public function user_increment_posts($user_id)
    {
        settype($user_id, 'int');

        $res = 0;
        if (!empty($user_id)) {
            $res = $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->user_table}
                 SET    posts = posts + 1
                 WHERE  user_id = $user_id",
                NULL,
                DB_MASTERQUERY
            );
        }
        return $res;
    }
    // }}}

    // {{{ Method: user_get_groups()
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
    public function user_get_groups($user_id)
    {
        settype($user_id, 'int');

        // Retrieve the groups for the user_id from the database.
        $groups = $this->interact(
            DB_RETURN_ROWS,
            "SELECT group_id,
                    status
             FROM   {$this->user_group_xref_table}
             WHERE  user_id = $user_id
             ORDER  BY status DESC",
            0 // keyfield 0 is the group_id
        );

        // The records are full rows, but we want a group_id -> status mapping.
        foreach ($groups as $id => $group) $groups[$id] = $group[1];

        return $groups;
    }
    // }}}

    // {{{ Method: user_get_unapproved()
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
    public function user_get_unapproved()
    {
        $users = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT user_id,
                    username,
                    email
             FROM   {$this->user_table}
             WHERE  active in (".PHORUM_USER_PENDING_BOTH.",
                               ".PHORUM_USER_PENDING_MOD.")
             ORDER  BY username",
            'user_id'
        );

        return $users;
    }
    // }}}

    // {{{ Method: user_delete()
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
    public function user_delete($user_id)
    {
        global $PHORUM;

        settype($user_id, 'int');

        // Retrieve a list of private mesage xrefs for this user. After we
        // delete the pm xrefs for this user in the code afterwards, we might
        // have created orphan PM messages (messages with no xrefs linked to
        // them), so we'll have to check for that later on.
        $pmxrefs = $this->interact(
            DB_RETURN_ROWS,
            "SELECT pm_message_id
             FROM   {$this->pm_xref_table}
             WHERE  user_id = $user_id",
            NULL,
            DB_MASTERQUERY
        );

        // These are tables that hold user related data.
        $tables = array (
            $this->user_table,
            $this->user_permissions_table,
            $this->user_newflags_min_id_table,
            $this->user_newflags_table,
            $this->subscribers_table,
            $this->user_group_xref_table,
            $this->pm_buddies_table,
            $this->pm_folders_table,
            $this->pm_xref_table
        );

        // Delete the data for the $user_id from all those tables.
        foreach ($tables as $table) {
            $this->interact(
                DB_RETURN_RES,
                "DELETE FROM $table
                 WHERE user_id = $user_id",
                NULL,
                DB_GLOBALQUERY | DB_MASTERQUERY
            );
        }

        $this->delete_custom_fields(PHORUM_CUSTOM_FIELD_USER,$user_id);


        // See if we created any orphan private messages. We do this in
        // a loop using the standard pm_update_message_info()
        // function and not a single SQL statement with something like
        // pm_message_id IN (...) in it, because MySQL won't use an index
        // for that, making the full lookup very slow on large PM tables.
        foreach ($pmxrefs as $row) {
            $this->pm_update_message_info($row[0]);
        }

        // Change the forum postings into anonymous postings.
        // If PHORUM_DELETE_CHANGE_AUTHOR is set, then the author field is
        // updated to {LANG->AnonymousUser}.
        $author = 'author';

        if (defined('PHORUM_DELETE_CHANGE_AUTHOR') && PHORUM_DELETE_CHANGE_AUTHOR) {
            $anonymous = $PHORUM['DATA']['LANG']['AnonymousUser'];
            $author = "'".$this->interact(DB_RETURN_QUOTED, $anonymous)."'";
        }

        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->message_table}
             SET    user_id = 0,
                    email   = '',
                    author  = $author
             WHERE  user_id = $user_id",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->message_table}
             SET    recent_user_id = 0,
                    recent_author  = $author
             WHERE  recent_user_id = $user_id",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        return TRUE;
    }
    // }}}

    // {{{ Method: delete_custom_fields()
    public function delete_custom_fields($type,$relation_id)
    {
        if (is_array($relation_id)) {
            $rel_where = "relation_id IN (".implode(',',$relation_id).")";
        } else {
            $rel_where = "relation_id = $relation_id";
        }

        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->custom_fields_table}
             WHERE  $rel_where AND
                    field_type =".$type,
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );
    }
    // }}}

    // {{{ Method: get_file_list()
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
    public function get_file_list($link_type = NULL, $user_id = NULL, $message_id = NULL)
    {
        $where = '';
        $clauses = array();
        if ($link_type !== NULL) {
            $qtype = $this->interact(DB_RETURN_QUOTED, $link_type);
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

        return $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT file_id,
                    filename,
                    filesize,
                    add_datetime
             FROM   {$this->files_table}
             $where
             ORDER  BY file_id",
            'file_id'
        );
    }
    // }}}

    // {{{ Method: get_user_file_list()
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
    public function get_user_file_list($user_id)
    {
        return $this->get_file_list(PHORUM_LINK_USER, $user_id, 0);
    }
    // }}}

    // {{{ Method: get_message_file_list()
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
    public function get_message_file_list($message_id)
    {
        return $this->get_file_list(PHORUM_LINK_MESSAGE, NULL, $message_id);
    }
    // }}}

    // {{{ Method: file_get()
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
    public function file_get($file_id, $include_file_data = TRUE)
    {
        settype($file_id, 'int');

        $fields = "file_id, user_id, filename, filesize, " .
                  "add_datetime, message_id, link";
        if ($include_file_data) $fields .= ",file_data";

        // Select the file from the database.
        $files = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT $fields
             FROM   {$this->files_table}
             WHERE  file_id = $file_id"
        );

        if (count($files) == 0) {
            return array();
        } else {
            return $files[0];
        }
    }
    // }}}

    // {{{ Method: file_save()
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
    public function file_save($file)
    {
        // If a link type is not provided, we'll guess for the type of link.
        // This is done to provide some backward compatibility.
        if ($file["link"] === NULL) {
            if     (!empty($file['message_id'])) $file["link"] = PHORUM_LINK_MESSAGE;
            elseif (!empty($file['user_id']))    $file["link"] = PHORUM_LINK_USER;
            else trigger_error(
                __METHOD__ . ': Missing link field in the $file parameter',
                E_USER_ERROR
            );
        }

        $user_id    = (int)$file["user_id"];
        $message_id = (int)$file["message_id"];
        $filesize   = (int)$file["filesize"];
        $file_id    = !isset($file["file_id"]) || $file["file_id"] === NULL
                    ? NULL : (int)$file["file_id"];
        $link       = $this->interact(DB_RETURN_QUOTED, $file["link"]);
        $filename   = $this->interact(DB_RETURN_QUOTED, $file["filename"]);
        $file_data  = $this->interact(DB_RETURN_QUOTED, $file["file_data"]);
        $datetime   = empty($file['add_datetime'])
                    ? time() : (int)$file['add_datetime'];

        // Create a new file record.
        if ($file_id === NULL) {
            $file_id = $this->interact(
                DB_RETURN_NEWID,
                "INSERT INTO {$this->files_table}
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
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->files_table}
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

    // {{{ Method: file_delete()
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
    public function file_delete($file_id)
    {
        settype($file_id, 'int');

        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->files_table}
             WHERE  file_id = $file_id",
            NULL,
            DB_MASTERQUERY
        );

        return TRUE;
    }
    // }}}

    // {{{ Method: file_link()
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
     *     See file_save() for the possible link types.
     *
     * @return boolean
     *     True if the file link was updated successfully. This function will
     *     always return TRUE, so we could do without a return value.
     *     The return value is here for backward compatibility.
     */
    public function file_link($file_id, $message_id, $link = NULL)
    {
        settype($file_id, 'int');
        settype($message_id, 'int');

        $link = $link === NULL
              ? PHORUM_LINK_MESSAGE
              : $this->interact(DB_RETURN_QUOTED, $link);

        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->files_table}
             SET    message_id = $message_id,
                    link       = '$link'
             WHERE  file_id    = $file_id",
            NULL,
            DB_MASTERQUERY
        );

        return TRUE;
    }
    // }}}

    // {{{ Method: get_user_filesize_total()
    /**
     * Retrieve the total size for all personal files for a user.
     *
     * @param integer $user_id
     *     The user to compute the total size for.
     *
     * @return integer
     *     The total size in bytes.
     */
    public function get_user_filesize_total($user_id)
    {
        settype($user_id, 'int');

        $size = $this->interact(
            DB_RETURN_VALUE,
            "SELECT SUM(filesize)
             FROM   {$this->files_table}
             WHERE  user_id    = $user_id AND
                    message_id = 0 AND
                    link       = '".PHORUM_LINK_USER."'"
        );

        return $size;
    }
    // }}}

    // {{{ Method: list_stale_files()
    /**
     * Retrieve a list of stale files from the database.
     *
     * Stale files are files that are not linked to anything anymore.
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
    public function list_stale_files()
    {
        // Select orphan editor files.
        // These are files that are linked to the editor and that were added
        // a while ago. These are from posts that were abandoned before posting.
        $stale_files = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT file_id,
                    filename,
                    filesize,
                    add_datetime,
                    'Attachments, left behind by unposted messages' AS reason
             FROM   {$this->files_table}
             WHERE  ( link = '".PHORUM_LINK_EDITOR."' OR link = '".PHORUM_LINK_TEMPFILE."' )
                    AND
                    add_datetime < ". (time()-PHORUM_MAX_EDIT_TIME),
            'file_id',
            DB_GLOBALQUERY
        );

        return $stale_files;
    }
    // }}}

    // {{{ Method: list_stale_messages()
    /**
     * Retrieve a list of stale messages from the database.
     *
     * Stale messages are messages that are not linked to an existing
     * thread anymore. This should not happen on a healthy system, but
     * we have had some bugs in the past that could result in stale
     * messages in the database.
     *
     * @return array
     *     An array of stale Phorum messages.
     */
    public function list_stale_messages()
    {

        return $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM   {$this->message_table} m1
             WHERE  NOT EXISTS (
                 SELECT *
                 FROM   {$this->message_table} m2
                 WHERE  m2.message_id = m1.thread
             )",
            'message_id',
            DB_GLOBALQUERY
        );
    }
    // }}}

    // {{{ Method: newflag_allread()
    /**
     * Mark all messages for a forum read for the active Phorum user.
     *
     * @param integer $forum_id
     *     The forum to mark read or 0 (zero) to mark the current forum read.
     */
    public function newflag_allread($forum_id = 0)
    {
        global $PHORUM;

        if (empty($forum_id)) $forum_id = $PHORUM['forum_id'];
        settype($forum_id, 'int');

        // Delete all the existing newflags for this user for this forum.
        $this->newflag_delete(0, $forum_id);

        // Retrieve the maximum message_id in this forum.
        $max_id = $this->interact(
            DB_RETURN_VALUE,
            "SELECT max(message_id)
             FROM   {$this->message_table}
             WHERE  forum_id = $forum_id"
        );

        // Set this message_id as the min-id for the forum.
        if ($max_id) {
            $this->newflag_add_min_id(array(array(
                'min_id'   => $max_id,
                'forum_id' => $forum_id
            )));
        }
    }
    // }}}

    // {{{ Method: newflag_get_flags()
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
    public function newflag_get_flags($forum_id = NULL)
    {
        global $PHORUM;

        if ($forum_id === NULL) $forum_id = $PHORUM['forum_id'];
        settype($forum_id, 'int');

        // Select the read messages from the newflags table.
        $newflags = $this->interact(
            DB_RETURN_ROWS,
            "SELECT message_id
             FROM   {$this->user_newflags_table}
             WHERE  user_id  = {$PHORUM['user']['user_id']} AND
                    forum_id = $forum_id
             ORDER  BY message_id ASC",
            0
        );

        // Select the forum's min_id for the current user.
        $min_id = $this->interact(
            DB_RETURN_VALUE,
            "SELECT min_id
             FROM   {$this->user_newflags_min_id_table}
             WHERE  user_id  = {$PHORUM['user']['user_id']} AND
                    forum_id = $forum_id"
        );

        // Initialize the forum's min_id for the current user in case
        // no min_id is available in the database yet.
        if ($min_id === NULL) {
            $this->newflag_add_min_id(array(array(
                'min_id'   => 0,
                'forum_id' => $forum_id
            )));
            $min_id = 0;
        }

        $newflags['min_id'] = $min_id;

        return $newflags;
    }
    // }}}

    // {{{ Method: newflag_check()
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
    public function newflag_check($forum_ids)
    {
        global $PHORUM;

        $this->sanitize_mixed($forum_ids, 'int');

        // Retrieve the min_id per forum.
        $min_ids = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT forum_id, min_id as message_id
             FROM   {$this->user_newflags_min_id_table}
             WHERE  user_id = " . $PHORUM["user"]["user_id"],
            "forum_id"
        );

        // Retrieve the number of newflags per forum (these should all
        // be newflags that have message_id > min_id.
        $counts = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT forum_id, count(*) as count
             FROM   {$this->user_newflags_table}
             WHERE  user_id=".$PHORUM["user"]["user_id"]."
             GROUP  BY forum_id",
            "forum_id"
        );

        $new_checks = array();

        foreach ($forum_ids as $forum_id)
        {
            // No min_id available for this forum. This is a completely new
            // forum for the user, so all the messages in it are new as well.
            if (empty($min_ids[$forum_id])) {
                $new_checks[$forum_id] = TRUE;
                continue;
            }

            // When no newflags exist in the database, then use a count of 0.
            if (empty($counts[$forum_id])) {
                $counts[$forum_id]["count"] = 0;
            }

            // Check how many messages exist in the database, which have
            // a message_id that is higher than the min_id for the forum.
            $count = $this->interact(
                DB_RETURN_VALUE,
                "SELECT count(*)
                 FROM  {$this->message_table}
                 WHERE forum_id=".$forum_id." AND
                       message_id > {$min_ids[$forum_id]["message_id"]} AND
                       status = " . PHORUM_STATUS_APPROVED . " AND
                       moved = 0"
            );

            // If we have more messages beyond the min_id than we have
            // newflags, then we have one or more new messages to read
            // for the user.
            $new_checks[$forum_id] = ($count > $counts[$forum_id]["count"]);
        }

        return $new_checks;

    }
    // }}}

    // {{{ Method: newflag_count()
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
    public function newflag_count($forum_ids)
    {
        global $PHORUM;

        $this->sanitize_mixed($forum_ids, 'int');

        $user_id = $PHORUM['user']['user_id'];

        // Get a list of forum_ids and minimum message ids from the
        // min_id table.
        $min_ids = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT forum_id, min_id AS message_id
             FROM   {$this->user_newflags_min_id_table}
             WHERE  user_id = $user_id",
            'forum_id'
        );

        // Get the total number of messages the user has read in each forum.
        $message_counts = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT flags.forum_id,
                    count(*) AS count
             FROM   {$this->user_newflags_table} AS flags
                    INNER JOIN {$this->message_table} AS msg
                    ON msg.message_id = flags.message_id AND
                       msg.forum_id   = flags.forum_id
             WHERE  flags.user_id = $user_id AND
                    status = ".PHORUM_STATUS_APPROVED."
             GROUP  BY flags.forum_id",
            'forum_id'
        );

        // Get the number of threads the user has read in each forum.
        $thread_counts = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT flags.forum_id AS forum_id,
                    count(*) AS count
             FROM   {$this->user_newflags_table} AS flags
                    INNER JOIN {$this->message_table} AS msg
                    ON flags.message_id = msg.message_id AND
                       flags.forum_id   = msg.forum_id
             WHERE  flags.user_id = $user_id AND
                    parent_id = 0 AND
                    status = ".PHORUM_STATUS_APPROVED."
             GROUP  BY flags.forum_id",
            'forum_id'
        );

        $new_checks = array();

        foreach ($forum_ids as $forum_id)
        {
            if (empty($min_ids[$forum_id]))
            {
                // No newflags for this user and forum.
                // Make it -1 for later processing. The calling code should
                // use the forum's thread_count and message_count in this case.
                $new_checks[$forum_id] = array('messages' => -1, 'threads' => -1);
            }
            else
            {
                // Find the number of new messages.
                $count = $this->interact(
                    DB_RETURN_VALUE,
                    "SELECT count(*) AS count
                     FROM   {$this->message_table}
                     WHERE  forum_id = $forum_id AND
                            message_id > {$min_ids[$forum_id]['message_id']} AND
                            status = ".PHORUM_STATUS_APPROVED." AND
                            moved = 0"
                );
                if (isset($message_counts[$forum_id]["count"])) {
                    $new_checks[$forum_id]["messages"] =
                        max(0, $count - $message_counts[$forum_id]["count"]);
                } else {
                    $new_checks[$forum_id]["messages"] =
                        max(0, $count);
                }

                // Find the number of new threads.
                $count = $this->interact(
                    DB_RETURN_VALUE,
                    "SELECT count(*) AS count
                     FROM   {$this->message_table}
                     WHERE  forum_id = $forum_id AND
                            message_id > {$min_ids[$forum_id]["message_id"]} AND
                            parent_id = 0 AND
                            status = ".PHORUM_STATUS_APPROVED." AND
                            moved = 0"
                );
                if (isset($thread_counts[$forum_id]["count"])) {
                    $new_checks[$forum_id]["threads"] =
                        max(0, $count - $thread_counts[$forum_id]["count"]);
                } else {
                    $new_checks[$forum_id]["threads"] = max(0, $count);
                }
            }
        }

        return $new_checks;
    }
    // }}}

    // {{{ Method: newflag_get_unread_count()
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
    public function newflag_get_unread_count($forum_id=NULL)
    {
        global $PHORUM;

        if ($forum_id === NULL) $forum_id = $PHORUM['forum_id'];
        settype($forum_id, 'int');

        // Retrieve the minimum message_id from newflags for the forum.
        $min_message_id = $this->interact(
            DB_RETURN_VALUE,
            "SELECT  min_id
             FROM    {$this->user_newflags_min_id_table}
             WHERE   user_id  = {$PHORUM['user']['user_id']} AND
                     forum_id = {$forum_id}"
        );

        // No result found? Then we know that the user never read a
        // message from this forum. We won't count the new messages
        // in that case. Return an empty result.
        if (!$min_message_id) return array(0,0);

        // Retrieve the unread thread count.
        $new_threads = $this->interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM {$this->message_table} AS m
                  LEFT JOIN {$this->user_newflags_table} AS n ON
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
        $new_messages = $this->interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM   {$this->message_table} AS m
                    LEFT JOIN {$this->user_newflags_table} AS n ON
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

    // {{{ Method: newflag_add_min_id()
    public function newflag_add_min_id($min_ids)
    {
        global $PHORUM;

        $user_id = $PHORUM['user']['user_id'];

        foreach ($min_ids as $min_id)
        {
            settype($min_id['forum_id'], 'int');
            settype($min_id['min_id'], 'int');

            // We ignore duplicate record errors here.
            $res = $this->interact(
                DB_RETURN_RES,
                "INSERT INTO {$this->user_newflags_min_id_table}
                        (user_id, forum_id, min_id)
                 VALUES ($user_id, {$min_id['forum_id']}, {$min_id['min_id']})",
                NULL,
                DB_DUPKEYOK | DB_MASTERQUERY
            );
            if (!$res) {
                // No res returned, therefore that key probably exists already.
                $this->interact(
                    DB_RETURN_RES,
                    "UPDATE {$this->user_newflags_min_id_table}
                     SET    min_id = {$min_id['min_id']}
                     WHERE  user_id  = $user_id AND
                            forum_id = {$min_id['forum_id']}",
                    NULL,
                    DB_MASTERQUERY
                );
            }
        }
    }
    // }}}

    // {{{ Method: newflag_add_read()
    /**
     * Mark a message as read for the active Phorum user.
     *
     * @param mixed $message_ids
     *     The message_id of the message to mark read in the active forum or
     *     an array description of messages to mark read. Elements in this
     *     array can be:
     *     - Simple message_id values, to mark messages read in the
     *       active forum.
     *     - An array containing two fields: "forum_id" containing a
     *       forum_id and "id" containing a message_id. This notation can
     *       be used to mark messages read in other forums than te active one.
     */
    public function newflag_add_read($message_ids)
    {
        global $PHORUM;

        // Find the number of newflags for the user
        $num_newflags = $this->newflag_get_count();

        if (!is_array($message_ids)) {
            $message_ids = array(0 => $message_ids);
        }

        // Delete newflags which would exceed the maximum number of
        // newflags that are allowed in the database per user.
        $num_end = $num_newflags + count($message_ids);
        if ($num_end > PHORUM_MAX_READ_COUNT_PER_FORUM) {
            $this->newflag_delete($num_end - PHORUM_MAX_READ_COUNT_PER_FORUM);
        }

        // Insert newflags.
        $inserts = array();
        foreach ($message_ids as $id => $data)
        {
            if (is_array($data)) {
                $user_id    = $PHORUM['user']['user_id'];
                $forum_id   = (int)$data['forum_id'];
                $message_id = (int)$data['id'];
            } else {
                $user_id    = $PHORUM['user']['user_id'];
                $forum_id   = $PHORUM['forum_id'];
                $message_id = (int)$data;
            }
            $values = "($user_id,$forum_id,$message_id)";
            $inserts[$values] = $values;
        }

        if (count($inserts))
        {
            $res = NULL;

            // Insert all records in one call when multiple inserts
            // are supported or when just one record has to be inserted.
            if (count($inserts) == 1 || $this->_can_insert_multiple)
            {
                // Try to insert the values (in a single query for speed.)
                // For systems that support "INSERT IGNORE", this query
                // will be all that we need.
                $INSERT = $this->_can_INSERT_IGNORE
                        ? 'INSERT IGNORE' : 'INSERT';
                $res = $this->interact(
                    DB_RETURN_RES,
                    "$INSERT INTO {$this->user_newflags_table}
                             (user_id, forum_id, message_id)
                     VALUES " . implode(",", $inserts),
                    NULL,
                    DB_DUPKEYOK | DB_MASTERQUERY
                );
            }

            // Multiple inserts are not available for the database system or
            // inserting the newflags failed.
            //
            // If inserting the values failed, then this most probably means
            // that one of the values already existed in the database, causing
            // a duplicate key error. We fallback to one-by-one insertion, so
            // the other records in the list will be created.
            if (!$res)
            {
                foreach ($inserts as $values)
                {
                    $res = $this->interact(
                        DB_RETURN_RES,
                        "INSERT INTO {$this->user_newflags_table}
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

    // {{{ Method: newflag_get_count()
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
    public function newflag_get_count($forum_id=0)
    {
        global $PHORUM;

        if (empty($forum_id)) $forum_id = $PHORUM['forum_id'];
        settype($forum_id, 'int');

        $count = $this->interact(
            DB_RETURN_VALUE,
            "SELECT count(*)
             FROM   {$this->user_newflags_table}
             WHERE  user_id  = {$PHORUM['user']['user_id']} AND
                    forum_id = {$forum_id}"
        );

        return $count;
    }
    // }}}

    // {{{ Method: newflag_delete()
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
    public function newflag_delete($numdelete = 0, $forum_id = 0)
    {
        global $PHORUM;

        if (empty($forum_id)) $forum_id = $PHORUM['forum_id'];
        settype($numdelete, 'int');
        settype($forum_id, 'int');

        $order = $numdelete > 0 ? 'ORDER BY message_id ASC' : '';

        // Delete the provided amount of newflags.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->user_newflags_table}
             WHERE  user_id  = {$PHORUM['user']['user_id']} AND
                    forum_id = {$forum_id}
             $order",
            NULL,
            DB_MASTERQUERY,
            $numdelete
        );
        // lets recalculate the new min_id
        if ($numdelete > 0) {
            // Retrieve the maximum message_id in this forum.
            $min_id = $this->interact(
                DB_RETURN_VALUE,
                "SELECT min(message_id)
                 FROM   {$this->user_newflags_table}
                 WHERE  forum_id = $forum_id AND
                        user_id = {$PHORUM['user']['user_id']}"
            );

            // Set this message_id as the min-id for the forum.
            if ($min_id) {
                $this->newflag_add_min_id(array(array(
                    'min_id'   => $min_id,
                    'forum_id' => $forum_id
                )));
            }
        }
    }
    // }}}

    // {{{ Method: newflag_update_forum()
    /**
     * Update the forum_id for the newflags. The newflags are updated by
     * setting their forum_ids to the forum_ids of the referenced message
     * table records.
     *
     * @param array $message_ids
     *     An array of message_ids which should be updated.
     */
    public function newflag_update_forum($message_ids)
    {
        $this->sanitize_mixed($message_ids, 'int');
        $ids_str = implode(', ', $message_ids);

        // If the database system supports "UPDATE IGNORE", then we
        // can use the following query for updating the forum_id of
        // the newflags.
        if ($this->_can_UPDATE_IGNORE)
        {
            $flags = $this->user_newflags_table;
            $msg   = $this->message_table;
            return $this->interact(
                DB_RETURN_RES,
                "UPDATE IGNORE $flags
                 SET forum_id = (
                     SELECT $msg.forum_id
                     FROM $msg
                     WHERE $flags.message_id = $msg.message_id
                 )
                 WHERE message_id IN ($ids_str)",
                NULL,
                DB_MASTERQUERY
            );
        }

        // No implementation is available. One needs to be implemented
        // in the derived database layer.
        trigger_error(
            __METHOD__ . ': no implementation available; one needs to ' .
            'be provided in the derived database layer class.',
            E_USER_ERROR
        );

        return true;
    }
    // }}}

    // {{{ Method: user_list_subscribers()
    /**
     * Retrieve the email addresses of the users that are subscribed to a
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
     *     function {@link user_subscribe()} for available
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
    public function user_list_subscribers($forum_id, $thread, $type, $ignore_active_user=TRUE)
    {
        global $PHORUM;

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
        $users = $this->interact(
            DB_RETURN_ROWS,
            "SELECT DISTINCT(u.email) AS email,
                    user_language
             FROM   {$this->subscribers_table} AS s,
                    {$this->user_table} AS u
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

    // {{{ Method: user_list_subscriptions()
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
    public function user_list_subscriptions($user_id, $days=0, $forum_ids=NULL)
    {

        settype($user_id, 'int');
        settype($days, 'int');
        if ($forum_ids !== NULL) $this->sanitize_mixed($forums_ids, 'int');

        $time_where = $days > 0
                    ? " AND (".time()." - m.modifystamp) <= ($days * 86400)"
                    : '';

        $forum_where = ($forum_ids !== NULL and is_array($forum_ids))
                     ? " AND s.forum_id IN (" . implode(",", $forum_ids) . ")"
                     : '';

        // Retrieve all subscribed threads from the database for which the
        // latest message in the thread was posted within the provided
        // time limit.
        $threads = $this->interact(
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
             FROM   {$this->subscribers_table} AS s,
                    {$this->message_table} AS m
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

    // {{{ Method: user_get_subscription()
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
     *     user_subscribe().
     */
    public function user_get_subscription($user_id, $forum_id, $thread)
    {

        settype($user_id, 'int');
        settype($forum_id, 'int');
        settype($thread, 'int');
        settype($type, 'int');

        $type = $this->interact(
            DB_RETURN_VALUE,
            "SELECT sub_type
             FROM   {$this->subscribers_table}
             WHERE  forum_id = $forum_id AND
                    thread   = $thread AND
                    user_id  = $user_id"
        );

        return $type;
    }
    // }}}

    // {{{ Method: get_banlists()
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
     *     mod_banlists(). Each value for a ban type is an array of
     *     bans. Each ban in those arrays is an array containing the fields:
     *     prce, string and forum_id.
     */
    public function get_banlists($ordered=FALSE)
    {
        global $PHORUM;

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

        $bans = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM {$this->banlist_table}
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

    // {{{ Method: get_banitem()
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
    public function get_banitem($banid)
    {

        settype($banid, 'int');

        $bans = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT *
             FROM   {$this->banlist_table}
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

    // {{{ Method: del_banitem()
    /**
     * Delete a single ban item from the ban lists.
     *
     * @param integer $banid
     *     The id of the ban item to delete.
     */
    public function del_banitem($banid)
    {

        settype($banid, 'int');

        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->banlist_table}
             WHERE  id = $banid",
            NULL,
            DB_MASTERQUERY
        );
    }
    // }}}

    // {{{ Method: mod_banlists()
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
    public function mod_banlists($type, $pcre, $string, $forum_id, $comments, $banid=0)
    {

        $retarr = array();

        settype($type, 'int');
        settype($pcre, 'int');
        settype($forum_id, 'int');
        settype($banid, 'int');

        $string = $this->interact(DB_RETURN_QUOTED, $string);
        $comments = $this->interact(DB_RETURN_QUOTED, $comments);

        // Update an existing ban item.
        if ($banid > 0) {
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->banlist_table}
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
            $this->interact(
                DB_RETURN_RES,
                "INSERT INTO {$this->banlist_table}
                        (forum_id, type, pcre, string, comments)
                 VALUES ($forum_id, $type, $pcre, '$string', '$comments')",
                NULL,
                DB_MASTERQUERY
            );
        }

        return TRUE;
    }
    // }}}

    // {{{ Method: pm_list()
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
    public function pm_list($folder, $user_id = NULL, $reverse = TRUE)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');
        settype($reverse, 'bool');

        $folder_where = "";

        if (is_numeric($folder)) {
            $folder_where = "pm_folder_id = $folder";
        } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
            $folder_where = "(pm_folder_id = 0 AND special_folder = '$folder')";
        } else trigger_error(
            __METHOD__ . ': Illegal folder "'.htmlspecialchars($folder).'" '.
            'requested for user id "'.$user_id.'"',
            E_USER_ERROR
        );

        // Retrieve the messages from the folder.
        $messages = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT m.pm_message_id AS pm_message_id,
                    m.user_id,       author,
                    subject,         datestamp,
                    meta,            pm_xref_id,
                    pm_folder_id,    special_folder,
                    read_flag,       reply_flag
             FROM   {$this->pm_messages_table} AS m,
                    {$this->pm_xref_table} AS x
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

    // {{{ Method: pm_get()
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
    public function pm_get($pm_id, $folder = NULL, $user_id = NULL)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');
        settype($pm_id, 'int');

        $folder_where = "";

        if ($folder === NULL) {
            $folder_where = '';
        } elseif (is_numeric($folder)) {
            $folder_where = "pm_folder_id = $folder AND ";
        } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
            $folder_where = "pm_folder_id = 0 AND special_folder = '$folder' AND ";
        } else trigger_error(
            __METHOD__ . ': Illegal folder "'.htmlspecialchars($folder).'" '.
            'requested for user id "'.$user_id.'"',
            E_USER_ERROR
        );

        // Retrieve the private message.
        $messages = $this->interact(
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
             FROM {$this->pm_messages_table} AS m,
                  {$this->pm_xref_table} AS x
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

    // {{{ Method: pm_create_folder()
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
    public function pm_create_folder($foldername, $user_id = NULL)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');
        $foldername = $this->interact(DB_RETURN_QUOTED, $foldername);

        $pm_folder_id = $this->interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$this->pm_folders_table}
                    (user_id, foldername)
             VALUES ($user_id, '$foldername')",
            NULL,
            DB_MASTERQUERY
        );

        return $pm_folder_id;
    }
    // }}}

    // {{{ Method: pm_rename_folder()
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
    public function pm_rename_folder($folder_id, $newname, $user_id = NULL)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');
        settype($folder_id, 'int');
        $newname = $this->interact(DB_RETURN_QUOTED, $newname);

        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->pm_folders_table}
             SET    foldername = '$newname'
             WHERE  pm_folder_id = $folder_id AND
                    user_id = $user_id",
            NULL,
            DB_MASTERQUERY
        );
    }
    // }}}

    // {{{ Method: pm_delete_folder()
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
    public function pm_delete_folder($folder_id, $user_id = NULL)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');
        settype($folder_id, 'int');

        // Retrieve the private messages in this folder and delete them.
        $list = $this->pm_list($folder_id, $user_id);
        foreach ($list as $id => $data) {
            $this->pm_delete($id, $folder_id, $user_id);
        }

        // Delete the folder itself.
        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->pm_folders_table}
             WHERE pm_folder_id = $folder_id AND
                   user_id      = $user_id",
            NULL,
            DB_MASTERQUERY
        );

        $this->pm_update_user_info($user_id);
    }
    // }}}

    // {{{ Method: pm_getfolders()
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
    public function pm_getfolders($user_id = NULL, $count = FALSE)
    {
        global $PHORUM;

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
        $customfolders = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT pm_folder_id AS id,
                    foldername   AS name
             FROM   {$this->pm_folders_table}
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
            $countinfo = $this->interact(
                DB_RETURN_ASSOCS,
                "SELECT pm_folder_id,
                        special_folder,
                        count(*) AS total,
                        (count(*) - sum(read_flag)) AS new
                 FROM   {$this->pm_xref_table}
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

    // {{{ Method: pm_messagecount()
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
    public function pm_messagecount($folder, $user_id = NULL)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        $folder_where = "";

        if (is_numeric($folder)) {
            $folder_where = "pm_folder_id = $folder AND";
        } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
            $folder_where = "pm_folder_id = 0 AND special_folder = '$folder' AND";
        } elseif ($folder == PHORUM_PM_ALLFOLDERS) {
            $folder_where = '';
        } else trigger_error(
            __METHOD__ . ': Illegal folder "' .
            htmlspecialchars($folder).'" requested for user id "'.$user_id.'"',
            E_USER_ERROR
        );

        $counters = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT count(*) AS total,
                    (count(*) - sum(read_flag)) AS new
             FROM   {$this->pm_xref_table}
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

    // {{{ Method: pm_checknew()
    /**
     * Check if the user has new private messages.
     *
     * @param mixed $user_id
     *     The user to check for or NULL to use the active Phorum user's id
     *     (default, but note that directly checking the "pm_new_count"
     *     field for that user is more efficient).
     *
     * @return integer
     *     The number of new private messages for the user,
     *     zero when there are none.
     */
    public function pm_checknew($user_id = NULL)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        $new = $this->interact(
            DB_RETURN_VALUE,
            "SELECT pm_new_count
             FROM   {$this->user_table}
             WHERE  user_id   = $user_id",
            NULL, NULL, 1
        );

        return $new;
    }
    // }}}

    // {{{ Method: pm_send()
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
    public function pm_send(
        $subject, $message, $to, $from = NULL, $keepcopy = FALSE)
    {
        global $PHORUM;

        // Prepare the sender.
        if ($from === NULL) $from = $PHORUM['user']['user_id'];
        settype($from, 'int');
        $fromuser = $this->user_get($from, FALSE);
        if (! $fromuser) trigger_error(
            __METHOD__ . ": Unknown sender user_id '$from'",
            E_USER_ERROR
        );
        $fromuser = $this->interact(DB_RETURN_QUOTED, $fromuser['display_name']);
        $subject = $this->interact(DB_RETURN_QUOTED, $subject);
        $message = $this->interact(DB_RETURN_QUOTED, $message);

        // Prepare the list of recipients and xref entries.
        $xref_entries = array();
        $rcpts = array();
        if (! is_array($to)) $to = array($to);
        foreach ($to as $user_id)
        {
            settype($user_id, 'int');

            $user = $this->user_get($user_id, FALSE);
            if (! $user) trigger_error(
                __METHOD__ . ": Unknown recipient user_id '$user_id'",
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
        $meta = $this->interact(DB_RETURN_QUOTED, serialize(array(
            'recipients' => $rcpts
        )));

        // Create the message.
        $pm_id = $this->interact(
            DB_RETURN_NEWID,
            "INSERT INTO {$this->pm_messages_table}
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
            $this->interact(
                DB_RETURN_RES,
                "INSERT INTO {$this->pm_xref_table}
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

        $this->pm_update_user_info($to);

        return $pm_id;
    }
    // }}}

    // {{{ Method: pm_setflag()
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
    public function pm_setflag($pm_id, $flag, $value, $user_id = NULL)
    {
        global $PHORUM;

        settype($pm_id, 'int');

        if ($flag != PHORUM_PM_READ_FLAG &&
            $flag != PHORUM_PM_REPLY_FLAG) trigger_error(
            __METHOD__ . ': Illegal value "' . htmlspecialchars($flag) .
            '" for parameter $flag',
            E_USER_WARNING
        );

        $value = $value ? 1 : 0;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        // Update the flag in the database.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->pm_xref_table}
             SET    $flag = $value
             WHERE  pm_message_id = $pm_id AND
                    user_id       = $user_id",
            NULL,
            DB_MASTERQUERY
        );

        // Update message counters.
        if ($flag == PHORUM_PM_READ_FLAG) {
            $this->pm_update_message_info($pm_id);
            $this->pm_update_user_info($user_id);
        }
    }
    // }}}

    // {{{ Method: pm_delete()
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
    public function pm_delete($pm_id, $folder, $user_id = NULL)
    {
        global $PHORUM;

        settype($pm_id, 'int');

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        $folder_where="";

        if (is_numeric($folder)) {
            $folder_where = "pm_folder_id = $folder";
        } elseif ($folder == PHORUM_PM_INBOX || $folder == PHORUM_PM_OUTBOX) {
            $folder_where = "(pm_folder_id = 0 AND special_folder = '$folder')";
        } else trigger_error(
            __METHOD__ . ': Illegal folder "' .
            htmlspecialchars($folder).'" requested for user id "'.$user_id.'"',
            E_USER_ERROR
        );

        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->pm_xref_table}
             WHERE user_id       = $user_id AND
                   pm_message_id = $pm_id AND
                   $folder_where",
            NULL,
            DB_MASTERQUERY
        );

        // Update message counters.
        $this->pm_update_message_info($pm_id);
        $this->pm_update_user_info($user_id);
    }
    // }}}

    // {{{ Method: pm_move()
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
    public function pm_move($pm_id, $from, $to, $user_id = NULL)
    {
        global $PHORUM;

        settype($pm_id, 'int');

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        // init vars
        $pm_folder_id   = 0;
        $special_folder = "";
        $folder_where   = "";


        if (is_numeric($from)) {
            $folder_where = "pm_folder_id = $from";
        } elseif ($from == PHORUM_PM_INBOX || $from == PHORUM_PM_OUTBOX) {
            $folder_where = "(pm_folder_id = 0 AND special_folder = '$from')";
        } else trigger_error(
            __METHOD__ . ': Illegal source folder "' .
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
            __METHOD__ . ': Illegal target folder "' .
            htmlspecialchars($to).'" requested for user_id "'.$user_id.'"',
            E_USER_ERROR
        );

        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->pm_xref_table}
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

    // {{{ Method: pm_update_user_info()
    /**
     * Update the pm_new_count field for one or more users.
     *
     * @param mixed $user_id
     *   One user_id or an array of user_ids.
     */
    public function pm_update_user_info($user_id)
    {
        if (!is_array($user_id)) {
            $user_id = array($user_id);
        }
        $user_ids = array();
        foreach ($user_id as $id) {
            $user_ids[] = (int) $id;
        }

        if (empty($user_ids)) return;

        $this->interact(
            DB_RETURN_RES,
            "UPDATE $this->user_table u
             SET pm_new_count = (
                 SELECT count(*)
                 FROM   $this->pm_xref_table x
                 WHERE  read_flag = 0 AND u.user_id = x.user_id
             )
             WHERE u.user_id IN (" . implode(', ', $user_ids) . ")"
        );
    }
    // }}}

    // {{{ Method: pm_update_message_info()
    /**
     * Update the meta information for a message.
     *
     * This function will update the meta information using the information
     * from the xrefs table. If we find that no xrefs are available for the
     * message anymore, the message will be deleted from the database.
     *
     * @param integer $pm_id
     *     The id of the private message for which to update the
     *     meta information.
     */
    public function pm_update_message_info($pm_id)
    {
        settype($pm_id, 'int');

        // Retrieve the meta data for the private message.
        $pm = $this->interact(
            DB_RETURN_ASSOC,
            "SELECT meta
             FROM   {$this->pm_messages_table}
             WHERE  pm_message_id = $pm_id",
            NULL,
            DB_MASTERQUERY
        );

        # Return immediately if no message was found.
        if (empty($pm)) return;

        // Find the xrefs for this message.
        $xrefs = $this->interact(
            DB_RETURN_ROWS,
            "SELECT user_id, read_flag
             FROM   {$this->pm_xref_table}
             WHERE  pm_message_id = $pm_id",
            NULL,
            DB_MASTERQUERY
        );

        // No xrefs left? Then the message can be fully deleted.
        if (count($xrefs) == 0) {
            $this->interact(
                DB_RETURN_RES,
                "DELETE FROM {$this->pm_messages_table}
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
        $meta = $this->interact(DB_RETURN_QUOTED, serialize($meta));
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->pm_messages_table}
             SET    meta = '$meta'
             WHERE  pm_message_id = $pm_id",
            NULL,
            DB_MASTERQUERY
        );
    }
    // }}}

    // {{{ Method: pm_is_buddy()
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
    public function pm_is_buddy($buddy_user_id, $user_id = NULL)
    {
        global $PHORUM;

        settype($buddy_user_id, 'int');

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        $pm_buddy_id = $this->interact(
            DB_RETURN_VALUE,
            "SELECT pm_buddy_id
             FROM   {$this->pm_buddies_table}
             WHERE  user_id       = $user_id AND
                    buddy_user_id = $buddy_user_id"
        );

        return $pm_buddy_id;
    }
    // }}}

    // {{{ Method: pm_buddy_add()
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
    public function pm_buddy_add($buddy_user_id, $user_id = NULL)
    {
        global $PHORUM;

        settype($buddy_user_id, 'int');

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        // Check if the buddy_user_id is a valid user_id.
        $valid = $this->user_get($buddy_user_id, FALSE);
        if (! $valid) return NULL;

        // See if the user is already a buddy.
        $pm_buddy_id = $this->pm_is_buddy($buddy_user_id);

        // If not, then create insert a new buddy relation.
        if ($pm_buddy_id === NULL) {
            $pm_buddy_id = $this->interact(
                DB_RETURN_NEWID,
                "INSERT INTO {$this->pm_buddies_table}
                        (user_id, buddy_user_id)
                 VALUES ($user_id, $buddy_user_id)",
                NULL,
                DB_MASTERQUERY
            );
        }

        return $pm_buddy_id;
    }
    // }}}

    // {{{ Method: pm_buddy_delete()
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
    public function pm_buddy_delete($buddy_user_id, $user_id = NULL)
    {
        global $PHORUM;

        settype($buddy_user_id, 'int');

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->pm_buddies_table}
             WHERE buddy_user_id = $buddy_user_id AND
                   user_id       = $user_id",
            NULL,
            DB_MASTERQUERY
        );
    }
    // }}}

    // {{{ Method: pm_buddy_list()
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
    public function pm_buddy_list($user_id = NULL, $find_mutual = FALSE)
    {
        global $PHORUM;

        if ($user_id === NULL) $user_id = $PHORUM['user']['user_id'];
        settype($user_id, 'int');

        settype($find_mutual, 'bool');

        // Retrieve all buddies for this user.
        $buddies = $this->interact(
            DB_RETURN_ASSOCS,
            "SELECT buddy_user_id AS user_id
             FROM {$this->pm_buddies_table}
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
        $mutuals = $this->interact(
            DB_RETURN_ROWS,
            "SELECT DISTINCT a.buddy_user_id AS buddy_user_id
             FROM {$this->pm_buddies_table} AS a,
                  {$this->pm_buddies_table} AS b
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

    // {{{ Method: split_thread()
    /**
     * Split a thread.
     *
     * @param integer $message_id
     *     The id of the message at which to split a thread.
     *
     * @param integer $forum_id
     *     The id of the forum in which the message can be found.
     *
     * @param NULL|string $new_subject
     *     A new subject to use for the thread starter message.
     *
     * @param boolean $update_subjects
     *     When TRUE (default is FALSE), the subject of all split off
     *     messages will be updated to match the $new_subject.
     */
    public function split_thread(
        $message_id, $forum_id, $new_subject = NULL, $update_subjects = FALSE)
    {
        settype($message_id, 'int');
        settype($forum_id, 'int');

        // By default, use the column name "subject", so we will assign
        // the existing column value to the subject field.
        $thread_subject = 'subject';
        $reply_subject  = 'subject';

        // Override these when requested.
        if ($new_subject !== NULL)
        {
            $quoted = $this->interact(DB_RETURN_QUOTED, $new_subject);
            $thread_subject = "'$quoted'";

            if ($update_subjects) {
                $reply_subject = "'Re: $quoted'";
            }
        }

        if ($message_id > 0 && $forum_id > 0)
        {
            // Retrieve the message tree for all messages below the split message.
            // This tree is used for updating the thread ids of the children
            // below the split message.
            $tree = $this->get_messagetree($message_id, $forum_id);

            // Link the messages below the split message to the split off thread.
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET    thread  = $message_id,
                        subject = $reply_subject
                 WHERE  message_id IN ($tree)",
                NULL,
                DB_MASTERQUERY
            );

            // Turn the split message into a thread starter message.
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->message_table}
                 SET    thread     = $message_id,
                        parent_id  = 0,
                        subject    = $thread_subject
                 WHERE  message_id = $message_id",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
    // }}}

    // {{{ Method: get_max_messageid()
    /**
     * Retrieve the maximum message_id in the database.
     *
     * @return integer $max_id
     *     The maximum available message_id or 0 (zero)
     *     if no message was found at all.
     */
    public function get_max_messageid()
    {
        $maxid = $this->interact(
            DB_RETURN_VALUE,
            "SELECT max(message_id)
             FROM   {$this->message_table}"
        );

        return $maxid === NULL ? 0 : $maxid;
    }
    // }}}

    // {{{ Method: increment_viewcount()
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
    public function increment_viewcount($message_id, $thread_id = NULL)
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
                $this->interact(
                    DB_RETURN_RES,
                    "UPDATE {$this->message_table}
                     SET    threadviewcount = threadviewcount + 1
                     WHERE  message_id = $thread_id",
                    NULL,
                    DB_MASTERQUERY
                );
            }
        }

        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->message_table}
             SET    viewcount = viewcount + 1
                    $tvc
             WHERE  message_id = $message_id",
            NULL,
            DB_MASTERQUERY
        );
    }
    // }}}

    // {{{ Method: rebuild_search_data()
    /**
     * Rebuild the search table data from scratch.
     */
    public function rebuild_search_data()
    {

        // Delete all records from the search table.
        $this->interact(
            DB_RETURN_RES,
            $this->_can_TRUNCATE
            ? "TRUNCATE TABLE {$this->search_table}"
            : "DELETE FROM {$this->search_table}",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        // Rebuild all search data from scratch.
        $search_text = $this->_concat_method == 'concat'
                     ? "concat(author, ' | ', subject, ' | ', body)"
                     : "author || ' | ' || subject || ' | ' || body";
        $this->interact(
            DB_RETURN_RES,
            "INSERT INTO {$this->search_table}
                    (message_id, search_text, forum_id)
             SELECT message_id, $search_text, forum_id
             FROM   {$this->message_table}",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );
    }
    // }}}

    // {{{ Method: rebuild_pm_new_counts()
    /**
     * Rebuild the user pm new counts from scratch.
     */
    public function rebuild_pm_new_counts()
    {
        // Clear the existing PM counts.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->user_table}
             SET pm_new_count = 0",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        // Set the new PM counts for the users to their correct values.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE $this->user_table u
             SET pm_new_count = (
                 SELECT count(*)
                 FROM   $this->pm_xref_table x
                 WHERE  read_flag = 0 AND u.user_id = x.user_id
             )"
        );
    }
    // }}}

    // {{{ Method: rebuild_user_posts()
    /**
     * Rebuild the user post counts from scratch.
     */
    public function rebuild_user_posts()
    {
        // Reset the post count for all users.
        $this->interact(
            DB_RETURN_RES,
            "UPDATE {$this->user_table}
             SET posts = 0",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        // Retrieve the post counts for all user_ids in the message table.
        $postcounts = $this->interact(
            DB_RETURN_ROWS,
            "SELECT user_id, count(*)
             FROM   {$this->message_table}
             GROUP  BY user_id",
            NULL,
            DB_GLOBALQUERY | DB_MASTERQUERY
        );

        // Set the post counts for the users to their correct values.
        foreach ($postcounts as $postcount) {
            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->user_table}
                 SET    posts   = {$postcount[1]}
                 WHERE  user_id = {$postcount[0]}",
                NULL,
                DB_MASTERQUERY
            );
        }
    }
    // }}}

    // {{{ Method: custom_field_config_get()
    /**
     * Retrieve the configuration for one or all of the custom fields.
     *
     * @param integer $field_id
     *   When NULL (the default), an array containing all custom
     *   field configurations is returned. Otherwise, only the
     *   custom field matching the provided id is returned.
     *
     * @param integer $field_type
     *   The field type to retrieve. When NULL (the default), then any
     *   field type is returned. Otherwise, only custom fields that match
     *   the provided field type are returned.
     *
     * @return null|array
     *   When a $field_id is provided, then the field config data is
     *   returned or NULL when no record exists for the $field_id.
     *   Otherwise, an array of field config data arrays is returned.
     *
     *   Note: when a $field_id is provided, but no existing config is
     *   found, then no error is triggered. This behavior was required
     *   for the conversion from the previous custom field management
     *   system to the new one that uses this method.
     */
    public function custom_field_config_get(
        $field_id = NULL, $field_type = NULL)
    {
        $where = array();

        if ($field_id !== NULL) {
            settype($field_id, 'int');
            $where[] = "id = $field_id";
        }

        if ($field_type !== NULL) {
            settype($field_type, 'int');
            $where[] = "field_type = $field_type";
        }

        $sql = "SELECT * FROM {$this->custom_fields_config_table}";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $rows = $this->interact(DB_RETURN_ASSOCS, $sql, 'id');

        if ($field_id !== NULL) {
            return empty($rows[$field_id]) ? NULL : $rows[$field_id];
        } else {
            return $rows;
        }
    }
    // }}}

    // {{{ Method: custom_field_configure()
    /**
     * Configure (create or update) a custom field.
     *
     * @param array $config
     *   An array containing configuration data for the custom field.
     *
     * @return integer $custom_field_id
     *   The id of the newly created custom field.
     */
    public function custom_field_config_set($config)
    {

        // The keys that we expect in the config and some default values
        // in case keys are missing.
        $fields = array(
            'id'            => NULL,
            'name'          => NULL,
            'field_type'    => NULL,
            'length'        => 255,
            'html_disabled' => TRUE,
            'show_in_admin' => FALSE,
            'deleted'       => FALSE
        );

        // Take data from an existing config, when applicable.
        $existing = NULL;
        if (!empty($config['id']))
        {
            $existing = $this->custom_field_config_get($config['id']);
            if ($existing) {
                foreach ($existing as $field => $value) {
                    $fields[$field] = $value;
                }
            }
        }


        // Merge in the fields from the provided $config.
        foreach ($config as $field => $value) {
            if (array_key_exists($field, $fields)) {
                $fields[$field] = $value;
            }
        }

        // Check if all required fields are set.
        foreach ($fields as $field => $value) {
            if ($value === NULL && $field !== 'id') trigger_error(
                "custom_field_config_set(): field $field cannot be NULL",
                E_USER_ERROR
            );
        }

        // Prepare fields for the SQL queries below.
        if (empty($fields['id'])) {
            unset($fields['id']);
        } else {
            $fields['id'] = (int) $fields['id'];
        }
        $name = $this->interact(DB_RETURN_QUOTED, $config['name']);
        $fields['name']          = "'$name'";
        $fields['field_type']    = (int) $fields['field_type'];
        $fields['length']        = (int) $fields['length'];
        $fields['html_disabled'] = $fields['html_disabled'] ? 1 : 0;
        $fields['show_in_admin'] = $fields['show_in_admin'] ? 1 : 0;
        $fields['deleted']       = $fields['deleted'] ? 1 : 0;

        // Update an existing config record.
        if ($existing)
        {
            $config_id = $fields['id'];
            unset($fields['id']);

            $updates = array();
            foreach ($fields as $field => $value) {
                $updates[] = "$field = $value";
            }

            $this->interact(
                DB_RETURN_RES,
                "UPDATE {$this->custom_fields_config_table}
                 SET " . implode(', ', $updates) . "
                 WHERE id = $config_id"
            );
        }
        // Insert a new config record.
        else
        {
            $flist = array();
            $vlist = array();
            foreach ($fields as $field => $value)
            {
                $flist[] = $field;
                $vlist[] = $value;
            }

            $config_id = $this->interact(
                DB_RETURN_NEWID,
                "INSERT INTO {$this->custom_fields_config_table}
                        (" . implode(', ', $flist) . ")
                 VALUES (" . implode(', ', $vlist) . ")",
                NULL,
                DB_MASTERQUERY
            );
        }

        return $config_id;
    }
    // }}}

    // {{{ Method: custom_field_config_delete()
    /**
     * Delete the configuration for a custom field.
     *
     * @param integer $field_id
     *   The id of the custom field to delete.
     */
    public function custom_field_config_delete($field_id)
    {
        if ($field_id === NULL) trigger_error(
            'custom_field_config_delete(): param $field_id cannot be NULL',
            E_USER_ERROR
        );

        settype($field_id, 'int');

        $this->interact(
            DB_RETURN_RES,
            "DELETE FROM {$this->custom_fields_config_table}
             WHERE  id = $field_id"
        );
    }
    // }}}

    // {{{ Method: user_search_custom_profile_field()
    /**
     * Search for users, based on a simple search condition,
     * which can be used to search on custom profile fields.
     *
     * ATTENTION: this function is only a wrapper for
     *            search_custom_profile_field()
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
    public function user_search_custom_profile_field(
        $field_id, $value, $operator = '=', $return_array = FALSE,
        $type = 'AND', $offset = 0, $length = 0)
    {
        return $this->search_custom_profile_field(
            PHORUM_CUSTOM_FIELD_USER,
            $field_id, $value, $operator, $return_array, $type, $offset, $length
        );
    }
    // }}}

    // {{{ Method: search_custom_profile_field()
    /**
     * Search for objects, based on a simple search condition,
     * which can be used to search on custom fields.
     *
     * The parameters $field_id, $value and $operator (which are used for defining
     * the search condition) can be arrays or single values. If arrays are used,
     * then all three parameter arrays must contain the same number of elements
     * and the key values in the arrays must be the same.
     *
     * @param integer $fieldtype
     *     The type of the fields to be retrieved, can currently be:
     *         PHORUM_CUSTOM_FIELD_USER
     *         PHORUM_CUSTOM_FIELD_FORUM
     *         PHORUM_CUSTOM_FIELD_MESSAGE
     *
     * @param mixed $field_id
     *     The custom profile field id (integer) or ids (array) to search on.
     *
     * @param mixed $value
     *     The value (string) or values (array) to search for.
     *
     * @param mixed $operator
     *     The operator (string) or operators (array) to use. Valid operators are
     *     "=", "!=", "<>", "<", ">", ">=" and "<=", "*". The
     *     "*" operator is for executing a "LIKE '%value%'" matching query.
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
     * @param integer $limit
     *     The result page limit (nr. of results per page)
     *     or 0 (zero, the default) to return all results.
     *
     * @return mixed
     *     An array of matching relation_ids or a single relation_id (based on the
     *     $return_array parameter). If no relation_ids can be found at all,
     *     then 0 (zero) will be returned.
     */
    public function search_custom_profile_field(
        $fieldtype, $field_id, $value, $operator = '=',
        $return_array = FALSE, $type = 'AND', $offset = 0, $limit = 0)
    {

        settype($return_array, 'bool');
        settype($offset, 'int');
        settype($limit, 'int');
        settype($fieldtype,'int');

        // Convert all search condition parameters to arrays.
        if (!is_array($field_id)) $field_id = array($field_id);
        if (!is_array($value))    $value    = array($value);
        if (!is_array($operator)) $operator = array($operator);

        // Basic check to see if all condition arrays contain the
        // same number of elements.
        if (count($field_id) != count($value) ||
            count($field_id) != count($operator)) trigger_error(
            __METHOD__ . ':array parameters $field_id, $value, and $operator ' .
            'do not contain the same number of elements',
            E_USER_ERROR
        );

        $type = strtoupper($type);
        if ($type != 'AND' && $type != 'OR') trigger_error(
            __METHOD__ . ': Illegal search type parameter (must be either ' .
            'AND" or "OR")',
            E_USER_ERROR
        );

        $valid_operators = array('=', '<>', '!=', '>', '<', '>=', '<=', '*', '?*', '*?');

        // Construct the required "WHERE" clause.
        $clauses = array();
        foreach ($field_id as $key => $id) {
            if (in_array($operator[$key], $valid_operators)) {
                settype($id, 'int');
                $value[$key] = $this->interact(DB_RETURN_QUOTED, $value[$key]);
                if ($operator[$key] == '*') {
                    $clauses[] = "(field_type = ".$fieldtype." AND type = $id AND data LIKE '%$value[$key]%')";
                } else if ($operator[$key] == '?*') {
                    $clauses[] = "(type = $id AND data LIKE '$value[$key]%')";
                } else if ($operator[$key] == '*?') {
                    $clauses[] = "(type = $id AND data LIKE '%$value[$key]')";
                } else {
                    $clauses[] = "(field_type = ".$fieldtype." AND type = $id AND data $operator[$key] '$value[$key]')";
                }
            }
        }
        if (!empty($clauses)) {
            $where = 'WHERE ' . implode(" OR ", $clauses);
        } else {
            $where = '';
        }

        // If we do not need to return an array, the we can limit the
        // query results to only one record.
        $limit = $return_array ? $limit : 1;

        // Build the final query.
        if ($type == 'OR' || count($clauses) == 1)
        {
            $sql = "SELECT DISTINCT(relation_id)
                    FROM   {$this->custom_fields_table}
                    $where";
        } else {
            $sql = "SELECT relation_id
                    FROM   {$this->custom_fields_table}
                    $where
                    GROUP  BY relation_id
                    HAVING count(*) = " . count($clauses);
        }

        // Retrieve the matching user_ids from the database.
        $relation_ids = $this->interact(
            DB_RETURN_ROWS, $sql,
            0, // keyfield 0 is the relation_id
            NULL, $limit
        );

        // No user_ids found at all?
        if (count($relation_ids) == 0) return 0;

        // Return an array of user_ids.
        if ($return_array) {
            foreach ($relation_ids as $id => $rel_id) {
                $relation_ids[$id] = $rel_id[0];
            }
            return $relation_ids;
        }

        // Return a single user_id.
        list ($rel_id, $dummy) = each($relation_ids);
        return $rel_id;
    }
    // }}}

    // {{{ Method: metaquery_compile()
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
    public function metaquery_compile($metaquery)
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
                            $matchsql .= $this->interact(DB_RETURN_QUOTED, $p);
                        } else {
                            $matchsql .= $this->interact(DB_RETURN_QUOTED, $m);
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

    // {{{ Method: metaquery_messagesearch()
    /**
     * Run a search on the messages, using a metaquery. See the documentation
     * for the metaquery_compile() function for more info on the
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
     *     A metaquery array. See {@link metaquery_compile()} for
     *     more information about the metaquery syntax.
     *
     * @return array
     *     An array of message records.
     */
    public function metaquery_messagesearch($metaquery)
    {

        // Compile the metaquery into a where statement.
        list($success, $where) = $this->metaquery_compile($metaquery);
        if (!$success) trigger_error($where, E_USER_ERROR);

        // Retrieve matching messages.
        $messages = $this->interact(
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
                    \"user\".username   AS user_username,
                    thread.closed       AS thread_closed,
                    thread.modifystamp  AS thread_modifystamp,
                    thread.thread_count AS thread_count
             FROM   {$this->message_table} AS thread,
                    {$this->message_table} AS message
                        LEFT JOIN {$this->user_table} AS \"user\"
                        ON message.user_id = \"user\".user_id
             WHERE  message.thread  = thread.message_id AND
                    ($where)
             ORDER BY message_id ASC",
            'message_id'
        );

        return $messages;
    }
    // }}}

    // ----------------------------------------------------------------------
    // Methods that must be overridden in a derived class
    // ----------------------------------------------------------------------

    // {{{ Method: interact()
    /**
     * This method is the central method for handling database
     * interaction. The method can be used for setting up a database
     * connection, for running a SQL query and for returning query rows.
     * Which of these actions the method will handle and what the method
     * return data will be, is determined by the $return method parameter.
     *
     * @param $return   - What to return. Options are the following constants:
     *                    DB_RETURN_CONN      a db connection handle
     *                    DB_RETURN_QUOTED    a quoted parameter
     *                    DB_RETURN_RES       result resource handle
     *                    DB_RETURN_ROW       single row as array
     *                    DB_RETURN_ROWS      all rows as arrays
     *                    DB_RETURN_ASSOC     single row as associative array
     *                    DB_RETURN_ASSOCS    all rows as associative arrays
     *                    DB_RETURN_VALUE     single row, single column
     *                    DB_RETURN_ROWCOUNT  number of selected rows
     *                    DB_RETURN_NEWID     new row id for insert query
     *                    DB_RETURN_ERROR     an error message if the query
     *                                        failed or NULL if there was
     *                                        no error
     *                    DB_CLOSE_CONN       close the connection, no
     *                                        return data
     *
     * @param $sql      - The SQL query to run or the parameter to quote if
     *                    DB_RETURN_QUOTED is used.
     *
     * @param $keyfield - When returning an array of rows, the indexes are
     *                    numerical by default (0, 1, 2, etc.). However, if
     *                    the $keyfield parameter is set, then from each
     *                    row the $keyfield index is taken as the key for the
     *                    return array. This way, you can create a direct
     *                    mapping between some id field and its row in the
     *                    return data. Mind that there is no error checking
     *                    at all, so you have to make sure that you provide
     *                    a valid $keyfield here!
     *
     * @param $flags    - Special flags for modifying the method's behavior.
     *                    These flags can be OR'ed if multiple flags are needed.
     *                    DB_NOCONNECTOK     Failure to connect is not fatal
     *                                       but lets the call return FALSE
     *                                       (useful in combination with
     *                                       DB_RETURN_CONN).
     *                    DB_MISSINGTABLEOK  Missing table errors not fatal.
     *                    DB_DUPFIELDNAMEOK  Duplicate field errors not fatal.
     *                    DB_DUPKEYNAMEOK    Duplicate key name errors
     *                                       not fatal.
     *                    DB_DUPKEYOK        Duplicate key errors not fatal.
     *
     * @param $limit    - The maximum number of rows to return.
     * @param $offset   - The number of rows to skip in the result set,
     *                    before returning rows to the caller.
     *
     * @return $res     - The result of the query, based on the $return
     *                    parameter.
     */
    public function interact(
        $return, $sql = NULL, $keyfield = NULL, $flags = 0,
        $limit = 0, $offset = 0)
    {
        trigger_error(
            __METHOD__ . ': the database layer does not implement interact()',
            E_USER_ERROR
        );
    }
    // }}}

    // {{{ Method: fetch_row()
    /**
     * Return a single row from a query result. This method can be used
     * if a lot of rows have to be processed one by one, in which case the
     * DB_RETURN_ROWS and DB_RETURN_ASSOCS return types for the
     * {@link PhorumDBLayer::interact()} method might consume lots of memory.
     *
     * @param resource $res
     *     The result set resource handle. This is the return value of the
     *     method {@link PhorumDBLayer::interact()}, when running a query
     *     with the DB_RETURN_RES return type.
     *
     * @param integer $type
     *     A flag, which indicates what type of row has to be returned.
     *     One of {@link DB_RETURN_ASSOC} or {@link DB_RETURN_ROW}, which
     *     will let this method return respectively an associative array
     *     (field name -> value) or an array (field index -> value).
     *
     * @return mixed
     *     This method returns either an array containing a single row
     *     or NULL if there are no more rows to retrieve.
     */
    public function fetch_row($res, $type)
    {
        trigger_error(
            __METHOD__ . ': the database layer does not implement fetch_row()',
            E_USER_ERROR
        );
    }
    // }}}

    // {{{ Method: create_tables()
    /**
     * Create the tables that are needed in the database. This function will
     * only be called at install time. After installation, changes in the
     * database schema will be handled by the database upgrade system.
     *
     * @return mixed
     *     NULL on success or an error message on failure.
     */
    public function create_tables()
    {
        trigger_error(
            __METHOD__ . ': the database layer does not implement ' .
            'create_tables()',
            E_USER_ERROR
        );
    }
    // }}}

    // ----------------------------------------------------------------------
    // Methods that can be overridden in a derived class, but that are
    // not part of the primary DB API.
    // ----------------------------------------------------------------------

    // {{{ Method: maxpacketsize()
    /**
     * This function is used by the sanity checking system in the admin
     * interface to determine how much data can be transferred in one query.
     * This is used to detect problems with uploads that are larger than the
     * database server can handle. The function returns the size in bytes.
     * For database implementations which do not have this kind of limit,
     * NULL can be returned.
     *
     * @return NULL|integer
     *     The maximum packet size in bytes or NULL if there is no limit.
     */
    public function maxpacketsize()
    {
        return NULL;
    }
    // }}}

    // {{{ Method: sanitychecks()
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
    public function sanitychecks()
    {
        // No checks in the default implementation, so we can
        // return an OK status.
        return array (PHORUM_SANITY_OK, NULL);
    }
    // }}}

    // {{{ Method: __construct()
    /**
     * The constructor sets up the table names for the database layer
     * as properties in the object.
     */
    public function __construct()
    {
        global $PHORUM;

        // Determine the table prefix to use. If no prefix is set in the
        // configuration, then "phorum" is used by default.
        $this->prefix = $prefix =
            isset($PHORUM['DBCONFIG']['table_prefix'])
            ? $PHORUM['DBCONFIG']['table_prefix']
            : 'phorum';

        // Setup the table names.
        $this->message_table              = $prefix . '_messages';
        $this->user_newflags_table        = $prefix . '_user_newflags';
        $this->user_newflags_min_id_table = $prefix . '_user_min_id';
        $this->subscribers_table          = $prefix . '_subscribers';
        $this->files_table                = $prefix . '_files';
        $this->search_table               = $prefix . '_search';
        $this->settings_table             = $prefix . '_settings';
        $this->forums_table               = $prefix . '_forums';
        $this->user_table                 = $prefix . '_users';
        $this->user_permissions_table     = $prefix . '_user_permissions';
        $this->groups_table               = $prefix . '_groups';
        $this->forum_group_xref_table     = $prefix . '_forum_group_xref';
        $this->user_group_xref_table      = $prefix . '_user_group_xref';
        $this->custom_fields_config_table = $prefix . '_custom_fields_config';
        $this->custom_fields_table        = $prefix . '_custom_fields';
        $this->banlist_table              = $prefix . '_banlists';
        $this->pm_messages_table          = $prefix . '_pm_messages';
        $this->pm_folders_table           = $prefix . '_pm_folders';
        $this->pm_xref_table              = $prefix . '_pm_xref';
        $this->pm_buddies_table           = $prefix . '_pm_buddies';
        $this->message_tracking_table     = $prefix . '_messages_edittrack';
    }
    // }}}
}

?>
