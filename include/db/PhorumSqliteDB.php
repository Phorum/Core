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
 * This script implements the Sqlite database layer for Phorum.
 *
 * ----------------------------------------------------------------------
 * IMPORTANT:
 * This is an experimental layer. We are not yet sure if it will be
 * fully supported by the Phorum team.
 * ----------------------------------------------------------------------
 */

/**
 * The PhorumSqliteDB class, which implements the Sqlite database
 * layer for Phorum.
 */
class PhorumSqliteDB extends PhorumDB
{
    /**
     * Whether or not the database system supports "UPDATE IGNORE".
     * @var boolean
     */
    protected $_can_UPDATE_IGNORE = TRUE;

    /**
     * Whether or not the database system supports "INSERT IGNORE".
     * @var boolean
     */
    protected $_can_INSERT_IGNORE = TRUE;

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
        global $PHORUM;

        static $conn;

        // Close the database connection.
        if ($return == DB_CLOSE_CONN)
        {
            // PDO disconnects when the PDO object is destroyed.
            $conn = NULL;
            return;
        }

        // Setup a database connection if no database connection is
        // available yet.
        if (empty($conn))
        {
            if (empty($PHORUM['DBCONFIG']['dbfile'])) {
                print 'The Phorum database configuration does not define ' .
                      'the path for the sqlite database file in the ' .
                      '"dbfile" option.';
                exit;
            }

            try {
                $conn = new PDO('sqlite:' . $PHORUM['DBCONFIG']['dbfile']);
            }
            catch (PDOException $e)
            {
                if ($flags & DB_NOCONNECTOK) return FALSE;
                phorum_database_error(
                    'Failed to open the database: ' .
                    $e->getMessage()
                );
                exit;
            }
        }

        // RETURN: database connection handle.
        if ($return === DB_RETURN_CONN) {
            return $conn;
        }

        // Return a quoted parameter.
        if ($return === DB_RETURN_QUOTED)
        {
            $quoted = $conn->quote($sql);

            // PDO adds the outer single quotes, but our DB layer adds those
            // on its own. Strip the quotes from the quoted string.
            $quoted = preg_replace('/(?:^\'|\'$)/', '', $quoted);

            return $quoted;
        }

        // By now, we really need a SQL query.
        if ($sql === NULL) trigger_error(
            __METHOD__ . ': Internal error: ' .
            'missing sql query statement!', E_USER_ERROR
        );

        // Apply limit and offset to the query.
        settype($limit, 'int');
        settype($offset, 'int');
        if ($limit  > 0) $sql .= " LIMIT $limit";
        if ($offset > 0) $sql .= " OFFSET $offset";

        // Tweak the syntax of the queries.
        $sql = preg_replace(
            '/^(INSERT|UPDATE)\s+IGNORE\s/i',
            '\\1 OR IGNORE ',
            $sql
        );

        // Execute the SQL query.
        $res = $conn->query($sql);
        if ($res === FALSE)
        {
            // An error has occurred. Fetch the error code and message.
            $error_info = $conn->errorInfo();
            $errno = $error_info[1];
            $error = $error_info[2];

            // See if the $flags tell us to ignore the error.
            $ignore_error = FALSE;

            if (strstr($error, "no such table") &&
                ($flags & DB_MISSINGTABLEOK)) {
                $ignore_error = TRUE;
            }

            if ($errno == 19 && ($flags & DB_DUPKEYOK)) {
                $ignore_error = TRUE;
            }
#
#            if (strstr($exec_error, "already exists") &&
#                substr($exec_error, 0, 5) == 'table' &&
#                ($flags & DB_TABLEEXISTSOK)) {
#                $ignore_error = TRUE;
#            }
#
#            if (strstr($exec_error, "already exists") &&
#                substr($exec_error, 0, 5) == 'index' &&
#                ($flags & DB_DUPKEYNAMEOK)) {
#                $ignore_error = TRUE;
#            }

##### HURDLE: no alter table for Sqlite2, which is the version supported
##### by php5-sqlite. Maybe PDO can be used, which seems to support Sqlite3.
#####  // Duplicate column name.
#####  case '42701':
#####    if ($flags & DB_DUPFIELDNAMEOK) $ignore_error = TRUE;
#####    break;
#####
#####  // Duplicate entry for key.
#####  case '23505':
#####    if ($flags & DB_DUPKEYOK) {
#####        $ignore_error = TRUE;
#####
#####        # the code expects res to have no value upon error
#####        $res = NULL;
#####    }
#####    break;


            // Handle this error if it's not to be ignored.
            if (! $ignore_error)
            {
                // RETURN: error message or NULL
                if ($return === DB_RETURN_ERROR) return $error;

                // Trigger an error.
                phorum_database_error("$error ($errno): $sql");
                exit;
            }
        }

        // RETURN: NULL (no error)
        if ($return === DB_RETURN_ERROR) {
            return NULL;
        }

        // RETURN: query resource handle
        if ($return === DB_RETURN_RES) {
            return $res;
        }

        // RETURN: number of rows
        if ($return === DB_RETURN_ROWCOUNT) {
            if (!$res) return 0;
            return $res->rowCount();
        }

        // RETURN: array rows or single value
        if ($return === DB_RETURN_ROW  ||
            $return === DB_RETURN_ROWS ||
            $return === DB_RETURN_VALUE)
        {
            // Keyfields are only valid for DB_RETURN_ROWS.
            if ($return !== DB_RETURN_ROWS) $keyfield = NULL;

            $rows = array();
            if ($res) {
                while ($row = $res->fetch(PDO::FETCH_NUM))
                {
                    if ($keyfield === NULL) {
                        $rows[] = $row;
                    } else {
                        $rows[$row[$keyfield]] = $row;
                    }
                }
            }

            // Return all rows.
            if ($return === DB_RETURN_ROWS) {
                return $rows;
            }

            // Return a single row.
            if ($return === DB_RETURN_ROW) {
                if (count($rows) == 0) {
                    return NULL;
                } else {
                    return $rows[0];
                }
            }

            // Return a single value.
            if (count($rows) == 0) {
                return NULL;
            } else {
                return $rows[0][0];
            }
        }

        // RETURN: associative array rows
        if ($return === DB_RETURN_ASSOC ||
            $return === DB_RETURN_ASSOCS)
        {
            // Keyfields are only valid for DB_RETURN_ASSOCS.
            if ($return !== DB_RETURN_ASSOCS) $keyfield = NULL;

            $rows = array();
            if ($res) {
                while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                    if ($keyfield === NULL) {
                        $rows[] = $row;
                    } else {
                        $rows[$row[$keyfield]] = $row;
                    }
                }
            }

            // Return all rows.
            if ($return === DB_RETURN_ASSOCS) {
                return $rows;
            }

            // Return a single row.
            if ($return === DB_RETURN_ASSOC) {
                if (count($rows) == 0) {
                    return NULL;
                } else {
                    return $rows[0];
                }
            }
        }

        // RETURN: new id after inserting a new record
        if ($return === DB_RETURN_NEWID) {
            return $conn->lastInsertId();
        }

        trigger_error(
            __METHOD__ . ': Internal error: ' .
            'illegal return type specified!', E_USER_ERROR
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
        if ($type === DB_RETURN_ASSOC) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
        } elseif ($type === DB_RETURN_ROW) {
            $row = $res->fetch(PDO::FETCH_NUM);
        } else trigger_error(
            __METHOD__ . ': Internal error: ' .
            'illegal \$type parameter used', E_USER_ERROR
        );

        return $row ? $row : NULL;
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
        global $PHORUM;

        $lang = PHORUM_DEFAULT_LANGUAGE;

        $create_table_queries = array(

          "CREATE TABLE {$this->forums_table} (
               forum_id                 integer        PRIMARY KEY,
               name                     varchar(50)    NOT NULL default '',
               active                   tinyint(1)     NOT NULL default 0,
               description              text           NOT NULL,
               template                 varchar(50)    NOT NULL default '',
               folder_flag              tinyint(1)     NOT NULL default 0,
               parent_id                int unsigned   NOT NULL default 0,
               list_length_flat         int unsigned   NOT NULL default 0,
               list_length_threaded     int unsigned   NOT NULL default 0,
               moderation               int unsigned   NOT NULL default 0,
               threaded_list            tinyint(1)     NOT NULL default 0,
               threaded_read            tinyint(1)     NOT NULL default 0,
               float_to_top             tinyint(1)     NOT NULL default 0,
               check_duplicate          tinyint(1)     NOT NULL default 0,
               allow_attachment_types   varchar(100)   NOT NULL default '',
               max_attachment_size      int unsigned   NOT NULL default 0,
               max_totalattachment_size int unsigned   NOT NULL default 0,
               max_attachments          int unsigned   NOT NULL default 0,
               pub_perms                int unsigned   NOT NULL default 0,
               reg_perms                int unsigned   NOT NULL default 0,
               display_ip_address       tinyint(1)     NOT NULL default 1,
               allow_email_notify       tinyint(1)     NOT NULL default 1,
               language                 varchar(100)   NOT NULL default '$lang',
               email_moderators         tinyint(1)     NOT NULL default 0,
               message_count            int unsigned   NOT NULL default 0,
               sticky_count             int unsigned   NOT NULL default 0,
               thread_count             int unsigned   NOT NULL default 0,
               last_post_time           int unsigned   NOT NULL default 0,
               display_order            int unsigned   NOT NULL default 0,
               read_length              int unsigned   NOT NULL default 0,
               vroot                    int unsigned   NOT NULL default 0,
               forum_path               text           NOT NULL,
               count_views              tinyint(1)     NOT NULL default 0,
               count_views_per_thread   tinyint(1)     NOT NULL default 0,
               display_fixed            tinyint(1)     NOT NULL default 0,
               reverse_threading        tinyint(1)     NOT NULL default 0,
               inherit_id               int unsigned       NULL default NULL,
               cache_version            int unsigned   NOT NULL default 0
           )",

          "CREATE INDEX name
           ON {$this->forums_table} (name)",

          "CREATE INDEX active
           ON {$this->forums_table} (active, parent_id)",

          "CREATE INDEX folder_index
           ON {$this->forums_table} (parent_id, vroot, active, folder_flag)",

          "CREATE TABLE {$this->message_table} (
               message_id               integer        PRIMARY KEY,
               forum_id                 int unsigned   NOT NULL default 0,
               thread                   int unsigned   NOT NULL default 0,
               parent_id                int unsigned   NOT NULL default 0,
               user_id                  int unsigned   NOT NULL default 0,
               author                   varchar(255)   NOT NULL default '',
               subject                  varchar(255)   NOT NULL default '',
               body                     text           NOT NULL,
               email                    varchar(100)   NOT NULL default '',
               ip                       varchar(255)   NOT NULL default '',
               status                   tinyint(1)     NOT NULL default 2,
               msgid                    varchar(100)   NOT NULL default '',
               modifystamp              int unsigned   NOT NULL default 0,
               thread_count             int unsigned   NOT NULL default 0,
               moderator_post           tinyint(1)     NOT NULL default 0,
               sort                     tinyint(1)     NOT NULL default 2,
               datestamp                int unsigned   NOT NULL default 0,
               meta                     text               NULL,
               viewcount                int unsigned   NOT NULL default 0,
               threadviewcount          int unsigned   NOT NULL default 0,
               closed                   tinyint(1)     NOT NULL default 0,
               recent_message_id        int unsigned   NOT NULL default 0,
               recent_user_id           int unsigned   NOT NULL default 0,
               recent_author            varchar(255)   NOT NULL default '',
               moved                    tinyint(1)     NOT NULL default 0,
               hide_period              int unsigned   NOT NULL default 0
           )",

          "CREATE INDEX {$this->message_table}_special_threads
           ON {$this->message_table} (sort, forum_id)",

          "CREATE INDEX {$this->message_table}_last_post_time
           ON {$this->message_table} (forum_id, status, modifystamp)",

          "CREATE INDEX {$this->message_table}_dup_check
           ON {$this->message_table} (forum_id, author, subject, datestamp)",

          "CREATE INDEX {$this->message_table}_recent_user_id
           ON {$this->message_table} (recent_user_id)",

          "CREATE INDEX {$this->message_table}_user_messages
           ON {$this->message_table} (user_id, message_id)",

          "CREATE INDEX {$this->message_table}_updated_threads
           ON {$this->message_table} (status, parent_id, modifystamp)",

          "CREATE INDEX {$this->message_table}_list_page_flat
           ON {$this->message_table} (forum_id, status, parent_id, datestamp)",

          "CREATE INDEX {$this->message_table}_thread_date
           ON {$this->message_table} (thread, datestamp)",

          "CREATE INDEX {$this->message_table}_list_page_float
           ON {$this->message_table}
           (forum_id, status, parent_id, modifystamp)",

          "CREATE INDEX {$this->message_table}_forum_recent_messages
           ON {$this->message_table} (forum_id, status, datestamp)",

          "CREATE INDEX {$this->message_table}_recent_threads
           ON {$this->message_table} (status, parent_id, datestamp)",

          "CREATE INDEX {$this->message_table}_recent_messages
           ON {$this->message_table} (status, datestamp)",

          "CREATE INDEX {$this->message_table}_forum_thread_count
           ON {$this->message_table}
           (forum_id, parent_id, status, moved, message_id)",

          "CREATE INDEX {$this->message_table}_forum_message_count
           ON {$this->message_table} (forum_id, status, moved, message_id)",

          "CREATE TABLE {$this->settings_table} (
               name                     varchar(255)   NOT NULL default '',
               type                     char(1)        NOT NULL default 'V',
               data                     text           NOT NULL,

               PRIMARY KEY (name)
           )",

          "CREATE TABLE {$this->subscribers_table} (
               user_id                  int unsigned   NOT NULL default 0,
               forum_id                 int unsigned   NOT NULL default 0,
               sub_type                 tinyint(1)     NOT NULL default 0,
               thread                   int unsigned   NOT NULL default 0,

               PRIMARY KEY (user_id, forum_id, thread)
           )",

          "CREATE INDEX {$this->message_table}_forum_id
           ON {$this->subscribers_table} (forum_id, thread, sub_type)",

          "CREATE TABLE {$this->user_permissions_table} (
               user_id                  int unsigned   NOT NULL default 0,
               forum_id                 int unsigned   NOT NULL default 0,
               permission               int unsigned   NOT NULL default 0,

               PRIMARY KEY (user_id,forum_id)
           )",

          "CREATE INDEX {$this->user_permissions_table}_forum_id
           ON {$this->user_permissions_table} (forum_id, permission)",

          // When creating extra fields, then mind to update the file
          // include/api/custom_field.php script too (it contains a
          // list of reserved names for custom profile fields).
          "CREATE TABLE {$this->user_table} (
               user_id                  integer        PRIMARY KEY,
               username                 varchar(50)    NOT NULL default '',
               real_name                varchar(255)   NOT NULL default '',
               display_name             varchar(255)   NOT NULL default '',
               password                 varchar(50)    NOT NULL default '',
               password_temp            varchar(50)    NOT NULL default '',
               sessid_lt                varchar(50)    NOT NULL default '',
               sessid_st                varchar(50)    NOT NULL default '',
               sessid_st_timeout        int unsigned   NOT NULL default 0,
               email                    varchar(100)   NOT NULL default '',
               email_temp               varchar(110)   NOT NULL default '',
               hide_email               tinyint(1)     NOT NULL default 1,
               active                   tinyint(1)     NOT NULL default 0,
               signature                text           NOT NULL,
               threaded_list            tinyint(1)     NOT NULL default 0,
               posts                    int unsigned   NOT NULL default 0,
               admin                    tinyint(1)     NOT NULL default 0,
               threaded_read            tinyint(1)     NOT NULL default 0,
               date_added               int unsigned   NOT NULL default 0,
               date_last_active         int unsigned   NOT NULL default 0,
               last_active_forum        int unsigned   NOT NULL default 0,
               hide_activity            tinyint(1)     NOT NULL default 0,
               show_signature           tinyint(1)     NOT NULL default 0,
               email_notify             tinyint(1)     NOT NULL default 0,
               pm_email_notify          tinyint(1)     NOT NULL default 1,
               tz_offset                numeric(4,2)   NOT NULL default -99.00,
               is_dst                   tinyint(1)     NOT NULL default 0,
               user_language            varchar(100)   NOT NULL default '',
               user_template            varchar(100)   NOT NULL default '',
               moderation_email         tinyint(1)     NOT NULL default 1,
               settings_data            text           NOT NULL
          )",

          "CREATE UNIQUE INDEX {$this->user_table}_username
           ON {$this->user_table} (username)",

          "CREATE INDEX {$this->user_table}_active
           ON {$this->user_table} (active)",

          "CREATE INDEX {$this->user_table}_userpass
           ON {$this->user_table} (username, password)",

          "CREATE INDEX {$this->user_table}_sessid_st
           ON {$this->user_table} (sessid_st)",

          "CREATE INDEX {$this->user_table}_sessid_lt
           ON {$this->user_table} (sessid_lt)",

          "CREATE INDEX {$this->user_table}_activity
           ON {$this->user_table}
           (date_last_active, hide_activity, last_active_forum)",

          "CREATE INDEX {$this->user_table}_date_added
           ON {$this->user_table} (date_added)",

          "CREATE INDEX {$this->user_table}_email_temp
           ON {$this->user_table} (email_temp)",

          "CREATE TABLE {$this->user_newflags_table} (
               user_id                  int unsigned   NOT NULL default 0,
               forum_id                 int unsigned   NOT NULL default 0,
               message_id               int unsigned   NOT NULL default 0,

               PRIMARY KEY (user_id, forum_id, message_id)
           )",

          "CREATE INDEX {$this->user_newflags_table}_move
           ON {$this->user_newflags_table} (message_id, forum_id)",

          "CREATE TABLE {$this->groups_table} (
               group_id                 integer        PRIMARY KEY,
               name                     varchar(255)   NOT NULL default '',
               open                     tinyint(1)     NOT NULL default 0
           )",

          "CREATE TABLE {$this->forum_group_xref_table} (
               forum_id                 int unsigned   NOT NULL default 0,
               group_id                 int unsigned   NOT NULL default 0,
               permission               int unsigned   NOT NULL default 0,

               PRIMARY KEY (forum_id, group_id)
           )",

          "CREATE INDEX {$this->forum_group_xref_table}_group_id
           ON {$this->forum_group_xref_table} (group_id)",

          "CREATE TABLE {$this->user_group_xref_table} (
               user_id                  int unsigned   NOT NULL default 0,
               group_id                 int unsigned   NOT NULL default 0,
               status                   tinyint(1)     NOT NULL default 1,

               PRIMARY KEY (user_id, group_id)
           )",

          "CREATE TABLE {$this->files_table} (
               file_id                  integer        PRIMARY KEY,
               user_id                  int unsigned   NOT NULL default 0,
               filename                 varchar(255)   NOT NULL default '',
               filesize                 int unsigned   NOT NULL default 0,
               file_data                text           NOT NULL,
               add_datetime             int unsigned   NOT NULL default 0,
               message_id               int unsigned   NOT NULL default 0,
               link                     varchar(10)    NOT NULL default ''
           )",

          "CREATE INDEX {$this->files_table}_add_datetime
           ON {$this->files_table} (add_datetime)",

          "CREATE INDEX {$this->files_table}_message_id_link
           ON {$this->files_table} (message_id, link)",

          "CREATE INDEX {$this->files_table}_user_id_link
           ON {$this->files_table} (user_id, link)",

          "CREATE TABLE {$this->banlist_table} (
               id                       integer        PRIMARY KEY,
               forum_id                 int unsigned   NOT NULL default 0,
               type                     tinyint(1)     NOT NULL default 0,
               pcre                     tinyint(1)     NOT NULL default 0,
               string                   varchar(255)   NOT NULL default '',
               comments                 text           NOT NULL
           )",

          "CREATE INDEX {$this->banlist_table}_forum_id
           ON {$this->banlist_table} (forum_id)",

          "CREATE TABLE {$this->search_table} (
               message_id               int unsigned   NOT NULL default 0,
               forum_id                 int unsigned   NOT NULL default 0,
               search_text              text           NOT NULL,

               PRIMARY KEY (message_id)
           )",

          "CREATE INDEX {$this->search_table}_forum_id
           ON {$this->search_table} (forum_id)",

          "CREATE TABLE {$this->custom_fields_config_table} (
               id                       integer        PRIMARY KEY,
               field_type               tinyint(1)     NOT NULL default 1,
               name                     varchar(50)    NOT NULL,
               length                   mediumint      NOT NULL default 255,
               html_disabled            tinyint(1)     NOT NULL default 1,
               show_in_admin            tinyint(1)     NOT NULL default 0,
               deleted                  tinyint(1)     NOT NULL default 0,

               PRIMARY KEY (id)
          )",

          "CREATE UNIQUE INDEX
              {$this->custom_fields_config_table}_field_type_name
           ON {$this->custom_fields_config_table} (field_type, name)",


          "CREATE TABLE {$this->custom_fields_table} (
               relation_id              int unsigned   NOT NULL default 0,
               field_type               tinyint(1)     NOT NULL default 1,
               type                     int unsigned   NOT NULL default 0,
               data                     text           NOT NULL,

               PRIMARY KEY (relation_id, field_type, type)
           )",

          "CREATE TABLE {$this->pm_messages_table} (
               pm_message_id            integer        PRIMARY KEY,
               user_id                  int unsigned   NOT NULL default 0,
               author                   varchar(255)   NOT NULL default '',
               subject                  varchar(100)   NOT NULL default '',
               message                  text           NOT NULL,
               datestamp                int unsigned   NOT NULL default 0,
               meta                     text           NOT NULL
           )",

          "CREATE INDEX {$this->pm_messages_table}_user_id
           ON {$this->pm_messages_table} (user_id)",

          "CREATE TABLE {$this->pm_folders_table} (
               pm_folder_id             integer        PRIMARY KEY,
               user_id                  int unsigned   NOT NULL default 0,
               foldername               varchar(20)    NOT NULL default ''
           )",

          "CREATE TABLE {$this->pm_xref_table} (
               pm_xref_id               integer        PRIMARY KEY,
               user_id                  int unsigned   NOT NULL default 0,
               pm_folder_id             int unsigned   NOT NULL default 0,
               special_folder           varchar(10)        NULL default NULL,
               pm_message_id            int unsigned   NOT NULL default 0,
               read_flag                tinyint(1)     NOT NULL default 0,
               reply_flag               tinyint(1)     NOT NULL default 0
           )",

          "CREATE INDEX {$this->pm_xref_table}_xref
           ON {$this->pm_xref_table} (user_id, pm_folder_id, pm_message_id)",

          "CREATE INDEX {$this->pm_xref_table}_read_flag
           ON {$this->pm_xref_table} (read_flag)",

          "CREATE TABLE {$this->pm_buddies_table} (
               pm_buddy_id              integer        PRIMARY KEY,
               user_id                  int unsigned   NOT NULL default 0,
               buddy_user_id            int unsigned   NOT NULL default 0
           )",

          "CREATE UNIQUE INDEX {$this->pm_buddies_table}_userids
           ON {$this->pm_buddies_table} (user_id, buddy_user_id)",

          "CREATE INDEX {$this->pm_buddies_table}_buddy_user_id
           ON {$this->pm_buddies_table} (buddy_user_id)",

          "CREATE TABLE {$this->message_tracking_table} (
               track_id                 integer        PRIMARY KEY,
               message_id               int unsigned   NOT NULL default 0,
               user_id                  int unsigned   NOT NULL default 0,
               time                     int unsigned   NOT NULL default 0,
               diff_body                text               NULL,
               diff_subject             text               NULL
           )",

          "CREATE INDEX {$this->message_tracking_table}_message_kd
           ON {$this->message_tracking_table} (message_id)",

          "CREATE TABLE {$this->user_newflags_min_id_table} (
              user_id                  int unsigned   NOT NULL default 0,
              forum_id                 int unsigned   NOT NULL default 0,
              min_id                   int unsigned   NOT NULL default 0,

              PRIMARY KEY (user_id, forum_id)
           )"
        );

        foreach ($create_table_queries as $sql)
        {
            $error = $this->interact(
                DB_RETURN_ERROR, $sql, NULL, DB_MASTERQUERY
            );

            if ($error !== NULL) {
                return $error;
            }
        }

        return NULL;
    }
    // }}}
}

?>
